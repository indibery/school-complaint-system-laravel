<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Complaint;
use App\Models\Comment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = [
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['pdf', 'application/pdf'],
            ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['txt', 'text/plain'],
        ];
        
        $fileType = fake()->randomElement($fileTypes);
        $originalName = fake()->words(2, true) . '.' . $fileType[0];
        
        return [
            'original_filename' => $originalName,
            'stored_filename' => fake()->uuid() . '.' . $fileType[0],
            'file_path' => 'attachments/' . date('Y/m/'),
            'file_size' => fake()->numberBetween(1024, 5242880), // 1KB - 5MB
            'mime_type' => $fileType[1],
            'uploaded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create attachment for specific complaint
     */
    public function forComplaint(Complaint $complaint): static
    {
        return $this->state(fn (array $attributes) => [
            'complaint_id' => $complaint->id,
            'comment_id' => null,
        ]);
    }

    /**
     * Create attachment for specific comment
     */
    public function forComment(Comment $comment): static
    {
        return $this->state(fn (array $attributes) => [
            'complaint_id' => $comment->complaint_id,
            'comment_id' => $comment->id,
        ]);
    }

    /**
     * Create image attachment
     */
    public function image(): static
    {
        $imageTypes = [
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['gif', 'image/gif'],
        ];
        
        $imageType = fake()->randomElement($imageTypes);
        
        return $this->state(fn (array $attributes) => [
            'original_filename' => fake()->words(2, true) . '.' . $imageType[0],
            'stored_filename' => fake()->uuid() . '.' . $imageType[0],
            'mime_type' => $imageType[1],
            'file_size' => fake()->numberBetween(51200, 2097152), // 50KB - 2MB
        ]);
    }

    /**
     * Create document attachment
     */
    public function document(): static
    {
        $docTypes = [
            ['pdf', 'application/pdf'],
            ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['txt', 'text/plain'],
        ];
        
        $docType = fake()->randomElement($docTypes);
        
        return $this->state(fn (array $attributes) => [
            'original_filename' => fake()->words(3, true) . '.' . $docType[0],
            'stored_filename' => fake()->uuid() . '.' . $docType[0],
            'mime_type' => $docType[1],
            'file_size' => fake()->numberBetween(10240, 1048576), // 10KB - 1MB
        ]);
    }
}

