import React from 'react';
import {
  View,
  ScrollView,
  StyleSheet,
  Alert,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  List,
  Switch,
  Avatar,
  Divider,
  useTheme,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';

export default function ProfileScreen({ navigation }) {
  const theme = useTheme();
  const { user, logout } = useAuth();
  const [darkMode, setDarkMode] = React.useState(false);
  const [notifications, setNotifications] = React.useState(true);

  const handleLogout = () => {
    Alert.alert(
      '로그아웃',
      '정말 로그아웃하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        { 
          text: '로그아웃', 
          style: 'destructive',
          onPress: logout
        }
      ]
    );
  };

  const getUserTypeLabel = (type) => {
    switch (type) {
      case 'parent': return '학부모';
      case 'school_guard': return '학교지킴이';
      default: return '사용자';
    }
  };

  const getUserIcon = (type) => {
    switch (type) {
      case 'parent': return 'account-group';
      case 'school_guard': return 'shield-check';
      default: return 'account';
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* 사용자 정보 */}
        <Card style={[styles.profileCard, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.profileContent}>
            <View style={styles.profileHeader}>
              <Avatar.Icon
                size={64}
                icon={getUserIcon(user?.user_type)}
                style={{ backgroundColor: theme.colors.primaryContainer }}
              />
              <View style={styles.profileInfo}>
                <Text variant="headlineSmall" style={styles.userName}>
                  {user?.name || '사용자'}
                </Text>
                <Text variant="bodyMedium" style={{ color: theme.colors.onSurfaceVariant }}>
                  {user?.email || 'user@example.com'}
                </Text>
                <Text variant="bodySmall" style={[styles.userType, { color: theme.colors.primary }]}>
                  {getUserTypeLabel(user?.user_type)}
                </Text>
              </View>
            </View>
          </Card.Content>
        </Card>

        {/* 설정 메뉴 */}
        <Card style={[styles.menuCard, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.menuContent}>
            <Text variant="titleMedium" style={styles.menuTitle}>
              설정
            </Text>
            
            <List.Item
              title="다크 모드"
              description="어두운 테마 사용"
              left={(props) => <List.Icon {...props} icon="brightness-6" />}
              right={() => (
                <Switch
                  value={darkMode}
                  onValueChange={setDarkMode}
                />
              )}
            />
            
            <Divider />
            
            <List.Item
              title="알림 설정"
              description="민원 상태 변경 알림"
              left={(props) => <List.Icon {...props} icon="bell" />}
              right={() => (
                <Switch
                  value={notifications}
                  onValueChange={setNotifications}
                />
              )}
            />
          </Card.Content>
        </Card>

        {/* 지원 메뉴 */}
        <Card style={[styles.menuCard, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.menuContent}>
            <Text variant="titleMedium" style={styles.menuTitle}>
              지원
            </Text>
            
            <List.Item
              title="도움말"
              description="사용법 안내"
              left={(props) => <List.Icon {...props} icon="help-circle" />}
              right={(props) => <List.Icon {...props} icon="chevron-right" />}
              onPress={() => Alert.alert('도움말', '준비 중인 기능입니다.')}
            />
            
            <Divider />
            
            <List.Item
              title="문의하기"
              description="기술 지원 및 문의"
              left={(props) => <List.Icon {...props} icon="email" />}
              right={(props) => <List.Icon {...props} icon="chevron-right" />}
              onPress={() => Alert.alert('문의하기', '준비 중인 기능입니다.')}
            />
            
            <Divider />
            
            <List.Item
              title="앱 정보"
              description="버전 1.0.0"
              left={(props) => <List.Icon {...props} icon="information" />}
              right={(props) => <List.Icon {...props} icon="chevron-right" />}
              onPress={() => Alert.alert('앱 정보', '학교 민원 시스템\n버전 1.0.0')}
            />
          </Card.Content>
        </Card>

        {/* 로그아웃 버튼 */}
        <Button
          mode="outlined"
          onPress={handleLogout}
          style={[styles.logoutButton, { borderColor: theme.colors.error }]}
          labelStyle={{ color: theme.colors.error }}
          icon="logout"
        >
          로그아웃
        </Button>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  profileCard: {
    marginBottom: 16,
    elevation: 2,
    borderRadius: 12,
  },
  profileContent: {
    padding: 20,
  },
  profileHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  profileInfo: {
    marginLeft: 16,
    flex: 1,
  },
  userName: {
    fontWeight: 'bold',
  },
  userType: {
    marginTop: 4,
    fontWeight: '600',
  },
  menuCard: {
    marginBottom: 16,
    elevation: 2,
    borderRadius: 12,
  },
  menuContent: {
    padding: 8,
  },
  menuTitle: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    fontWeight: 'bold',
  },
  logoutButton: {
    marginTop: 16,
    paddingVertical: 8,
  },
});
