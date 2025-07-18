import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiDiscovery } from '../utils/apiDiscovery';
import { APP_CONFIG } from '../constants/config';

/**
 * 동적 API 관리자
 * 자동 IP 감지 및 API 연결 관리
 */
class ApiManager {
  constructor() {
    this.api = null;
    this.baseUrl = null;
    this.initialized = false;
    this.initializationPromise = null;
  }

  /**
   * API 매니저 초기화
   */
  async initialize() {
    // 이미 초기화 중이면 기존 Promise 반환
    if (this.initializationPromise) {
      return this.initializationPromise;
    }

    // 이미 초기화됨
    if (this.initialized && this.api) {
      return this.api;
    }

    // 초기화 시작
    this.initializationPromise = this._performInitialization();
    return this.initializationPromise;
  }

  /**
   * 실제 초기화 수행
   */
  async _performInitialization() {
    try {
      console.log('🚀 API 매니저 초기화 시작...');

      // 1. 먼저 캐시된 URL 확인
      let apiUrl = await apiDiscovery.validateCachedUrl();
      
      // 2. 캐시된 URL이 없거나 무효하면 자동 발견
      if (!apiUrl) {
        console.log('🔍 API 서버 자동 발견 시작...');
        apiUrl = await apiDiscovery.quickDiscoverApiEndpoint();
      }

      // 3. API 인스턴스 생성
      this.baseUrl = apiUrl;
      this.api = await this._createApiInstance(apiUrl);
      this.initialized = true;

      console.log(`✅ API 매니저 초기화 완료: ${apiUrl}`);
      return this.api;

    } catch (error) {
      console.error('❌ API 매니저 초기화 실패:', error);
      this.initialized = false;
      this.initializationPromise = null;
      throw new Error(`API 서버에 연결할 수 없습니다: ${error.message}`);
    }
  }

  /**
   * Axios 인스턴스 생성
   */
  async _createApiInstance(baseUrl) {
    const api = axios.create({
      baseURL: baseUrl,
      timeout: 15000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // 요청 인터셉터 - 토큰 자동 추가
    api.interceptors.request.use(
      async (config) => {
        try {
          const token = await AsyncStorage.getItem(APP_CONFIG.STORAGE_KEYS.TOKEN);
          if (token) {
            config.headers.Authorization = `Bearer ${token}`;
            console.log('🔐 토큰 설정:', `Bearer ${token.substring(0, 20)}...`);
          }
        } catch (error) {
          console.error('토큰 가져오기 오류:', error);
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // 응답 인터셉터 - 에러 처리 및 재연결
    api.interceptors.response.use(
      (response) => {
        console.log('✅ API 응답 성공:', response.config.url, response.status);
        return response;
      },
      async (error) => {
        console.error('❌ API 응답 오류:', error.config?.url, error.response?.status);

        // 네트워크 오류 또는 서버 연결 실패
        if (!error.response) {
          console.log('🔄 네트워크 오류 감지 - API 재발견 시도...');
          await this._handleNetworkError();
        }

        // 401 오류 - 토큰 만료
        if (error.response?.status === 401) {
          console.log('🔓 토큰 만료 또는 인증 실패');
          await this._handleAuthError();
        }

        return Promise.reject(error);
      }
    );

    return api;
  }

  /**
   * 네트워크 오류 처리
   */
  async _handleNetworkError() {
    try {
      console.log('🔄 API 서버 재발견 시도...');
      
      // 현재 URL 무효화
      await AsyncStorage.removeItem('LAST_WORKING_API_URL');
      
      // 새로운 API 서버 찾기
      const newApiUrl = await apiDiscovery.quickDiscoverApiEndpoint();
      
      if (newApiUrl !== this.baseUrl) {
        console.log(`🔄 새로운 API 서버로 전환: ${this.baseUrl} → ${newApiUrl}`);
        this.baseUrl = newApiUrl;
        this.api = await this._createApiInstance(newApiUrl);
      }
      
    } catch (error) {
      console.error('API 서버 재발견 실패:', error);
    }
  }

  /**
   * 인증 오류 처리
   */
  async _handleAuthError() {
    try {
      // 토큰 제거
      await AsyncStorage.multiRemove([
        APP_CONFIG.STORAGE_KEYS.TOKEN,
        APP_CONFIG.STORAGE_KEYS.USER,
      ]);
      
      // 로그인 화면으로 리디렉션 (필요시)
      // navigation.navigate('Login');
      
    } catch (error) {
      console.error('인증 오류 처리 실패:', error);
    }
  }

  /**
   * API 인스턴스 가져오기
   */
  async getApi() {
    if (!this.initialized) {
      await this.initialize();
    }
    return this.api;
  }

  /**
   * 현재 API URL 가져오기
   */
  getCurrentUrl() {
    return this.baseUrl;
  }

  /**
   * API 연결 상태 확인
   */
  async checkConnection() {
    try {
      const api = await this.getApi();
      const response = await api.get('/health');
      return response.status === 200;
    } catch (error) {
      console.error('연결 상태 확인 실패:', error);
      return false;
    }
  }

  /**
   * API 매니저 재설정
   */
  async reset() {
    console.log('🔄 API 매니저 재설정...');
    
    this.api = null;
    this.baseUrl = null;
    this.initialized = false;
    this.initializationPromise = null;
    
    // 캐시 제거
    await AsyncStorage.removeItem('LAST_WORKING_API_URL');
    
    // 재초기화
    return this.initialize();
  }

  /**
   * 수동 API URL 설정
   */
  async setManualUrl(url) {
    try {
      console.log(`🔧 수동 API URL 설정: ${url}`);
      
      // URL 유효성 검사
      const isValid = await apiDiscovery.testConnection(url);
      if (!isValid) {
        throw new Error('설정한 URL에 연결할 수 없습니다');
      }
      
      // 사용자 설정 저장
      await AsyncStorage.setItem('USER_API_URL', url);
      await AsyncStorage.setItem('LAST_WORKING_API_URL', url);
      
      // API 인스턴스 재생성
      this.baseUrl = url;
      this.api = await this._createApiInstance(url);
      this.initialized = true;
      
      console.log(`✅ 수동 API URL 설정 완료: ${url}`);
      return this.api;
      
    } catch (error) {
      console.error('수동 API URL 설정 실패:', error);
      throw error;
    }
  }
}

// 싱글톤 인스턴스
export const apiManager = new ApiManager();

// 편의 함수
export const getApi = () => apiManager.getApi();
export const checkApiConnection = () => apiManager.checkConnection();
export const resetApiManager = () => apiManager.reset();
export const setManualApiUrl = (url) => apiManager.setManualUrl(url);