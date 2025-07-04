#!/bin/bash

# UserController API 테스트 스크립트
# 사용법: ./test-user-api.sh

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 설정
BASE_URL="http://localhost:8000/api/v1"
ADMIN_EMAIL="admin@school.com"
ADMIN_PASSWORD="password"
ACCESS_TOKEN=""

# 함수: 제목 출력
print_title() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

# 함수: 성공 메시지 출력
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# 함수: 에러 메시지 출력
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# 함수: 경고 메시지 출력
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 함수: HTTP 요청 결과 확인
check_response() {
    local response_code=$1
    local test_name=$2
    
    if [ $response_code -eq 200 ] || [ $response_code -eq 201 ]; then
        print_success "$test_name - 성공 (HTTP $response_code)"
        return 0
    else
        print_error "$test_name - 실패 (HTTP $response_code)"
        return 1
    fi
}

# 1. 헬스 체크
print_title "1. API 헬스 체크"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/../health")
check_response $response_code "헬스 체크"

# 2. 관리자 로그인
print_title "2. 관리자 로그인"
login_response=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")

# 토큰 추출
ACCESS_TOKEN=$(echo $login_response | jq -r '.data.access_token // empty')

if [ -n "$ACCESS_TOKEN" ]; then
    print_success "관리자 로그인 성공"
    echo "액세스 토큰: ${ACCESS_TOKEN:0:20}..."
else
    print_error "관리자 로그인 실패"
    echo "응답: $login_response"
    exit 1
fi

# 3. 사용자 목록 조회
print_title "3. 사용자 목록 조회"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "사용자 목록 조회"

# 4. 사용자 생성
print_title "4. 사용자 생성"
new_user_response=$(curl -s -X POST "$BASE_URL/users" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "name": "테스트 사용자",
        "email": "test@school.com",
        "password": "password123",
        "password_confirmation": "password123",
        "role": "teacher",
        "employee_id": "T999",
        "is_active": true
    }')

new_user_id=$(echo $new_user_response | jq -r '.data.id // empty')

if [ -n "$new_user_id" ]; then
    print_success "사용자 생성 성공 (ID: $new_user_id)"
else
    print_error "사용자 생성 실패"
    echo "응답: $new_user_response"
fi

# 5. 사용자 상세 조회
if [ -n "$new_user_id" ]; then
    print_title "5. 사용자 상세 조회"
    response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/$new_user_id" \
        -H "Authorization: Bearer $ACCESS_TOKEN")
    check_response $response_code "사용자 상세 조회"
fi

# 6. 사용자 정보 수정
if [ -n "$new_user_id" ]; then
    print_title "6. 사용자 정보 수정"
    response_code=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$BASE_URL/users/$new_user_id" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "name": "수정된 테스트 사용자",
            "phone": "010-1234-5678"
        }')
    check_response $response_code "사용자 정보 수정"
fi

# 7. 사용자 상태 변경
if [ -n "$new_user_id" ]; then
    print_title "7. 사용자 상태 변경"
    response_code=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$BASE_URL/users/$new_user_id/status" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "is_active": false,
            "reason": "테스트 비활성화"
        }')
    check_response $response_code "사용자 상태 변경"
fi

# 8. 교사 목록 조회
print_title "8. 교사 목록 조회"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/teachers" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "교사 목록 조회"

# 9. 고급 검색
print_title "9. 고급 검색"
response_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/users/search" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "query": "테스트",
        "filters": {
            "roles": ["teacher"],
            "status": "all"
        },
        "sort": {
            "field": "name",
            "direction": "asc"
        },
        "pagination": {
            "page": 1,
            "per_page": 10
        }
    }')
check_response $response_code "고급 검색"

# 10. 검색 제안
print_title "10. 검색 제안"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/suggestions?query=테스트&type=name&limit=5" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "검색 제안"

# 11. 필터 옵션 조회
print_title "11. 필터 옵션 조회"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/filter-options" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "필터 옵션 조회"

# 12. 사용자 통계
print_title "12. 사용자 통계"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/statistics" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "사용자 통계"

# 13. 대량 작업 옵션
print_title "13. 대량 작업 옵션"
response_code=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/users/bulk-options" \
    -H "Authorization: Bearer $ACCESS_TOKEN")
check_response $response_code "대량 작업 옵션"

# 14. 데이터 내보내기
print_title "14. 데이터 내보내기"
response_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE_URL/users/export" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "format": "csv",
        "include_metadata": true,
        "filters": {
            "roles": ["teacher"]
        }
    }')
check_response $response_code "데이터 내보내기"

# 15. 사용자 삭제 (정리)
if [ -n "$new_user_id" ]; then
    print_title "15. 사용자 삭제 (정리)"
    response_code=$(curl -s -o /dev/null -w "%{http_code}" -X DELETE "$BASE_URL/users/$new_user_id" \
        -H "Authorization: Bearer $ACCESS_TOKEN")
    check_response $response_code "사용자 삭제"
fi

print_title "테스트 완료"
print_success "모든 UserController API 테스트가 완료되었습니다!"
echo -e "\n참고: 실제 응답 내용을 확인하려면 -v 옵션을 사용하여 curl 명령을 실행하세요."
