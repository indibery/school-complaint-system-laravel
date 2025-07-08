// 민원 카테고리 상수
export const COMPLAINT_CATEGORIES = [
  {
    id: 'academic',
    label: '학사 관련',
    description: '수업, 성적, 교육과정 관련 민원',
    icon: 'book-open-variant',
    color: '#2196F3',
    userTypes: ['parent'],
  },
  {
    id: 'life',
    label: '학교생활',
    description: '학교 내 생활, 인간관계, 학생 활동 관련',
    icon: 'account-group',
    color: '#4CAF50',
    userTypes: ['parent'],
  },
  {
    id: 'safety',
    label: '안전 관련',
    description: '학교 안전, 사고 예방, 위험 요소',
    icon: 'shield-check',
    color: '#FF5722',
    userTypes: ['parent', 'school_guard'],
  },
  {
    id: 'facility',
    label: '시설 관련',
    description: '건물, 시설물, 장비 관련 민원',
    icon: 'home-variant',
    color: '#FF9800',
    userTypes: ['school_guard'],
  },
  {
    id: 'environment',
    label: '환경 관련',
    description: '학교 환경, 청결, 위생 관련',
    icon: 'leaf',
    color: '#8BC34A',
    userTypes: ['school_guard'],
  },
  {
    id: 'other',
    label: '기타',
    description: '기타 모든 민원 사항',
    icon: 'help-circle',
    color: '#9C27B0',
    userTypes: ['parent', 'school_guard'],
  },
];

// 우선순위 상수
export const PRIORITY_LEVELS = [
  {
    id: 'low',
    label: '낮음',
    description: '일반적인 문의나 개선 요청',
    color: '#4CAF50',
    icon: 'arrow-down-circle',
  },
  {
    id: 'medium',
    label: '보통',
    description: '중요한 문제나 요청',
    color: '#FF9800',
    icon: 'minus-circle',
  },
  {
    id: 'high',
    label: '높음',
    description: '긴급하거나 중대한 문제',
    color: '#F44336',
    icon: 'arrow-up-circle',
  },
];

// 민원 상태 상수
export const COMPLAINT_STATUS = {
  PENDING: 'pending',
  REVIEWING: 'reviewing',
  IN_PROGRESS: 'in_progress',
  ON_HOLD: 'on_hold',
  REJECTED: 'rejected',
  COMPLETED: 'completed',
};

// 상태별 라벨 및 색상
export const STATUS_CONFIG = {
  [COMPLAINT_STATUS.PENDING]: {
    label: '접수',
    color: '#607D8B',
    icon: 'clock-outline',
  },
  [COMPLAINT_STATUS.REVIEWING]: {
    label: '검토',
    color: '#2196F3',
    icon: 'eye-outline',
  },
  [COMPLAINT_STATUS.IN_PROGRESS]: {
    label: '처리중',
    color: '#FF9800',
    icon: 'cog-outline',
  },
  [COMPLAINT_STATUS.ON_HOLD]: {
    label: '보류',
    color: '#795548',
    icon: 'pause-circle-outline',
  },
  [COMPLAINT_STATUS.REJECTED]: {
    label: '거절',
    color: '#F44336',
    icon: 'close-circle-outline',
  },
  [COMPLAINT_STATUS.COMPLETED]: {
    label: '완료',
    color: '#4CAF50',
    icon: 'check-circle-outline',
  },
};
