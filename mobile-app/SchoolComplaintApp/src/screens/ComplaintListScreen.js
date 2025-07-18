import React, { useState, useEffect } from 'react';
import {
  View,
  FlatList,
  StyleSheet,
  Alert,
  RefreshControl,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  Chip,
  Searchbar,
  ActivityIndicator,
  useTheme,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import { complaintAPI } from '../services/api';

export default function ComplaintListScreen({ navigation }) {
  const theme = useTheme();
  const { user } = useAuth();
  const [complaints, setComplaints] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');

  // 컴포넌트가 포커스될 때마다 민원 목록을 새로고침
  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      loadComplaints();
    });
    return unsubscribe;
  }, [navigation]);

  useEffect(() => {
    loadComplaints();
  }, []);

  const loadComplaints = async () => {
    try {
      setLoading(true);
      const response = await complaintAPI.getMyComplaints();
      
      if (response.success) {
        setComplaints(response.data || []);
      } else {
        console.error('민원 목록 로드 실패:', response.message);
        showSnackbar('민원 목록을 불러오는데 실패했습니다.');
        setComplaints([]);
      }
    } catch (error) {
      console.error('민원 목록 로드 오류:', error);
      showSnackbar('네트워크 오류가 발생했습니다.');
      setComplaints([]);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadComplaints();
    setRefreshing(false);
  };

  const showSnackbar = (message) => {
    setSnackbarMessage(message);
    setSnackbarVisible(true);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return '#FF9800';
      case 'processing': return '#2196F3';
      case 'completed': return '#4CAF50';
      case 'rejected': return '#F44336';
      default: return '#757575';
    }
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'pending': return '대기';
      case 'processing': return '처리중';
      case 'completed': return '완료';
      case 'rejected': return '거절';
      default: return '알 수 없음';
    }
  };

  const formatDate = (dateString) => {
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('ko-KR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      });
    } catch (error) {
      return dateString;
    }
  };

  const filteredComplaints = complaints.filter(complaint =>
    complaint.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    complaint.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    complaint.category?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const renderComplaint = ({ item }) => (
    <Card 
      style={[styles.complaintCard, { backgroundColor: theme.colors.surface }]}
      onPress={() => navigation.navigate('ComplaintDetail', { complaintId: item.id, complaint: item })}
    >
      <Card.Content style={styles.cardContent}>
        <View style={styles.cardHeader}>
          <Text variant="titleMedium" style={styles.complaintTitle}>
            {item.title || '제목 없음'}
          </Text>
          <Chip 
            style={[styles.statusChip, { backgroundColor: getStatusColor(item.status) + '20' }]}
            textStyle={{ color: getStatusColor(item.status) }}
          >
            {getStatusLabel(item.status)}
          </Chip>
        </View>
        <Text variant="bodySmall" style={[styles.description, { color: theme.colors.onSurfaceVariant }]}>
          {item.description || '내용 없음'}
        </Text>
        <View style={styles.cardInfo}>
          <Text variant="bodySmall" style={{ color: theme.colors.onSurfaceVariant }}>
            {item.category || '기타'} • {formatDate(item.created_at || item.createdAt)}
          </Text>
        </View>
      </Card.Content>
    </Card>
  );

  if (loading) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text style={[styles.loadingText, { color: theme.colors.onSurfaceVariant }]}>
            민원 목록을 불러오는 중...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <View style={styles.content}>
        {/* 검색바 */}
        <View style={styles.searchContainer}>
          <Searchbar
            placeholder="민원 검색..."
            onChangeText={setSearchQuery}
            value={searchQuery}
            style={styles.searchbar}
          />
        </View>

        {/* 민원 목록 */}
        <FlatList
          data={filteredComplaints}
          renderItem={renderComplaint}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContainer}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={[theme.colors.primary]}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text variant="bodyLarge" style={{ color: theme.colors.onSurfaceVariant }}>
                {searchQuery ? '검색 결과가 없습니다.' : '등록된 민원이 없습니다.'}
              </Text>
              <Button 
                mode="contained" 
                onPress={() => navigation.navigate('Home', { screen: 'CreateComplaint' })}
                style={styles.createButton}
              >
                민원 등록하기
              </Button>
            </View>
          }
        />
      </View>

      {/* 스낵바 */}
      <Snackbar
        visible={snackbarVisible}
        onDismiss={() => setSnackbarVisible(false)}
        duration={3000}
        style={styles.snackbar}
      >
        {snackbarMessage}
      </Snackbar>
    </SafeAreaView>
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
  content: {
    flex: 1,
  },
  searchContainer: {
    padding: 16,
  },
  searchbar: {
    elevation: 0,
    borderRadius: 8,
  },
  listContainer: {
    padding: 16,
    paddingTop: 0,
  },
  complaintCard: {
    marginBottom: 12,
    elevation: 2,
    borderRadius: 8,
  },
  cardContent: {
    padding: 16,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  complaintTitle: {
    flex: 1,
    marginRight: 8,
    fontWeight: '600',
  },
  statusChip: {
    height: 28,
  },
  description: {
    marginBottom: 8,
    lineHeight: 18,
  },
  cardInfo: {
    marginTop: 4,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: 40,
  },
  createButton: {
    marginTop: 16,
  },
  snackbar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
});
