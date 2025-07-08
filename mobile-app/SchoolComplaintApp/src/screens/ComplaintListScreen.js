import React, { useState, useEffect } from 'react';
import {
  View,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  Dimensions,
} from 'react-native';
import {
  Text,
  Card,
  Chip,
  SearchBar,
  Button,
  Menu,
  ActivityIndicator,
  FAB,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { COMPLAINT_CATEGORIES, COMPLAINT_STATUS } from '../constants/config';
import { colors, tw, componentStyles } from '../utils/tailwind';
import { dateUtils } from '../utils/helpers';

const { width } = Dimensions.get('window');

export default function ComplaintListScreen({ navigation }) {
  const { user } = useAuth();
  const [complaints, setComplaints] = useState([]);
  const [filteredComplaints, setFilteredComplaints] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('all');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [menuVisible, setMenuVisible] = useState(false);
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');

  // 샘플 데이터 (추후 API 연동)
  const sampleComplaints = [
    {
      id: 1,
      title: '급식실 위생 상태 개선 요청',
      description: '급식실 청결도가 우려스러워 개선을 요청드립니다.',
      category: COMPLAINT_CATEGORIES.LIFE,
      status: COMPLAINT_STATUS.PROCESSING,
      createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
      priority: 'high',
      commentsCount: 3,
    },
    {
      id: 2,
      title: '운동장 안전시설 점검 요청',
      description: '운동장 놀이시설의 안전점검을 요청드립니다.',
      category: COMPLAINT_CATEGORIES.SAFETY,
      status: COMPLAINT_STATUS.PENDING,
      createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
      priority: 'medium',
      commentsCount: 1,
    },
    {
      id: 3,
      title: '교실 에어컨 수리 요청',
      description: '3학년 2반 교실 에어컨이 고장났습니다.',
      category: COMPLAINT_CATEGORIES.FACILITY,
      status: COMPLAINT_STATUS.COMPLETED,
      createdAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
      priority: 'low',
      commentsCount: 0,
    },
    {
      id: 4,
      title: '수업 시간표 변경 건의',
      description: '체육 수업 시간 조정을 건의드립니다.',
      category: COMPLAINT_CATEGORIES.ACADEMIC,
      status: COMPLAINT_STATUS.REVIEWING,
      createdAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
      priority: 'medium',
      commentsCount: 2,
    },
    {
      id: 5,
      title: '화장실 수도 고장 신고',
      description: '1층 화장실 수도가 고장났습니다.',
      category: COMPLAINT_CATEGORIES.FACILITY,
      status: COMPLAINT_STATUS.REJECTED,
      createdAt: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
      updatedAt: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000),
      priority: 'high',
      commentsCount: 5,
    },
  ];

  // 데이터 로드
  const loadComplaints = async () => {
    setLoading(true);
    try {
      // 임시 데이터 (추후 API 연동)
      await new Promise(resolve => setTimeout(resolve, 1000));
      setComplaints(sampleComplaints);
      setFilteredComplaints(sampleComplaints);
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
    await loadComplaints();
    setRefreshing(false);
  };

  // 초기 데이터 로드
  useEffect(() => {
    loadComplaints();
  }, []);

  // 필터링 적용
  useEffect(() => {
    let filtered = complaints;

    // 상태별 필터링
    if (selectedStatus !== 'all') {
      filtered = filtered.filter(complaint => complaint.status.id === selectedStatus);
    }

    // 카테고리별 필터링
    if (selectedCategory !== 'all') {
      filtered = filtered.filter(complaint => complaint.category.id === selectedCategory);
    }

    // 검색어 필터링
    if (searchQuery) {
      filtered = filtered.filter(complaint =>
        complaint.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        complaint.description.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    setFilteredComplaints(filtered);
  }, [complaints, selectedStatus, selectedCategory, searchQuery]);

  // 민원 상세 이동
  const handleComplaintPress = (complaint) => {
    navigation.navigate('ComplaintDetail', { complaintId: complaint.id });
  };

  // 민원 등록
  const handleCreateComplaint = () => {
    navigation.navigate('CreateComplaint');
  };

  // 우선순위 색상
  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'high': return colors.danger[500];
      case 'medium': return colors.warning[500];
      case 'low': return colors.success[500];
      default: return colors.gray[500];
    }
  };

  // 상태 필터 옵션
  const statusOptions = [
    { label: '전체', value: 'all' },
    ...Object.values(COMPLAINT_STATUS).map(status => ({
      label: status.name,
      value: status.id,
    })),
  ];

  // 카테고리 필터 옵션
  const categoryOptions = [
    { label: '전체', value: 'all' },
    ...Object.values(COMPLAINT_CATEGORIES).map(category => ({
      label: category.name,
      value: category.id,
    })),
  ];

  if (loading) {
    return (
      <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
        <View style={[tw.flex(1), tw.justifyCenter, tw.itemsCenter]}>
          <ActivityIndicator size="large" color={colors.primary[600]} />
          <Text style={[tw.mt(4), { color: colors.gray[600] }]}>
            민원 목록을 불러오는 중...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
      {/* 검색 및 필터 */}
      <View style={[tw.px(5), tw.py(4), tw.bg(colors.white)]}>
        <SearchBar
          placeholder="민원 제목이나 내용을 검색하세요"
          onChangeText={setSearchQuery}
          value={searchQuery}
          style={[tw.mb(3)]}
        />
        
        <View style={[tw.flexRow, tw.justifyBetween, tw.itemsCenter]}>
          <View style={[tw.flexRow, { gap: 8 }]}>
            <Menu
              visible={menuVisible}
              onDismiss={() => setMenuVisible(false)}
              anchor={
                <Button
                  mode="outlined"
                  onPress={() => setMenuVisible(true)}
                  compact
                  style={[tw.border(colors.gray[300])]}
                >
                  {statusOptions.find(opt => opt.value === selectedStatus)?.label}
                </Button>
              }
            >
              {statusOptions.map(option => (
                <Menu.Item
                  key={option.value}
                  onPress={() => {
                    setSelectedStatus(option.value);
                    setMenuVisible(false);
                  }}
                  title={option.label}
                />
              ))}
            </Menu>
          </View>
          
          <Text variant="bodySmall" style={{ color: colors.gray[600] }}>
            {filteredComplaints.length}개의 민원
          </Text>
        </View>
      </View>

      {/* 민원 목록 */}
      <ScrollView
        contentContainerStyle={[tw.p(5), tw.pb(20)]}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        showsVerticalScrollIndicator={false}
      >
        {filteredComplaints.length === 0 ? (
          <View style={[tw.flex(1), tw.justifyCenter, tw.itemsCenter, tw.py(20)]}>
            <MaterialIcons name="assignment" size={64} color={colors.gray[400]} />
            <Text variant="titleMedium" style={[tw.mt(4), { color: colors.gray[600] }]}>
              민원이 없습니다
            </Text>
            <Text variant="bodyMedium" style={[tw.mt(2), tw.textCenter, { color: colors.gray[500] }]}>
              새로운 민원을 등록해보세요
            </Text>
          </View>
        ) : (
          <View style={[tw.flexCol, { gap: 16 }]}>
            {filteredComplaints.map((complaint) => (
              <TouchableOpacity
                key={complaint.id}
                onPress={() => handleComplaintPress(complaint)}
                style={[
                  componentStyles.card,
                  tw.shadow('md'),
                  tw.bg(colors.white),
                ]}
              >
                <View style={tw.p(5)}>
                  {/* 헤더 */}
                  <View style={[tw.flexRow, tw.justifyBetween, tw.itemsStart, tw.mb(3)]}>
                    <View style={tw.flex(1)}>
                      <Text
                        variant="titleMedium"
                        style={[tw.fontMedium, { color: colors.gray[800] }]}
                      >
                        {complaint.title}
                      </Text>
                      <Text
                        variant="bodyMedium"
                        style={[tw.mt(1), { color: colors.gray[600] }]}
                        numberOfLines={2}
                      >
                        {complaint.description}
                      </Text>
                    </View>
                    <View
                      style={[
                        tw.w(1),
                        tw.h(16),
                        tw.rounded('full'),
                        tw.ml(3),
                        { backgroundColor: getPriorityColor(complaint.priority) }
                      ]}
                    />
                  </View>

                  {/* 카테고리 및 상태 */}
                  <View style={[tw.flexRow, tw.justifyBetween, tw.itemsCenter, tw.mb(3)]}>
                    <View style={[tw.flexRow, { gap: 8 }]}>
                      <Chip
                        style={[
                          tw.bg(complaint.category.color + '20'),
                        ]}
                        textStyle={{ color: complaint.category.color, fontSize: 12 }}
                      >
                        {complaint.category.name}
                      </Chip>
                      <Chip
                        style={[
                          { backgroundColor: complaint.status.color + '20' }
                        ]}
                        textStyle={{ color: complaint.status.color, fontSize: 12 }}
                      >
                        {complaint.status.name}
                      </Chip>
                    </View>
                  </View>

                  {/* 하단 정보 */}
                  <View style={[tw.flexRow, tw.justifyBetween, tw.itemsCenter]}>
                    <Text variant="bodySmall" style={{ color: colors.gray[500] }}>
                      {dateUtils.formatRelativeTime(complaint.createdAt)}
                    </Text>
                    <View style={[tw.flexRow, tw.itemsCenter, { gap: 4 }]}>
                      <MaterialIcons name="comment" size={16} color={colors.gray[500]} />
                      <Text variant="bodySmall" style={{ color: colors.gray[500] }}>
                        {complaint.commentsCount}
                      </Text>
                    </View>
                  </View>
                </View>
              </TouchableOpacity>
            ))}
          </View>
        )}
      </ScrollView>

      {/* 플로팅 액션 버튼 */}
      <FAB
        icon="plus"
        style={[
          tw.absolute,
          tw.bottom(6),
          tw.right(5),
          tw.bg(colors.primary[600]),
        ]}
        onPress={handleCreateComplaint}
        label="민원 등록"
      />

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
