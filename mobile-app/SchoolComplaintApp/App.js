import React, { useEffect } from 'react';
import { StatusBar } from 'expo-status-bar';
import { PaperProvider, MD3LightTheme, MD3DarkTheme } from 'react-native-paper';
import { useColorScheme, Alert } from 'react-native';
import { AuthProvider } from './src/context/AuthContext';
import AppNavigator from './src/navigation/AppNavigator';
import { apiManager } from './src/services/ApiManager';
import SimpleTestApp from './SimpleTestApp';

// 커스텀 테마 설정
const customLightTheme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    primary: 'rgb(33, 150, 243)',
    primaryContainer: 'rgb(227, 242, 253)',
    secondary: 'rgb(76, 175, 80)',
    secondaryContainer: 'rgb(232, 245, 232)',
    tertiary: 'rgb(255, 152, 0)',
    tertiaryContainer: 'rgb(255, 243, 224)',
    error: 'rgb(244, 67, 54)',
    errorContainer: 'rgb(255, 235, 238)',
    background: 'rgb(250, 250, 250)',
    surface: 'rgb(255, 255, 255)',
    surfaceVariant: 'rgb(245, 245, 245)',
    outline: 'rgb(224, 224, 224)',
    outlineVariant: 'rgb(238, 238, 238)',
    shadow: 'rgb(0, 0, 0)',
    scrim: 'rgb(0, 0, 0)',
    inverseSurface: 'rgb(48, 48, 48)',
    inverseOnSurface: 'rgb(245, 245, 245)',
    inversePrimary: 'rgb(144, 202, 249)',
    elevation: {
      level0: 'transparent',
      level1: 'rgb(255, 255, 255)',
      level2: 'rgb(247, 247, 247)',
      level3: 'rgb(238, 238, 238)',
      level4: 'rgb(230, 230, 230)',
      level5: 'rgb(224, 224, 224)',
    },
  },
};

const customDarkTheme = {
  ...MD3DarkTheme,
  colors: {
    ...MD3DarkTheme.colors,
    primary: 'rgb(33, 150, 243)',
    primaryContainer: 'rgb(21, 101, 192)',
    secondary: 'rgb(76, 175, 80)',
    secondaryContainer: 'rgb(46, 125, 50)',
    tertiary: 'rgb(255, 152, 0)',
    tertiaryContainer: 'rgb(230, 108, 0)',
    error: 'rgb(244, 67, 54)',
    errorContainer: 'rgb(198, 40, 40)',
    background: 'rgb(18, 18, 18)',
    surface: 'rgb(30, 30, 30)',
    surfaceVariant: 'rgb(66, 66, 66)',
    outline: 'rgb(117, 117, 117)',
    outlineVariant: 'rgb(97, 97, 97)',
    shadow: 'rgb(0, 0, 0)',
    scrim: 'rgb(0, 0, 0)',
    inverseSurface: 'rgb(230, 230, 230)',
    inverseOnSurface: 'rgb(48, 48, 48)',
    inversePrimary: 'rgb(25, 118, 210)',
    elevation: {
      level0: 'transparent',
      level1: 'rgb(22, 22, 22)',
      level2: 'rgb(28, 28, 28)',
      level3: 'rgb(35, 35, 35)',
      level4: 'rgb(37, 37, 37)',
      level5: 'rgb(41, 41, 41)',
    },
  },
};

export default function App() {
  const colorScheme = useColorScheme();
  const theme = colorScheme === 'dark' ? customDarkTheme : customLightTheme;

  // 앱 시작 시 API 매니저 초기화 (네트워크 변경 대응)
  useEffect(() => {
    const initializeApp = async () => {
      try {
        console.log('🚀 앱 시작 - API 매니저 초기화...');
        await apiManager.initialize();
        console.log('✅ API 매니저 초기화 완료');
      } catch (error) {
        console.error('❌ API 매니저 초기화 실패:', error);
        // 자동 발견 실패 시 사용자가 수동 설정 가능
      }
    };

    initializeApp();
  }, []);

  // SimpleTestApp 테스트용 (보안 설정 완료 후 재활성화)
  return <SimpleTestApp />;
  
  // 메인 앱 (테스트 완료 후 주석 해제)
  // return (
  //   <PaperProvider theme={theme}>
  //     <AuthProvider>
  //       <StatusBar style={colorScheme === 'dark' ? 'light' : 'dark'} />
  //       <AppNavigator />
  //     </AuthProvider>
  //   </PaperProvider>
  // );
}
