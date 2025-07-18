#!/bin/bash

# 학교민원시스템 API 테스트 스크립트
echo "=== 학교민원시스템 API 테스트 ==="
echo ""

BASE_URL="http://172.16.29.142:8000/api"

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 테스트 함수
test_api() {
    local url=$1
    local method=$2
    local description=$3
    local data=$4
    
    echo -e "${YELLOW}테스트: $description${NC}"
    echo "URL: $url"
    echo "Method: $method"
    
    if [ "$method" == "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -X GET "$url" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json")
    elif [ "$method" == "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -X POST "$url" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -eq 200 ] || [ "$http_code" -eq 201 ]; then
        echo -e "${GREEN}✓ 성공 (HTTP $http_code)${NC}"
        echo "$body" | jq . 2>/dev/null || echo "$body"
    else
        echo -e "${RED}✗ 실패 (HTTP $http_code)${NC}"
        echo "$body" | jq . 2>/dev/null || echo "$body"
    fi
    
    echo ""
    echo "----------------------------------------"
    echo ""
}

# 캐시 클리어
echo "=== 캐시 클리어 ==="
cd /Users/kwangsukim/code/school-complaint-system-laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo ""

# 기본 API 테스트
test_api "$BASE_URL/" "GET" "기본 API 상태 확인"

# v1 API 테스트
test_api "$BASE_URL/v1/" "GET" "V1 API 상태 확인"

# 공개 카테고리 테스트
test_api "$BASE_URL/v1/categories/public" "GET" "공개 카테고리 목록"

# 데이터베이스 확인
test_api "$BASE_URL/debug/check-db" "GET" "데이터베이스 연결 테스트"

# 간단한 로그인 테스트
test_api "$BASE_URL/debug/simple-login" "POST" "간단한 로그인 테스트" '{"email":"parent@test.com","password":"password123"}'

echo "=== 테스트 완료 ==="
