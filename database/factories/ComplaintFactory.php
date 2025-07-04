<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Category;
use App\Models\Student;

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
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $statuses = ['submitted', 'in_progress', 'resolved', 'closed'];
        
        // 랜덤 학부모 선택
        $parent = User::where('role', 'parent')->inRandomOrder()->first();
        
        // 랜덤 카테고리 선택
        $category = Category::inRandomOrder()->first();
        
        // 학부모의 자녀 중 랜덤 선택 (있다면)
        $student = null;
        if ($parent) {
            $student = Student::where('parent_id', $parent->id)->inRandomOrder()->first();
        }
        
        return [
            'user_id' => $parent?->id ?? User::where('role', 'parent')->first()?->id,
            'student_id' => $student?->id,
            'category_id' => $category?->id ?? 1,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'priority' => fake()->randomElement($priorities),
            'status' => fake()->randomElement($statuses),
            'complainant_name' => $parent?->name ?? fake()->name(),
            'complainant_email' => $parent?->email ?? fake()->email(),
            'complainant_phone' => fake()->phoneNumber(),
            'incident_date' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'incident_location' => fake()->optional(0.6)->randomElement([
                '교실', '운동장', '급식실', '화장실', '복도', '도서관', '체육관', '과학실', '음악실', '미술실'
            ]),
            'complaint_number' => $this->generateComplaintNumber(),
            'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }

    /**
     * 민원번호 생성
     */
    private function generateComplaintNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = fake()->numberBetween(1, 999);
        return $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create a complaint by specific parent
     */
    public function byParent(User $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $parent->id,
            'complainant_name' => $parent->name,
            'complainant_email' => $parent->email,
        ]);
    }

    /**
     * Create a complaint in specific category
     */
    public function inCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Create a complaint about specific student
     */
    public function aboutStudent(Student $student): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $student->id,
        ]);
    }

    /**
     * Create an assigned complaint
     */
    public function assignedTo(User $assignee): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $assignee->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Create a complaint with specific priority
     */
    public function priority(string $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Create a complaint with specific status
     */
    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Create a resolved complaint
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => fake()->dateTimeBetween($attributes['created_at'] ?? '-30 days', 'now'),
        ]);
    }

    /**
     * Create a closed complaint
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'resolved_at' => fake()->dateTimeBetween($attributes['created_at'] ?? '-30 days', 'now'),
        ]);
    }
}
