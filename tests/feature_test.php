<?php

/**
 * 핵심 기능 테스트 스크립트
 * 이 스크립트는 민원 시스템의 핵심 기능들이 정상 작동하는지 확인합니다.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Complaint;
use App\Models\Category;
use App\Models\Department;
use App\Models\Comment;

// Laravel 애플리케이션 초기화
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 핵심 기능 테스트 시작\n";
echo "==========================================\n\n";

// 1. 데이터베이스 연결 테스트
echo "1. 데이터베이스 연결 테스트\n";
echo "----------------------------------------\n";
try {
    $dbConnection = DB::connection()->getPdo();
    echo "✅ 데이터베이스 연결 성공\n";
    echo "   - 드라이버: " . $dbConnection->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "   - 버전: " . $dbConnection->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
} catch (Exception $e) {
    echo "❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// 2. 모델 및 관계 테스트
echo "2. 모델 및 관계 테스트\n";
echo "----------------------------------------\n";
try {
    // 사용자 모델 테스트
    $userCount = User::count();
    echo "✅ User 모델 접근 성공 (총 {$userCount}명)\n";
    
    // 민원 모델 테스트
    $complaintCount = Complaint::count();
    echo "✅ Complaint 모델 접근 성공 (총 {$complaintCount}건)\n";
    
    // 카테고리 모델 테스트
    $categoryCount = Category::count();
    echo "✅ Category 모델 접근 성공 (총 {$categoryCount}개)\n";
    
    // 부서 모델 테스트
    $departmentCount = Department::count();
    echo "✅ Department 모델 접근 성공 (총 {$departmentCount}개)\n";
    
    // 댓글 모델 테스트
    $commentCount = Comment::count();
    echo "✅ Comment 모델 접근 성공 (총 {$commentCount}개)\n";
    
} catch (Exception $e) {
    echo "❌ 모델 접근 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. 모델 관계 테스트
echo "3. 모델 관계 테스트\n";
echo "----------------------------------------\n";
try {
    // 민원과 사용자 관계 테스트
    $complaint = Complaint::with('user')->first();
    if ($complaint) {
        echo "✅ Complaint->User 관계 테스트 성공\n";
        echo "   - 민원: {$complaint->title}\n";
        echo "   - 작성자: {$complaint->user->name}\n";
    } else {
        echo "⚠️ 테스트할 민원 데이터가 없습니다.\n";
    }
    
    // 민원과 카테고리 관계 테스트
    $complaintWithCategory = Complaint::with('category')->first();
    if ($complaintWithCategory && $complaintWithCategory->category) {
        echo "✅ Complaint->Category 관계 테스트 성공\n";
        echo "   - 민원: {$complaintWithCategory->title}\n";
        echo "   - 카테고리: {$complaintWithCategory->category->name}\n";
    } else {
        echo "⚠️ 카테고리가 연결된 민원이 없습니다.\n";
    }
    
    // 민원과 댓글 관계 테스트
    $complaintWithComments = Complaint::with('comments')->first();
    if ($complaintWithComments) {
        echo "✅ Complaint->Comments 관계 테스트 성공\n";
        echo "   - 민원: {$complaintWithComments->title}\n";
        echo "   - 댓글 수: {$complaintWithComments->comments->count()}\n";
    }
    
} catch (Exception $e) {
    echo "❌ 모델 관계 테스트 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Service Layer 테스트
echo "4. Service Layer 테스트\n";
echo "----------------------------------------\n";
try {
    $services = [
        'App\Services\Complaint\ComplaintService',
        'App\Services\Complaint\ComplaintStatusService',
        'App\Services\Complaint\ComplaintAssignmentService',
        'App\Services\Complaint\ComplaintFileService',
        'App\Services\Complaint\ComplaintStatisticsService',
        'App\Services\Complaint\ComplaintNotificationService',
    ];
    
    foreach ($services as $service) {
        if (class_exists($service)) {
            echo "✅ {$service} 클래스 존재\n";
            
            // 인터페이스 확인
            $interfaceName = $service . 'Interface';
            if (interface_exists($interfaceName)) {
                echo "   - {$interfaceName} 인터페이스 존재\n";
            }
        } else {
            echo "❌ {$service} 클래스 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Service Layer 테스트 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Action Classes 테스트
echo "5. Action Classes 테스트\n";
echo "----------------------------------------\n";
try {
    $actions = [
        'App\Actions\Complaint\CreateComplaintAction',
        'App\Actions\Complaint\UpdateComplaintStatusAction',
        'App\Actions\Complaint\AssignComplaintAction',
    ];
    
    foreach ($actions as $action) {
        if (class_exists($action)) {
            echo "✅ {$action} 클래스 존재\n";
        } else {
            echo "❌ {$action} 클래스 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Action Classes 테스트 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Request Validation 테스트
echo "6. Request Validation 테스트\n";
echo "----------------------------------------\n";
try {
    $requests = [
        'App\Http\Requests\Api\Complaint\ComplaintStoreRequest',
        'App\Http\Requests\Api\Complaint\ComplaintUpdateRequest',
        'App\Http\Requests\Api\Complaint\ComplaintIndexRequest',
        'App\Http\Requests\Api\Complaint\ComplaintStatusRequest',
        'App\Http\Requests\Api\Complaint\ComplaintAssignRequest',
    ];
    
    foreach ($requests as $request) {
        if (class_exists($request)) {
            echo "✅ {$request} 클래스 존재\n";
        } else {
            echo "❌ {$request} 클래스 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Request Validation 테스트 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Resource Classes 테스트
echo "7. Resource Classes 테스트\n";
echo "----------------------------------------\n";
try {
    $resources = [
        'App\Http\Resources\ComplaintResource',
        'App\Http\Resources\UserResource',
        'App\Http\Resources\CategoryResource',
        'App\Http\Resources\CommentResource',
    ];
    
    foreach ($resources as $resource) {
        if (class_exists($resource)) {
            echo "✅ {$resource} 클래스 존재\n";
        } else {
            echo "❌ {$resource} 클래스 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Resource Classes 테스트 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. 상태 및 우선순위 확인
echo "8. 상태 및 우선순위 확인\n";
echo "----------------------------------------\n";
try {
    $statuses = DB::table('complaints')->distinct()->pluck('status');
    echo "✅ 사용 중인 상태: " . implode(', ', $statuses->toArray()) . "\n";
    
    $priorities = DB::table('complaints')->distinct()->pluck('priority');
    echo "✅ 사용 중인 우선순위: " . implode(', ', $priorities->toArray()) . "\n";
    
} catch (Exception $e) {
    echo "❌ 상태/우선순위 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

// 9. 기본 데이터 무결성 확인
echo "9. 기본 데이터 무결성 확인\n";
echo "----------------------------------------\n";
try {
    // 민원 번호 중복 확인
    $duplicateNumbers = DB::table('complaints')
        ->select('complaint_number')
        ->groupBy('complaint_number')
        ->havingRaw('COUNT(*) > 1')
        ->count();
    
    if ($duplicateNumbers == 0) {
        echo "✅ 민원 번호 중복 없음\n";
    } else {
        echo "❌ 민원 번호 중복 발견: {$duplicateNumbers}개\n";
    }
    
    // 사용자 이메일 중복 확인
    $duplicateEmails = DB::table('users')
        ->select('email')
        ->groupBy('email')
        ->havingRaw('COUNT(*) > 1')
        ->count();
    
    if ($duplicateEmails == 0) {
        echo "✅ 사용자 이메일 중복 없음\n";
    } else {
        echo "❌ 사용자 이메일 중복 발견: {$duplicateEmails}개\n";
    }
    
} catch (Exception $e) {
    echo "❌ 데이터 무결성 확인 실패: " . $e->getMessage() . "\n";
}
echo "\n";

echo "==========================================\n";
echo "🎉 핵심 기능 테스트 완료\n";
echo "==========================================\n";
