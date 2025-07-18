<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Category;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 역할 생성
        $roles = ['admin', 'teacher', 'parent', 'staff', 'student'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 권한 생성
        $permissions = [
            'view complaints',
            'create complaints',
            'edit complaints',
            'delete complaints',
            'assign complaints',
            'export complaints',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 부서 생성
        $departments = [
            ['name' => '교무부', 'description' => '교육과정 관련'],
            ['name' => '학생부', 'description' => '학생 생활 관련'],
            ['name' => '행정실', 'description' => '행정업무 관련'],
            ['name' => '보건실', 'description' => '건강 관련'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate($dept);
        }

        // 카테고리 생성
        $categories = [
            ['name' => '학습 관련', 'description' => '수업, 교육과정 등', 'is_active' => true],
            ['name' => '시설 관련', 'description' => '학교 시설, 환경 등', 'is_active' => true],
            ['name' => '급식 관련', 'description' => '급식 품질, 메뉴 등', 'is_active' => true],
            ['name' => '교사 관련', 'description' => '교사 지도, 상담 등', 'is_active' => true],
            ['name' => '기타', 'description' => '기타 민원사항', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate($category);
        }

        // 관리자 계정 생성
        $admin = User::firstOrCreate([
            'email' => 'admin@school.com'
        ], [
            'name' => '관리자',
            'password' => bcrypt('password'),
            'phone' => '010-1234-5678',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // 교사 계정 생성
        $teacher = User::firstOrCreate([
            'email' => 'teacher@school.com'
        ], [
            'name' => '김교사',
            'password' => bcrypt('password'),
            'phone' => '010-2345-6789',
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $teacher->assignRole('teacher');

        // 학부모 계정 생성
        $parent = User::firstOrCreate([
            'email' => 'parent@school.com'
        ], [
            'name' => '박학부모',
            'password' => bcrypt('password'),
            'phone' => '010-3456-7890',
            'role' => 'parent',
            'is_active' => true,
        ]);
        $parent->assignRole('parent');

        // 학생 데이터 생성
        $student1 = Student::firstOrCreate([
            'student_number' => '2024001'
        ], [
            'parent_id' => $parent->id,
            'name' => '박학생1',
            'grade' => 3,
            'class' => 1,
            'is_active' => true,
        ]);

        $student2 = Student::firstOrCreate([
            'student_number' => '2024002'
        ], [
            'parent_id' => $parent->id,
            'name' => '박학생2',
            'grade' => 5,
            'class' => 2,
            'is_active' => true,
        ]);

        $this->command->info('테스트 데이터가 성공적으로 생성되었습니다.');
    }
}
