<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeProfileAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_employee_profile_photo_and_documents_on_create(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/admin/employees', array_merge($this->employeePayload(), [
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            'nid_front_file' => UploadedFile::fake()->image('nid-front.jpg'),
            'nid_back_file' => UploadedFile::fake()->create('nid-back.pdf', 100, 'application/pdf'),
            'cv_file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'appointment_letter_file' => UploadedFile::fake()->create('appointment.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'agreement_file' => UploadedFile::fake()->image('agreement.png'),
        ]));

        $response->assertRedirect('/admin/employees');
        $employee = Employee::where('name', 'Asset Employee')->firstOrFail();

        foreach ([
            'profile_photo',
            'nid_front_file',
            'nid_back_file',
            'cv_file',
            'appointment_letter_file',
            'agreement_file',
        ] as $field) {
            $this->assertNotNull($employee->{$field});
            $this->assertStringStartsWith('employees/' . $employee->employee_id . '/', $employee->{$field});
            Storage::disk('public')->assertExists($employee->{$field});
        }

        $profile = $this->actingAs($admin)->get('/admin/employees/' . $employee->id);

        $profile->assertOk();
        $profile->assertSee('View / Download');
        $profile->assertSee('Uploaded');
        $profile->assertDontSee('Photo upload not added yet');
        $profile->assertDontSee('Upload not added yet');
    }

    public function test_admin_can_reset_linked_employee_login_password(): void
    {
        $admin = $this->admin();
        $employeeUser = User::factory()->create([
            'role' => 'employee',
            'password' => Hash::make('old-password'),
        ]);
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
        ]);

        $form = $this->actingAs($admin)->get('/admin/employees/' . $employee->id . '/reset-login-password');

        $form->assertOk();
        $form->assertSee('Reset Employee Password');

        $response = $this->actingAs($admin)->post('/admin/employees/' . $employee->id . '/reset-login-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/admin/employees/' . $employee->id);
        $this->assertTrue(Hash::check('new-password', $employeeUser->refresh()->password));
    }

    private function employeePayload(): array
    {
        return [
            'name' => 'Asset Employee',
            'mobile' => '01711111111',
            'email' => 'asset.employee@example.com',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'probation',
            'monthly_salary' => 20000,
        ];
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'employee_id' => 'EMP-' . uniqid(),
            'name' => 'Asset Employee',
            'department' => 'Moderator',
            'role' => 'Moderator',
            'joining_date' => '2026-06-01',
            'status' => 'probation',
            'monthly_salary' => 10000,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
