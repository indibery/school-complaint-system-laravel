import React, { useState, useEffect } from 'react';
import {
  View,
  ScrollView,
  RefreshControl,
  Dimensions,
  TouchableOpacity,
  Alert,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Chip,
  Avatar,
  IconButton,
  ActivityIndicator,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { COMPLAINT_CATEGORIES, COMPLAINT_STATUS, USER_TYPES } from '../constants/config';
import { colors, tw, componentStyles } from '../utils/tailwind';
import { dateUtils } from '../utils/helpers';

const { width } = Dimensions.get('window');

export default function HomeScreen({ navigation }) {
  const { user } = useAuth();
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState({
    pending: 0,
    processing: 0,
    completed: 0,
    total: 0,
  });
  const [recentComplaints, setRecentComplaints] = useState([]);
  const [notices, setNotices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');

  // 사용자 타입별 환영 메시지
  const getWelcomeMessage = () => {
    if (user?.user_type === USER_TYPES.PARENT.id) {
      return {
        greeting: `안녕하세요, ${user?.name}님!`,
        subtitle: '자녀 관련 민원을 쉽게 등록하고 관리하세요',
        icon: 'family-restroom',
      };
    } else if (user?.user_type === USER_TYPES.GUARD.id) {
      return {
        greeting: `안녕하세요, ${user?.name}님!`,
        subtitle: '학교 시설과 안전 관련 민원을 관리하세요',
        icon: 'security',
      };
    }
    return {
      greeting: `안녕하세요, ${user?.name}님!`,
      subtitle: '학교 민원 시스템을 이용해보세요',
      icon: 'person',
    };
  };

  // 사용자 타입별 카테고리 필터링
  const getAvailableCategories = () => {
    if (user?.user_type === USER_TYPES.PARENT.id) {
      return [
        COMPLAINT_CATEGORIES.ACADEMIC,
        COMPLAINT_CATEGORIES.LIFE,
        COMPLAINT_CATEGORIES.SAFETY,
      ];
    } else if (user?.user_type === USER_TYPES.GUARD.id) {
      return [
        COMPLAINT_CATEGORIES.SAFETY,
        COMPLAINT_CATEGORIES.FACILITY,
        COMPLAINT_CATEGORIES.EMERGENCY,
      ];
    }
    return Object.values(COMPLAINT_CATEGORIES);
  };

  // 데이터 로드
  const loadData = async () => {
    setLoading(true);
    try {
      // 임시 데이터 (추후 API 연동)
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      setStats({
        pending: 2,
        processing: 1,
        completed: 12,
        total: 15,
      });

      setRecentComplaints([
        {
          id: 1,
          title: '급식실 위생 관련 건의',
          category: COMPLAINT_CATEGORIES.LIFE,
          status: COMPLAINT_STATUS.PROCESSING,
          createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
        },
        {
          id: 2,
          title: '운동장 안전시설 점검 요청',
          category: COMPLAINT_CATEGORIES.SAFETY,
          status: COMPLAINT_STATUS.PENDING,
          createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
        },
      ]);

      setNotices([
        {
          id: 1,
          title: '민원 처리 시간 단축 안내',
          type: 'important',
          createdAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
        },
        {
          id: 2,
          title: '모바일 앱 업데이트 안내',
          type: 'general',
          createdAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
        },
      ]);
    } catch (error) {
      setSnackbarMessage('데이터를 불러오는 중 오류가 발생했습니다.');
      setSnackbarVisible(true);
    } finally {
      setLoading(false);
    }
  };

  // 새로고침
  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  // 초기 데이터 로드
  useEffect(() => {
    loadData();
  }, []);

  // 빠른 민원 등록
  const handleQuickComplaint = (category) => {
    navigation.navigate('CreateComplaint', { category });
  };

  // 긴급 신고
  const handleEmergencyReport = () => {
    Alert.alert(
      '긴급 신고',
      '긴급 상황을 신고하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        {
          text: '신고',
          style: 'destructive',
          onPress: () => {
            navigation.navigate('CreateComplaint', { 
              category: COMPLAINT_CATEGORIES.EMERGENCY,
              isEmergency: true 
            });
          }
        }
      ]
    );
  };

  const welcomeMessage = getWelcomeMessage();
  const availableCategories = getAvailableCategories();

  if (loading) {
    return (
      <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
        <View style={[tw.flex(1), tw.justifyCenter, tw.itemsCenter]}>
          <ActivityIndicator size="large" color={colors.primary[600]} />
          <Text style={[tw.mt(4), { color: colors.gray[600] }]}>
            데이터를 불러오는 중...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
      <ScrollView
        contentContainerStyle={[tw.pb(6)]}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        showsVerticalScrollIndicator={false}
      >
        {/* 헤더 */}
        <View style={[
          tw.px(5),
          tw.py(6),
          tw.bg(colors.primary[600]),
          { paddingTop: 20 }
        ]}>
          <View style={[tw.flexRow, tw.itemsCenter, tw.justifyBetween]}>
            <View style={[tw.flexRow, tw.itemsCenter, { gap: 12 }]}>
              <Avatar.Icon
                size={48}
                icon={welcomeMessage.icon}
                style={[tw.bg(colors.primary[100])]}
                color={colors.primary[600]}
              />
              <View>
                <Text
                  variant="headlineSmall"
                  style={[tw.fontBold, { color: colors.white }]}
                >
                  {welcomeMessage.greeting}
                </Text>
                <Text
                  variant="bodyMedium"
                  style={[tw.mt(1), { color: colors.primary[100] }]}
                >
                  {welcomeMessage.subtitle}
                </Text>
              </View>
            </View>
            <IconButton
              icon="notifications"
              size={24}
              iconColor={colors.white}
              style={[tw.bg(colors.primary[500])]}
              onPress={() => {/* 알림 화면으로 이동 */}}
            />
          </View>
        </View>

        {/* 긴급 신고 버튼 (학교지킴이용) */}
        {user?.user_type === USER_TYPES.GUARD.id && (
          <View style={[tw.px(5), tw.mt(5)]}>
            <TouchableOpacity
              onPress={handleEmergencyReport}
              style={[
                tw.flexRow,
                tw.itemsCenter,
                tw.p(4),
                tw.bg(colors.danger[500]),
                tw.rounded('xl'),
                tw.shadow('lg'),
                { gap: 12 }
              ]}
            >
              <MaterialIcons name="warning" size={28} color={colors.white} />
              <View style={tw.flex(1)}>
                <Text
                  variant="titleMedium"
                  style={[tw.fontBold, { color: colors.white }]}
                >
                  긴급 상황 신고
                </Text>
                <Text
                  variant="bodySmall"
                  style={[tw.mt(1), { color: colors.danger[100] }]}
                >
                  즉시 대응이 필요한 상황을 신고하세요
                </Text>
              </View>
              <MaterialIcons name="arrow-forward" size={20} color={colors.white} />
            </TouchableOpacity>
          </View>
        )}

        {/* 내 민원 현황 */}
        <View style={[tw.px(5), tw.mt(5)]}>
          <Card style={[componentStyles.card, tw.shadow('md')]}>
            <Card.Content style={tw.p(5)}>
              <Text variant="titleLarge" style={[tw.mb(4), { color: colors.gray[800] }]}>
                내 민원 현황
              </Text>
              
              <View style={[tw.flexRow, tw.justifyBetween, tw.mb(5)]} >
                <View style={[tw.itemsCenter, tw.flex(1)]}>
                  <Text
                    variant="headlineMedium"
                    style={[tw.fontBold, { color: colors.warning[600] }]}
                  >
                    {stats.pending}
                  </Text>
                  <Text
                    variant="bodySmall"
                    style={[tw.mt(1), { color: colors.gray[600] }]}
                  >
                    접수 대기
                  </Text>
                </View>
                <View style={[tw.itemsCenter, tw.flex(1)]}>
                  <Text
                    variant="headlineMedium"
                    style={[tw.fontBold, { color: colors.info[600] }]}
                  >
                    {stats.processing}
                  </Text>
                  <Text
                    variant="bodySmall"
                    style={[tw.mt(1), { color: colors.gray[600] }]}
                  >
                    처리 중
                  </Text>
                </View>
                <View style={[tw.itemsCenter, tw.flex(1)]}>
                  <Text
                    variant="headlineMedium"
                    style={[tw.fontBold, { color: colors.success[600] }]}
                  >
                    {stats.completed}
                  </Text>
                  <Text
                    variant="bodySmall"
                    style={[tw.mt(1), { color: colors.gray[600] }]}
                  >
                    완료
                  </Text>
                </View>
                <View style={[tw.itemsCenter, tw.flex(1)]}>
                  <Text
                    variant="headlineMedium"
                    style={[tw.fontBold, { color: colors.primary[600] }]}
                  >
                    {stats.total}
                  </Text>
                  <Text
                    variant="bodySmall"
                    style={[tw.mt(1), { color: colors.gray[600] }]}
                  >
                    전체
                  </Text>
                </View>
              </View>

              <Button
                mode="outlined"
                onPress={() => navigation.navigate('Complaints')}
                style={[tw.mt(2), tw.border(colors.primary[300])]}
                labelStyle={{ color: colors.primary[600] }}
              >
                전체 민원 보기
              </Button>
            </Card.Content>
          </Card>
        </View>

        {/* 빠른 민원 등록 */}
        <View style={[tw.px(5), tw.mt(5)]}>
          <Card style={[componentStyles.card, tw.shadow('md')]}>
            <Card.Content style={tw.p(5)}>
              <Text variant="titleLarge" style={[tw.mb(2), { color: colors.gray[800] }]}>
                빠른 민원 등록
              </Text>
              <Text
                variant="bodyMedium"
                style={[tw.mb(4), { color: colors.gray[600] }]}
              >
                카테고리를 선택하여 민원을 등록하세요
              </Text>
              
              <View style={[tw.flexRow, tw.flexWrap, { gap: 12 }]}>
                {availableCategories.map((category) => (
                  <TouchableOpacity
                    key={category.id}
                    onPress={() => handleQuickComplaint(category)}
                    style={[
                      tw.flexCol,
                      tw.itemsCenter,
                      tw.p(4),
                      tw.bg(colors.white),
                      tw.rounded('xl'),
                      tw.border(colors.gray[200]),
                      tw.borderW(1),
                      tw.shadow('sm'),
                      {
                        width: (width - 64) / 2,
                        minHeight: 100,
                      }
                    ]}
                  >
                    <View
                      style={[
                        tw.w(12),
                        tw.h(12),
                        tw.rounded('full'),
                        tw.itemsCenter,
                        tw.justifyCenter,
                        tw.mb(2),
                        { backgroundColor: category.color + '20' }
                      ]}
                    >
                      <MaterialIcons
                        name={category.icon}
                        size={24}
                        color={category.color}
                      />
                    </View>
                    <Text
                      variant="titleSmall"
                      style={[tw.textCenter, tw.fontMedium, { color: colors.gray[800] }]}
                    >
                      {category.name}
                    </Text>
                    <Text
                      variant="bodySmall"
                      style={[tw.textCenter, tw.mt(1), { color: colors.gray[600] }]}
                    >
                      {category.description}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </Card.Content>
          </Card>
        </View>

        {/* 최근 민원 */}
        {recentComplaints.length > 0 && (
          <View style={[tw.px(5), tw.mt(5)]}>
            <Card style={[componentStyles.card, tw.shadow('md')]}>
              <Card.Content style={tw.p(5)}>
                <Text variant="titleLarge" style={[tw.mb(4), { color: colors.gray[800] }]}>
                  최근 민원
                </Text>
                
                <View style={[tw.flexCol, { gap: 12 }]}>
                  {recentComplaints.map((complaint) => (
                    <TouchableOpacity
                      key={complaint.id}
                      onPress={() => navigation.navigate('Complaints', {
                        screen: 'ComplaintDetail',
                        params: { complaintId: complaint.id }
                      })}
                      style={[
                        tw.p(4),
                        tw.bg(colors.gray[50]),
                        tw.rounded('lg'),
                        tw.border(colors.gray[200]),
                        tw.borderW(1),
                      ]}
                    >
                      <View style={[tw.flexRow, tw.justifyBetween, tw.itemsStart]}>
                        <View style={tw.flex(1)}>
                          <Text
                            variant="titleMedium"
                            style={[tw.fontMedium, { color: colors.gray[800] }]}
                          >
                            {complaint.title}
                          </Text>
                          <Text
                            variant="bodySmall"
                            style={[tw.mt(1), { color: colors.gray[600] }]}
                          >
                            {complaint.category.name} • {dateUtils.formatRelativeTime(complaint.createdAt)}
                          </Text>
                        </View>
                        <Chip
                          style={[
                            tw.ml(2),
                            { backgroundColor: complaint.status.color + '20' }
                          ]}
                          textStyle={{ color: complaint.status.color, fontSize: 12 }}
                        >
                          {complaint.status.name}
                        </Chip>
                      </View>
                    </TouchableOpacity>
                  ))}
                </View>
              </Card.Content>
            </Card>
          </View>
        )}

        {/* 최근 공지사항 */}
        {notices.length > 0 && (
          <View style={[tw.px(5), tw.mt(5)]}>
            <Card style={[componentStyles.card, tw.shadow('md')]}>
              <Card.Content style={tw.p(5)}>
                <Text variant="titleLarge" style={[tw.mb(4), { color: colors.gray[800] }]}>
                  최근 공지사항
                </Text>
                
                <View style={[tw.flexCol, { gap: 12 }]}>
                  {notices.map((notice) => (
                    <TouchableOpacity
                      key={notice.id}
                      style={[
                        tw.flexRow,
                        tw.itemsCenter,
                        tw.p(3),
                        tw.bg(colors.gray[50]),
                        tw.rounded('lg'),
                        { gap: 12 }
                      ]}
                    >
                      <Chip
                        icon={notice.type === 'important' ? 'notification-important' : 'info'}
                        style={[
                          notice.type === 'important' 
                            ? tw.bg(colors.danger[100]) 
                            : tw.bg(colors.info[100])
                        ]}
                        textStyle={{
                          color: notice.type === 'important' ? colors.danger[600] : colors.info[600],
                          fontSize: 12
                        }}
                      >
                        {notice.type === 'important' ? '중요' : '일반'}
                      </Chip>
                      <View style={tw.flex(1)}>
                        <Text
                          variant="bodyMedium"
                          style={[tw.fontMedium, { color: colors.gray[800] }]}
                        >
                          {notice.title}
                        </Text>
                        <Text
                          variant="bodySmall"
                          style={[tw.mt(1), { color: colors.gray[600] }]}
                        >
                          {dateUtils.formatRelativeTime(notice.createdAt)}
                        </Text>
                      </View>
                    </TouchableOpacity>
                  ))}
                </View>
              </Card.Content>
            </Card>
          </View>
        )}
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
