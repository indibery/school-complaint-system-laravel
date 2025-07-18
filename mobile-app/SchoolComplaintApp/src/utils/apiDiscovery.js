import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

/**
 * API 서버 자동 발견 유틸리티
 * 다양한 네트워크 환경에서 자동으로 API 서버를 찾아 연결
 */
export class ApiDiscovery {
  constructor() {
    this.timeout = 10000; // 10초 타임아웃 (React Native용)
    this.healthCheckPath = '/health';
    this.storageKey = 'LAST_WORKING_API_URL';
  }

  /**
   * 사용 가능한 API 후보 URL들을 생성
   */
  async getCandidateUrls() {
    const urls = [];
    
    try {
      // 1. 사용자가 직접 설정한 URL (최우선)
      const userUrl = await AsyncStorage.getItem('USER_API_URL');
      if (userUrl) urls.push(userUrl);
      
      // 2. 마지막으로 작동한 URL
      const lastWorkingUrl = await AsyncStorage.getItem(this.storageKey);
      if (lastWorkingUrl) urls.push(lastWorkingUrl);
    } catch (error) {
      console.log('캐시된 URL 로드 실패:', error);
    }
    
    // 3. 자동 감지된 URL들
    urls.push(...this.getAutoDetectedUrls());
    
    // 4. 일반적인 fallback URL들 (실제 IP 우선)
    urls.push(
      'http://172.30.1.2:8000/api',     // 현재 실제 IP 최우선
      'http://localhost:8000/api',      // iOS 시뮬레이터
      'http://127.0.0.1:8000/api',      // 로컬호스트 별칭
      'http://10.0.2.2:8000/api',       // Android 에뮬레이터용
      'http://192.0.0.2:8000/api',      // 핫스팟 IP
      'http://192.168.219.109:8000/api', // 이전 네트워크 IP
      'http://192.168.219.1:8000/api',   // 이전 네트워크 게이트웨이
      'http://172.20.10.1:8000/api',    // iPhone 핫스팟 일반 IP
      'http://172.20.10.2:8000/api',    // iPhone 핫스팟 클라이언트 IP
      'http://192.168.43.1:8000/api',   // Android 핫스팟 일반 IP
      'http://192.168.1.1:8000/api',
      'http://192.168.1.100:8000/api',
      'http://192.168.0.1:8000/api',
      'http://192.168.0.100:8000/api',
      'http://10.0.0.1:8000/api',
      'http://10.0.0.100:8000/api'
    );
    
    // 중복 제거
    return [...new Set(urls.filter(Boolean))];
  }

  /**
   * Expo 개발 환경에서 자동으로 IP 감지 (개선된 버전)
   */
  getAutoDetectedUrls() {
    const urls = [];
    
    try {
      if (__DEV__ && Constants.manifest?.debuggerHost) {
        const host = Constants.manifest.debuggerHost.split(':')[0];
        
        if (host && host !== 'localhost' && host !== '127.0.0.1') {
          // 메인 호스트에 다양한 포트 시도 (Expo 포트 우선)
          urls.push(`http://${host}:8081/api`);  // Expo 기본 포트
          urls.push(`http://${host}:8080/api`);  // Laravel 기본 포트
          urls.push(`http://${host}:8000/api`);  // 대체 포트
          
          // 같은 네트워크 대역의 다른 IP들 시도
          const networkVariations = this.generateNetworkVariations(host);
          urls.push(...networkVariations);
        }
      }
      
      // Expo Go 앱에서 현재 연결된 네트워크 정보도 활용
      if (__DEV__ && Constants.manifest?.hostUri) {
        const hostPart = Constants.manifest.hostUri.split(':')[0];
        if (hostPart && hostPart !== 'localhost' && hostPart !== '127.0.0.1') {
          urls.push(`http://${hostPart}:8081/api`);
          urls.push(`http://${hostPart}:8080/api`);
          urls.push(`http://${hostPart}:8000/api`);
        }
      }
    } catch (error) {
      console.log('자동 IP 감지 실패:', error);
    }
    
    return urls;
  }

