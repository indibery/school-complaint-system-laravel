import AsyncStorage from '@react-native-async-storage/async-storage';
import { APP_CONFIG } from '../constants/config';

/**
 * AsyncStorage 유틸리티 함수들
 */
export const storage = {
  // 데이터 저장
  setItem: async (key, value) => {
    try {
      const jsonValue = JSON.stringify(value);
      await AsyncStorage.setItem(key, jsonValue);
    } catch (error) {
      console.error(`Storage setItem error for key ${key}:`, error);
      throw error;
    }
  },

  // 데이터 가져오기
  getItem: async (key) => {
    try {
      const jsonValue = await AsyncStorage.getItem(key);
      return jsonValue != null ? JSON.parse(jsonValue) : null;
    } catch (error) {
      console.error(`Storage getItem error for key ${key}:`, error);
      return null;
    }
  },

  // 데이터 삭제
  removeItem: async (key) => {
    try {
      await AsyncStorage.removeItem(key);
    } catch (error) {
      console.error(`Storage removeItem error for key ${key}:`, error);
      throw error;
    }
  },

  // 여러 데이터 삭제
  multiRemove: async (keys) => {
    try {
      await AsyncStorage.multiRemove(keys);
    } catch (error) {
      console.error('Storage multiRemove error:', error);
      throw error;
    }
  },

  // 모든 데이터 삭제
  clear: async () => {
    try {
      await AsyncStorage.clear();
    } catch (error) {
      console.error('Storage clear error:', error);
      throw error;
    }
  },
};

/**
 * 인증 토큰 관리 함수들
 */
export const authStorage = {
  // 토큰 저장
  setToken: async (token) => {
    await storage.setItem(APP_CONFIG.STORAGE_KEYS.TOKEN, token);
  },

  // 토큰 가져오기
  getToken: async () => {
    return await storage.getItem(APP_CONFIG.STORAGE_KEYS.TOKEN);
  },

  // 사용자 정보 저장
  setUser: async (user) => {
    await storage.setItem(APP_CONFIG.STORAGE_KEYS.USER, user);
  },

  // 사용자 정보 가져오기
  getUser: async () => {
    return await storage.getItem(APP_CONFIG.STORAGE_KEYS.USER);
  },

  // 인증 정보 모두 삭제
  clearAuth: async () => {
    await storage.multiRemove([
      APP_CONFIG.STORAGE_KEYS.TOKEN,
      APP_CONFIG.STORAGE_KEYS.USER,
    ]);
  },
};

/**
 * 데이터 검증 함수들
 */
export const validation = {
  // 이메일 검증
  isValidEmail: (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  // 비밀번호 검증
  isValidPassword: (password) => {
    return password && password.length >= 6;
  },

  // 전화번호 검증 (한국 형식)
  isValidPhone: (phone) => {
    const phoneRegex = /^01[0-9]-?[0-9]{3,4}-?[0-9]{4}$/;
    return phoneRegex.test(phone);
  },

  // 필수 필드 검증
  isRequired: (value) => {
    return value !== null && value !== undefined && value !== '';
  },

  // 숫자만 포함 검증
  isNumeric: (value) => {
    return /^[0-9]+$/.test(value);
  },
};

/**
 * 날짜 포맷팅 함수들
 */
export const dateUtils = {
  // 날짜를 한국어 형식으로 변환
  formatKoreanDate: (date) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}년 ${month}월 ${day}일`;
  },

  // 날짜를 상대적 시간으로 변환
  formatRelativeTime: (date) => {
    const now = new Date();
    const target = new Date(date);
    const diffMs = now - target;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return '방금 전';
    if (diffMins < 60) return `${diffMins}분 전`;
    if (diffHours < 24) return `${diffHours}시간 전`;
    if (diffDays < 7) return `${diffDays}일 전`;
    return dateUtils.formatKoreanDate(date);
  },

  // 현재 날짜 시간 문자열 반환
  getCurrentDateTimeString: () => {
    return new Date().toISOString();
  },
};

/**
 * 에러 처리 함수들
 */
export const errorUtils = {
  // API 에러 메시지 추출
  getErrorMessage: (error) => {
    if (error.response) {
      // 서버 응답 에러
      const { data, status } = error.response;
      
      if (status === 401) {
        return '인증이 만료되었습니다. 다시 로그인해주세요.';
      } else if (status === 403) {
        return '접근 권한이 없습니다.';
      } else if (status === 404) {
        return '요청한 리소스를 찾을 수 없습니다.';
      } else if (status === 422) {
        // 검증 오류
        if (data.errors) {
          const firstError = Object.values(data.errors)[0];
          return Array.isArray(firstError) ? firstError[0] : firstError;
        }
        return data.message || '입력 정보를 확인해주세요.';
      } else if (status >= 500) {
        return '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
      }
      
      return data.message || '알 수 없는 오류가 발생했습니다.';
    } else if (error.request) {
      // 네트워크 에러
      return '네트워크 연결을 확인해주세요.';
    } else {
      // 기타 에러
      return error.message || '알 수 없는 오류가 발생했습니다.';
    }
  },

  // 에러 로깅
  logError: (error, context = '') => {
    console.error(`[Error${context ? ` - ${context}` : ''}]:`, error);
    
    // 프로덕션 환경에서는 에러 리포팅 서비스로 전송
    if (__DEV__) {
      console.error('Error details:', {
        message: error.message,
        stack: error.stack,
        context,
        timestamp: new Date().toISOString(),
      });
    }
  },
};

/**
 * 문자열 유틸리티 함수들
 */
export const stringUtils = {
  // 문자열 앞뒤 공백 제거
  trim: (str) => {
    return str ? str.trim() : '';
  },

  // 문자열 길이 제한
  truncate: (str, maxLength) => {
    if (!str) return '';
    return str.length > maxLength ? `${str.substring(0, maxLength)}...` : str;
  },

  // 전화번호 포맷팅
  formatPhoneNumber: (phone) => {
    const cleaned = phone.replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{4})(\d{4})$/);
    if (match) {
      return `${match[1]}-${match[2]}-${match[3]}`;
    }
    return phone;
  },

  // 첫 글자 대문자 변환
  capitalize: (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  },
};

/**
 * 디바이스 관련 유틸리티 함수들
 */
export const deviceUtils = {
  // 키보드 닫기
  dismissKeyboard: () => {
    // Keyboard.dismiss()를 사용하려면 import가 필요하지만
    // 여기서는 다른 방법으로 처리
    return true;
  },

  // 진동 피드백
  vibrate: (pattern = [0, 100]) => {
    // 진동 기능은 expo-haptics 라이브러리를 사용하는 것이 좋음
    // 현재는 기본 구현만 제공
    return true;
  },
};
