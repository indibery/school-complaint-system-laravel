<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // 관리자
            [
                'name' => '시스템 관리자',
                'email' => 'admin@school.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'access_channel' => 'admin_web',
                'employee_id' => 'ADM001',
                'phone' => '02-1234-5678',
                'is_active' => true,
            ],
            
            // 교사들
            [
                'name' => '김선생님',
                'email' => 'kim.teacher@school.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'access_channel' => 'teacher_web',
                'employee_id' => 'TCH001',
                'phone' => '02-1234-5679',
                'is_active' => true,
            ],
            [
                'name' => '이선생님',
                'email' => 'lee.teacher@school.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'access_channel' => 'teacher_web',
                'employee_id' => 'TCH002',
                'phone' => '02-1234-5680',
                'is_active' => true,
            ],
            
            // 운영팀
            [
                'name' => '박운영팀',
                'email' => 'park.ops@school.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'ops_staff',
                'access_channel' => 'ops_web',
                'employee_id' => 'OPS001',
                'phone' => '02-1234-5681',
                'is_active' => true,
            ],
            
            // 학교지킴이
            [
                'name' => '최지킴이',
                'email' => 'choi.security@school.edu',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'security_staff',
                'access_channel' => 'security_app',
                'employee_id' => 'SEC001',
                'phone' => '02-1234-5682',
                'is_active' => true,
            ],
            
            // 학부모들
            [
                'name' => '김민수',
                'email' => 'kim.parent@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'parent',
                'access_channel' => 'parent_app',
                'phone' => '010-1234-5678',
                'is_active' => true,
            ],
            [
                'name' => '이영희',
                'email' => 'lee.parent@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'parent',
                'access_channel' => 'parent_app',
                'phone' => '010-1234-5679',
                'is_active' => true,
            ],
            [
                'name' => '박철수',
                'email' => 'park.parent@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'parent',
                'access_channel' => 'parent_app',
                'phone' => '010-1234-5680',
                'is_active' => true,
            ],
            [
                'name' => '최영수',
                'email' => 'choi.parent@gmail.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'parent',
                'access_channel' => 'parent_app',
                'phone' => '010-1234-5681',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert(array_merge($user, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
