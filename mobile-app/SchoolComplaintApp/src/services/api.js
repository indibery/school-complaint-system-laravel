import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_CONFIG, APP_CONFIG } from '../constants/config';
import { apiManager } from './ApiManager';

// 레거시 지원을 위한 더미 인스턴스 (더 이상 사용하지 않음)
const api = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: API_CONFIG.HEADERS,
});

// 동적 API 인스턴스 가져오기
const getApiInstance = async () => {
  try {
    return await apiManager.getApi();
  } catch (error) {
    console.error('API 인스턴스 가져오기 실패:', error);
    // 폴백: 기존 정적 인스턴스 사용
    return api;
  }
};

// 요청 인터셉터 - 인증 토큰 자동 추가
api.interceptors.request.use(
  async (config) => {
    try {
      const token = await AsyncStorage.getItem(APP_CONFIG.STORAGE_KEYS.TOKEN);
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
        // 토큰 디버깅 로그
        console.log('🔐 토큰 설정:', `Bearer ${token.substring(0, 20)}...`);
      } else {
        console.log('⚠️ 토큰 없음');
      }
    } catch (error) {
      console.error('토큰 가져오기 오류:', error);
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 응답 인터셉터 - 오류 처리
api.interceptors.response.use(
  (response) => {
    console.log('✅ API 응답 성공:', response.config.url, response.status);
    return response;
  },
  async (error) => {
    console.error('❌ API 응답 오류:', error.config?.url, error.response?.status, error.response?.data);
    
    if (error.response?.status === 401) {
      console.log('🔓 토큰 만료 또는 인증 실패 - 로그아웃 처리');
      // 토큰 만료 시 로그아웃 처리
      await AsyncStorage.multiRemove([
        APP_CONFIG.STORAGE_KEYS.TOKEN,
        APP_CONFIG.STORAGE_KEYS.USER,
      ]);
      // 여기서 로그인 화면으로 이동하는 로직 추가 가능
    }
    return Promise.reject(error);
  }
);

// API 응답 래퍼 함수 (동적 API 인스턴스 사용)
const handleApiResponse = async (apiCall) => {
  try {
    const response = await apiCall();
    return {
      success: true,
      data: response.data.data || response.data,
      message: response.data.message || 'Success',
    };
  } catch (error) {
    console.error('API Error:', error);
    return {
      success: false,
      data: null,
      message: error.response?.data?.message || error.message || 'Unknown error',
      error: error.response?.data || error,
    };
  }
};

// 인증 관련 API
export const authAPI = {
  // 로그인
  login: async (credentials) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      const response = await apiInstance.post('/login', credentials);
      if (response.data.token) {
        await AsyncStorage.setItem(APP_CONFIG.STORAGE_KEYS.TOKEN, response.data.token);
        await AsyncStorage.setItem(APP_CONFIG.STORAGE_KEYS.USER, JSON.stringify(response.data.user));
      }
      return response;
    });
  },

  // 회원가입
  register: async (userData) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.post('/register', userData);
    });
  },

  // 로그아웃
  logout: async () => {
    try {
      const apiInstance = await getApiInstance();
      await apiInstance.post('/logout');
    } catch (error) {
      console.error('로그아웃 API 오류:', error);
    } finally {
      await AsyncStorage.multiRemove([
        APP_CONFIG.STORAGE_KEYS.TOKEN,
        APP_CONFIG.STORAGE_KEYS.USER,
      ]);
    }
    return { success: true };
  },

  // 프로필 조회
  profile: async () => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get('/me');
    });
  },

  // 토큰 검증
  validateToken: async () => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get('/me');
    });
  },
};

// 민원 관련 API
export const complaintAPI = {
  // 민원 목록 조회
  getComplaints: async (filters = {}) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get('/complaints', { params: filters });
    });
  },

  // 내 민원 목록 조회
  getMyComplaints: async (filters = {}) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get('/complaints/my-complaints', { params: filters });
    });
  },

  // 민원 상세 조회
  getComplaint: async (id) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get(`/complaints/${id}`);
    });
  },

  // 민원 등록
  createComplaint: async (complaintData) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.post('/complaints', complaintData);
    });
  },

  // 민원 수정
  updateComplaint: async (id, complaintData) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.put(`/complaints/${id}`, complaintData);
    });
  },

  // 민원 삭제
  deleteComplaint: async (id) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.delete(`/complaints/${id}`);
    });
  },

  // 민원 댓글 조회
  getComments: async (complaintId) => {
    return handleApiResponse(async () => {
      const apiInstance = await getApiInstance();
      return await apiInstance.get(`/complaints/${complaintId}/comments`);
    });
  },

  // 민원 댓글 작성
  createComment: async (complaintId, commentData) => {
    return handleApiResponse(async () => {
      return await api.post(`/complaints/${complaintId}/comments`, commentData);
    });
  },

  // 민원 통계
  getStatistics: async () => {
    return handleApiResponse(async () => {
      return await api.get('/complaints/statistics');
    });
  },
};

// 첨부파일 API
export const attachmentAPI = {
  // 파일 업로드
  uploadFile: async (complaintId, file) => {
    return handleApiResponse(async () => {
      const formData = new FormData();
      formData.append('file', {
        uri: file.uri,
        type: file.type || 'image/jpeg',
        name: file.name || 'file',
      });

      return await api.post(`/complaints/${complaintId}/attachments`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    });
  },

  // 파일 삭제
  deleteFile: async (attachmentId) => {
    return handleApiResponse(async () => {
      return await api.delete(`/attachments/${attachmentId}`);
    });
  },
};

// 카테고리 API
export const categoryAPI = {
  // 공개 카테고리 목록
  getPublicCategories: async () => {
    return handleApiResponse(async () => {
      return await api.get('/categories/public');
    });
  },

  // 전체 카테고리 목록 (인증 필요)
  getCategories: async () => {
    return handleApiResponse(async () => {
      return await api.get('/categories');
    });
  },
};

// 대시보드 API
export const dashboardAPI = {
  // 대시보드 데이터
  getDashboard: async () => {
    return handleApiResponse(async () => {
      return await api.get('/dashboard');
    });
  },

  // 통계 데이터
  getStats: async () => {
    return handleApiResponse(async () => {
      return await api.get('/dashboard/stats');
    });
  },
};

// 편의 서비스 - 민원 관련
export const complaintService = {
  // 민원 생성 (간편 인터페이스)
  createComplaint: async (formData) => {
    return await complaintAPI.createComplaint(formData);
  },

  // 첨부파일 업로드
  uploadAttachment: async (complaintId, attachment) => {
    return await attachmentAPI.uploadFile(complaintId, {
      uri: attachment.uri,
      type: attachment.type === 'image' ? 'image/jpeg' : 'application/pdf',
      name: attachment.name,
    });
  },

  // 민원 목록 조회 (필터링 포함)
  getComplaintList: async (filters = {}) => {
    return await complaintAPI.getMyComplaints(filters);
  },

  // 민원 상세 정보 조회
  getComplaintDetail: async (id) => {
    return await complaintAPI.getComplaint(id);
  },

  // 민원 댓글 조회
  getComplaintComments: async (complaintId) => {
    return await complaintAPI.getComments(complaintId);
  },

  // 민원 댓글 작성
  addComplaintComment: async (complaintId, content) => {
    return await complaintAPI.createComment(complaintId, { content });
  },

  // 대시보드 통계
  getDashboardStats: async () => {
    return await dashboardAPI.getStats();
  },
};

// 기본 API 인스턴스 내보내기
export default api;
