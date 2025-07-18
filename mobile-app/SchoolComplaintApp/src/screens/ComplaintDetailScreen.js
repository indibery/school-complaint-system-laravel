import React, { useState, useEffect } from 'react';
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
  Chip,
  TextInput,
  useTheme,
  ActivityIndicator,
  Divider,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';

export default function ComplaintDetailScreen({ route, navigation }) {
  const theme = useTheme();
  const { complaintId, complaint } = route.params || {};
  const [complaintData, setComplaintData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [newComment, setNewComment] = useState('');
  const [submittingComment, setSubmittingComment] = useState(false);

  useEffect(() => {
    // 민원 상세 정보 로드
    setTimeout(() => {
      setComplaintData({
        id: complaintId,
        title: complaint?.title || '민원 제목',
        description: complaint?.description || '민원 내용입니다.',
        status: complaint?.status || 'pending',
        category: complaint?.category || '기타',
        createdAt: complaint?.createdAt || '2024-07-08',
        priority: 'medium',
        contact_info: '010-1234-5678',
        location: '서울시 강남구',
        comments: [
          {
            id: 1,
            content: '민원을 접수하였습니다. 검토 후 처리하겠습니다.',
            author: '관리자',
            createdAt: '2024-07-08 14:30',
            isAdmin: true,
          }
        ]
      });
      setLoading(false);
    }, 1000);
  }, [complaintId, complaint]);

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
      case 'pending': return '접수 대기';
      case 'processing': return '처리 중';
      case 'completed': return '완료';
      case 'rejected': return '거절';
      default: return '알 수 없음';
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'low': return '#4CAF50';
      case 'medium': return '#FF9800';
      case 'high': return '#F44336';
      default: return '#757575';
    }
  };

  const getPriorityLabel = (priority) => {
    switch (priority) {
      case 'low': return '낮음';
      case 'medium': return '보통';
      case 'high': return '높음';
      default: return '보통';
    }
  };

  const handleAddComment = async () => {
    if (!newComment.trim()) {
      Alert.alert('입력 오류', '댓글 내용을 입력해주세요.');
      return;
    }

    try {
      setSubmittingComment(true);
      
      // 더미 댓글 추가
      const comment = {
        id: Date.now(),
        content: newComment,
        author: '사용자',
        createdAt: new Date().toLocaleString(),
        isAdmin: false,
      };

      setComplaintData(prev => ({
        ...prev,
        comments: [...prev.comments, comment]
      }));

      setNewComment('');
      Alert.alert('완료', '댓글이 추가되었습니다.');
    } catch (error) {
      Alert.alert('오류', '댓글 추가 중 오류가 발생했습니다.');
    } finally {
      setSubmittingComment(false);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text style={[styles.loadingText, { color: theme.colors.onSurfaceVariant }]}>
            민원 정보를 불러오는 중...
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!complaintData) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
        <View style={styles.errorContainer}>
          <Text variant="bodyLarge" style={{ color: theme.colors.onSurfaceVariant }}>
            민원 정보를 찾을 수 없습니다.
          </Text>
          <Button 
            mode="contained" 
            onPress={() => navigation.goBack()}
            style={styles.backButton}
          >
            돌아가기
          </Button>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <ScrollView style={styles.scrollView} showsVerticalScrollIndicator={false}>
        {/* 민원 기본 정보 */}
        <Card style={[styles.card, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.cardContent}>
            <View style={styles.header}>
              <Text variant="headlineSmall" style={styles.title}>
                {complaintData.title}
              </Text>
              <View style={styles.statusContainer}>
                <Chip 
                  style={[styles.statusChip, { backgroundColor: getStatusColor(complaintData.status) + '20' }]}
                  textStyle={{ color: getStatusColor(complaintData.status), fontWeight: 'bold' }}
                >
                  {getStatusLabel(complaintData.status)}
                </Chip>
                <Chip 
                  style={[styles.priorityChip, { backgroundColor: getPriorityColor(complaintData.priority) + '20' }]}
                  textStyle={{ color: getPriorityColor(complaintData.priority) }}
                >
                  {getPriorityLabel(complaintData.priority)}
                </Chip>
              </View>
            </View>
            
            <View style={styles.infoRow}>
              <MaterialIcons name="category" size={16} color={theme.colors.onSurfaceVariant} />
              <Text variant="bodyMedium" style={[styles.infoText, { color: theme.colors.onSurfaceVariant }]}>
                {complaintData.category}
              </Text>
            </View>
            
            <View style={styles.infoRow}>
              <MaterialIcons name="access-time" size={16} color={theme.colors.onSurfaceVariant} />
              <Text variant="bodyMedium" style={[styles.infoText, { color: theme.colors.onSurfaceVariant }]}>
                {complaintData.createdAt}
              </Text>
            </View>

            <View style={styles.infoRow}>
              <MaterialIcons name="phone" size={16} color={theme.colors.onSurfaceVariant} />
              <Text variant="bodyMedium" style={[styles.infoText, { color: theme.colors.onSurfaceVariant }]}>
                {complaintData.contact_info}
              </Text>
            </View>

            {complaintData.location && (
              <View style={styles.infoRow}>
                <MaterialIcons name="location-on" size={16} color={theme.colors.onSurfaceVariant} />
                <Text variant="bodyMedium" style={[styles.infoText, { color: theme.colors.onSurfaceVariant }]}>
                  {complaintData.location}
                </Text>
              </View>
            )}
          </Card.Content>
        </Card>

        {/* 민원 내용 */}
        <Card style={[styles.card, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.cardContent}>
            <Text variant="titleMedium" style={styles.sectionTitle}>
              민원 내용
            </Text>
            <Text variant="bodyMedium" style={[styles.description, { color: theme.colors.onSurface }]}>
              {complaintData.description}
            </Text>
          </Card.Content>
        </Card>

        {/* 댓글 섹션 */}
        <Card style={[styles.card, { backgroundColor: theme.colors.surface }]}>
          <Card.Content style={styles.cardContent}>
            <Text variant="titleMedium" style={styles.sectionTitle}>
              처리 현황 및 댓글 ({complaintData.comments.length})
            </Text>
            
            {complaintData.comments.map((comment) => (
              <View key={comment.id} style={styles.comment}>
                <View style={styles.commentHeader}>
                  <View style={styles.commentAuthor}>
                    <MaterialIcons 
                      name={comment.isAdmin ? "admin-panel-settings" : "person"} 
                      size={16} 
                      color={comment.isAdmin ? theme.colors.primary : theme.colors.onSurfaceVariant} 
                    />
                    <Text 
                      variant="bodyMedium" 
                      style={[
                        styles.authorName, 
                        { color: comment.isAdmin ? theme.colors.primary : theme.colors.onSurfaceVariant }
                      ]}
                    >
                      {comment.author}
                    </Text>
                  </View>
                  <Text variant="bodySmall" style={{ color: theme.colors.onSurfaceVariant }}>
                    {comment.createdAt}
                  </Text>
                </View>
                <Text variant="bodyMedium" style={styles.commentContent}>
                  {comment.content}
                </Text>
                {comment.id !== complaintData.comments[complaintData.comments.length - 1].id && (
                  <Divider style={styles.commentDivider} />
                )}
              </View>
            ))}

            {/* 새 댓글 입력 */}
            <View style={styles.newCommentSection}>
              <TextInput
                label="문의사항이나 추가 설명을 입력하세요"
                value={newComment}
                onChangeText={setNewComment}
                mode="outlined"
                multiline
                numberOfLines={3}
                style={styles.commentInput}
              />
              <Button
                mode="contained"
                onPress={handleAddComment}
                loading={submittingComment}
                disabled={submittingComment || !newComment.trim()}
                style={styles.commentButton}
              >
                댓글 추가
              </Button>
            </View>
          </Card.Content>
        </Card>
      </ScrollView>
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  backButton: {
    marginTop: 16,
  },
  scrollView: {
    flex: 1,
  },
  card: {
    margin: 16,
    marginBottom: 0,
    elevation: 2,
    borderRadius: 12,
  },
  cardContent: {
    padding: 20,
  },
  header: {
    marginBottom: 16,
  },
  title: {
    fontWeight: 'bold',
    marginBottom: 12,
  },
  statusContainer: {
    flexDirection: 'row',
    gap: 8,
  },
  statusChip: {
    height: 32,
  },
  priorityChip: {
    height: 32,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  infoText: {
    marginLeft: 8,
  },
  sectionTitle: {
    fontWeight: 'bold',
    marginBottom: 12,
  },
  description: {
    lineHeight: 22,
  },
  comment: {
    marginBottom: 16,
  },
  commentHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  commentAuthor: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  authorName: {
    marginLeft: 6,
    fontWeight: '600',
  },
  commentContent: {
    lineHeight: 20,
  },
  commentDivider: {
    marginTop: 16,
  },
  newCommentSection: {
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
  },
  commentInput: {
    marginBottom: 12,
    backgroundColor: 'transparent',
  },
  commentButton: {
    alignSelf: 'flex-end',
  },
});
