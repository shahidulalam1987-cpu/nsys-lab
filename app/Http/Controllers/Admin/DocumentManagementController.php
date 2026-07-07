<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManagedDocument;
use App\Services\DocumentManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class DocumentManagementController extends Controller
{
    public function index(Request $request, DocumentManagementService $documents)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(ManagedDocument::CATEGORIES)],
            'status' => ['nullable', Rule::in(array_keys(ManagedDocument::STATUSES))],
            'owner_module' => ['nullable', Rule::in(array_keys(ManagedDocument::OWNER_MODULES))],
            'tag' => ['nullable', 'string', 'max:50'],
        ]);

        return view('admin.documents.index', [
            'documents' => $documents->query($filters, $request->user())->paginate(20)->withQueryString(),
            'filters' => $filters,
            'categories' => ManagedDocument::CATEGORIES,
            'statuses' => ManagedDocument::STATUSES,
            'ownerModules' => ManagedDocument::OWNER_MODULES,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.documents.create', [
            'categories' => ManagedDocument::CATEGORIES,
            'ownerModules' => ManagedDocument::OWNER_MODULES,
            'prefill' => $request->only(['owner_module', 'owner_record_id', 'category']),
        ]);
    }

    public function store(Request $request, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        $data = $this->validatedDocument($request);
        $documents->store($data, $request->file('document'), $request->user());

        return redirect('/admin/documents')->with('success', 'Document uploaded successfully.');
    }

    public function show(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canView($document, $request->user()), 403);

        $document->load(['versions.uploader:id,name,email', 'audits.user:id,name,email', 'uploader:id,name,email']);

        return view('admin.documents.show', compact('document'));
    }

    public function edit(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        return view('admin.documents.edit', [
            'document' => $document,
            'categories' => ManagedDocument::CATEGORIES,
        ]);
    }

    public function update(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(ManagedDocument::CATEGORIES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $documents->updateMetadata($document, $data, $request->user());

        return redirect('/admin/documents/' . $document->id)->with('success', 'Document updated successfully.');
    }

    public function version(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:' . implode(',', DocumentManagementService::ALLOWED_MIMES), 'max:' . DocumentManagementService::MAX_UPLOAD_KB],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        $documents->addVersion($document, $request->file('document'), $request->user(), $data['change_note'] ?? null);

        return back()->with('success', 'New document version uploaded successfully.');
    }

    public function archive(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        $documents->archive($document, $request->user());

        return back()->with('success', 'Document archived successfully.');
    }

    public function restore(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canManage($request->user()), 403);

        $documents->restore($document, $request->user());

        return back()->with('success', 'Document restored successfully.');
    }

    public function download(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canView($document, $request->user()), 403);

        $documents->logDownload($document, $request->user());

        return response()->download(
            Storage::disk('public')->path($document->current_file_path),
            $document->original_file_name ?: basename($document->current_file_path)
        );
    }

    public function preview(Request $request, ManagedDocument $document, DocumentManagementService $documents)
    {
        abort_unless($documents->canView($document, $request->user()), 403);

        if (! in_array(strtolower((string) $document->file_type), ['pdf', 'png', 'jpg', 'jpeg'], true)) {
            return redirect('/admin/documents/' . $document->id)->with('error', 'Preview is available only for PDF and image files.');
        }

        return response()->file(Storage::disk('public')->path($document->current_file_path));
    }

    private function validatedDocument(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(ManagedDocument::CATEGORIES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'owner_module' => ['nullable', Rule::in(array_keys(ManagedDocument::OWNER_MODULES))],
            'owner_record_id' => ['nullable', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date'],
            'document' => ['required', 'file', 'mimes:' . implode(',', DocumentManagementService::ALLOWED_MIMES), 'max:' . DocumentManagementService::MAX_UPLOAD_KB],
        ]);
    }
}
