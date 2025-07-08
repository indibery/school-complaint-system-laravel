# 학교 민원 시스템 모바일 앱

라라벨 기반 학교 민원 시스템의 React Native 모바일 앱입니다.

## 🚀 프로젝트 개요

**목표**: 학부모와 학교지킴이가 쉽게 민원을 등록하고 관리할 수 있는 모바일 앱

**사용자 타겟**:
- **학부모**: 자녀 관련 민원 등록/조회 (학사, 생활, 안전)
- **학교지킴이**: 시설/안전 관련 민원 등록 (긴급 신고 포함)

## 🛠️ 기술 스택

- **Framework**: React Native (Expo)
- **Navigation**: React Navigation 6
- **UI Library**: React Native Paper (Material Design 3)
- **State Management**: React Context API
- **Storage**: AsyncStorage
- **HTTP Client**: Axios
- **Backend**: Laravel API

## 📱 주요 기능

### 인증 시스템
- 로그인/회원가입 (학부모/학교지킴이 구분)
- 토큰 기반 인증
- 자동 로그인 기능

### 민원 관리
- 카테고리별 민원 등록
- 민원 목록 조회 및 상태별 필터링
- 민원 상세 정보 및 진행 상황 확인
- 실시간 댓글 시스템

### 특화 기능
- 긴급 신고 (학교지킴이용)
- 사진/파일 첨부
- 위치 정보 자동 입력
- 푸시 알림

### UI/UX 특징
- Material Design 3 적용
- 다크 모드 지원
- 한국어 완전 지원
- 접근성 고려

## 🗂️ 프로젝트 구조

```
src/
├── components/          # 재사용 가능한 컴포넌트
├── constants/          # 상수 및 설정
│   └── config.js       # API 설정, 카테고리, 상태 등
├── context/            # React Context
│   └── AuthContext.js  # 인증 상태 관리
├── navigation/         # 네비게이션 설정
│   ├── AppNavigator.js
│   └── MainTabNavigator.js
├── screens/            # 화면 컴포넌트
│   ├── LoadingScreen.js
│   ├── LoginScreen.js
│   ├── RegisterScreen.js
│   ├── HomeScreen.js
│   ├── ComplaintListScreen.js
│   ├── ComplaintDetailScreen.js
│   ├── CreateComplaintScreen.js
│   └── ProfileScreen.js
├── services/           # API 서비스
│   └── api.js         # Axios 설정 및 API 함수
└── utils/             # 유틸리티 함수
```

## 🔧 설치 및 실행

### 1. 의존성 설치

```bash
cd mobile-app/SchoolComplaintApp
npm install
```

### 2. 개발 서버 실행

```bash
npm start
```

### 3. 플랫폼별 실행

```bash
# Android
npm run android

# iOS
npm run ios

# Web
npm run web
```

## 📋 설치된 주요 라이브러리

```json
{
  "@react-navigation/native": "^7.1.14",
  "@react-navigation/stack": "^7.4.2",
  "@react-navigation/bottom-tabs": "^7.4.2",
  "react-native-paper": "^5.14.5",
  "@react-native-async-storage/async-storage": "^2.2.0",
  "expo-image-picker": "^16.1.4",
  "expo-document-picker": "^13.1.6",
  "expo-location": "^18.1.6",
  "expo-notifications": "^0.31.4",
  "axios": "^1.10.0",
  "@expo/vector-icons": "^14.1.0"
}
```

## 🔗 API 연동

### 기본 설정
- **BASE_URL**: `http://localhost:8000/api`
- **인증 방식**: Bearer Token
- **토큰 저장**: AsyncStorage

### 주요 엔드포인트
- `POST /api/auth/login` - 로그인
- `POST /api/auth/register` - 회원가입
- `GET /api/complaints` - 민원 목록 조회
- `POST /api/complaints` - 민원 등록
- `GET /api/complaints/{id}` - 민원 상세 조회
- `POST /api/complaints/{id}/comments` - 댓글 작성
- `POST /api/attachments` - 파일 업로드

## 🎨 디자인 시스템

### 색상 테마
- **Primary**: #1976D2 (파랑)
- **Secondary**: #4CAF50 (초록)
- **Tertiary**: #FF9800 (주황)
- **Error**: #F44336 (빨강)

### 카테고리 색상
- **학사 관련**: #2196F3
- **생활 관련**: #4CAF50
- **안전 관련**: #FF9800
- **시설 관련**: #9C27B0
- **긴급 신고**: #F44336

## 📱 화면 구성

### 인증 화면
- 로그인 (사용자 구분 선택)
- 회원가입 (상세 정보 입력)

### 메인 화면
- 홈 (빠른 민원 등록, 현황 요약)
- 민원 목록 (필터링, 정렬)
- 프로필 (사용자 정보, 설정)

### 민원 관리
- 민원 등록 (카테고리별 템플릿)
- 민원 상세 (진행 상황, 댓글)

## 🔐 보안 고려사항

- 토큰 기반 인증
- 자동 토큰 갱신
- 401 오류 시 자동 로그아웃
- 민감 정보 암호화 저장

## 🚧 개발 현황

### ✅ 완료된 작업
1. **프로젝트 초기 설정**
   - Expo 프로젝트 생성
   - 필수 라이브러리 설치
   - 기본 디렉토리 구조 설정

2. **기본 시스템 구축**
   - 네비게이션 구조 설정
   - 인증 Context 구현
   - API 서비스 구성
   - 기본 화면 템플릿 생성

3. **UI/UX 기반 작업**
   - Material Design 3 테마 적용
   - 다크 모드 지원
   - 반응형 레이아웃 구현

### 🔄 진행 중인 작업
- 인증 시스템 완성
- 민원 등록/조회 기능 구현
- 파일 업로드 시스템
- 푸시 알림 설정

### 📋 향후 계획
1. 민원 등록 화면 완성
2. 민원 목록 및 상세 화면 구현
3. 파일 업로드 기능 추가
4. 푸시 알림 구현
5. 오프라인 모드 지원
6. 성능 최적화
7. 테스트 및 배포 준비

## 🤝 기여 방법

1. 이 저장소를 Fork 합니다
2. 새로운 기능 브랜치를 생성합니다 (`git checkout -b feature/새기능`)
3. 변경사항을 커밋합니다 (`git commit -am '새 기능 추가'`)
4. 브랜치에 Push 합니다 (`git push origin feature/새기능`)
5. Pull Request를 생성합니다

## 📄 라이센스

이 프로젝트는 MIT 라이센스 하에 있습니다.

---

**개발자**: 학교 민원 시스템 개발팀
**최종 업데이트**: 2025년 7월 8일
