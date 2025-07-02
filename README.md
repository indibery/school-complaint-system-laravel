# 학교 민원 시스템 - Laravel API

> Node.js에서 PHP Laravel로 재작성된 학교 민원 관리 시스템의 백엔드 API 서버입니다.

## 📋 프로젝트 개요

이 프로젝트는 기존 Node.js 기반의 학교 민원 시스템을 Laravel 프레임워크로 재작성하는 프로젝트입니다. 
학생, 교직원, 관리자가 효율적으로 민원을 등록하고 관리할 수 있는 RESTful API를 제공합니다.

### 주요 기능

- 🔐 **사용자 인증 및 권한 관리**
  - JWT 토큰 기반 인증 (Laravel Sanctum)
  - 역할 기반 접근 제어 (학생, 교직원, 관리자)
  
- 📝 **민원 관리**
  - 민원 등록, 조회, 수정, 삭제
  - 민원 상태 관리 (접수, 처리중, 완료 등)
  - 페이지네이션 및 검색 기능
  
- 💬 **댓글 시스템**
  - 민원에 대한 댓글 작성
  - 대댓글 지원 (계층 구조)
  
- 📎 **파일 관리**
  - 민원 첨부파일 업로드/다운로드
  - 안전한 파일 저장 및 접근 제어
  
- 🔔 **알림 시스템**
  - 민원 상태 변경 알림
  - 이메일 및 웹 알림 지원

## 🛠 기술 스택

- **Backend Framework**: Laravel 11.x
- **Language**: PHP 8.3+
- **Database**: MySQL 8.0+ / PostgreSQL 15+
- **Authentication**: Laravel Sanctum
- **Cache**: Redis
- **File Storage**: Laravel Storage (Local/S3)
- **Queue**: Redis/Database
- **Testing**: PHPUnit

## 📁 프로젝트 구조

```
school-complaint-system-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   └── Resources/
│   ├── Models/
│   ├── Policies/
│   └── Services/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
└── storage/
    └── app/
        └── uploads/
```

## 🚀 설치 및 실행

### 1. 저장소 클론

```bash
git clone https://github.com/indibery/school-complaint-system-laravel.git
cd school-complaint-system-laravel
```

### 2. 의존성 설치

```bash
composer install
```

### 3. 환경 설정

```bash
# .env 파일 생성
cp .env.example .env

# 애플리케이션 키 생성
php artisan key:generate
```

### 4. 데이터베이스 설정

`.env` 파일에서 데이터베이스 설정을 구성합니다:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_complaint_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. 데이터베이스 마이그레이션

```bash
# 마이그레이션 실행
php artisan migrate

# 시드 데이터 생성 (선택사항)
php artisan db:seed
```

### 6. 스토리지 링크 생성

```bash
php artisan storage:link
```

### 7. 개발 서버 실행

```bash
php artisan serve
```

서버는 `http://localhost:8000`에서 실행됩니다.

## 📚 API 문서

### 인증 엔드포인트

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | 회원가입 |
| POST | `/api/auth/login` | 로그인 |
| POST | `/api/auth/logout` | 로그아웃 |
| GET | `/api/auth/user` | 현재 사용자 정보 |

### 민원 관리 엔드포인트

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/complaints` | 민원 목록 조회 |
| POST | `/api/complaints` | 민원 등록 |
| GET | `/api/complaints/{id}` | 특정 민원 조회 |
| PUT | `/api/complaints/{id}` | 민원 수정 |
| DELETE | `/api/complaints/{id}` | 민원 삭제 |

### 댓글 관리 엔드포인트

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/complaints/{id}/comments` | 민원 댓글 목록 |
| POST | `/api/complaints/{id}/comments` | 댓글 작성 |
| PUT | `/api/comments/{id}` | 댓글 수정 |
| DELETE | `/api/comments/{id}` | 댓글 삭제 |

## 🧪 테스트

### 단위 테스트 실행

```bash
php artisan test
```

### 특정 테스트 실행

```bash
php artisan test --filter=ComplaintTest
```

### 코드 커버리지

```bash
php artisan test --coverage
```

## 🔧 개발 환경 설정

### 코드 스타일

이 프로젝트는 PSR-12 코딩 표준을 따릅니다.

```bash
# 코드 스타일 검사
./vendor/bin/pint --test

# 코드 스타일 자동 수정
./vendor/bin/pint
```

### 정적 분석

```bash
# PHPStan을 사용한 정적 분석
./vendor/bin/phpstan analyse
```

## 🚀 배포

### 프로덕션 환경 설정

1. 환경 변수 설정
2. 의존성 최적화
3. 설정 캐싱
4. 라우트 캐싱

```bash
# 프로덕션 최적화
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### CI/CD

GitHub Actions를 통한 자동 배포가 설정되어 있습니다.

## 🤝 기여 방법

1. 이 저장소를 포크합니다
2. 새로운 기능 브랜치를 생성합니다 (`git checkout -b feature/amazing-feature`)
3. 변경사항을 커밋합니다 (`git commit -m 'Add some amazing feature'`)
4. 브랜치에 푸시합니다 (`git push origin feature/amazing-feature`)
5. Pull Request를 생성합니다

## 📝 개발 진행 상황

- [x] ~~GitHub 저장소 생성 및 초기 설정~~
- [ ] Laravel 환경 설정 및 기본 구성
- [ ] 데이터베이스 스키마 설계 및 마이그레이션
- [ ] Eloquent 모델 생성 및 관계 설정
- [ ] 인증 시스템 구현
- [ ] 민원 관리 API 개발
- [ ] 댓글 시스템 API 개발
- [ ] 파일 업로드 시스템 구현
- [ ] 권한 관리 시스템 구현
- [ ] 알림 시스템 구현
- [ ] API 문서화 및 테스트 작성
- [ ] 성능 최적화 및 캐싱
- [ ] 배포 설정 및 CI/CD 구축

## 📄 라이선스

이 프로젝트는 [MIT 라이선스](LICENSE) 하에 배포됩니다.

## 📞 문의

프로젝트에 대한 문의사항이 있으시면 이슈를 생성해 주세요.

---

**개발 시작일**: 2025년 7월 2일  
**개발자**: indibery  
**상태**: 개발 진행중 🚧
