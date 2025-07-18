#!/bin/bash

echo "🔄 Laravel 서버 재시작 스크립트"
echo "================================="

cd /Users/kwangsukim/code/school-complaint-system-laravel

echo "1. 현재 실행 중인 서버 프로세스 확인..."
ps aux | grep "php artisan serve" | grep -v grep

echo "2. 기존 서버 프로세스 종료..."
pkill -f "php artisan serve"

echo "3. 캐시 정리..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "4. 서버 시작..."
echo "   - Host: 172.16.29.142"
echo "   - Port: 8000"
echo "   - CSRF 토큰: API 경로에서 비활성화됨"

nohup php artisan serve --host=172.16.29.142 --port=8000 > server.log 2>&1 &

echo "5. 서버 상태 확인..."
sleep 2
curl -s http://172.16.29.142:8000/api/debug/public-test | jq '.' || echo "서버 응답 확인 실패"

echo "6. 서버 프로세스 확인..."
ps aux | grep "php artisan serve" | grep -v grep

echo ""
echo "✅ 서버 재시작 완료!"
echo "📱 이제 앱을 테스트해보세요:"
echo "   cd /Users/kwangsukim/code/school-complaint-system-laravel/mobile-app/SchoolComplaintApp"
echo "   npm start"
