<?php

echo "🔍 데이터베이스 구조 확인\n";
echo "========================================\n\n";

// SQLite 데이터베이스 연결
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SQLite 데이터베이스 연결 성공\n\n";
    
    // 테이블 목록 조회
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 데이터베이스 테이블 목록:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    echo "\n";
    
    // 각 테이블의 레코드 수 확인
    echo "📈 각 테이블의 레코드 수:\n";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $count = $stmt->fetchColumn();
            echo "  - {$table}: {$count}개\n";
        } catch (Exception $e) {
            echo "  - {$table}: 조회 오류 - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // 민원 테이블 구조 확인
    if (in_array('complaints', $tables)) {
        echo "📋 민원 테이블 구조:\n";
        $stmt = $pdo->query("PRAGMA table_info(complaints)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "  - {$column['name']}: {$column['type']}\n";
        }
        echo "\n";
    }
    
    // 사용자 테이블 구조 확인  
    if (in_array('users', $tables)) {
        echo "👤 사용자 테이블 구조:\n";
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "  - {$column['name']}: {$column['type']}\n";
        }
        echo "\n";
    }
    
    // 기본 데이터 확인
    if (in_array('users', $tables)) {
        echo "👥 사용자 데이터 확인:\n";
        $stmt = $pdo->query("SELECT name, email, created_at FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($users)) {
            foreach ($users as $user) {
                echo "  - {$user['name']} ({$user['email']}) - {$user['created_at']}\n";
            }
        } else {
            echo "  - 사용자 데이터가 없습니다.\n";
        }
        echo "\n";
    }
    
    // 카테고리 데이터 확인
    if (in_array('categories', $tables)) {
        echo "📂 카테고리 데이터 확인:\n";
        $stmt = $pdo->query("SELECT name, description FROM categories LIMIT 10");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                echo "  - {$category['name']}: {$category['description']}\n";
            }
        } else {
            echo "  - 카테고리 데이터가 없습니다.\n";
        }
        echo "\n";
    }
    
    // 부서 데이터 확인
    if (in_array('departments', $tables)) {
        echo "🏢 부서 데이터 확인:\n";
        $stmt = $pdo->query("SELECT name, description FROM departments LIMIT 10");
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($departments)) {
            foreach ($departments as $department) {
                echo "  - {$department['name']}: {$department['description']}\n";
            }
        } else {
            echo "  - 부서 데이터가 없습니다.\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n";
}

echo "========================================\n";
echo "🎉 데이터베이스 구조 확인 완료\n";
echo "========================================\n";
