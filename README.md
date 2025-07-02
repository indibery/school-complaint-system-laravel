# 학교 민원 시스템 (School Complaint System)

Laravel 11 기반의 학교 민원 관리 시스템입니다.

## 📋 프로젝트 개요

이 시스템은 학교 내 다양한 민원을 효율적으로 관리하고 처리하기 위해 개발되었습니다.

### 주요 기능
- 👤 사용자 인증 및 권한 관리
- 📝 민원 접수 및 관리
- 📊 민원 현황 대시보드
- 📧 이메일 알림 시스템
- 📁 첨부파일 관리
- 🔐 역할 기반 접근 제어

## 🛠 기술 스택

- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL 8.4+
- **Frontend**: Blade Templates + Tailwind CSS
- **Authentication**: Laravel Breeze
- **Authorization**: Spatie Laravel Permission
- **UI Components**: Livewire
- **Data Tables**: Yajra DataTables

## 📦 설치된 패키지

### 주요 패키지
- `laravel/breeze`: 인증 시스템
- `spatie/laravel-permission`: 권한 관리
- `yajra/laravel-datatables`: 데이터테이블
- `livewire/livewire`: 실시간 UI 컴포넌트

## 🚀 설치 및 실행

### 요구사항
- PHP 8.2 이상
- MySQL 8.0 이상
- Composer
- Node.js & NPM

### 설치 단계

1. **저장소 클론**
   ```bash
   git clone <repository-url>
   cd school-complaint-system-laravel
   ```

2. **의존성 설치**
   ```bash
   composer install
   npm install
   ```

3. **환경 설정**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **데이터베이스 설정**
   - MySQL에서 `school_complaint_system` 데이터베이스 생성
   - `.env` 파일에서 데이터베이스 연결 정보 설정

5. **마이그레이션 실행**
   ```bash
   php artisan migrate
   ```

6. **자산 빌드**
   ```bash
   npm run build
   ```

7. **개발 서버 실행**
   ```bash
   php artisan serve
   ```

## 📁 프로젝트 구조

```
school-complaint-system-laravel/
├── app/
│   ├── Http/Controllers/
│   │   └── Complaint/          # 민원 관련 컨트롤러
│   ├── Models/
│   │   └── Complaint/          # 민원 관련 모델
│   └── Services/               # 비즈니스 로직
├── resources/
│   └── views/
│       └── complaints/         # 민원 관련 뷰
├── storage/
│   └── app/
│       └── complaints/         # 민원 첨부파일
└── lang/
    └── ko/                     # 한국어 언어 파일
```

## ⚙️ 환경 변수

### 기본 설정
- `APP_NAME`: "학교 민원 시스템"
- `APP_LOCALE`: ko (한국어)
- `APP_URL`: http://localhost:8000

### 민원 시스템 설정
- `COMPLAINT_AUTO_ASSIGN`: 자동 배정 여부
- `COMPLAINT_EMAIL_NOTIFICATION`: 이메일 알림 여부
- `COMPLAINT_FILE_MAX_SIZE`: 파일 최대 크기 (KB)
- `COMPLAINT_ALLOWED_EXTENSIONS`: 허용 파일 확장자
- `COMPLAINT_DEFAULT_PRIORITY`: 기본 우선도
- `COMPLAINT_RESPONSE_DEADLINE_DAYS`: 응답 기한 (일)

## 🔐 권한 시스템

### 기본 역할
- **관리자 (Admin)**: 전체 시스템 관리
- **직원 (Staff)**: 민원 처리 담당
- **사용자 (User)**: 민원 접수

### 주요 권한
- `create_complaint`: 민원 생성
- `view_complaint`: 민원 조회
- `update_complaint`: 민원 수정
- `delete_complaint`: 민원 삭제
- `assign_complaint`: 민원 배정
- `respond_complaint`: 민원 응답

## 📧 이메일 알림

- 민원 접수 시 자동 알림
- 민원 상태 변경 알림
- 응답 등록 알림
- 기한 임박 알림

## 🐛 버그 리포트

이슈나 버그를 발견하시면 GitHub Issues를 통해 신고해 주세요.

## 📄 라이센스

이 프로젝트는 MIT 라이센스 하에 배포됩니다.

## 👥 기여자

- 개발팀

---

**개발 환경**: Laravel 11, PHP 8.4, MySQL 8.4  
**최종 업데이트**: 2025-07-03
