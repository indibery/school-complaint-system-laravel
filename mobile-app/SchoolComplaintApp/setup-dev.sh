#!/bin/bash

# 개발 환경 자동 설정 스크립트
echo "🔧 개발 환경 설정 중..."

# 1. 현재 IP 주소 감지
CURRENT_IP=$(ifconfig | grep -E "inet " | grep -v 127.0.0.1 | head -1 | awk '{print $2}')
echo "📍 현재 IP 주소: $CURRENT_IP"

# 2. Expo 개발 서버 포트 확인
EXPO_PORT=$(lsof -ti:8081 || echo "8081")
echo "📱 Expo 포트: $EXPO_PORT"

# 3. Laravel 서버 시작
echo "🚀 Laravel 서버 시작 중..."
cd ../../../
php artisan serve --host=$CURRENT_IP --port=$EXPO_PORT &
LARAVEL_PID=$!

echo "✅ Laravel 서버가 http://$CURRENT_IP:$EXPO_PORT 에서 실행 중"
echo "📱 모바일 앱에서 API URL: http://$CURRENT_IP:$EXPO_PORT/api"

# 4. 사용 안내
echo ""
echo "📋 다음 단계:"
echo "1. npm start 실행"
echo "2. QR 코드 스캔"
echo "3. SimpleTestApp에서 API URL: http://$CURRENT_IP:$EXPO_PORT/api"
echo ""
echo "🛑 서버 중지: kill $LARAVEL_PID"

# PID 저장
echo $LARAVEL_PID > laravel.pid