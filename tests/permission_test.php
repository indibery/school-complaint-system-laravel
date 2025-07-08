<?php

echo "🔐 권한 시스템 검증 테스트\n";
echo "===============================================\n\n";

// SQLite 데이터베이스 연결
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SQLite 데이터베이스 연결 성공\n\n";
    
    // 1. 역할(Role) 시스템 확인
    echo "1. 역할(Role) 시스템 확인\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 정의된 역할 목록:\n";
    foreach ($roles as $role) {
        echo "  - ID: {$role['id']}, 이름: {$role['name']}, 표시명: {$role['guard_name']}\n";
    }
    echo "\n";
    
    // 2. 권한(Permission) 시스템 확인
    echo "2. 권한(Permission) 시스템 확인\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->query("SELECT * FROM permissions ORDER BY id");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 정의된 권한 목록:\n";
    foreach ($permissions as $permission) {
        echo "  - ID: {$permission['id']}, 이름: {$permission['name']}, 가드: {$permission['guard_name']}\n";
    }
    echo "\n";
    
    // 3. 역할-권한 매핑 확인
    echo "3. 역할-권한 매핑 확인\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT r.name as role_name, p.name as permission_name 
        FROM role_has_permissions rhp
        JOIN roles r ON rhp.role_id = r.id
        JOIN permissions p ON rhp.permission_id = p.id
        ORDER BY r.name, p.name
    ");
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rolePermissionMap = [];
    foreach ($rolePermissions as $rp) {
        $rolePermissionMap[$rp['role_name']][] = $rp['permission_name'];
    }
    
    echo "📊 역할별 권한 매핑:\n";
    foreach ($rolePermissionMap as $roleName => $permissions) {
        echo "  🎭 {$roleName}:\n";
        foreach ($permissions as $permission) {
            echo "    - {$permission}\n";
        }
        echo "\n";
    }
    
    // 4. 사용자-역할 매핑 확인
    echo "4. 사용자-역할 매핑 확인\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT u.name as user_name, u.email, r.name as role_name
        FROM model_has_roles mhr
        JOIN users u ON mhr.model_id = u.id
        JOIN roles r ON mhr.role_id = r.id
        WHERE mhr.model_type = 'App\\Models\\User'
        ORDER BY u.name
    ");
    $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 사용자별 역할 할당:\n";
    foreach ($userRoles as $ur) {
        echo "  👤 {$ur['user_name']} ({$ur['email']}): {$ur['role_name']}\n";
    }
    echo "\n";
    
    // 5. 권한 시스템 분석
    echo "5. 권한 시스템 분석\n";
    echo "-----------------------------------------------\n";
    
    $totalRoles = count($roles);
    $totalPermissions = count($permissions);
    $totalMappings = count($rolePermissions);
    $totalUsers = count($userRoles);
    
    echo "📈 권한 시스템 통계:\n";
    echo "  - 전체 역할: {$totalRoles}개\n";
    echo "  - 전체 권한: {$totalPermissions}개\n";
    echo "  - 역할-권한 매핑: {$totalMappings}개\n";
    echo "  - 역할 할당된 사용자: {$totalUsers}명\n";
    echo "\n";
    
    // 6. 권한 커버리지 분석
    echo "6. 권한 커버리지 분석\n";
    echo "-----------------------------------------------\n";
    
    $expectedPermissions = [
        'view complaints', 'create complaints', 'update complaints', 'delete complaints',
        'manage users', 'manage categories', 'manage departments', 'export data', 'view reports'
    ];
    
    $actualPermissions = array_column($permissions, 'name');
    
    echo "📊 권한 커버리지 확인:\n";
    foreach ($expectedPermissions as $expectedPerm) {
        $exists = in_array($expectedPerm, $actualPermissions);
        $status = $exists ? "✅" : "❌";
        echo "  {$status} {$expectedPerm}\n";
    }
    echo "\n";
    
    // 7. 역할별 권한 분석
    echo "7. 역할별 권한 분석\n";
    echo "-----------------------------------------------\n";
    
    foreach ($rolePermissionMap as $roleName => $permissions) {
        $permissionCount = count($permissions);
        $coverage = round(($permissionCount / $totalPermissions) * 100, 1);
        
        echo "📊 {$roleName}: {$permissionCount}개 권한 ({$coverage}% 커버리지)\n";
        
        // 권한 분류
        $crudPermissions = array_filter($permissions, function($p) {
            return preg_match('/^(view|create|update|delete)/', $p);
        });
        
        $managePermissions = array_filter($permissions, function($p) {
            return preg_match('/^manage/', $p);
        });
        
        $otherPermissions = array_diff($permissions, $crudPermissions, $managePermissions);
        
        echo "  - CRUD 권한: " . count($crudPermissions) . "개\n";
        echo "  - 관리 권한: " . count($managePermissions) . "개\n";
        echo "  - 기타 권한: " . count($otherPermissions) . "개\n";
        echo "\n";
    }
    
    // 8. 보안 분석
    echo "8. 보안 분석\n";
    echo "-----------------------------------------------\n";
    
    // 관리자 권한 확인
    $adminPermissions = $rolePermissionMap['admin'] ?? [];
    $adminHasAllPermissions = count($adminPermissions) === $totalPermissions;
    
    echo "🛡️  보안 검사:\n";
    echo ($adminHasAllPermissions ? "✅" : "❌") . " 관리자가 모든 권한을 가지고 있는지: " . ($adminHasAllPermissions ? "예" : "아니요") . "\n";
    
    // 일반 사용자 권한 제한 확인
    $userPermissions = $rolePermissionMap['user'] ?? [];
    $userHasManagePermissions = !empty(array_filter($userPermissions, function($p) {
        return preg_match('/^manage/', $p);
    }));
    
    echo ($userHasManagePermissions ? "❌" : "✅") . " 일반 사용자가 관리 권한을 가지지 않는지: " . ($userHasManagePermissions ? "아니요" : "예") . "\n";
    
    // 권한 중복 확인
    $duplicatePermissions = array_diff_key($actualPermissions, array_unique($actualPermissions));
    $hasDuplicates = !empty($duplicatePermissions);
    
    echo ($hasDuplicates ? "❌" : "✅") . " 권한 중복이 없는지: " . ($hasDuplicates ? "아니요" : "예") . "\n";
    echo "\n";
    
    // 9. 권한 시스템 평가
    echo "9. 권한 시스템 평가\n";
    echo "-----------------------------------------------\n";
    
    $score = 0;
    $maxScore = 10;
    
    // 평가 기준들
    if ($totalRoles >= 3) $score += 2; // 적절한 역할 수
    if ($totalPermissions >= 8) $score += 2; // 충분한 권한 수
    if ($totalMappings >= 10) $score += 2; // 적절한 매핑 수
    if ($adminHasAllPermissions) $score += 2; // 관리자 완전 권한
    if (!$userHasManagePermissions) $score += 2; // 사용자 권한 제한
    
    $scorePercentage = round(($score / $maxScore) * 100);
    
    echo "🎯 권한 시스템 종합 평가:\n";
    echo "  - 점수: {$score}/{$maxScore} ({$scorePercentage}%)\n";
    
    if ($scorePercentage >= 90) {
        echo "  - 등급: 🟢 우수 (Excellent)\n";
    } elseif ($scorePercentage >= 70) {
        echo "  - 등급: 🟡 양호 (Good)\n";
    } else {
        echo "  - 등급: 🔴 개선 필요 (Needs Improvement)\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n";
}

echo "===============================================\n";
echo "🎉 권한 시스템 검증 테스트 완료\n";
echo "===============================================\n";
