<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 카테고리 생성
        $categories = [
            ['name' => '학사 관리', 'description' => '학사 일정, 교육과정 등에 관한 민원'],
            ['name' => '시설 관리', 'description' => '학교 시설물 관리 및 개선에 관한 민원'],
            ['name' => '급식', 'description' => '급식 품질, 메뉴 등에 관한 민원'],
            ['name' => '학교 폭력', 'description' => '학교 폭력 예방 및 대응에 관한 민원'],
            ['name' => '교육 과정', 'description' => '수업, 평가 등 교육과정에 관한 민원'],
            ['name' => '방과후 활동', 'description' => '방과후학교, 동아리 등에 관한 민원'],
            ['name' => '학생 생활', 'description' => '교복, 두발, 생활지도 등에 관한 민원'],
            ['name' => '교직원', 'description' => '교사, 직원 등에 관한 민원'],
            ['name' => '통학', 'description' => '통학버스, 통학로 안전 등에 관한 민원'],
            ['name' => '기타', 'description' => '기타 학교 운영에 관한 민원'],
        ];

        foreach ($categories as $categoryData) {
            Category::create(array_merge($categoryData, ['is_active' => true]));
        }

        // 사용자별로 민원 생성
        $users = User::all();
        $categories = Category::all();
        $assignees = User::role(['admin', 'staff'])->get();

        foreach ($users as $user) {
            // 각 사용자마다 0~5개의 민원 생성
            $complaintCount = rand(0, 5);
            
            for ($i = 0; $i < $complaintCount; $i++) {
                $status = $this->faker->randomElement(['pending', 'in_progress', 'resolved', 'closed']);
                $priority = $this->faker->randomElement(['low', 'normal', 'high', 'urgent']);
                
                $complaint = Complaint::create([
                    'complaint_number' => $this->generateComplaintNumber(),
                    'title' => $this->faker->sentence(),
                    'content' => $this->faker->paragraphs(3, true),
                    'status' => $status,
                    'priority' => $priority,
                    'category_id' => $categories->random()->id,
                    'user_id' => $user->id,
                    'assigned_to' => $status !== 'pending' && $assignees->count() > 0 ? $assignees->random()->id : null,
                    'is_public' => $this->faker->boolean(80),
                    'is_anonymous' => $this->faker->boolean(20),
                    'expected_completion_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
                    'completed_at' => in_array($status, ['resolved', 'closed']) ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
                    'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
                ]);

                // 상태 로그 생성
                $complaint->statusLogs()->create([
                    'status' => 'pending',
                    'comment' => '민원이 접수되었습니다.',
                    'user_id' => $complaint->user_id,
                    'created_at' => $complaint->created_at,
                ]);

                // 추가 상태 변경 로그
                if ($status !== 'pending') {
                    $complaint->statusLogs()->create([
                        'status' => $status,
                        'comment' => $this->getStatusComment($status),
                        'user_id' => $complaint->assigned_to ?? $complaint->user_id,
                        'created_at' => $this->faker->dateTimeBetween($complaint->created_at, 'now'),
                    ]);
                }

                // 댓글 추가 (50% 확률)
                if ($this->faker->boolean(50)) {
                    $commentCount = rand(1, 5);
                    for ($j = 0; $j < $commentCount; $j++) {
                        $complaint->comments()->create([
                            'user_id' => $this->faker->randomElement([$complaint->user_id, $complaint->assigned_to, $users->random()->id]),
                            'content' => $this->faker->paragraph(),
                            'is_internal' => $this->faker->boolean(30),
                            'created_at' => $this->faker->dateTimeBetween($complaint->created_at, 'now'),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Generate complaint number
     */
    private function generateComplaintNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Complaint::whereDate('created_at', today())->count() + 1;
        return $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status comment
     */
    private function getStatusComment(string $status): string
    {
        $comments = [
            'in_progress' => '민원 처리를 시작했습니다.',
            'resolved' => '민원이 해결되었습니다.',
            'closed' => '민원 처리가 완료되었습니다.',
        ];

        return $comments[$status] ?? '상태가 변경되었습니다.';
    }

    /**
     * Get faker instance
     */
    private function faker()
    {
        return \Faker\Factory::create('ko_KR');
    }
}
