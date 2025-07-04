<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Complaint;
use App\Models\User;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $complaints = Complaint::all();
        $users = User::all();
        
        if ($complaints->isEmpty() || $users->isEmpty()) {
            return;
        }

        $sampleComments = [
            [
                'content' => '민원을 접수했습니다. 빠른 시일 내에 처리하겠습니다.',
                'is_public' => true,
            ],
            [
                'content' => '현장 확인을 위해 방문 예정입니다.',
                'is_public' => true,
            ],
            [
                'content' => '담당 부서에 전달했습니다.',
                'is_public' => false,
            ],
            [
                'content' => '문제가 해결되었는지 확인 부탁드립니다.',
                'is_public' => true,
            ],
            [
                'content' => '감사합니다. 빠른 처리에 만족합니다.',
                'is_public' => true,
            ],
        ];

        foreach ($complaints as $complaint) {
            // 각 민원에 1-3개의 댓글 추가
            $commentCount = rand(1, 3);
            
            for ($i = 0; $i < $commentCount; $i++) {
                $comment = $sampleComments[array_rand($sampleComments)];
                
                DB::table('comments')->insert([
                    'complaint_id' => $complaint->id,
                    'user_id' => $users->random()->id,
                    'content' => $comment['content'],
                    'is_public' => $comment['is_public'],
                    'created_at' => now()->subHours(rand(1, 48)),
                    'updated_at' => now()->subHours(rand(1, 48)),
                ]);
            }
        }
    }
}
