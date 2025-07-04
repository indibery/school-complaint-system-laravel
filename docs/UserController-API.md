# UserController API 문서

## 📝 개요

UserController는 학교 민원 시스템의 사용자 관리 기능을 제공하는 RESTful API입니다.  
권한 기반 접근 제어, 고급 검색, 필터링, 데이터 내보내기 등 다양한 기능을 지원합니다.

## 🔐 인증

모든 API 엔드포인트는 **Bearer 토큰 인증**이 필요합니다.

```bash
Authorization: Bearer {access_token}
```

## 📋 API 엔드포인트

### 1. 기본 CRUD 작업

#### 1.1 사용자 목록 조회
```http
GET /api/v1/users
```

**파라미터:**
- `page` (integer): 페이지 번호 (기본값: 1)
- `per_page` (integer): 페이지당 항목 수 (기본값: 15, 최대: 100)
- `search` (string): 검색어 (이름, 이메일, 직원번호, 학번)
- `role` (string): 역할 필터 (admin, teacher, student, parent, staff)
- `department_id` (integer): 부서 ID
- `grade` (integer): 학년 (1-12)
- `class_number` (integer): 반 (1-20)
- `is_active` (boolean): 활성 상태
- `sort_by` (string): 정렬 기준 (기본값: created_at)
- `sort_order` (string): 정렬 순서 (asc, desc)

