<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;
use App\Models\Student;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $parent = User::where('role', 'parent')->first();
        $category = Category::first();
        $student = Student::first();
        
        if (!$parent || !$category) {
            return;
        }

        $complaints = [
            [
                'user_id' => $parent->id,
                'student_id' => $student?->id,
                'category_id' => $category->id,
                'title' => '급식실 위생 상태 개선 요청',
                'content' => '급식실의 위생 상태가 좋지 않아 보입니다. 개선해 주시기 바랍니다.',
                'status' => 'submitted',
                'priority' => 'normal',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $parent->id,
                'student_id' => $student?->id,
                'category_id' => $category->id,
                'title' => '교실 에어컨 고장 신고',
                'content' => '3층 2-1 교실의 에어컨이 고장났습니다. 빠른 수리 부탁드립니다.',
                'status' => 'in_progress',
                'priority' => 'high',
                'is_public' => false,
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
            [
                'user_id' => $parent->id,
                'student_id' => $student?->id,
                'category_id' => $category->id,
                'title' => '운동장 놀이기구 점검 요청',
                'content' => '운동장의 놀이기구가 낡아 보여 안전 점검이 필요합니다.',
                'status' => 'resolved',
                'priority' => 'normal',
                'is_public' => false,
                'completed_at' => now()->subHours(5),
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ],
            [
                'user_id' => $parent->id,
                'student_id' => $student?->id,
                'category_id' => $category->id,
                'title' => '도서관 소음 문제',
                'content' => '도서관에서 소음이 심해 학습에 방해가 됩니다.',
                'status' => 'submitted',
                'priority' => 'low',
                'is_public' => false,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'user_id' => $parent->id,
                'student_id' => $student?->id,
                'category_id' => $category->id,
                'title' => '화장실 청소 상태 불량',
                'content' => '1층 화장실의 청소 상태가 매우 불량합니다. 개선이 필요합니다.',
                'status' => 'closed',
                'priority' => 'urgent',
                'is_public' => false,
                'completed_at' => now()->subDays(1),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(1),
            ],
        ];

        foreach ($complaints as $complaint) {
            DB::table('complaints')->insert($complaint);
        }
    }
}
