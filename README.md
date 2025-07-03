# 🏫 학교 민원 시스템 - Laravel API

> Laravel 기반 학교 민원 관리 시스템의 백엔드 API 서버입니다.

## 📋 프로젝트 개요

학교 환경에서 학부모, 교직원, 관리자가 효율적으로 민원을 등록하고 관리할 수 있는 Laravel 기반 RESTful API 시스템입니다.

### 🎯 핵심 특징

- **역할 기반 접근 제어**: 5가지 사용자 역할 (관리자, 교사, 학부모, 학교지킴이, 운영팀)
- **계층적 민원 카테고리**: 체계적인 민원 분류 시스템
- **학부모-자녀 관계 관리**: 학부모가 자녀별 민원 제기 가능
- **완전한 민원 처리 흐름**: 접수 → 처리 → 완료까지 전 과정 추적
- **첨부파일 관리**: 안전한 파일 업로드 및 관리 시스템

## 🛠 기술 스택

- **Framework**: Laravel 11.x
- **Language**: PHP 8.3+
- **Database**: SQLite (개발), MySQL (프로덕션)
- **Authentication**: Laravel Sanctum
- **Queue**: Database
- **Testing**: PHPUnit

## 🗄️ 데이터베이스 스키마

### 핵심 테이블 구조

```
📊 Users (사용자)
├── 역할: admin, teacher, parent, security_staff, ops_staff
├── 접근 채널: admin_web, teacher_web, parent_app, security_app, ops_web
└── 식별자: student_id (학부모), employee_id (교직원)

📁 Categories (민원 카테고리)
├── 계층 구조 지원 (parent_id)
└── 기본 카테고리: 학습/교육, 시설/환경, 급식, 교통/안전, 학생지도, 기타

👥 Students (학생)
├── 학부모와 연결 (parent_id)
└── 학년, 반 정보 관리

📋 Complaints (민원)
├── 상태: submitted, in_progress, resolved, closed
├── 우선순위: low, normal, high, urgent
└── 만족도 평가 시스템 포함

💬 Comments (댓글)
├── 공개/내부 댓글 구분
└── 민원 처리 과정 소통

📎 Attachments (첨부파일)
├── 민원/댓글별 파일 관리
└── 안전한 파일 저장

📊 ComplaintStatusLogs (상태 변경 로그)
└── 민원 처리 과정 추적
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

**개발 환경 (SQLite - 권장)**
```env
DB_CONNECTION=sqlite
# SQLite 파일은 database/database.sqlite에 자동 생성됩니다
```

**프로덕션 환경 (MySQL)**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_complaint_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. 데이터베이스 구축

```bash
# 마이그레이션 실행 (테이블 생성)
php artisan migrate

# 기본 데이터 생성 (카테고리, 샘플 사용자)
php artisan db:seed

# 전체 재구축 (필요시)
php artisan migrate:fresh --seed
```

### 6. 개발 서버 실행

```bash
php artisan serve
```

서버는 `http://localhost:8000`에서 실행됩니다.

## 👥 사용자 역할 시스템

### 역할별 특징

| 역할 | 코드 | 설명 | 접근 채널 | 주요 권한 |
|------|------|------|-----------|-----------|
| **관리자** | `admin` | 시스템 전체 관리자 | `admin_web` | 모든 권한 |
| **교사** | `teacher` | 담당 반/과목 교사 | `teacher_web` | 담당 학생 관련 민원 처리 |
| **학부모** | `parent` | 학생의 보호자 | `parent_app` | 자녀 관련 민원 제기 |
| **학교지킴이** | `security_staff` | 시설/보안 담당 | `security_app` | 시설/안전 관련 민원 처리 |
| **운영팀** | `ops_staff` | 학교 운영진 | `ops_web` | 운영 관련 민원 처리 |

### 중요한 설계 원칙

- **민원 제기자**: 학부모만 민원 제기 가능
- **민원 처리자**: 학교 구성원들 (관리자, 교사, 학교지킴이, 운영팀)
- **교사 제외**: 교사는 민원 시스템 대신 내부 소통 채널 사용

## 🧪 테스트 데이터

### 기본 생성 데이터

**카테고리 (6개)**
- 학습/교육, 시설/환경, 급식, 교통/안전, 학생 지도, 기타

**기본 사용자**
- 관리자: `admin@school.com` / `password`
- 학부모: `parent@example.com` / `password`

**팩토리를 통한 더미 데이터 생성**
```bash
# 추가 테스트 데이터 생성
php artisan tinker

# 학부모 10명과 각각의 자녀 생성
User::factory()->parent()->count(10)->create()->each(function($parent) {
    Student::factory()->withParent($parent)->count(rand(1,3))->create();
});

# 민원 50개 생성
Complaint::factory()->count(50)->create();
```

## 📁 프로젝트 구조

