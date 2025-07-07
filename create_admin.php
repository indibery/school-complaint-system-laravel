<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// 관리자 사용자 생성
$admin = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

// 관리자 역할 할당
$admin->assignRole('admin');

echo "Admin user created successfully!\n";
echo "Email: admin@example.com\n";
echo "Password: password\n";
