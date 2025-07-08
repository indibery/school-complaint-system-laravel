#!/bin/bash

# 포트 충돌 방지 및 안전한 Expo 실행 스크립트

echo "🔍 포트 사용 상태 확인 중..."

# 사용 가능한 포트 찾기
find_available_port() {
    local start_port=$1
    local max_port=$((start_port + 100))
    
    for port in $(seq $start_port $max_port); do
        if ! lsof -i :$port > /dev/null 2>&1; then
            echo $port
            return 0
        fi
    done
    
    echo "사용 가능한 포트를 찾을 수 없습니다." >&2
    return 1
}

# 기존 프로세스 정리
cleanup_ports() {
    echo "🧹 기존 프로세스 정리 중..."
    
    # Expo 관련 프로세스 종료
    pkill -f "expo start" 2>/dev/null || true
    pkill -f "react-native start" 2>/dev/null || true
    
    # 주요 포트들 정리
    for port in 8081 8082 8083 19000 19001 19002; do
        if lsof -i :$port > /dev/null 2>&1; then
            echo "포트 $port 사용 중인 프로세스 종료..."
            lsof -ti :$port | xargs kill -9 2>/dev/null || true
        fi
    done
    
    sleep 2
}

# 메인 실행
main() {
    cleanup_ports
    
    echo "🚀 React Native 앱 시작..."
    
    # 사용 가능한 포트 찾기
    AVAILABLE_PORT=$(find_available_port 8081)
    
    if [ $? -eq 0 ]; then
        echo "✅ 포트 $AVAILABLE_PORT 사용"
        cd mobile-app/SchoolComplaintApp
        npx expo start --port $AVAILABLE_PORT --clear
    else
        echo "❌ 사용 가능한 포트를 찾을 수 없습니다."
        exit 1
    fi
}

main "$@"
