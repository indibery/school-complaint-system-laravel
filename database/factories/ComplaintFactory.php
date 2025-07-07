<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        return [
            'complaint_number' => $this->generateComplaintNumber(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->faker->randomElement($priorities),
            'category_id' => Category::factory(),
            'user_id' => User::factory(),
            'assigned_to' => $this->faker->boolean(70) ? User::factory() : null,
            'is_public' => $this->faker->boolean(80),
            'is_anonymous' => $this->faker->boolean(20),
            'expected_completion_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Generate a unique complaint number
     */
    private function generateComplaintNumber(): string
    {
        $date = now()->format('Ymd');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$date}-{$random}";
    }

    /**
     * Indicate that the complaint is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'completed_at' => $this->faker->dateTimeBetween($attributes['created_at'], 'now'),
        ]);
    }

    /**
     * Indicate that the complaint is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the complaint is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'assigned_to' => null,
        ]);
    }
}
