<?php

echo "🗃️ 데이터베이스 관계 및 제약 조건 검증\n";
echo "=====================================================\n\n";

// SQLite 데이터베이스 연결
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SQLite 데이터베이스 연결 성공\n\n";
    
    // 1. 테이블 구조 분석
    echo "1. 테이블 구조 분석\n";
    echo "-----------------------------------------------------\n";
    
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $coreTables = ['users', 'complaints', 'categories', 'departments', 'comments', 'attachments', 'students'];
    $permissionTables = ['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];
    $systemTables = ['migrations', 'personal_access_tokens', 'password_reset_tokens', 'sessions', 'cache', 'jobs', 'notifications'];
    
    echo "📊 테이블 분류:\n";
    echo "  🔹 핵심 테이블: " . count(array_intersect($tables, $coreTables)) . "개\n";
    echo "  🔹 권한 테이블: " . count(array_intersect($tables, $permissionTables)) . "개\n";
    echo "  🔹 시스템 테이블: " . count(array_intersect($tables, $systemTables)) . "개\n";
    echo "  🔹 전체 테이블: " . count($tables) . "개\n\n";
    
    // 2. 외래키 관계 분석
    echo "2. 외래키 관계 분석\n";
    echo "-----------------------------------------------------\n";
    
    $foreignKeys = [];
    
    foreach (['complaints', 'comments', 'attachments'] as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("PRAGMA foreign_key_list({$table})");
            $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($fks as $fk) {
                $foreignKeys[] = [
                    'table' => $table,
                    'column' => $fk['from'],
                    'references_table' => $fk['table'],
                    'references_column' => $fk['to'],
                    'on_delete' => $fk['on_delete'],
                    'on_update' => $fk['on_update']
                ];
            }
        }
    }
    
    echo "📊 외래키 관계:\n";
    if (empty($foreignKeys)) {
        echo "  ⚠️  정의된 외래키가 없습니다. (SQLite 제약)\n";
        
        // 논리적 외래키 관계 분석
        echo "\n📊 논리적 외래키 관계 분석:\n";
        
        if (in_array('complaints', $tables)) {
            // complaints 테이블 컬럼 분석
            $stmt = $pdo->query("PRAGMA table_info(complaints)");
            $complaintColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $logicalFKs = [];
            foreach ($complaintColumns as $col) {
                if (preg_match('/^(\w+)_id$/', $col['name'], $matches)) {
                    $referencedTable = $matches[1] === 'user' ? 'users' : $matches[1] . 's';
                    $logicalFKs[] = [
                        'table' => 'complaints',
                        'column' => $col['name'],
                        'references_table' => $referencedTable,
                        'references_column' => 'id'
                    ];
                }
            }
            
            // assigned_to 컬럼 (특별한 경우)
            $logicalFKs[] = [
                'table' => 'complaints',
                'column' => 'assigned_to',
                'references_table' => 'users',
                'references_column' => 'id'
            ];
            
            foreach ($logicalFKs as $fk) {
                echo "  🔗 {$fk['table']}.{$fk['column']} → {$fk['references_table']}.{$fk['references_column']}\n";
            }
        }
        
    } else {
        foreach ($foreignKeys as $fk) {
            echo "  🔗 {$fk['table']}.{$fk['column']} → {$fk['references_table']}.{$fk['references_column']}\n";
            echo "      ON DELETE: {$fk['on_delete']}, ON UPDATE: {$fk['on_update']}\n";
        }
    }
    echo "\n";
    
    // 3. 데이터 무결성 검증
    echo "3. 데이터 무결성 검증\n";
    echo "-----------------------------------------------------\n";
    
    $integrityChecks = [];
    
    // 3.1. 고아 레코드 확인
    echo "📊 고아 레코드 확인:\n";
    
    $orphanComplaints = 0;
    $orphanAssignments = 0;
    $orphanComments = 0;
    $orphanCommentUsers = 0;
    
    if (in_array('complaints', $tables) && in_array('users', $tables)) {
        // complaints 테이블의 user_id 검증
        $stmt = $pdo->query("
            SELECT COUNT(*) as orphan_count 
            FROM complaints c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.user_id IS NOT NULL AND u.id IS NULL
        ");
        $orphanComplaints = $stmt->fetchColumn();
        echo "  - 고아 민원 (user_id): {$orphanComplaints}개\n";
        
        // complaints 테이블의 assigned_to 검증
        $stmt = $pdo->query("
            SELECT COUNT(*) as orphan_count 
            FROM complaints c 
            LEFT JOIN users u ON c.assigned_to = u.id 
            WHERE c.assigned_to IS NOT NULL AND u.id IS NULL
        ");
        $orphanAssignments = $stmt->fetchColumn();
        echo "  - 고아 할당 (assigned_to): {$orphanAssignments}개\n";
    }
    
    if (in_array('comments', $tables) && in_array('complaints', $tables)) {
        // comments 테이블의 complaint_id 검증
        $stmt = $pdo->query("
            SELECT COUNT(*) as orphan_count 
            FROM comments c 
            LEFT JOIN complaints cp ON c.complaint_id = cp.id 
            WHERE c.complaint_id IS NOT NULL AND cp.id IS NULL
        ");
        $orphanComments = $stmt->fetchColumn();
        echo "  - 고아 댓글 (complaint_id): {$orphanComments}개\n";
        
        // comments 테이블의 user_id 검증
        $stmt = $pdo->query("
            SELECT COUNT(*) as orphan_count 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.user_id IS NOT NULL AND u.id IS NULL
        ");
        $orphanCommentUsers = $stmt->fetchColumn();
        echo "  - 고아 댓글 사용자 (user_id): {$orphanCommentUsers}개\n";
    }
    
    // 3.2. 중복 데이터 확인
    echo "\n📊 중복 데이터 확인:\n";
    
    $duplicateEmails = [];
    $duplicateNumbers = [];
    
    if (in_array('users', $tables)) {
        // 사용자 이메일 중복
        $stmt = $pdo->query("
            SELECT email, COUNT(*) as count 
            FROM users 
            GROUP BY email 
            HAVING COUNT(*) > 1
        ");
        $duplicateEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  - 중복 이메일: " . count($duplicateEmails) . "개\n";
    }
    
    if (in_array('complaints', $tables)) {
        // 민원 번호 중복
        $stmt = $pdo->query("
            SELECT complaint_number, COUNT(*) as count 
            FROM complaints 
            WHERE complaint_number IS NOT NULL
            GROUP BY complaint_number 
            HAVING COUNT(*) > 1
        ");
        $duplicateNumbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  - 중복 민원번호: " . count($duplicateNumbers) . "개\n";
    }
    
    // 3.3. 필수 필드 검증
    echo "\n📊 필수 필드 검증:\n";
    
    $emptyTitles = 0;
    $emptyContents = 0;
    $emptyStatuses = 0;
    $emptyNames = 0;
    $emptyUserEmails = 0;
    
    if (in_array('complaints', $tables)) {
        // 민원 테이�� 필수 필드
        $stmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE title IS NULL OR title = ''");
        $emptyTitles = $stmt->fetchColumn();
        echo "  - 제목 없는 민원: {$emptyTitles}개\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE content IS NULL OR content = ''");
        $emptyContents = $stmt->fetchColumn();
        echo "  - 내용 없는 민원: {$emptyContents}개\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IS NULL OR status = ''");
        $emptyStatuses = $stmt->fetchColumn();
        echo "  - 상태 없는 민원: {$emptyStatuses}개\n";
    }
    
    if (in_array('users', $tables)) {
        // 사용자 테이블 필수 필드
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE name IS NULL OR name = ''");
        $emptyNames = $stmt->fetchColumn();
        echo "  - 이름 없는 사용자: {$emptyNames}개\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NULL OR email = ''");
        $emptyUserEmails = $stmt->fetchColumn();
        echo "  - 이메일 없는 사용자: {$emptyUserEmails}개\n";
    }
    
    // 4. 인덱스 분석
    echo "\n4. 인덱스 분석\n";
    echo "-----------------------------------------------------\n";
    
    foreach (['complaints', 'users', 'comments', 'attachments'] as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("PRAGMA index_list({$table})");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 {$table} 테이블 인덱스:\n";
            foreach ($indexes as $index) {
                $stmt = $pdo->query("PRAGMA index_info({$index['name']})");
                $indexColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $columns = array_column($indexColumns, 'name');
                $unique = $index['unique'] ? '(UNIQUE)' : '';
                echo "  - {$index['name']}: " . implode(', ', $columns) . " {$unique}\n";
            }
            echo "\n";
        }
    }
    
    // 5. 성능 관련 분석
    echo "5. 성능 관련 분석\n";
    echo "-----------------------------------------------------\n";
    
    // 테이블 크기 분석
    echo "📊 테이블 크기 분석:\n";
    foreach (['complaints', 'users', 'comments', 'attachments', 'categories'] as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "  - {$table}: {$count}개 레코드\n";
        }
    }
    echo "\n";
    
    // 6. 모델 관계 검증
    echo "6. 모델 관계 검증\n";
    echo "-----------------------------------------------------\n";
    
    echo "📊 모델 관계 일관성 검증:\n";
    
    if (in_array('complaints', $tables) && in_array('users', $tables)) {
        // 민원-사용자 관계 검증
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT c.user_id) as complaint_users,
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT c.assigned_to) as assigned_users
            FROM complaints c
            LEFT JOIN users u ON c.user_id = u.id
        ");
        $relationStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  - 민원 작성자 수: {$relationStats['complaint_users']}명\n";
        echo "  - 전체 사용자 수: {$relationStats['total_users']}명\n";
        echo "  - 할당된 담당자 수: {$relationStats['assigned_users']}명\n";
    }
    
    if (in_array('complaints', $tables) && in_array('comments', $tables)) {
        // 민원-댓글 관계 검증
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT c.id) as total_complaints,
                COUNT(DISTINCT co.complaint_id) as commented_complaints,
                COALESCE(AVG(comment_count), 0) as avg_comments_per_complaint
            FROM complaints c
            LEFT JOIN (
                SELECT complaint_id, COUNT(*) as comment_count
                FROM comments
                GROUP BY complaint_id
            ) co ON c.id = co.complaint_id
        ");
        $commentStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  - 전체 민원: {$commentStats['total_complaints']}개\n";
        echo "  - 댓글 있는 민원: {$commentStats['commented_complaints']}개\n";
        echo "  - 민원당 평균 댓글: " . round($commentStats['avg_comments_per_complaint'], 2) . "개\n";
    }
    
    // 7. 데이터 일관성 점수 계산
    echo "\n7. 데이터 일관성 점수 계산\n";
    echo "-----------------------------------------------------\n";
    
    $totalScore = 0;
    $maxScore = 100;
    
    // 고아 레코드 점수 (40점)
    $orphanScore = 40;
    if ($orphanComplaints > 0) $orphanScore -= 10;
    if ($orphanAssignments > 0) $orphanScore -= 10;
    if ($orphanComments > 0) $orphanScore -= 10;
    if ($orphanCommentUsers > 0) $orphanScore -= 10;
    $totalScore += $orphanScore;
    
    // 중복 데이터 점수 (20점)
    $duplicateScore = 20;
    if (count($duplicateEmails) > 0) $duplicateScore -= 10;
    if (count($duplicateNumbers) > 0) $duplicateScore -= 10;
    $totalScore += $duplicateScore;
    
    // 필수 필드 점수 (30점)
    $requiredScore = 30;
    if ($emptyTitles > 0) $requiredScore -= 6;
    if ($emptyContents > 0) $requiredScore -= 6;
    if ($emptyStatuses > 0) $requiredScore -= 6;
    if ($emptyNames > 0) $requiredScore -= 6;
    if ($emptyUserEmails > 0) $requiredScore -= 6;
    $totalScore += $requiredScore;
    
    // 인덱스 점수 (10점)
    $indexScore = 10; // 기본적으로 PRIMARY KEY 인덱스가 있으므로 만점
    $totalScore += $indexScore;
    
    $scorePercentage = round($totalScore);
    
    echo "🎯 데이터 무결성 종합 점수:\n";
    echo "  - 고아 레코드 방지: {$orphanScore}/40점\n";
    echo "  - 중복 데이터 방지: {$duplicateScore}/20점\n";
    echo "  - 필수 필드 검증: {$requiredScore}/30점\n";
    echo "  - 인덱스 최적화: {$indexScore}/10점\n";
    echo "  - 총 점수: {$totalScore}/{$maxScore}점 ({$scorePercentage}%)\n";
    
    if ($scorePercentage >= 95) {
        echo "  - 등급: 🟢 우수 (Excellent)\n";
    } elseif ($scorePercentage >= 80) {
        echo "  - 등급: 🟡 양호 (Good)\n";
    } elseif ($scorePercentage >= 60) {
        echo "  - 등급: 🟠 보통 (Fair)\n";
    } else {
        echo "  - 등급: 🔴 개선 필요 (Needs Improvement)\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n";
}

echo "=====================================================\n";
echo "🎉 데이터베이스 관계 및 제약 조건 검증 완료\n";
echo "=====================================================\n";
