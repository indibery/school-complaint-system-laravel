// 테스트용 고정 API 설정
export const API_CONFIG = {
  BASE_URL: 'http://127.0.0.1:8000/api', // iOS 시뮬레이터용 127.0.0.1
  TIMEOUT: 15000,
  HEADERS: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
};

// 앱 설정
export const APP_CONFIG = {
  NAME: '학교 민원 시스템',
  VERSION: '1.0.0',
  STORAGE_KEYS: {
    TOKEN: 'auth_token',
    USER: 'user_data',
    SETTINGS: 'app_settings',
    DRAFT_COMPLAINTS: 'draft_complaints',
  },
};

export const COMPLAINT_CATEGORIES = {
  ACADEMIC: {
    id: 'academic',
    name: '학사 관련',
    description: '수업, 시험, 성적 등 학사 관련 민원',
    icon: 'school',
    color: '#2196F3',
  },
  LIFE: {
    id: 'life',
    name: '생활 관련',
    description: '급식, 생활지도, 교우관계 등 생활 관련 민원',
    icon: 'people',
    color: '#4CAF50',
  },
  SAFETY: {
    id: 'safety',
    name: '안전 관련',
    description: '시설 안전, 교통 안전 등 안전 관련 민원',
    icon: 'security',
    color: '#FF9800',
  },
  FACILITY: {
    id: 'facility',
    name: '시설 관련',
    description: '교실, 화장실, 운동장 등 시설 관련 민원',
    icon: 'domain',
    color: '#9C27B0',
  },
  EMERGENCY: {
    id: 'emergency',
    name: '긴급 신고',
    description: '즉시 대응이 필요한 긴급 상황',
    icon: 'warning',
    color: '#F44336',
  },
};