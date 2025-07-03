<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grades = ['1학년', '2학년', '3학년', '4학년', '5학년', '6학년'];
        $classes = ['1반', '2반', '3반', '4반', '5반'];
        
        return [
            'name' => fake()->name(),
            'student_number' => fake()->unique()->numerify('######'),
            'grade' => fake()->randomElement($grades),
            'class' => fake()->randomElement($classes),
            'birth_date' => fake()->dateTimeBetween('-12 years', '-6 years')->format('Y-m-d'),
            'phone' => fake()->optional(0.3)->phoneNumber(), // 30% 확률로 연락처 있음
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    /**
     * Create a student with specific parent
     */
    public function withParent(User $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Create a student in specific grade
     */
    public function grade(string $grade): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $grade,
        ]);
    }

    /**
     * Create an inactive student (졸업생 등)
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
