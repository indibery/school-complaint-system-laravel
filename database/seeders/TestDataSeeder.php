<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Category;
use App\Models\Department;
use App\Models\Complaint;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 권한 생성
        $this->createPermissions();
        
        // 역할 생성
        $this->createRoles();
        
        // 사용자 생성
        $this->createUsers();
        
        // 부서 생성
        $this->createDepartments();
        
        // 카테고리 생성
        $this->createCategories();
        
        // 학생 생성
        $this->createStudents();
        
        // 민원 생성
        $this->createComplaints();
        
        $this->command->info('테스트 데이터 시딩이 완료되었습니다!');
    }
    
    private function createPermissions()
    {
        $permissions = [
            'view_complaints',
            'create_complaints',
            'edit_complaints',
            'delete_complaints',
            'manage_users',
            'manage_categories',
            'manage_departments',
            'view_reports',
            'export_reports',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
    
    private function createRoles()
    {
        // Admin 역할
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view_complaints',
            'create_complaints',
            'edit_complaints',
            'delete_complaints',
            'manage_users',
            'manage_categories',
            'manage_departments',
            'view_reports',
            'export_reports',
        ]);
        
        // Staff 역할
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->givePermissionTo([
            'view_complaints',
            'create_complaints',
            'edit_complaints',
            'view_reports',
        ]);
        
        // User 역할
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'view_complaints',
            'create_complaints',
        ]);
        
        // Parent 역할
        $parent = Role::firstOrCreate(['name' => 'parent']);
        $parent->givePermissionTo([
            'view_complaints',
            'create_complaints',
        ]);
    }
    
    private function createUsers()
    {
        // 관리자 사용자
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');
        
        // 직원 사용자
        $staff = User::firstOrCreate([
            'email' => 'staff@example.com'
        ], [
            'name' => 'Staff User',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staff->assignRole('staff');
        
        // 부모 사용자
        $parent = User::firstOrCreate([
            'email' => 'parent@example.com'
        ], [
            'name' => '김부모',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $parent->assignRole('parent');
        
        // 일반 사용자
        $user = User::firstOrCreate([
            'email' => 'user@example.com'
        ], [
            'name' => '일반사용자',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole('user');
    }
    
    private function createDepartments()
    {
        Department::firstOrCreate([
            'name' => '행정실'
        ], [
            'description' => '행정업무 담당',
            'code' => 'ADM',
            'is_active' => true,
        ]);
        
        Department::firstOrCreate([
            'name' => '교무실'
        ], [
            'description' => '교육업무 담당',
            'code' => 'EDU',
            'is_active' => true,
        ]);
        
        Department::firstOrCreate([
            'name' => '시설관리팀'
        ], [
            'description' => '시설 관리 및 유지보수',
            'code' => 'FAC',
            'is_active' => true,
        ]);
    }
    
    private function createCategories()
    {
        Category::firstOrCreate([
            'name' => '시설관리'
        ], [
            'description' => '시설 관련 민원',
            'is_active' => true,
        ]);
        
        Category::firstOrCreate([
            'name' => '교육과정'
        ], [
            'description' => '교육과정 관련 민원',
            'is_active' => true,
        ]);
        
        Category::firstOrCreate([
            'name' => '급식'
        ], [
            'description' => '급식 관련 민원',
            'is_active' => true,
        ]);
        
        Category::firstOrCreate([
            'name' => '안전'
        ], [
            'description' => '안전 관련 민원',
            'is_active' => true,
        ]);
        
        Category::firstOrCreate([
            'name' => '기타'
        ], [
            'description' => '기타 민원',
            'is_active' => true,
        ]);
    }
    
    private function createStudents()
    {
        $parent = User::where('email', 'parent@example.com')->first();
        
        Student::firstOrCreate([
            'name' => '김학생'
        ], [
            'student_number' => '2024001',
            'grade' => '3',
            'class' => '1',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
        
        Student::firstOrCreate([
            'name' => '이학생'
        ], [
            'student_number' => '2024002',
            'grade' => '2',
            'class' => '3',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
        
        Student::firstOrCreate([
            'name' => '박학생'
        ], [
            'student_number' => '2024003',
            'grade' => '1',
            'class' => '2',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
    }
    
    private function createComplaints()
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $staff = User::where('email', 'staff@example.com')->first();
        $parent = User::where('email', 'parent@example.com')->first();
        
        $categories = Category::all();
        $students = Student::all();
        
        // 관리자 민원
        Complaint::firstOrCreate([
            'title' => '교실 에어컨 고장'
        ], [
            'content' => '3층 2학년 1반 교실 에어컨이 고장났습니다. 빠른 수리 부탁드립니다.',
            'status' => 'pending',
            'priority' => 'high',
            'user_id' => $admin->id,
            'category_id' => $categories->where('name', '시설관리')->first()->id,
            'student_id' => $students->first()->id,
            'is_public' => true,
            'is_urgent' => false,
        ]);
        
        // 부모 민원
        Complaint::firstOrCreate([
            'title' => '급식실 청소 요청'
        ], [
            'content' => '급식실 바닥에 기름때가 많이 끼어있습니다. 청소를 요청합니다.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $parent->id,
            'category_id' => $categories->where('name', '급식')->first()->id,
            'student_id' => $students->skip(1)->first()->id,
            'assigned_to' => $staff->id,
            'is_public' => true,
            'is_urgent' => false,
        ]);
        
        // 직원 민원
        Complaint::firstOrCreate([
            'title' => '운동장 배수로 막힘'
        ], [
            'content' => '운동장 배수로가 막혀서 비 온 후 물이 잘 빠지지 않습니다.',
            'status' => 'resolved',
            'priority' => 'normal',
            'user_id' => $staff->id,
            'category_id' => $categories->where('name', '시설관리')->first()->id,
            'student_id' => $students->last()->id,
            'assigned_to' => $admin->id,
            'is_public' => true,
            'is_urgent' => false,
            'resolved_at' => now()->subDays(2),
        ]);
        
        // 긴급 민원
        Complaint::firstOrCreate([
            'title' => '화재 경보기 오작동'
        ], [
            'content' => '1층 복도 화재 경보기가 계속 울리고 있습니다. 긴급 확인 부탁드립니다.',
            'status' => 'pending',
            'priority' => 'urgent',
            'user_id' => $admin->id,
            'category_id' => $categories->where('name', '안전')->first()->id,
            'student_id' => $students->first()->id,
            'is_public' => true,
            'is_urgent' => true,
        ]);
        
        // 기타 민원
        Complaint::firstOrCreate([
            'title' => '도서관 에어컨 온도 조절'
        ], [
            'content' => '도서관 에어컨 온도가 너무 낮습니다. 온도 조절을 요청합니다.',
            'status' => 'pending',
            'priority' => 'low',
            'user_id' => $parent->id,
            'category_id' => $categories->where('name', '기타')->first()->id,
            'student_id' => $students->skip(2)->first()->id,
            'is_public' => true,
            'is_urgent' => false,
        ]);
        
        // 교육과정 민원
        Complaint::firstOrCreate([
            'title' => '체육 수업 시간 변경 요청'
        ], [
            'content' => '현재 체육 수업 시간이 너무 더운 시간대입니다. 시간 변경을 요청합니다.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $parent->id,
            'category_id' => $categories->where('name', '교육과정')->first()->id,
            'student_id' => $students->first()->id,
            'assigned_to' => $staff->id,
            'is_public' => true,
            'is_urgent' => false,
        ]);
    }
}