```
school-complaint-system-laravel/
├── 📂 app/
│   ├── 📂 Models/                 # Eloquent 모델
│   │   ├── User.php              # 사용자 모델
│   │   ├── Student.php           # 학생 모델
│   │   ├── Category.php          # 카테고리 모델
│   │   ├── Complaint.php         # 민원 모델
│   │   ├── Comment.php           # 댓글 모델
│   │   ├── Attachment.php        # 첨부파일 모델
│   │   └── ComplaintStatusLog.php # 상태 로그 모델
│   └── 📂 Http/
│       └── 📂 Controllers/        # 컨트롤러 (향후 개발)
├── 📂 database/
│   ├── 📂 migrations/            # 데이터베이스 마이그레이션
│   │   ├── 2025_07_03_000010_add_columns_to_users_table.php
│   │   ├── 2025_07_03_000030_create_categories_table.php
│   │   ├── 2025_07_03_000035_create_students_table.php
│   │   ├── 2025_07_03_000040_create_complaints_table.php
│   │   ├── 2025_07_03_000050_create_comments_table.php
│   │   ├── 2025_07_03_000060_create_attachments_table.php
│   │   └── 2025_07_03_000070_create_complaint_status_logs_table.php
│   ├── 📂 seeders/               # 시드 데이터
│   │   ├── DatabaseSeeder.php
│   │   ├── CategorySeeder.php
│   │   ├── UserSeeder.php
│   │   └── StudentSeeder.php
│   ├── 📂 factories/             # 팩토리 (테스트 데이터)
│   │   ├── UserFactory.php
│   │   ├── StudentFactory.php
│   │   ├── CategoryFactory.php
│   │   ├── ComplaintFactory.php
│   │   ├── CommentFactory.php
│   │   ├── AttachmentFactory.php
│   │   └── ComplaintStatusLogFactory.php
│   └── 📄 database.sqlite        # SQLite 데이터베이스 파일
└── 📂 storage/
    └── 📂 app/
        └── 📂 attachments/       # 첨부파일 저장소
```

## ✅ 개발 진행 상황

### 🎉 완료된 작업 (7/7)

- [x] **데이터베이스 스키마 설계** - 완전한 ERD 및 테이블 구조
- [x] **마이그레이션 생성** - 7개 테이블 마이그레이션 파일
- [x] **시더 생성** - 기본 데이터 생성 시더
- [x] **팩토리 생성** - 테스트 데이터 생성 팩토리
- [x] **데이터베이스 구축** - SQLite 기반 완전 구축
- [x] **기본 데이터 삽입** - 카테고리, 사용자, 학생 데이터
- [x] **Git 저장소 관리** - 체계적인 커밋 및 브랜치 관리

### 🚧 향후 개발 계획

- [ ] **Eloquent 모델 관계 설정** - 모델 간 관계 정의
- [ ] **API 컨트롤러 개발** - RESTful API 엔드포인트
- [ ] **인증 시스템 구현** - Laravel Sanctum 기반
- [ ] **권한 관리 시스템** - 역할 기반 접근 제어
- [ ] **파일 업로드 시스템** - 첨부파일 관리
- [ ] **알림 시스템** - 민원 상태 변경 알림
- [ ] **API 문서화** - Swagger/OpenAPI 문서
- [ ] **테스트 코드 작성** - Feature/Unit 테스트
- [ ] **프론트엔드 연동** - Vue.js/React 연동 준비

## 🤝 기여 방법

1. 이 저장소를 포크합니다
2. 새로운 기능 브랜치를 생성합니다 (`git checkout -b feature/amazing-feature`)
3. 변경사항을 커밋합니다 (`git commit -m 'Add some amazing feature'`)
4. 브랜치에 푸시합니다 (`git push origin feature/amazing-feature`)
5. Pull Request를 생성합니다

## 📝 개발 노트

### 데이터베이스 설계 철학

1. **실용성 우선**: 실제 학교 환경에서 사용 가능한 현실적 설계
2. **확장성 고려**: 향후 기능 추가에 유연한 구조
3. **성능 최적화**: 적절한 인덱스 및 관계 설정
4. **데이터 무결성**: 외래키 제약조건 및 검증 규칙

### 기술적 결정사항

- **SQLite 선택 이유**: 개발 환경에서의 편의성, 향후 MySQL 전환 용이
- **역할 기반 설계**: 학교 조직의 실제 역할 구조 반영
- **민원 중심 설계**: 민원 처리 흐름을 중심으로 한 테이블 구조

## 📄 라이선스

이 프로젝트는 [MIT 라이선스](LICENSE) 하에 배포됩니다.

## 📞 문의

프로젝트에 대한 문의사항이 있으시면 이슈를 생성해 주세요.

---

**프로젝트 시작일**: 2025년 7월 3일  
**데이터베이스 구축 완료**: 2025년 7월 4일  
**개발자**: indibery  
**현재 상태**: 데이터베이스 구축 완료 ✅