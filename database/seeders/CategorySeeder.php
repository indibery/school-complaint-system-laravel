<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // 학습 관련
            [
                'name' => '학습/교육',
                'description' => '교육과정, 수업, 학습 관련 민원',
                'parent_id' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '교과과정',
                'description' => '교과과정 관련 문의사항',
                'parent_id' => 1,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '숙제/과제',
                'description' => '숙제나 과제 관련 문의',
                'parent_id' => 1,
                'sort_order' => 2,
                'is_active' => true,
            ],
            
            // 시설 관련
            [
                'name' => '시설/환경',
                'description' => '학교 시설 및 환경 관련 민원',
                'parent_id' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => '교실 환경',
                'description' => '교실 시설 및 환경 문제',
                'parent_id' => 4,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '화장실',
                'description' => '화장실 시설 관련 문제',
                'parent_id' => 4,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => '운동장/체육관',
                'description' => '운동장 및 체육관 시설 문제',
                'parent_id' => 4,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}

            
            // 급식 관련
            [
                'name' => '급식/위생',
                'description' => '급식 및 위생 관련 민원',
                'parent_id' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => '급식 메뉴',
                'description' => '급식 메뉴 관련 문의',
                'parent_id' => 8,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '급식 위생',
                'description' => '급식 위생 상태 관련',
                'parent_id' => 8,
                'sort_order' => 2,
                'is_active' => true,
            ],
            
            // 안전 관련
            [
                'name' => '안전/보안',
                'description' => '학교 안전 및 보안 관련 민원',
                'parent_id' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => '등하교 안전',
                'description' => '등하교 시 안전 문제',
                'parent_id' => 11,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '교내 안전',
                'description' => '교내 안전사고 관련',
                'parent_id' => 11,
                'sort_order' => 2,
                'is_active' => true,
            ],
            
            // 학생 지도 관련
            [
                'name' => '학생 지도',
                'description' => '학생 생활지도 관련 민원',
                'parent_id' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => '교우관계',
                'description' => '친구 관계, 따돌림 등',
                'parent_id' => 14,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '학교 규칙',
                'description' => '학교 규칙 관련 문의',
                'parent_id' => 14,
                'sort_order' => 2,
                'is_active' => true,
            ],
            
            // 기타
            [
                'name' => '기타',
                'description' => '기타 민원사항',
                'parent_id' => null,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}
