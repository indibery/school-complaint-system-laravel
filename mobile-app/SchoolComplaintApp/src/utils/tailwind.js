// React Native에서 사용할 수 있는 기본 색상 상수들
export const colors = {
  primary: {
    50: '#e3f2fd',
    100: '#bbdefb',
    200: '#90caf9',
    300: '#64b5f6',
    400: '#42a5f5',
    500: '#2196f3',
    600: '#1e88e5',
    700: '#1976d2',
    800: '#1565c0',
    900: '#0d47a1',
  },
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
  success: '#4CAF50',
  warning: '#FF9800',
  error: '#F44336',
  info: '#2196F3',
};

// 간단한 스타일 유틸리티
export const tw = {
  // Flexbox
  flex: { display: 'flex' },
  flexRow: { flexDirection: 'row' },
  flexCol: { flexDirection: 'column' },
  justifyCenter: { justifyContent: 'center' },
  justifyBetween: { justifyContent: 'space-between' },
  itemsCenter: { alignItems: 'center' },
  
  // Spacing
  p: (value) => ({ padding: value }),
  px: (value) => ({ paddingHorizontal: value }),
  py: (value) => ({ paddingVertical: value }),
  m: (value) => ({ margin: value }),
  mx: (value) => ({ marginHorizontal: value }),
  my: (value) => ({ marginVertical: value }),
  
  // Sizing
  w: (value) => ({ width: value }),
  h: (value) => ({ height: value }),
  
  // Border
  rounded: (value) => ({ borderRadius: value === 'full' ? 9999 : value }),
  
  // Position
  absolute: { position: 'absolute' },
  relative: { position: 'relative' },
  
  // Text
  textCenter: { textAlign: 'center' },
  fontBold: { fontWeight: 'bold' },
};
