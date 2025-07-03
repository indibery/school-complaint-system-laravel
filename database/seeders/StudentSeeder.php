<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            // 김민수 학부모의 자녀들 (parent_id = 6)
            [
                'parent_id' => 6,
                'name' => '김철수',
                'student_number' => '20230301',
                'grade' => 3,
                'class' => 2,
                'is_active' => true,
            ],
            [
                'parent_id' => 6,
                'name' => '김영희',
                'student_number' => '20250101',
                'grade' => 1,
                'class' => 3,
                'is_active' => true,
            ],
            
            // 이영희 학부모의 자녀 (parent_id = 7)
            [
                'parent_id' => 7,
                'name' => '이민준',
                'student_number' => '20210501',
                'grade' => 5,
                'class' => 1,
                'is_active' => true,
            ],
            
            // 박철수 학부모의 자녀들 (parent_id = 8)
            [
                'parent_id' => 8,
                'name' => '박서연',
                'student_number' => '20220201',
                'grade' => 4,
                'class' => 2,
                'is_active' => true,
            ],
            [
                'parent_id' => 8,
                'name' => '박준호',
                'student_number' => '20240301',
                'grade' => 2,
                'class' => 1,
                'is_active' => true,
            ],
            [
                'parent_id' => 8,
                'name' => '박소희',
                'student_number' => '20200901',
                'grade' => 6,
                'class' => 3,
                'is_active' => true,
            ],
            
            // 최영수 학부모의 자녀 (parent_id = 9)
            [
                'parent_id' => 9,
                'name' => '최예준',
                'student_number' => '20231001',
                'grade' => 3,
                'class' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($students as $student) {
            DB::table('students')->insert(array_merge($student, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
