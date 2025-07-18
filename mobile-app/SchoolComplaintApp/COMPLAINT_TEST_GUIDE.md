# 민원 생성 테스트 가이드

## 🎯 테스트 목적
학교 민원 시스템의 민원 생성 기능이 정상적으로 작동하는지 확인

## 📱 테스트 환경 설정

### 1. 서버 실행
```bash
cd /Users/kwangsukim/code/school-complaint-system-laravel
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. 모바일 앱 실행
```bash
cd mobile-app/SchoolComplaintApp
npm start
```

## ✅ 테스트 시나리오

### 🔗 1. API 연결 테스트

**목표**: 앱이 자동으로 API 서버를 찾아 연결하는지 확인

**단계**:
1. 앱 실행
2. 로그 확인:
   ```
   LOG  🚀 앱 시작 - API 매니저 초기화...
   LOG  🔍 API 연결 테스트: http://192.168.219.109:8000/api
   LOG  ✅ API 연결 성공: http://192.168.219.109:8000/api
   LOG  ✅ API 매니저 초기화 완료
   ```

**예상 결과**: ✅ 성공 시 "API 매니저 초기화 완료" 메시지
**실패 시**: 설정 화면에서 수동으로 API URL 설정

### 🔐 2. 로그인 테스트

**목표**: 사용자 인증이 정상적으로 작동하는지 확인

**테스트 계정**:
- **이메일**: `parent@test.com`
- **비밀번호**: `password123`
- **사용자 타입**: 학부모

**단계**:
1. 로그인 화면에서 위 계정 정보 입력
2. "로그인" 버튼 클릭
3. 홈 화면으로 이동 확인

**예상 결과**: ✅ 홈 화면으로 자동 이동
**실패 시**: 에러 메시지 확인 후 서버 로그 점검

### 📝 3. 기본 민원 생성 테스트

**목표**: 필수 정보만으로 민원 생성이 가능한지 확인

**테스트 데이터**:
```
제목: 급식 문의
내용: 급식 메뉴에 대한 문의사항입니다.
카테고리: 생활 관련
우선도: 보통
연락처: 010-1234-5678
```

**단계**:
1. 홈 화면에서 "+" 버튼 또는 "민원 등록" 선택
2. 위 정보 입력
3. "등록" 버튼 클릭
4. 성공 메시지 확인

**예상 결과**: ✅ "민원이 성공적으로 등록되었습니다" 알림
**로그 확인**:
```
LOG  📤 민원 등록 API 호출 시작...
LOG  📥 민원 등록 API 응답: {success: true, ...}
```

### 📎 4. 첨부파일 포함 민원 생성 테스트

**목표**: 이미지 첨부 기능이 정상 작동하는지 확인

**단계**:
1. 민원 등록 화면 진입
2. 기본 정보 입력
3. "첨부파일 추가" 버튼 클릭
4. "갤러리에서 선택" 또는 "사진 촬영" 선택
5. 이미지 선택/촬영
6. 첨부된 이미지 확인
7. "등록" 버튼 클릭

**예상 결과**: ✅ 이미지와 함께 민원 등록 성공

### 🚨 5. 에러 처리 테스트

**목표**: 다양한 에러 상황에서 적절한 처리가 되는지 확인

#### 5-1. 필수 필드 누락 테스트
**단계**:
1. 제목 없이 "등록" 버튼 클릭
2. 에러 메시지 확인

**예상 결과**: ✅ "필수 항목을 모두 입력해주세요" 알림

#### 5-2. 네트워크 오류 테스트
**단계**:
1. 서버 중지: `Ctrl+C`로 Laravel 서버 종료
2. 민원 등록 시도
3. 에러 메시지 확인

**예상 결과**: ✅ "서버 연결에 실패했습니다" 알림

## 🛠️ 문제 해결 가이드

### API 연결 실패 시
```bash
# 1. Laravel 서버 상태 확인
curl http://192.168.219.109:8000/api/health

# 2. IP 주소 확인
ifconfig | grep "inet " | grep -v 127.0.0.1

# 3. 방화벽 확인 (필요 시)
sudo ufw allow 8000
```

### 로그인 실패 시
```bash
# Laravel 로그 확인
tail -f storage/logs/laravel.log

# 테스트 사용자 생성
php artisan tinker
```

```php
// tinker에서 실행
User::create([
    'name' => 'Test Parent',
    'email' => 'parent@test.com',
    'password' => Hash::make('password123'),
    'user_type' => 'parent'
]);
```

### 민원 등록 실패 시
1. **Laravel 로그 확인**: `storage/logs/laravel.log`
2. **API 라우트 확인**: `php artisan route:list | grep complaint`
3. **데이터베이스 확인**: 
   ```bash
   php artisan tinker
   ```
   ```php
   \App\Models\Complaint::count(); // 민원 개수 확인
   ```

## 📊 성공 기준

### ✅ 모든 테스트 통과 시
- API 자동 연결 성공
- 로그인 성공
- 기본 민원 생성 성공
- 첨부파일 포함 민원 생성 성공
- 적절한 에러 처리

### ⚠️ 부분 성공 시
- 수동 API 설정으로 연결 가능
- 기본 민원 생성만 성공 (첨부파일 실패)

### ❌ 실패 시
- API 연결 자체가 불가능
- 로그인 불가
- 민원 생성 완전 실패

## 🔍 디버깅 팁

### 로그 모니터링
```bash
# React Native 로그
npm start
# 콘솔에서 로그 확인

# Laravel 로그
tail -f storage/logs/laravel.log
```

### API 직접 테스트
```bash
# 헬스 체크
curl http://192.168.219.109:8000/api/health

# 로그인 테스트
curl -X POST http://192.168.219.109:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"parent@test.com","password":"password123"}'
```

### 데이터베이스 확인
```bash
php artisan db:seed --class=UserSeeder
php artisan migrate:status
```

---

**참고**: 이 가이드는 개발 환경 기준으로 작성되었습니다. 프로덕션 환경에서는 보안 설정을 추가로 검토해야 합니다.