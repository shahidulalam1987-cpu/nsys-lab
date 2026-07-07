<?php

namespace App\Services;

use App\Models\AutomationTask;
use App\Models\Client;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeePayroll;
use App\Models\FinanceAccount;
use App\Models\ManagedDocument;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentManagementService
{
    public const MAX_UPLOAD_KB = 10240;

    public const ALLOWED_MIMES = [
        'pdf',
        'docx',
        'xlsx',
        'png',
        'jpg',
        'jpeg',
        'zip',
    ];

    public const STORAGE_DISK = 'local';

    public function ownerMap(): array
    {
        return [
            'employee' => Employee::class,
            'client' => Client::class,
            'payroll' => EmployeePayroll::class,
            'assignment' => EmployeeAssignment::class,
            'finance_account' => FinanceAccount::class,
            'client_payment' => SalaryPayment::class,
            'employee_payment' => EmployeePayroll::class,
            'daily_performance' => DailyPerformanceReport::class,
            'automation_task' => AutomationTask::class,
        ];
    }

    public function query(array $filters = [], ?User $user = null): Builder
    {
        $query = ManagedDocument::query()
            ->with(['uploader:id,name,email'])
            ->latest();

        $this->applyVisibility($query, $user);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('original_file_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['owner_module'])) {
            $query->where('owner_module', $filters['owner_module']);
        }

        if (! empty($filters['tag'])) {
            $tag = Str::lower($filters['tag']);
            $query->where('tags', 'like', "%{$tag}%");
        }

        return $query;
    }

    public function relatedDocuments(string $ownerModule, int $ownerRecordId, ?User $user = null, int $limit = 5)
    {
        return $this->query([
            'owner_module' => $ownerModule,
            'status' => 'active',
        ], $user)
            ->where('owner_record_id', $ownerRecordId)
            ->limit($limit)
            ->get();
    }

    public function store(array $data, UploadedFile $file, User $user): ManagedDocument
    {
        return DB::transaction(function () use ($data, $file, $user) {
            [$ownerType, $ownerId] = $this->resolveOwner($data['owner_module'] ?? null, $data['owner_record_id'] ?? null);
            $path = $this->storeFile($file, $data['owner_module'] ?? 'general');

            $document = ManagedDocument::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? 'General',
                'tags' => $this->normalizeTags($data['tags'] ?? null),
                'owner_module' => $data['owner_module'] ?? null,
                'owner_record_type' => $ownerType,
                'owner_record_id' => $ownerId,
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize() ?: 0,
                'version' => 1,
                'status' => 'active',
                'expiry_date' => $data['expiry_date'] ?? null,
                'current_file_path' => $path,
                'original_file_name' => $file->getClientOriginalName(),
            ]);

            $document->versions()->create([
                'version' => 1,
                'file_path' => $path,
                'original_file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize() ?: 0,
                'uploaded_by' => $user->id,
                'change_note' => 'Initial upload',
            ]);

            $this->audit($document, $user, 'uploaded', 'Document uploaded.');

            return $document;
        });
    }

    public function updateMetadata(ManagedDocument $document, array $data, User $user): ManagedDocument
    {
        $document->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? $document->category,
            'tags' => $this->normalizeTags($data['tags'] ?? null),
            'expiry_date' => $data['expiry_date'] ?? null,
        ])->save();

        $this->audit($document, $user, 'updated', 'Document metadata updated.');

        return $document;
    }

    public function addVersion(ManagedDocument $document, UploadedFile $file, User $user, ?string $changeNote = null): ManagedDocument
    {
        return DB::transaction(function () use ($document, $file, $user, $changeNote) {
            $nextVersion = ((int) $document->version) + 1;
            $path = $this->storeFile($file, $document->owner_module ?: 'general');

            $document->versions()->create([
                'version' => $nextVersion,
                'file_path' => $path,
                'original_file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize() ?: 0,
                'uploaded_by' => $user->id,
                'change_note' => $changeNote,
            ]);

            $document->update([
                'version' => $nextVersion,
                'current_file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize() ?: 0,
                'original_file_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
            ]);

            $this->audit($document, $user, 'updated', 'New document version uploaded.', ['version' => $nextVersion]);

            return $document;
        });
    }

    public function archive(ManagedDocument $document, User $user): void
    {
        $document->update(['status' => 'archived']);
        $this->audit($document, $user, 'archived', 'Document archived.');
    }

    public function restore(ManagedDocument $document, User $user): void
    {
        $document->update(['status' => 'active']);
        $this->audit($document, $user, 'restored', 'Document restored.');
    }

    public function logDownload(ManagedDocument $document, User $user): void
    {
        $this->audit($document, $user, 'downloaded', 'Document downloaded.');
    }

    public function fileExists(string $path): bool
    {
        return Storage::disk(self::STORAGE_DISK)->exists($path)
            || Storage::disk('public')->exists($path);
    }

    public function filePath(string $path): string
    {
        if (Storage::disk(self::STORAGE_DISK)->exists($path)) {
            return Storage::disk(self::STORAGE_DISK)->path($path);
        }

        return Storage::disk('public')->path($path);
    }

    public function canView(ManagedDocument $document, User $user): bool
    {
        if ($user->isSuperAdmin() || $user->hasPermission('documents.view') || $user->hasPermission('documents.manage')) {
            return true;
        }

        if ($document->uploaded_by === $user->id) {
            return true;
        }

        if ($user->role === 'employee' && $document->owner_record_type === Employee::class) {
            return (int) $user->employee?->id === (int) $document->owner_record_id;
        }

        if ($user->role === 'client' && $document->owner_record_type === Client::class) {
            return (int) $user->client?->id === (int) $document->owner_record_id;
        }

        return false;
    }

    public function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('documents.manage');
    }

    private function applyVisibility(Builder $query, ?User $user): void
    {
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->isSuperAdmin() || $user->hasPermission('documents.view') || $user->hasPermission('documents.manage')) {
            return;
        }

        $query->where(function ($inner) use ($user) {
            $inner->where('uploaded_by', $user->id);

            if ($user->role === 'employee' && $user->employee) {
                $inner->orWhere(function ($employeeQuery) use ($user) {
                    $employeeQuery->where('owner_record_type', Employee::class)
                        ->where('owner_record_id', $user->employee->id);
                });
            }

            if ($user->role === 'client' && $user->client) {
                $inner->orWhere(function ($clientQuery) use ($user) {
                    $clientQuery->where('owner_record_type', Client::class)
                        ->where('owner_record_id', $user->client->id);
                });
            }
        });
    }

    private function resolveOwner(?string $module, mixed $recordId): array
    {
        if (! $module || ! $recordId || ! isset($this->ownerMap()[$module])) {
            return [null, null];
        }

        $class = $this->ownerMap()[$module];
        $class::query()->whereKey($recordId)->firstOrFail();

        return [$class, (int) $recordId];
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            return collect($tags)->map(fn ($tag) => Str::lower(trim((string) $tag)))->filter()->values()->all();
        }

        return collect(explode(',', (string) $tags))
            ->map(fn ($tag) => Str::lower(trim($tag)))
            ->filter()
            ->values()
            ->all();
    }

    private function storeFile(UploadedFile $file, string $module): string
    {
        $directory = 'managed-documents/' . Str::slug($module ?: 'general') . '/' . now()->format('Y/m');

        return $file->store($directory, self::STORAGE_DISK);
    }

    private function audit(ManagedDocument $document, User $user, string $action, string $description, array $metadata = []): void
    {
        $document->audits()->create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'metadata' => $metadata,
        ]);
    }
}
