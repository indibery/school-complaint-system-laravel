import React, { createContext, useContext, useReducer, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { APP_CONFIG } from '../constants/config';
import { authAPI } from '../services/api';

// 초기 상태
const initialState = {
  isAuthenticated: false,
  user: null,
  token: null,
  isLoading: true,
  error: null,
};

// 액션 타입
const AUTH_ACTIONS = {
  LOGIN_START: 'LOGIN_START',
  LOGIN_SUCCESS: 'LOGIN_SUCCESS',
  LOGIN_FAILURE: 'LOGIN_FAILURE',
  LOGOUT: 'LOGOUT',
  RESTORE_TOKEN: 'RESTORE_TOKEN',
  CLEAR_ERROR: 'CLEAR_ERROR',
};

// 리듀서
function authReducer(state, action) {
  switch (action.type) {
    case AUTH_ACTIONS.LOGIN_START:
      return {
        ...state,
        isLoading: true,
        error: null,
      };
    
    case AUTH_ACTIONS.LOGIN_SUCCESS:
      return {
        ...state,
        isAuthenticated: true,
        user: action.payload.user,
        token: action.payload.token,
        isLoading: false,
        error: null,
      };
    
    case AUTH_ACTIONS.LOGIN_FAILURE:
      return {
        ...state,
        isAuthenticated: false,
        user: null,
        token: null,
        isLoading: false,
        error: action.payload,
      };
    
    case AUTH_ACTIONS.LOGOUT:
      return {
        ...state,
        isAuthenticated: false,
        user: null,
        token: null,
        isLoading: false,
        error: null,
      };
    
    case AUTH_ACTIONS.RESTORE_TOKEN:
      return {
        ...state,
        isAuthenticated: !!action.payload.token,
        user: action.payload.user,
        token: action.payload.token,
        isLoading: false,
      };
    
    case AUTH_ACTIONS.CLEAR_ERROR:
      return {
        ...state,
        error: null,
      };
    
    default:
      return state;
  }
}

// Context 생성
const AuthContext = createContext();

// Provider 컴포넌트
export function AuthProvider({ children }) {
  const [state, dispatch] = useReducer(authReducer, initialState);

  // 앱 시작 시 토큰 복원
  useEffect(() => {
    const restoreToken = async () => {
      try {
        const token = await AsyncStorage.getItem(APP_CONFIG.STORAGE_KEYS.TOKEN);
        const userData = await AsyncStorage.getItem(APP_CONFIG.STORAGE_KEYS.USER);
        
        if (token && userData) {
          const user = JSON.parse(userData);
          // 토큰 유효성 검증
          try {
            await authAPI.validateToken();
            dispatch({
              type: AUTH_ACTIONS.RESTORE_TOKEN,
              payload: { token, user },
            });
          } catch (error) {
            // 토큰이 유효하지 않으면 제거
            await AsyncStorage.multiRemove([
              APP_CONFIG.STORAGE_KEYS.TOKEN,
              APP_CONFIG.STORAGE_KEYS.USER,
            ]);
            dispatch({
              type: AUTH_ACTIONS.RESTORE_TOKEN,
              payload: { token: null, user: null },
            });
          }
        } else {
          dispatch({
            type: AUTH_ACTIONS.RESTORE_TOKEN,
            payload: { token: null, user: null },
          });
        }
      } catch (error) {
        console.error('토큰 복원 오류:', error);
        dispatch({
          type: AUTH_ACTIONS.RESTORE_TOKEN,
          payload: { token: null, user: null },
        });
      }
    };

    restoreToken();
  }, []);

  // 로그인 함수
  const login = async (credentials) => {
    dispatch({ type: AUTH_ACTIONS.LOGIN_START });
    
    try {
      const response = await authAPI.login(credentials);
      dispatch({
        type: AUTH_ACTIONS.LOGIN_SUCCESS,
        payload: {
          user: response.user,
          token: response.token,
        },
      });
      return response;
    } catch (error) {
      const errorMessage = error.response?.data?.message || '로그인에 실패했습니다.';
      dispatch({
        type: AUTH_ACTIONS.LOGIN_FAILURE,
        payload: errorMessage,
      });
      throw error;
    }
  };

  // 로그아웃 함수
  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('로그아웃 오류:', error);
    } finally {
      dispatch({ type: AUTH_ACTIONS.LOGOUT });
    }
  };

  // 에러 클리어 함수
  const clearError = () => {
    dispatch({ type: AUTH_ACTIONS.CLEAR_ERROR });
  };

  const value = {
    ...state,
    login,
    logout,
    clearError,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// Hook
export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
