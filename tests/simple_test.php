<?php

echo "🧪 간단한 시스템 테스트 시작\n";
echo "========================================\n\n";

// 1. PHP 버전 확인
echo "1. PHP 버전 확인\n";
echo "PHP 버전: " . phpversion() . "\n\n";

// 2. 디렉토리 구조 확인
echo "2. 디렉토리 구조 확인\n";
$directories = [
    'app',
    'app/Http/Controllers',
    'app/Services',
    'app/Models',
    'resources/views',
    'tests',
    'database'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "✅ {$dir} 디렉토리 존재\n";
    } else {
        echo "❌ {$dir} 디렉토리 없음\n";
    }
}
echo "\n";

// 3. 주요 파일 확인
echo "3. 주요 파일 확인\n";
$files = [
    'app/Http/Controllers/Api/ComplaintController.php',
    'app/Services/Complaint/ComplaintService.php',
    'app/Models/Complaint.php',
    'resources/views/complaints/index.blade.php',
    'database/database.sqlite',
    '.env'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} 파일 존재\n";
    } else {
        echo "❌ {$file} 파일 없음\n";
    }
}
echo "\n";

// 4. 클래스 존재 확인
echo "4. 클래스 존재 확인\n";
$classes = [
    'App\\Http\\Controllers\\Api\\ComplaintController',
    'App\\Services\\Complaint\\ComplaintService',
    'App\\Models\\Complaint',
    'App\\Models\\User'
];

// Composer autoload 로드
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "✅ Composer autoload 로드 완료\n";
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "✅ {$class} 클래스 존재\n";
        } else {
            echo "❌ {$class} 클래스 없음\n";
        }
    }
} else {
    echo "❌ Composer autoload 파일 없음\n";
}
echo "\n";

echo "========================================\n";
echo "🎉 간단한 시스템 테스트 완료\n";
echo "========================================\n";
