/**
 * Tailwind 스타일 헬퍼 유틸리티
 * React Native에서 Tailwind 스타일을 쉽게 사용할 수 있도록 돕는 함수들
 */

// 색상 팔레트 (Tailwind 기반)
export const colors = {
  // Primary Colors
  primary: {
    50: '#e3f2fd',
    100: '#bbdefb',
    200: '#90caf9',
    300: '#64b5f6',
    400: '#42a5f5',
    500: '#2196f3',
    600: '#1976d2',
    700: '#1565c0',
    800: '#0d47a1',
    900: '#0d47a1',
  },
  
  // Secondary Colors
  secondary: {
    50: '#e8f5e8',
    100: '#c8e6c9',
    200: '#a5d6a7',
    300: '#81c784',
    400: '#66bb6a',
    500: '#4caf50',
    600: '#43a047',
    700: '#388e3c',
    800: '#2e7d32',
    900: '#1b5e20',
  },
  
  // Accent Colors
  accent: {
    50: '#fff3e0',
    100: '#ffe0b2',
    200: '#ffcc80',
    300: '#ffb74d',
    400: '#ffa726',
    500: '#ff9800',
    600: '#f57c00',
    700: '#ef6c00',
    800: '#e65100',
    900: '#bf360c',
  },
  
  // Danger Colors
  danger: {
    50: '#ffebee',
    100: '#ffcdd2',
    200: '#ef9a9a',
    300: '#e57373',
    400: '#ef5350',
    500: '#f44336',
    600: '#e53935',
    700: '#d32f2f',
    800: '#c62828',
    900: '#b71c1c',
  },
  
  // Success Colors
  success: {
    50: '#e8f5e8',
    100: '#c8e6c9',
    200: '#a5d6a7',
    300: '#81c784',
    400: '#66bb6a',
    500: '#4caf50',
    600: '#43a047',
    700: '#388e3c',
    800: '#2e7d32',
    900: '#1b5e20',
  },
  
  // Warning Colors
  warning: {
    50: '#fff8e1',
    100: '#ffecb3',
    200: '#ffe082',
    300: '#ffd54f',
    400: '#ffca28',
    500: '#ffc107',
    600: '#ffb300',
    700: '#ffa000',
    800: '#ff8f00',
    900: '#ff6f00',
  },
  
  // Info Colors
  info: {
    50: '#e1f5fe',
    100: '#b3e5fc',
    200: '#81d4fa',
    300: '#4fc3f7',
    400: '#29b6f6',
    500: '#03a9f4',
    600: '#039be5',
    700: '#0288d1',
    800: '#0277bd',
    900: '#01579b',
  },
  
  // Gray Colors
  gray: {
    50: '#fafafa',
    100: '#f5f5f5',
    200: '#eeeeee',
    300: '#e0e0e0',
    400: '#bdbdbd',
    500: '#9e9e9e',
    600: '#757575',
    700: '#616161',
    800: '#424242',
    900: '#212121',
  },
  
  // Dark Colors
  dark: {
    50: '#f5f5f5',
    100: '#eeeeee',
    200: '#e0e0e0',
    300: '#bdbdbd',
    400: '#9e9e9e',
    500: '#757575',
    600: '#616161',
    700: '#424242',
    800: '#303030',
    900: '#212121',
  },
  
  // Basic Colors
  white: '#ffffff',
  black: '#000000',
  transparent: 'transparent',
};

// 스페이싱 (Tailwind 기반)
export const spacing = {
  0: 0,
  1: 4,
  2: 8,
  3: 12,
  4: 16,
  5: 20,
  6: 24,
  7: 28,
  8: 32,
  9: 36,
  10: 40,
  11: 44,
  12: 48,
  14: 56,
  16: 64,
  18: 72,
  20: 80,
  24: 96,
  28: 112,
  32: 128,
  36: 144,
  40: 160,
  44: 176,
  48: 192,
  52: 208,
  56: 224,
  60: 240,
  64: 256,
  72: 288,
  80: 320,
  88: 352,
  96: 384,
};

// 폰트 크기 (Tailwind 기반)
export const fontSize = {
  xs: 12,
  sm: 14,
  base: 16,
  lg: 18,
  xl: 20,
  '2xl': 24,
  '3xl': 30,
  '4xl': 36,
  '5xl': 48,
  '6xl': 60,
  '7xl': 72,
  '8xl': 96,
  '9xl': 128,
};

// 라인 높이 (Tailwind 기반)
export const lineHeight = {
  xs: 16,
  sm: 20,
  base: 24,
  lg: 28,
  xl: 28,
  '2xl': 32,
  '3xl': 36,
  '4xl': 40,
  '5xl': 48,
  '6xl': 60,
  '7xl': 72,
  '8xl': 96,
  '9xl': 128,
};

// 테두리 반지름 (Tailwind 기반)
export const borderRadius = {
  none: 0,
  sm: 2,
  DEFAULT: 4,
  md: 6,
  lg: 8,
  xl: 12,
  '2xl': 16,
  '3xl': 24,
  full: 9999,
};

