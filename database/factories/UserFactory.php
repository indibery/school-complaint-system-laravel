<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['admin', 'teacher', 'parent', 'security_staff', 'ops_staff'];
        $role = fake()->randomElement($roles);
        
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => $role,
            'access_channel' => $this->getAccessChannelByRole($role),
            'student_id' => $role === 'parent' ? fake()->numerify('######') : null,
            'employee_id' => in_array($role, ['admin', 'teacher', 'security_staff', 'ops_staff']) ? fake()->numerify('EMP####') : null,
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    /**
     * Get access channel by role
     */
    private function getAccessChannelByRole(string $role): string
    {
        return match($role) {
            'admin' => 'admin_web',
            'teacher' => 'teacher_web',
            'parent' => 'parent_app',
            'security_staff' => 'security_app',
            'ops_staff' => 'ops_web',
            default => 'admin_web'
        };
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'access_channel' => 'admin_web',
            'student_id' => null,
            'employee_id' => fake()->numerify('ADM####'),
        ]);
    }

    /**
     * Create a teacher user
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teacher',
            'access_channel' => 'teacher_web',
            'student_id' => null,
            'employee_id' => fake()->numerify('TCH####'),
        ]);
    }

    /**
     * Create a parent user
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'parent',
            'access_channel' => 'parent_app',
            'student_id' => fake()->numerify('######'),
            'employee_id' => null,
        ]);
    }

    /**
     * Create a security staff user
     */
    public function securityStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'security_staff',
            'access_channel' => 'security_app',
            'student_id' => null,
            'employee_id' => fake()->numerify('SEC####'),
        ]);
    }

    /**
     * Create an operations staff user
     */
    public function opsStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'ops_staff',
            'access_channel' => 'ops_web',
            'student_id' => null,
            'employee_id' => fake()->numerify('OPS####'),
        ]);
    }

    /**
     * Create an inactive user
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
