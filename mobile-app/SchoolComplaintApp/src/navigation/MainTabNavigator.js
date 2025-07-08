import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { MaterialIcons } from '@expo/vector-icons';
import { useTheme } from 'react-native-paper';
import { View, Text, Platform } from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors, tw } from '../utils/tailwind';

// 화면 임포트
import HomeScreen from '../screens/HomeScreen';
import ComplaintListScreen from '../screens/ComplaintListScreen';
import ComplaintDetailScreen from '../screens/ComplaintDetailScreen';
import CreateComplaintScreen from '../screens/CreateComplaintScreen';
import ProfileScreen from '../screens/ProfileScreen';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

// 홈 스택 네비게이터
function HomeStackNavigator() {
  const theme = useTheme();
  
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {
          backgroundColor: theme.colors.surface,
          ...Platform.select({
            ios: {
              shadowColor: colors.gray[400],
              shadowOffset: { width: 0, height: 1 },
              shadowOpacity: 0.1,
              shadowRadius: 3,
            },
            android: {
              elevation: 4,
            },
          }),
        },
        headerTitleStyle: {
          fontSize: 18,
          fontWeight: '600',
          color: theme.colors.onSurface,
        },
        headerTintColor: theme.colors.primary,
        headerBackTitleVisible: false,
        cardStyle: {
          backgroundColor: theme.colors.background,
        },
        headerShadowVisible: true,
      }}
    >
      <Stack.Screen
        name="HomeMain"
        component={HomeScreen}
        options={{
          headerTitle: '홈',
          headerLeft: () => null,
        }}
      />
      <Stack.Screen
        name="CreateComplaint"
        component={CreateComplaintScreen}
        options={{
          headerTitle: '민원 등록',
          presentation: 'modal',
          headerStyle: {
            backgroundColor: theme.colors.surface,
          },
        }}
      />
    </Stack.Navigator>
  );
}

// 민원 스택 네비게이터
function ComplaintStackNavigator() {
  const theme = useTheme();
  
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {
          backgroundColor: theme.colors.surface,
          ...Platform.select({
            ios: {
              shadowColor: colors.gray[400],
              shadowOffset: { width: 0, height: 1 },
              shadowOpacity: 0.1,
              shadowRadius: 3,
            },
            android: {
              elevation: 4,
            },
          }),
        },
        headerTitleStyle: {
          fontSize: 18,
          fontWeight: '600',
          color: theme.colors.onSurface,
        },
        headerTintColor: theme.colors.primary,
        headerBackTitleVisible: false,
        cardStyle: {
          backgroundColor: theme.colors.background,
        },
      }}
    >
      <Stack.Screen
        name="ComplaintList"
        component={ComplaintListScreen}
        options={{
          headerTitle: '내 민원',
          headerLeft: () => null,
        }}
      />
      <Stack.Screen
        name="ComplaintDetail"
        component={ComplaintDetailScreen}
        options={{
          headerTitle: '민원 상세',
        }}
      />
    </Stack.Navigator>
  );
}

// 프로필 스택 네비게이터
function ProfileStackNavigator() {
  const theme = useTheme();
  
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {
          backgroundColor: theme.colors.surface,
          ...Platform.select({
            ios: {
              shadowColor: colors.gray[400],
              shadowOffset: { width: 0, height: 1 },
              shadowOpacity: 0.1,
              shadowRadius: 3,
            },
            android: {
              elevation: 4,
            },
          }),
        },
        headerTitleStyle: {
          fontSize: 18,
          fontWeight: '600',
          color: theme.colors.onSurface,
        },
        headerTintColor: theme.colors.primary,
        headerBackTitleVisible: false,
        cardStyle: {
          backgroundColor: theme.colors.background,
        },
      }}
    >
      <Stack.Screen
        name="ProfileMain"
        component={ProfileScreen}
        options={{
          headerTitle: '프로필',
          headerLeft: () => null,
        }}
      />
    </Stack.Navigator>
  );
}

// 커스텀 탭 바 레이블
function TabBarLabel({ focused, children }) {
  return (
    <Text
      style={[
        {
          fontSize: 12,
          fontWeight: focused ? '600' : '400',
          marginTop: 4,
        },
        focused ? { color: colors.primary[600] } : { color: colors.gray[500] }
      ]}
    >
      {children}
    </Text>
  );
}

// 커스텀 탭 바 아이콘
function TabBarIcon({ focused, iconName, size = 24 }) {
  return (
    <View style={[
      tw.itemsCenter,
      tw.justifyCenter,
      {
        width: size + 8,
        height: size + 8,
      }
    ]}>
      <MaterialIcons
        name={iconName}
        size={size}
        color={focused ? colors.primary[600] : colors.gray[500]}
      />
      {focused && (
        <View
          style={[
            tw.absolute,
            tw.rounded('full'),
            {
              width: size + 16,
              height: size + 16,
              backgroundColor: colors.primary[50],
              opacity: 0.3,
            }
          ]}
        />
      )}
    </View>
  );
}

export default function MainTabNavigator() {
  const theme = useTheme();
  const { user } = useAuth();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarIcon: ({ focused }) => {
          let iconName;
          let size = 24;

          if (route.name === 'Home') {
            iconName = focused ? 'home' : 'home';
          } else if (route.name === 'Complaints') {
            iconName = focused ? 'assignment' : 'assignment';
          } else if (route.name === 'Profile') {
            iconName = focused ? 'person' : 'person';
          }

          return <TabBarIcon focused={focused} iconName={iconName} size={size} />;
        },
        tabBarLabel: ({ focused, children }) => (
          <TabBarLabel focused={focused}>{children}</TabBarLabel>
        ),
        tabBarStyle: {
          backgroundColor: theme.colors.surface,
          borderTopColor: theme.colors.outline,
          borderTopWidth: 1,
          paddingBottom: Platform.OS === 'ios' ? 20 : 8,
          paddingTop: 8,
          height: Platform.OS === 'ios' ? 85 : 65,
          ...Platform.select({
            ios: {
              shadowColor: colors.gray[400],
              shadowOffset: { width: 0, height: -1 },
              shadowOpacity: 0.1,
              shadowRadius: 3,
            },
            android: {
              elevation: 8,
            },
          }),
        },
        tabBarItemStyle: {
          paddingVertical: 4,
        },
        tabBarActiveTintColor: colors.primary[600],
        tabBarInactiveTintColor: colors.gray[500],
        tabBarHideOnKeyboard: true,
      })}
    >
      <Tab.Screen
        name="Home"
        component={HomeStackNavigator}
        options={{
          title: '홈',
          tabBarBadge: undefined, // 추후 알림 개수 표시
        }}
      />
      <Tab.Screen
        name="Complaints"
        component={ComplaintStackNavigator}
        options={{
          title: '내 민원',
          tabBarBadge: undefined, // 추후 처리 중인 민원 개수 표시
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileStackNavigator}
        options={{
          title: '프로필',
          tabBarBadge: undefined, // 추후 중요 알림 표시
        }}
      />
    </Tab.Navigator>
  );
}