**응답 예시:**
```json
{
  "success": true,
  "message": "사용자 목록을 조회했습니다.",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "김교사",
        "email": "teacher@school.com",
        "employee_id": "T001",
        "roles": [{"name": "teacher"}],
        "department": {"name": "수학과"},
        "is_active": true,
        "created_at": "2024-01-01T00:00:00Z"
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

#### 1.2 사용자 생성
```http
POST /api/v1/users
```

**권한:** 관리자만 가능

**요청 데이터:**
```json
{
  "name": "새사용자",
  "email": "newuser@school.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "teacher",
  "employee_id": "T002",
  "department_id": 1,
  "grade": 3,
  "class_number": 2,
  "is_active": true,
  "metadata": {
    "homeroom_teacher": true,
    "subject": "수학",
    "gender": "male"
  }
}
```

#### 1.3 사용자 상세 조회
```http
GET /api/v1/users/{id}
```

**권한:** 관리자 또는 본인만 가능

#### 1.4 사용자 정보 수정
```http
PUT /api/v1/users/{id}
```

**권한:** 관리자 또는 본인만 가능

#### 1.5 사용자 삭제
```http
DELETE /api/v1/users/{id}
```

**권한:** 관리자만 가능 (자기 자신 삭제 불가)

### 2. 상태 관리

#### 2.1 사용자 상태 변경
```http
PUT /api/v1/users/{id}/status
```

**권한:** 관리자만 가능

**요청 데이터:**
```json
{
  "is_active": false,
  "reason": "정책 위반으로 인한 계정 정지"
}
```

### 3. 역할별 사용자 조회

#### 3.1 교사 목록
```http
GET /api/v1/users/teachers
```

#### 3.2 학생 목록
```http
GET /api/v1/users/students
```

#### 3.3 학부모 목록
```http
GET /api/v1/users/parents
```

#### 3.4 직원 목록
```http
GET /api/v1/users/staff
```

#### 3.5 담임교사 목록
```http
GET /api/v1/users/homeroom-teachers
```

**파라미터:**
- `grade` (integer): 학년 필터
- `class_number` (integer): 반 필터
- `department_id` (integer): 부서 필터

#### 3.6 학급별 학생 목록
```http
GET /api/v1/users/students/by-class?grade=3&class_number=2
```

**필수 파라미터:**
- `grade` (integer): 학년 (1-12)
- `class_number` (integer): 반 (1-20)

### 4. 고급 검색 및 필터링

#### 4.1 고급 검색
```http
POST /api/v1/users/search
```

**요청 데이터:**
```json
{
  "query": "김교사",
  "filters": {
    "roles": ["teacher", "admin"],
    "departments": [1, 2],
    "grades": [1, 2, 3],
    "classes": [1, 2],
    "status": "active",
    "date_range": {
      "start": "2024-01-01",
      "end": "2024-12-31"
    },
    "metadata": {
      "homeroom_teacher": true,
      "subject": "수학",
      "gender": "male"
    }
  },
  "sort": {
    "field": "name",
    "direction": "asc"
  },
  "pagination": {
    "page": 1,
    "per_page": 20
  }
}
```

#### 4.2 검색 제안
```http
GET /api/v1/users/suggestions?query=김&type=name&limit=5
```

**파라미터:**
- `query` (string): 검색어 (최소 2자)
- `type` (string): 검색 타입 (name, email, employee_id, student_id)
- `limit` (integer): 제한 개수 (최대 20)

#### 4.3 필터 옵션 조회
```http
GET /api/v1/users/filter-options
```

**권한:** 관리자만 가능

### 5. 통계 및 대량 작업

#### 5.1 사용자 통계
```http
GET /api/v1/users/statistics
```

**권한:** 관리자만 가능

**응답 예시:**
```json
{
  "success": true,
  "data": {
    "total_users": 1250,
    "active_users": 1200,
    "inactive_users": 50,
    "roles": {
      "admin": 5,
      "teacher": 80,
      "student": 1000,
      "parent": 150,
      "staff": 15
    },
    "recent_registrations": 25,
    "homeroom_teachers": 45
  }
}
```

#### 5.2 데이터 내보내기
```http
POST /api/v1/users/export
```

**권한:** 관리자만 가능

**요청 데이터:**
```json
{
  "format": "csv",
  "include_metadata": true,
  "filters": {
    "roles": ["teacher"],
    "status": "active"
  }
}
```

**응답 예시:**
```json
{
  "success": true,
  "data": {
    "filename": "users_2024-01-01_12-00-00.csv",
    "filepath": "/storage/exports/users_2024-01-01_12-00-00.csv",
    "download_url": "http://localhost/api/v1/users/download/users_2024-01-01_12-00-00.csv",
    "total_records": 80,
    "format": "csv",
    "created_at": "2024-01-01T12:00:00Z"
  }
}
```

#### 5.3 대량 작업 옵션
```http
GET /api/v1/users/bulk-options
```

**권한:** 관리자만 가능

## 🔒 권한 시스템

### 역할 기반 접근 제어

- **admin**: 모든 사용자 관리 가능
- **teacher**: 자신의 정보만 조회/수정 가능
- **student**: 자신의 정보만 조회/수정 가능
- **parent**: 자신의 정보만 조회/수정 가능
- **staff**: 자신의 정보만 조회/수정 가능

### 권한별 접근 가능 기능

| 기능 | admin | teacher | student | parent | staff |
|------|-------|---------|---------|---------|-------|
| 사용자 목록 조회 | 전체 | 본인만 | 본인만 | 본인만 | 본인만 |
| 사용자 생성 | ✅ | ❌ | ❌ | ❌ | ❌ |
| 사용자 수정 | 전체 | 본인만 | 본인만 | 본인만 | 본인만 |
| 사용자 삭제 | ✅ | ❌ | ❌ | ❌ | ❌ |
| 상태 변경 | ✅ | ❌ | ❌ | ❌ | ❌ |
| 역할별 조회 | ✅ | 활성만 | 활성만 | 활성만 | 활성만 |
| 고급 검색 | ✅ | 본인만 | 본인만 | 본인만 | 본인만 |
| 통계 조회 | ✅ | ❌ | ❌ | ❌ | ❌ |
| 데이터 내보내기 | ✅ | ❌ | ❌ | ❌ | ❌ |

## 🚦 HTTP 상태 코드

- `200`: 성공
- `201`: 생성 성공
- `400`: 잘못된 요청
- `401`: 인증 실패
- `403`: 권한 없음
- `404`: 리소스 없음
- `422`: 유효성 검사 실패
- `500`: 서버 오류

## 📊 응답 형식

### 성공 응답
```json
{
  "success": true,
  "message": "작업이 성공했습니다.",
  "data": {...}
}
```

### 오류 응답
```json
{
  "success": false,
  "message": "오류가 발생했습니다.",
  "errors": {...}
}
```

### 페이지네이션 응답
```json
{
  "success": true,
  "message": "목록을 조회했습니다.",
  "data": {
    "data": [...],
    "links": {
      "first": "...",
      "last": "...",
      "prev": null,
      "next": "..."
    },
    "meta": {
      "current_page": 1,
      "from": 1,
      "last_page": 10,
      "per_page": 15,
      "to": 15,
      "total": 150
    }
  }
}
```

## 🧪 테스트 실행

### Unit 테스트
```bash
php artisan test --filter=UserControllerTest
```

### API 테스트 스크립트
```bash
./tests/api-tests/test-user-api.sh
```

## 📝 개발 노트

1. **성능 최적화**
   - Eager loading 사용으로 N+1 쿼리 방지
   - 인덱스 활용 최적화
   - 페이지네이션으로 대용량 데이터 처리

2. **보안**
   - 권한 기반 접근 제어
   - 비밀번호 자동 해시화
   - 자기 자신 삭제 방지

3. **사용성**
   - 한국어 에러 메시지
   - 실시간 검색 제안
   - 필터 옵션 제공

4. **확장성**
   - 메타데이터 기반 유연한 필터링
   - 역할별 특화 기능
   - 대량 작업 지원