  /**
   * 네트워크 대역의 다양한 IP 변형 생성
   */
  generateNetworkVariations(baseIp) {
    const variations = [];
    
    try {
      const parts = baseIp.split('.');
      if (parts.length === 4) {
        const network = parts.slice(0, 3).join('.');
        
        // 일반적으로 사용되는 IP들
        const commonLastOctets = [1, 100, 101, 102, 103, 104, 105, 200, 254];
        
        commonLastOctets.forEach(lastOctet => {
          const ip = `${network}.${lastOctet}`;
          if (ip !== baseIp) {
            variations.push(`http://${ip}:8000/api`);
          }
        });
      }
    } catch (error) {
      console.log('네트워크 변형 생성 실패:', error);
    }
    
    return variations;
  }

  /**
   * 특정 URL에서 API 서버 연결 테스트
   */
  async testConnection(url) {
    try {
      console.log(`🔍 API 연결 테스트: ${url}`);
      
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), this.timeout);
      
      const response = await fetch(`${url}${this.healthCheckPath}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        signal: controller.signal,
      });
      
      clearTimeout(timeoutId);
      
      if (response.ok) {
        const data = await response.json();
        console.log(`✅ API 연결 성공: ${url}`, data);
        return true;
      }
      
      console.log(`❌ API 응답 실패: ${url} (${response.status})`);
      return false;
      
    } catch (error) {
      if (error.name === 'AbortError') {
        console.log(`⏰ API 연결 타임아웃: ${url}`);
      } else {
        console.log(`❌ API 연결 오류: ${url}`, error.message);
      }
      return false;
    }
  }

  /**
   * 사용 가능한 API 서버 자동 발견
   */
  async discoverApiEndpoint() {
    console.log('🔍 API 서버 자동 발견 시작...');
    
    const candidateUrls = await this.getCandidateUrls();
    console.log('후보 URL들:', candidateUrls);
    
    // 병렬로 여러 URL 동시 테스트 (더 빠른 발견)
    const testPromises = candidateUrls.map(async (url) => {
      const isWorking = await this.testConnection(url);
      return { url, isWorking };
    });
    
    try {
      const results = await Promise.all(testPromises);
      const workingUrl = results.find(result => result.isWorking);
      
      if (workingUrl) {
        console.log(`🎉 API 서버 발견: ${workingUrl.url}`);
        await AsyncStorage.setItem(this.storageKey, workingUrl.url);
        return workingUrl.url;
      }
      
      throw new Error('사용 가능한 API 서버를 찾을 수 없습니다');
      
    } catch (error) {
      console.error('API 서버 발견 실패:', error);
      throw error;
    }
  }

  /**
   * 빠른 발견 (첫 번째 성공한 URL 즉시 반환)
   */
  async quickDiscoverApiEndpoint() {
    console.log('⚡ 빠른 API 서버 발견 시작...');
    
    const candidateUrls = await this.getCandidateUrls();
    
    // 순차적으로 테스트하여 첫 번째 성공한 URL 즉시 반환
    for (const url of candidateUrls) {
      const isWorking = await this.testConnection(url);
      if (isWorking) {
        console.log(`⚡ 빠른 발견 성공: ${url}`);
        await AsyncStorage.setItem(this.storageKey, url);
        return url;
      }
    }
    
    throw new Error('사용 가능한 API 서버를 찾을 수 없습니다');
  }

  /**
   * 캐시된 URL 유효성 검사
   */
  async validateCachedUrl() {
    try {
      const cachedUrl = await AsyncStorage.getItem(this.storageKey);
      if (cachedUrl) {
        const isValid = await this.testConnection(cachedUrl);
        if (isValid) {
          console.log(`✅ 캐시된 URL 유효: ${cachedUrl}`);
          return cachedUrl;
        } else {
          console.log(`❌ 캐시된 URL 무효: ${cachedUrl}`);
          await AsyncStorage.removeItem(this.storageKey);
        }
      }
    } catch (error) {
      console.error('캐시된 URL 검증 실패:', error);
    }
    return null;
  }
}

// 싱글톤 인스턴스
export const apiDiscovery = new ApiDiscovery();

// 편의 함수들
export const discoverApiEndpoint = () => apiDiscovery.discoverApiEndpoint();
export const quickDiscoverApiEndpoint = () => apiDiscovery.quickDiscoverApiEndpoint();
export const validateCachedUrl = () => apiDiscovery.validateCachedUrl();