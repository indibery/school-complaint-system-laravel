<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            '학사 관리',
            '시설 관리',
            '급식',
            '학교 폭력',
            '교육 과정',
            '방과후 활동',
            '학생 생활',
            '교직원',
            '통학',
            '기타'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
