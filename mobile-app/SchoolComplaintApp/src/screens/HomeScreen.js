import React, { useState, useEffect } from 'react';
import {
  View,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Alert,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Avatar,
  IconButton,
  ActivityIndicator,
  useTheme,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';

export default function HomeScreen({ navigation }) {
  const theme = useTheme();
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);

  // 사용자 타입별 환영 메시지
  const getWelcomeMessage = () => {
    if (user?.user_type === 'parent') {
      return {
        greeting: `안녕하세요, ${user?.name || '학부모'}님!`,
        subtitle: '자녀 관련 민원을 쉽게 등록하고 관리하세요',
        icon: 'account-group',
      };
    } else if (user?.user_type === 'school_guard') {
      return {
        greeting: `안녕하세요, ${user?.name || '지킴이'}님!`,
        subtitle: '학교 시설과 안전 관련 민원을 관리하세요',
        icon: 'shield-check',
      };
    }
    return {
      greeting: `안녕하세요, ${user?.name || '사용자'}님!`,
      subtitle: '학교 민원 시스템을 이용해보세요',
      icon: 'account',
    };
  };

  // 빠른 민원 등록
  const handleCreateComplaint = () => {
    navigation.navigate('CreateComplaint');
  };

  // 내 민원 보기
  const handleViewComplaints = () => {
    navigation.navigate('Complaints');
  };

  const welcomeMessage = getWelcomeMessage();

  if (loading) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text style={[styles.loadingText, { color: theme.colors.onSurfaceVariant }]}>
            데이터를 불러오는 중...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <View style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* 헤더 - 상단 여백 제거 */}
        <View style={[styles.header, { backgroundColor: theme.colors.primary }]}>
          <View style={styles.headerContent}>
            <View style={styles.welcomeSection}>
              <Avatar.Icon
                size={48}
                icon={welcomeMessage.icon}
                style={{ backgroundColor: theme.colors.primaryContainer }}
              />
              <View style={styles.welcomeText}>
                <Text variant="headlineSmall" style={[styles.greeting, { color: theme.colors.onPrimary }]}>
                  {welcomeMessage.greeting}
                </Text>
                <Text variant="bodyMedium" style={[styles.subtitle, { color: theme.colors.onPrimary }]}>
                  {welcomeMessage.subtitle}
                </Text>
              </View>
            </View>
            <IconButton
              icon="bell"
              size={24}
              iconColor={theme.colors.onPrimary}
              style={{ backgroundColor: theme.colors.primaryContainer }}
              onPress={() => Alert.alert('알림', '준비 중인 기능입니다.')}
            />
          </View>
        </View>

        {/* 메인 액션 버튼들 */}
        <View style={styles.actionsContainer}>
          {/* 민원 등록 버튼 */}
          <Card style={[styles.actionCard, { backgroundColor: theme.colors.surface }]}>
            <Card.Content style={styles.actionCardContent}>
              <View style={styles.actionHeader}>
                <MaterialIcons name="add-box" size={32} color={theme.colors.primary} />
                <Text variant="titleLarge" style={styles.actionTitle}>
                  민원 등록
                </Text>
              </View>
              <Text variant="bodyMedium" style={[styles.actionDescription, { color: theme.colors.onSurfaceVariant }]}>
                새로운 민원을 등록하고 진행 상황을 확인하세요
              </Text>
              <Button
                mode="contained"
                onPress={handleCreateComplaint}
                style={styles.actionButton}
                contentStyle={styles.actionButtonContent}
              >
                민원 등록하기
              </Button>
            </Card.Content>
          </Card>

          {/* 내 민원 보기 버튼 */}
          <Card style={[styles.actionCard, { backgroundColor: theme.colors.surface }]}>
            <Card.Content style={styles.actionCardContent}>
              <View style={styles.actionHeader}>
                <MaterialIcons name="assignment" size={32} color={theme.colors.primary} />
                <Text variant="titleLarge" style={styles.actionTitle}>
                  내 민원
                </Text>
              </View>
              <Text variant="bodyMedium" style={[styles.actionDescription, { color: theme.colors.onSurfaceVariant }]}>
                등록한 민원의 처리 상태를 확인하고 관리하세요
              </Text>
              <Button
                mode="outlined"
                onPress={handleViewComplaints}
                style={styles.actionButton}
                contentStyle={styles.actionButtonContent}
              >
                민원 목록 보기
              </Button>
            </Card.Content>
          </Card>

          {/* 긴급 신고 (학교지킴이용) */}
          {user?.user_type === 'school_guard' && (
            <TouchableOpacity
              onPress={() => {
                Alert.alert(
                  '긴급 신고',
                  '긴급 상황을 신고하시겠습니까?',
                  [
                    { text: '취소', style: 'cancel' },
                    {
                      text: '신고',
                      style: 'destructive',
                      onPress: () => navigation.navigate('CreateComplaint', { isEmergency: true })
                    }
                  ]
                );
              }}
              style={[styles.emergencyButton, { backgroundColor: theme.colors.error }]}
            >
              <MaterialIcons name="warning" size={28} color="white" />
              <View style={styles.emergencyContent}>
                <Text variant="titleMedium" style={styles.emergencyTitle}>
                  긴급 상황 신고
                </Text>
                <Text variant="bodySmall" style={styles.emergencySubtitle}>
                  즉시 대응이 필요한 상황을 신고하세요
                </Text>
              </View>
              <MaterialIcons name="arrow-forward" size={20} color="white" />
            </TouchableOpacity>
          )}
        </View>

        {/* 도움말 섹션 */}
        <View style={styles.helpSection}>
        <Card style={[styles.helpCard, { backgroundColor: theme.colors.surfaceVariant }]}>
        <Card.Content style={styles.helpContent}>
        <MaterialIcons name="help-outline" size={24} color={theme.colors.primary} />
        <View style={styles.helpText}>
        <Text variant="titleMedium" style={styles.helpTitle}>
        도움이 필요하신가요?
        </Text>
        <Text variant="bodySmall" style={[styles.helpDescription, { color: theme.colors.onSurfaceVariant }]}>
        민원 등록 방법이나 시스템 사용법을 확인하세요
        </Text>
        </View>
        <Button
        mode="text"
        onPress={() => Alert.alert('도움말', '준비 중인 기능입니다.')}
        compact
        >
        도움말
        </Button>
        </Card.Content>
        </Card>
          
          {/* API 테스트 버튼 (개발 중에만 표시) */}
          {__DEV__ && (
            <Card style={[styles.helpCard, { backgroundColor: theme.colors.secondaryContainer, marginTop: 16 }]}>
              <Card.Content style={styles.helpContent}>
                <MaterialIcons name="api" size={24} color={theme.colors.secondary} />
                <View style={styles.helpText}>
                  <Text variant="titleMedium" style={styles.helpTitle}>
                    API 테스트
                  </Text>
                  <Text variant="bodySmall" style={[styles.helpDescription, { color: theme.colors.onSurfaceVariant }]}>
                    백엔드 API 연결 및 민원 등록 테스트
                  </Text>
                </View>
                <Button
                  mode="text"
                  onPress={() => navigation.navigate('ApiSettings')}
                  compact
                >
                  API 설정
                </Button>
              </Card.Content>
            </Card>
          )}
          
          {/* 디버깅 버튼 (개발 중에만 표시) */}
          {__DEV__ && (
            <Card style={[styles.helpCard, { backgroundColor: theme.colors.errorContainer, marginTop: 16 }]}>
              <Card.Content style={styles.helpContent}>
                <MaterialIcons name="bug-report" size={24} color={theme.colors.error} />
                <View style={styles.helpText}>
                  <Text variant="titleMedium" style={styles.helpTitle}>
                    디버깅 도구
                  </Text>
                  <Text variant="bodySmall" style={[styles.helpDescription, { color: theme.colors.onSurfaceVariant }]}>
                    API 연결 및 데이터 확인용
                  </Text>
                </View>
                <Button
                  mode="text"
                  onPress={() => navigation.navigate('ApiSettings')}
                  compact
                >
                  API 설정
                </Button>
              </Card.Content>
            </Card>
          )}
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 16,
  },
  scrollContent: {
    flexGrow: 1,
  },
  header: {
    paddingHorizontal: 20,
    paddingTop: 10, // 상단 패딩 줄임
    paddingBottom: 24,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  welcomeSection: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  welcomeText: {
    marginLeft: 12,
    flex: 1,
  },
  greeting: {
    fontWeight: 'bold',
  },
  subtitle: {
    marginTop: 4,
    opacity: 0.9,
  },
  actionsContainer: {
    padding: 20,
    gap: 16,
  },
  actionCard: {
    elevation: 2,
    borderRadius: 12,
  },
  actionCardContent: {
    padding: 20,
  },
  actionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
    gap: 12,
  },
  actionTitle: {
    fontWeight: 'bold',
  },
  actionDescription: {
    marginBottom: 16,
    lineHeight: 20,
  },
  actionButton: {
    marginTop: 8,
  },
  actionButtonContent: {
    paddingVertical: 8,
  },
  emergencyButton: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 12,
    gap: 12,
  },
  emergencyContent: {
    flex: 1,
  },
  emergencyTitle: {
    color: 'white',
    fontWeight: 'bold',
  },
  emergencySubtitle: {
    color: 'white',
    marginTop: 4,
    opacity: 0.9,
  },
  helpSection: {
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  helpCard: {
    borderRadius: 8,
  },
  helpContent: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    gap: 12,
  },
  helpText: {
    flex: 1,
  },
  helpTitle: {
    fontWeight: '600',
  },
  helpDescription: {
    marginTop: 2,
  },
});
