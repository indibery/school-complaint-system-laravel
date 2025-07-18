# 🌐 네트워크 환경별 개발 설정 가이드

## 📍 새로운 장소에서 개발할 때

### 🚀 **자동 설정 (권장)**

```bash
# 모바일 앱 디렉토리에서
./setup-dev.sh
```

### 🔧 **수동 설정**

#### 1단계: 현재 IP 확인
```bash
ifconfig | grep -E "inet " | grep -v 127.0.0.1 | head -1
```

#### 2단계: Laravel 서버 시작
```bash
cd /Users/kwangsukim/code/school-complaint-system-laravel
php artisan serve --host=[현재IP] --port=8081
```

#### 3단계: Expo 앱 시작
```bash
cd mobile-app/SchoolComplaintApp
npm start
```

## 📱 **앱에서 설정**

### SimpleTestApp 사용 시:
- API URL: `http://[현재IP]:8081/api`
- 헬스 체크, 로그인, 민원 생성 순서로 테스트

### 메인 앱 사용 시:
- 자동 API 발견이 실행되어 자동 연결 시도
- 실패 시 "API 설정" 메뉴에서 수동 설정

## 🔍 **네트워크별 주요 IP 패턴**

### 카페/공공 WiFi:
- `172.x.x.x` (예: 172.30.1.2)
- `192.168.x.x` (예: 192.168.1.100)

### 집 WiFi:
- `192.168.0.x` 또는 `192.168.1.x`
- `10.0.0.x`

### 핫스팟:
- iPhone: `172.20.10.x`
- Android: `192.168.43.x`

## ⚠️ **문제 해결**

### 연결 실패 시:
1. **방화벽 확인**: macOS 시스템 환경설정
2. **포트 충돌**: 다른 포트 사용 (8082, 8083 등)
3. **IP 재확인**: 네트워크 변경 후 IP 갱신

### 주요 명령어:
```bash
# IP 확인
ifconfig

# 포트 사용 확인
lsof -i :8081

# Laravel 서버 중지
pkill -f "php artisan serve"

# 캐시 클리어
npm start -- --clear
```

## 🎯 **빠른 체크리스트**

- [ ] 현재 IP 확인
- [ ] Laravel 서버 해당 IP로 시작
- [ ] Expo 앱 시작
- [ ] QR 코드 스캔
- [ ] API URL 확인/수정
- [ ] 헬스 체크 테스트

## 📝 **환경별 설정 저장**

자주 사용하는 장소의 IP를 메모해두세요:

- **카페**: 172.30.1.2
- **집**: [여기에 기록]
- **사무실**: [여기에 기록]
- **기타**: [여기에 기록]