<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Complaint;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComplaintStatusLog>
 */
class ComplaintStatusLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'in_progress', 'resolved', 'closed'];
        
        return [
            'old_status' => fake()->randomElement($statuses),
            'new_status' => fake()->randomElement($statuses),
            'comment' => fake()->optional(0.6)->sentence(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create log for specific complaint
     */
    public function forComplaint(Complaint $complaint): static
    {
        return $this->state(fn (array $attributes) => [
            'complaint_id' => $complaint->id,
        ]);
    }

    /**
     * Create log by specific user
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create status change log
     */
    public function statusChange(string $oldStatus, string $newStatus): static
    {
        return $this->state(fn (array $attributes) => [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }
}
