<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $adminUser;
    protected $teacherUser;
    protected $studentUser;
    protected $parentUser;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 기본 역할 생성
        $this->createRoles();
        
        // 테스트용 부서 생성
        $this->department = Department::factory()->create([
            'name' => '테스트 부서',
            'is_active' => true,
        ]);
        
        // 테스트용 사용자 생성
        $this->createTestUsers();
    }

    private function createRoles()
    {
        $roles = ['admin', 'teacher', 'student', 'parent', 'staff'];
        
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }

    private function createTestUsers()
    {
        // 관리자 사용자
        $this->adminUser = User::factory()->create([
            'name' => '관리자',
            'email' => 'admin@school.com',
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');

        // 교사 사용자
        $this->teacherUser = User::factory()->create([
            'name' => '김교사',
            'email' => 'teacher@school.com',
            'employee_id' => 'T001',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->teacherUser->assignRole('teacher');

        // 학생 사용자
        $this->studentUser = User::factory()->create([
            'name' => '이학생',
            'email' => 'student@school.com',
            'student_id' => 'S001',
            'grade' => 3,
            'class_number' => 2,
            'is_active' => true,
        ]);
        $this->studentUser->assignRole('student');

        // 학부모 사용자
        $this->parentUser = User::factory()->create([
            'name' => '박학부모',
            'email' => 'parent@school.com',
            'is_active' => true,
        ]);
        $this->parentUser->assignRole('parent');
    }

    /** @test */
    public function admin_can_get_users_list()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'roles',
                            'is_active',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'links',
                    'meta'
                ]
            ]);
    }

    /** @test */
    public function non_admin_can_only_see_their_own_profile()
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200);
        
        $users = $response->json('data.data');
        $this->assertCount(1, $users);
        $this->assertEquals($this->teacherUser->id, $users[0]['id']);
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => '새사용자',
            'email' => 'newuser@school.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'employee_id' => 'T002',
            'department_id' => $this->department->id,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => '새사용자',
                'email' => 'newuser@school.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@school.com',
            'employee_id' => 'T002',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_user()
    {
        Sanctum::actingAs($this->teacherUser);

        $userData = [
            'name' => '새사용자',
            'email' => 'newuser@school.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_any_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/v1/users/{$this->teacherUser->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->teacherUser->id,
                'name' => $this->teacherUser->name,
                'email' => $this->teacherUser->email,
            ]);
    }

    /** @test */
    public function user_can_view_their_own_profile()
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson("/api/v1/users/{$this->teacherUser->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->teacherUser->id,
                'name' => $this->teacherUser->name,
            ]);
    }

    /** @test */
    public function user_cannot_view_other_users_profile()
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson("/api/v1/users/{$this->studentUser->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_user()
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'name' => '수정된 이름',
            'email' => 'updated@school.com',
        ];

        $response = $this->putJson("/api/v1/users/{$this->teacherUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => '수정된 이름',
                'email' => 'updated@school.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->teacherUser->id,
            'name' => '수정된 이름',
            'email' => 'updated@school.com',
        ]);
    }

    /** @test */
    public function user_can_update_their_own_profile()
    {
        Sanctum::actingAs($this->teacherUser);

        $updateData = [
            'name' => '자신의 수정된 이름',
            'phone' => '010-1234-5678',
        ];

        $response = $this->putJson("/api/v1/users/{$this->teacherUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => '자신의 수정된 이름',
            ]);
    }

    /** @test */
    public function admin_can_change_user_status()
    {
        Sanctum::actingAs($this->adminUser);

        $statusData = [
            'is_active' => false,
            'reason' => '테스트 비활성화',
        ];

        $response = $this->putJson("/api/v1/users/{$this->teacherUser->id}/status", $statusData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->teacherUser->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function non_admin_cannot_change_user_status()
    {
        Sanctum::actingAs($this->teacherUser);

        $statusData = [
            'is_active' => false,
            'reason' => '테스트 비활성화',
        ];

        $response = $this->putJson("/api/v1/users/{$this->studentUser->id}/status", $statusData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/v1/users/{$this->teacherUser->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('users', [
            'id' => $this->teacherUser->id,
        ]);
    }

    /** @test */
    public function admin_cannot_delete_themselves()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/v1/users/{$this->adminUser->id}");

        $response->assertStatus(400);
    }

    /** @test */
    public function can_get_users_by_role()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/teachers');

        $response->assertStatus(200);
        
        $users = $response->json('data.data');
        $this->assertGreaterThan(0, count($users));
        
        foreach ($users as $user) {
            $this->assertContains('teacher', array_column($user['roles'], 'name'));
        }
    }

    /** @test */
    public function can_get_students_by_class()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/students/by-class?grade=3&class_number=2');

        $response->assertStatus(200);
        
        $users = $response->json('data.data');
        foreach ($users as $user) {
            $this->assertEquals(3, $user['grade']);
            $this->assertEquals(2, $user['class_number']);
        }
    }

    /** @test */
    public function can_perform_advanced_search()
    {
        Sanctum::actingAs($this->adminUser);

        $searchData = [
            'query' => '김교사',
            'filters' => [
                'roles' => ['teacher'],
                'status' => 'active',
            ],
            'sort' => [
                'field' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ];

        $response = $this->postJson('/api/v1/users/search', $searchData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'users' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'email',
                                'roles',
                            ]
                        ]
                    ],
                    'pagination',
                    'stats'
                ]
            ]);
    }

    /** @test */
    public function can_get_search_suggestions()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/suggestions?query=김&type=name&limit=5');

        $response->assertStatus(200);
        
        $suggestions = $response->json('data');
        $this->assertIsArray($suggestions);
        
        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('id', $suggestion);
            $this->assertArrayHasKey('text', $suggestion);
            $this->assertArrayHasKey('type', $suggestion);
        }
    }

    /** @test */
    public function can_get_filter_options()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/filter-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'roles',
                    'departments',
                    'grades',
                    'classes',
                    'status_options',
                    'sort_options',
                    'sort_directions',
                ]
            ]);
    }

    /** @test */
    public function can_get_user_statistics()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_users',
                    'active_users',
                    'inactive_users',
                    'roles' => [
                        'admin',
                        'teacher',
                        'student',
                        'parent',
                        'staff',
                    ],
                    'recent_registrations',
                    'homeroom_teachers',
                ]
            ]);
    }

    /** @test */
    public function can_export_users()
    {
        Sanctum::actingAs($this->adminUser);

        $exportData = [
            'format' => 'csv',
            'include_metadata' => true,
            'filters' => [
                'roles' => ['teacher'],
            ],
        ];

        $response = $this->postJson('/api/v1/users/export', $exportData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'filename',
                    'filepath',
                    'download_url',
                    'total_records',
                    'format',
                    'created_at',
                ]
            ]);
    }

    /** @test */
    public function can_get_bulk_options()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/bulk-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'actions',
                    'export_formats',
                    'max_selection',
                    'confirmation_required',
                ]
            ]);
    }

    /** @test */
    public function validates_user_creation_data()
    {
        Sanctum::actingAs($this->adminUser);

        $invalidData = [
            'name' => '', // 빈 이름
            'email' => 'invalid-email', // 잘못된 이메일
            'password' => '123', // 너무 짧은 비밀번호
            'role' => 'invalid-role', // 잘못된 역할
        ];

        $response = $this->postJson('/api/v1/users', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    /** @test */
    public function prevents_duplicate_email()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => '중복 이메일 테스트',
            'email' => $this->teacherUser->email, // 기존 이메일
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
