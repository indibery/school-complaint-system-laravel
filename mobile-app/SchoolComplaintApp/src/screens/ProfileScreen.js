import React, { useState } from 'react';
import {
  View,
  ScrollView,
  TouchableOpacity,
  Alert,
  Share,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  Switch,
  Divider,
  List,
  IconButton,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { USER_TYPES } from '../constants/config';
import { colors, tw, componentStyles } from '../utils/tailwind';

export default function ProfileScreen() {
  const { user, logout } = useAuth();
  const [darkMode, setDarkMode] = useState(false);
  const [notifications, setNotifications] = useState(true);
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');

  // 사용자 정보 가져오기
  const getUserInfo = () => {
    if (user?.user_type === USER_TYPES.PARENT.id) {
      return {
        title: '학부모',
        subtitle: user?.child_name ? `${user.child_name}의 학부모` : '학부모',
        icon: 'family-restroom',
        color: colors.primary[600],
      };
    } else if (user?.user_type === USER_TYPES.GUARD.id) {
      return {
        title: '학교지킴이',
        subtitle: user?.department || '학교지킴이',
        icon: 'security',
        color: colors.secondary[600],
      };
    }
    return {
      title: '사용자',
      subtitle: '학교 구성원',
      icon: 'person',
      color: colors.gray[600],
    };
  };

  // 로그아웃 처리
  const handleLogout = async () => {
    Alert.alert(
      '로그아웃',
      '정말로 로그아웃하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        {
          text: '로그아웃',
          style: 'destructive',
          onPress: async () => {
            try {
              await logout();
              setSnackbarMessage('로그아웃되었습니다.');
              setSnackbarVisible(true);
            } catch (error) {
              setSnackbarMessage('로그아웃 중 오류가 발생했습니다.');
              setSnackbarVisible(true);
            }
          }
        }
      ]
    );
  };

  // 앱 공유
  const handleShare = async () => {
    try {
      await Share.share({
        message: '학교 민원 시스템 앱을 사용해보세요!',
        title: '학교 민원 시스템',
      });
    } catch (error) {
      setSnackbarMessage('공유 중 오류가 발생했습니다.');
      setSnackbarVisible(true);
    }
  };

  // 설정 변경 처리
  const handleSettingChange = (setting, value) => {
    switch (setting) {
      case 'darkMode':
        setDarkMode(value);
        setSnackbarMessage('다크 모드 설정이 변경되었습니다.');
        setSnackbarVisible(true);
        break;
      case 'notifications':
        setNotifications(value);
        setSnackbarMessage('알림 설정이 변경되었습니다.');
        setSnackbarVisible(true);
        break;
      default:
        break;
    }
  };

  const userInfo = getUserInfo();

  // 메뉴 항목 데이터
  const menuItems = [
    {
      section: '내 정보',
      items: [
        {
          title: '프로필 수정',
          icon: 'edit',
          onPress: () => {
            setSnackbarMessage('프로필 수정 기능은 준비 중입니다.');
            setSnackbarVisible(true);
          }
        },
        {
          title: '비밀번호 변경',
          icon: 'lock',
          onPress: () => {
            setSnackbarMessage('비밀번호 변경 기능은 준비 중입니다.');
            setSnackbarVisible(true);
          }
        },
      ]
    },
    {
      section: '설정',
      items: [
        {
          title: '다크 모드',
          icon: 'dark-mode',
          type: 'switch',
          value: darkMode,
          onValueChange: (value) => handleSettingChange('darkMode', value),
        },
        {
          title: '알림 설정',
          icon: 'notifications',
          type: 'switch',
          value: notifications,
          onValueChange: (value) => handleSettingChange('notifications', value),
        },
      ]
    },
    {
      section: '지원',
      items: [
        {
          title: '도움말',
          icon: 'help',
          onPress: () => {
            setSnackbarMessage('도움말 기능은 준비 중입니다.');
            setSnackbarVisible(true);
          }
        },
        {
          title: '문의하기',
          icon: 'contact-support',
          onPress: () => {
            setSnackbarMessage('문의하기 기능은 준비 중입니다.');
            setSnackbarVisible(true);
          }
        },
        {
          title: '앱 공유',
          icon: 'share',
          onPress: handleShare,
        },
        {
          title: '앱 정보',
          icon: 'info',
          onPress: () => {
            Alert.alert(
              '앱 정보',
              '학교 민원 시스템 v1.0.0\n\n학부모와 학교지킴이를 위한 민원 관리 앱입니다.',
              [{ text: '확인' }]
            );
          }
        },
      ]
    }
  ];

  return (
    <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
      <ScrollView
        contentContainerStyle={[tw.p(5), tw.pb(8)]}
        showsVerticalScrollIndicator={false}
      >
        {/* 프로필 헤더 */}
        <Card style={[componentStyles.card, tw.shadow('md'), tw.mb(6)]}>
          <Card.Content style={tw.p(6)}>
            <View style={[tw.flexRow, tw.itemsCenter, { gap: 16 }]}>
              <Avatar.Icon
                size={80}
                icon={userInfo.icon}
                style={{ backgroundColor: userInfo.color + '20' }}
                color={userInfo.color}
              />
              <View style={tw.flex(1)}>
                <Text
                  variant="headlineSmall"
                  style={[tw.fontBold, { color: colors.gray[800] }]}
                >
                  {user?.name}
                </Text>
                <Text
                  variant="bodyMedium"
                  style={[tw.mt(1), { color: colors.gray[600] }]}
                >
                  {userInfo.subtitle}
                </Text>
                <Text
                  variant="bodySmall"
                  style={[tw.mt(1), { color: colors.gray[500] }]}
                >
                  {user?.email}
                </Text>
                {user?.phone && (
                  <Text
                    variant="bodySmall"
                    style={[tw.mt(1), { color: colors.gray[500] }]}
                  >
                    {user.phone}
                  </Text>
                )}
              </View>
            </View>

            {/* 사용자 타입별 추가 정보 */}
            {user?.user_type === USER_TYPES.PARENT.id && (
              <View style={[tw.mt(4), tw.pt(4), tw.border(colors.gray[200]), tw.borderW(1)]}>
                <Text variant="titleSmall" style={[tw.mb(2), { color: colors.gray[700] }]}>
                  자녀 정보
                </Text>
                <View style={[tw.flexRow, tw.flexWrap, { gap: 8 }]}>
                  {user.child_name && (
                    <View style={[tw.flexRow, tw.itemsCenter, { gap: 4 }]}>
                      <MaterialIcons name="child-care" size={16} color={colors.primary[600]} />
                      <Text variant="bodySmall" style={{ color: colors.gray[600] }}>
                        {user.child_name}
                      </Text>
                    </View>
                  )}
                  {user.child_grade && (
                    <View style={[tw.flexRow, tw.itemsCenter, { gap: 4 }]}>
                      <MaterialIcons name="school" size={16} color={colors.primary[600]} />
                      <Text variant="bodySmall" style={{ color: colors.gray[600] }}>
                        {user.child_grade}학년
                      </Text>
                    </View>
                  )}
                  {user.child_class && (
                    <View style={[tw.flexRow, tw.itemsCenter, { gap: 4 }]}>
                      <MaterialIcons name="class" size={16} color={colors.primary[600]} />
                      <Text variant="bodySmall" style={{ color: colors.gray[600] }}>
                        {user.child_class}반
                      </Text>
                    </View>
                  )}
                </View>
              </View>
            )}

            {user?.user_type === USER_TYPES.GUARD.id && user?.employee_id && (
              <View style={[tw.mt(4), tw.pt(4), tw.border(colors.gray[200]), tw.borderW(1)]}>
                <Text variant="titleSmall" style={[tw.mb(2), { color: colors.gray[700] }]}>
                  직원 정보
                </Text>
                <View style={[tw.flexRow, tw.itemsCenter, { gap: 4 }]}>
                  <MaterialIcons name="badge" size={16} color={colors.secondary[600]} />
                  <Text variant="bodySmall" style={{ color: colors.gray[600] }}>
                    직원번호: {user.employee_id}
                  </Text>
                </View>
              </View>
            )}
          </Card.Content>
        </Card>

        {/* 메뉴 항목들 */}
        {menuItems.map((section, sectionIndex) => (
          <Card
            key={sectionIndex}
            style={[componentStyles.card, tw.shadow('md'), tw.mb(4)]}
          >
            <Card.Content style={tw.p(0)}>
              <Text
                variant="titleMedium"
                style={[tw.p(4), tw.pb(2), tw.fontMedium, { color: colors.gray[700] }]}
              >
                {section.section}
              </Text>
              <Divider style={{ backgroundColor: colors.gray[200] }} />
              
              {section.items.map((item, itemIndex) => (
                <View key={itemIndex}>
                  <TouchableOpacity
                    onPress={item.onPress}
                    style={[
                      tw.flexRow,
                      tw.itemsCenter,
                      tw.justifyBetween,
                      tw.p(4),
                      itemIndex < section.items.length - 1 && tw.mb(1)
                    ]}
                  >
                    <View style={[tw.flexRow, tw.itemsCenter, { gap: 16 }]}>
                      <MaterialIcons
                        name={item.icon}
                        size={24}
                        color={colors.gray[600]}
                      />
                      <Text
                        variant="bodyLarge"
                        style={{ color: colors.gray[800] }}
                      >
                        {item.title}
                      </Text>
                    </View>
                    
                    {item.type === 'switch' ? (
                      <Switch
                        value={item.value}
                        onValueChange={item.onValueChange}
                        thumbColor={item.value ? colors.primary[600] : colors.gray[300]}
                        trackColor={{
                          false: colors.gray[300],
                          true: colors.primary[200],
                        }}
                      />
                    ) : (
                      <MaterialIcons
                        name="chevron-right"
                        size={24}
                        color={colors.gray[400]}
                      />
                    )}
                  </TouchableOpacity>
                  {itemIndex < section.items.length - 1 && (
                    <Divider style={{ backgroundColor: colors.gray[100] }} />
                  )}
                </View>
              ))}
            </Card.Content>
          </Card>
        ))}

        {/* 로그아웃 버튼 */}
        <Button
          mode="contained"
          onPress={handleLogout}
          style={[
            tw.mt(4),
            tw.shadow('md'),
            { backgroundColor: colors.danger[500] }
          ]}
          contentStyle={tw.py(2)}
          labelStyle={{ color: colors.white }}
        >
          로그아웃
        </Button>

        {/* 앱 버전 정보 */}
        <Text
          variant="bodySmall"
          style={[tw.textCenter, tw.mt(6), { color: colors.gray[500] }]}
        >
          학교 민원 시스템 v1.0.0
        </Text>
      </ScrollView>

      <Snackbar
        visible={snackbarVisible}
        onDismiss={() => setSnackbarVisible(false)}
        duration={3000}
        style={tw.m(4)}
      >
        {snackbarMessage}
      </Snackbar>
    </SafeAreaView>
  );
}