// 그림자 (Tailwind 기반)
export const shadows = {
  sm: {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 1,
    },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  DEFAULT: {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  md: {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.12,
    shadowRadius: 8,
    elevation: 4,
  },
  lg: {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 8,
    },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 8,
  },
  xl: {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 12,
    },
    shadowOpacity: 0.18,
    shadowRadius: 24,
    elevation: 12,
  },
  '2xl': {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 16,
    },
    shadowOpacity: 0.2,
    shadowRadius: 32,
    elevation: 16,
  },
};

// 유틸리티 함수들
export const tw = {
  // 색상 헬퍼
  bg: (color) => ({ backgroundColor: color }),
  text: (color) => ({ color }),
  border: (color) => ({ borderColor: color }),
  
  // 스페이싱 헬퍼
  p: (size) => ({ padding: spacing[size] }),
  px: (size) => ({ paddingHorizontal: spacing[size] }),
  py: (size) => ({ paddingVertical: spacing[size] }),
  pt: (size) => ({ paddingTop: spacing[size] }),
  pb: (size) => ({ paddingBottom: spacing[size] }),
  pl: (size) => ({ paddingLeft: spacing[size] }),
  pr: (size) => ({ paddingRight: spacing[size] }),
  
  m: (size) => ({ margin: spacing[size] }),
  mx: (size) => ({ marginHorizontal: spacing[size] }),
  my: (size) => ({ marginVertical: spacing[size] }),
  mt: (size) => ({ marginTop: spacing[size] }),
  mb: (size) => ({ marginBottom: spacing[size] }),
  ml: (size) => ({ marginLeft: spacing[size] }),
  mr: (size) => ({ marginRight: spacing[size] }),
  
  // 폰트 헬퍼
  textSize: (size) => ({ fontSize: fontSize[size] }),
  leading: (height) => ({ lineHeight: lineHeight[height] }),
  
  // 테두리 헬퍼
  rounded: (radius) => ({ borderRadius: borderRadius[radius] }),
  borderW: (width) => ({ borderWidth: width }),
  
  // 그림자 헬퍼
  shadow: (level) => shadows[level],
  
  // 플렉스 헬퍼
  flex: (value) => ({ flex: value }),
  flexRow: { flexDirection: 'row' },
  flexCol: { flexDirection: 'column' },
  itemsCenter: { alignItems: 'center' },
  itemsStart: { alignItems: 'flex-start' },
  itemsEnd: { alignItems: 'flex-end' },
  justifyCenter: { justifyContent: 'center' },
  justifyStart: { justifyContent: 'flex-start' },
  justifyEnd: { justifyContent: 'flex-end' },
  justifyBetween: { justifyContent: 'space-between' },
  justifyAround: { justifyContent: 'space-around' },
  justifyEvenly: { justifyContent: 'space-evenly' },
  
  // 포지션 헬퍼
  absolute: { position: 'absolute' },
  relative: { position: 'relative' },
  
  // 크기 헬퍼
  w: (size) => ({ width: spacing[size] }),
  h: (size) => ({ height: spacing[size] }),
  wFull: { width: '100%' },
  hFull: { height: '100%' },
  
  // 텍스트 헬퍼
  textCenter: { textAlign: 'center' },
  textLeft: { textAlign: 'left' },
  textRight: { textAlign: 'right' },
  fontBold: { fontWeight: 'bold' },
  fontSemiBold: { fontWeight: '600' },
  fontMedium: { fontWeight: '500' },
  fontNormal: { fontWeight: 'normal' },
  
  // 투명도 헬퍼
  opacity: (value) => ({ opacity: value }),
};

// 반응형 헬퍼
export const responsive = {
  // 화면 크기에 따른 조건부 스타일
  screen: {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
  },
};

// 컴포넌트별 기본 스타일
export const componentStyles = {
  // 카드 스타일
  card: {
    ...tw.bg(colors.white),
    ...tw.rounded('lg'),
    ...tw.shadow('md'),
    ...tw.p(4),
  },
  
  // 버튼 스타일
  button: {
    primary: {
      ...tw.bg(colors.primary[500]),
      ...tw.rounded('lg'),
      ...tw.py(3),
      ...tw.px(6),
    },
    secondary: {
      ...tw.bg(colors.secondary[500]),
      ...tw.rounded('lg'),
      ...tw.py(3),
      ...tw.px(6),
    },
  },
  
  // 입력 필드 스타일
  input: {
    ...tw.border(colors.gray[300]),
    ...tw.rounded('md'),
    ...tw.p(3),
    ...tw.bg(colors.white),
  },
  
  // 컨테이너 스타일
  container: {
    ...tw.px(4),
    ...tw.py(6),
  },
  
  // 헤더 스타일
  header: {
    ...tw.bg(colors.primary[500]),
    ...tw.py(4),
    ...tw.px(6),
    ...tw.shadow('md'),
  },
};

export default {
  colors,
  spacing,
  fontSize,
  lineHeight,
  borderRadius,
  shadows,
  tw,
  responsive,
  componentStyles,
};
