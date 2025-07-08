// Tailwind 스타일 import
import 'tailwindcss/tailwind.css';

import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { PaperProvider, MD3LightTheme, MD3DarkTheme } from 'react-native-paper';
import { useColorScheme } from 'react-native';
import { AuthProvider } from './src/context/AuthContext';
import AppNavigator from './src/navigation/AppNavigator';

// Tailwind 기반 커스텀 테마 설정
const customLightTheme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    primary: 'rgb(33, 150, 243)', // text-primary-500
    primaryContainer: 'rgb(227, 242, 253)', // text-primary-50
    secondary: 'rgb(76, 175, 80)', // text-secondary-500
    secondaryContainer: 'rgb(232, 245, 232)', // text-secondary-50
    tertiary: 'rgb(255, 152, 0)', // text-accent-500
    tertiaryContainer: 'rgb(255, 243, 224)', // text-accent-50
    error: 'rgb(244, 67, 54)', // text-danger-500
    errorContainer: 'rgb(255, 235, 238)', // text-danger-50
    background: 'rgb(250, 250, 250)', // bg-gray-50
    surface: 'rgb(255, 255, 255)', // bg-white
    surfaceVariant: 'rgb(245, 245, 245)', // bg-gray-100
    outline: 'rgb(224, 224, 224)', // border-gray-300
    outlineVariant: 'rgb(238, 238, 238)', // border-gray-200
    shadow: 'rgb(0, 0, 0)',
    scrim: 'rgb(0, 0, 0)',
    inverseSurface: 'rgb(48, 48, 48)', // bg-dark-800
    inverseOnSurface: 'rgb(245, 245, 245)', // text-gray-100
    inversePrimary: 'rgb(144, 202, 249)', // text-primary-300
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
    primary: 'rgb(33, 150, 243)', // text-primary-500
    primaryContainer: 'rgb(21, 101, 192)', // text-primary-700
    secondary: 'rgb(76, 175, 80)', // text-secondary-500
    secondaryContainer: 'rgb(46, 125, 50)', // text-secondary-700
    tertiary: 'rgb(255, 152, 0)', // text-accent-500
    tertiaryContainer: 'rgb(230, 108, 0)', // text-accent-700
    error: 'rgb(244, 67, 54)', // text-danger-500
    errorContainer: 'rgb(198, 40, 40)', // text-danger-700
    background: 'rgb(18, 18, 18)', // bg-dark-900
    surface: 'rgb(30, 30, 30)', // bg-dark-800
    surfaceVariant: 'rgb(66, 66, 66)', // bg-dark-700
    outline: 'rgb(117, 117, 117)', // border-dark-500
    outlineVariant: 'rgb(97, 97, 97)', // border-dark-600
    shadow: 'rgb(0, 0, 0)',
    scrim: 'rgb(0, 0, 0)',
    inverseSurface: 'rgb(230, 230, 230)', // bg-gray-200
    inverseOnSurface: 'rgb(48, 48, 48)', // text-dark-800
    inversePrimary: 'rgb(25, 118, 210)', // text-primary-600
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

  return (
    <PaperProvider theme={theme}>
      <AuthProvider>
        <StatusBar style={colorScheme === 'dark' ? 'light' : 'dark'} />
        <AppNavigator />
      </AuthProvider>
    </PaperProvider>
  );
}
