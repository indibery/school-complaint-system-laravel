import React, { useState, useEffect, useContext } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  Platform,
  KeyboardAvoidingView,
  Image,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  Chip,
  Portal,
  Dialog,
  List,
  useTheme,
  Surface,
  IconButton,
  Divider,
  HelperText,
  Snackbar,
  Badge,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import * as Location from 'expo-location';
import { AuthContext } from '../context/AuthContext';
import { COMPLAINT_CATEGORIES, PRIORITY_LEVELS } from '../constants/categories';
import { complaintAPI } from '../services/api';

export default function CreateComplaintScreen({ navigation }) {
  const theme = useTheme();
  const { user } = useContext(AuthContext);
  
  // 상태 관리
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    category: '',
    priority: 'medium',
    contact_info: '',
  });
  
  const [attachments, setAttachments] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showCategoryDialog, setShowCategoryDialog] = useState(false);
  const [showPriorityDialog, setShowPriorityDialog] = useState(false);
  const [errors, setErrors] = useState({});
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  
  // 디버깅 상태
  const [isDebugging, setIsDebugging] = useState(false);
  const [debugResults, setDebugResults] = useState(null);
  const [networkStatus, setNetworkStatus] = useState('unknown');
  
  // 컴포넌트 마운트 시 네트워크 상태 확인
  useEffect(() => {
    checkNetworkStatus();
  }, []);
  
  // 네트워크 상태 확인
  const checkNetworkStatus = async () => {
    try {
      const results = await testNetworkConnection();
      const publicApiWorking = results.some(r => r.name.includes('퍼블릭 API') && r.success);
      const v1ApiWorking = results.some(r => r.name.includes('카테고리 API') && r.success);
      
      if (publicApiWorking && v1ApiWorking) {
        setNetworkStatus('good');
      } else if (publicApiWorking) {
        setNetworkStatus('partial');
      } else {
        setNetworkStatus('poor');
      }
    } catch (error) {
      setNetworkStatus('error');
    }
  };
  
  // 네트워크 진단 실행 (개선된 버전)
  const runNetworkDiagnosis = async () => {
    setIsDebugging(true);
    try {
      // 1. 기본 네트워크 진단
      const diagnosis = await diagnoseNetworkIssues();
      setDebugResults(diagnosis);
      
      // 2. 추가 API 테스트
      const apiTests = await testAPIEndpoints();
      
      // 3. 종합 결과 분석
      const allTests = [...diagnosis.tests, ...apiTests];
      const successCount = allTests.filter(t => t.success).length;
      const totalCount = allTests.length;
      
      // 4. 상세 결과 표시
      let resultMessage = `성공: ${successCount}/${totalCount}\n\n`;
      
      // CSRF 관련 특별 체크
      const csrfError = allTests.find(t => t.statusCode === 419);
      if (csrfError) {
        resultMessage += '🚨 CSRF 토큰 오류 발견!\n- 서버에서 API 라우트의 CSRF 보호 해제 필요\n\n';
      }
      
      // 500 오류 체크
      const serverError = allTests.find(t => t.status === 500);
      if (serverError) {
        resultMessage += '🔧 서버 내부 오류 발견!\n- 서버 로그 확인 필요\n\n';
      }
      
      // 404 오류 체크
      const notFoundError = allTests.find(t => t.status === 404);
      if (notFoundError) {
        resultMessage += '🔍 API 경로 오류 발견!\n- 라우트 설정 확인 필요\n\n';
      }
      
      resultMessage += diagnosis.recommendations.join('\n');
      
      Alert.alert(
        '네트워크 진단 결과',
        resultMessage,
        [
          {
            text: '상세 보기',
            onPress: () => {
              console.log('📊 상세 진단 결과:', { diagnosis, apiTests });
              showSnackbar('콘솔에서 상세 결과를 확인하세요');
            },
          },
          { text: '확인' },
        ]
      );
      
    } catch (error) {
      Alert.alert('진단 오류', error.message);
    } finally {
      setIsDebugging(false);
    }
  };
  
  // 사용자 타입별 카테고리 필터링
  const getAvailableCategories = () => {
    if (user?.user_type === 'school_guard') {
      return COMPLAINT_CATEGORIES.filter(cat => 
        cat.userTypes.includes('school_guard')
      );
    } else {
      return COMPLAINT_CATEGORIES.filter(cat => 
        cat.userTypes.includes('parent')
      );
    }
  };
  
  // 카테고리별 템플릿 적용
  const applyCategoryTemplate = (category) => {
    const templates = {
      academic: {
        title: '학사 관련 민원',
        description: '문제 상황:\n\n발생 일시:\n\n관련 과목/교사:\n\n요청 사항:\n\n',
      },
      life: {
        title: '학교생활 관련 민원',
        description: '문제 상황:\n\n발생 장소:\n\n관련 학생:\n\n목격자:\n\n요청 사항:\n\n',
      },
      safety: {
        title: '안전 관련 민원',
        description: '안전 문제:\n\n발생 위치:\n\n위험 정도:\n\n긴급성:\n\n요청 조치:\n\n',
      },
      facility: {
        title: '시설 관련 민원',
        description: '시설 문제:\n\n위치:\n\n손상 정도:\n\n사용 불가 여부:\n\n수리 요청:\n\n',
      },
      environment: {
        title: '환경 관련 민원',
        description: '환경 문제:\n\n발생 구역:\n\n문제 정도:\n\n개선 요청:\n\n',
      },
      other: {
        title: '기타 민원',
        description: '민원 내용을 자세히 작성해주세요:\n\n\n\n',
      },
    };
    
    const template = templates[category.id];
    if (template) {
      setFormData(prev => ({
        ...prev,
        category: category.id,
        title: template.title,
        description: template.description,
      }));
      
      // 에러 초기화
      setErrors(prev => ({
        ...prev,
        category: null,
      }));
    }
  };
  
  // 이미지 선택 (에러 수정)
  const pickImage = async () => {
    try {
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('권한 필요', '사진 선택을 위해 갤러리 접근 권한이 필요합니다.');
        return;
      }
      
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: 'Images', // 간단한 방식으로 수정
        allowsEditing: true,
        aspect: [4, 3],
        quality: 0.8,
        allowsMultipleSelection: false,
      });
      
      if (!result.canceled && result.assets[0]) {
        const newAttachment = {
          id: Date.now().toString(),
          uri: result.assets[0].uri,
          type: 'image',
          name: `image_${Date.now()}.jpg`,
          size: result.assets[0].fileSize || 0,
        };
        setAttachments(prev => [...prev, newAttachment]);
        showSnackbar('이미지가 추가되었습니다.');
      }
    } catch (error) {
      console.error('Image picker error:', error);
      Alert.alert('오류', '이미지 선택 중 오류가 발생했습니다.');
    }
  };
  
  // 카메라 촬영 (에러 수정)
  const takePicture = async () => {
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('권한 필요', '사진 촬영을 위해 카메라 접근 권한이 필요합니다.');
        return;
      }
      
      const result = await ImagePicker.launchCameraAsync({
        mediaTypes: 'Images', // 간단한 방식으로 수정
        allowsEditing: true,
        aspect: [4, 3],
        quality: 0.8,
      });
      
      if (!result.canceled && result.assets[0]) {
        const newAttachment = {
          id: Date.now().toString(),
          uri: result.assets[0].uri,
          type: 'image',
          name: `photo_${Date.now()}.jpg`,
          size: result.assets[0].fileSize || 0,
        };
        setAttachments(prev => [...prev, newAttachment]);
        showSnackbar('사진이 추가되었습니다.');
      }
    } catch (error) {
      console.error('Camera error:', error);
      Alert.alert('오류', '사진 촬영 중 오류가 발생했습니다.');
    }
  };
  
  // 문서 선택
  const pickDocument = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        copyToCacheDirectory: true,
      });
      
      if (!result.canceled && result.assets[0]) {
        const newAttachment = {
          id: Date.now().toString(),
          uri: result.assets[0].uri,
          type: 'document',
          name: result.assets[0].name,
          size: result.assets[0].size || 0,
        };
        setAttachments(prev => [...prev, newAttachment]);
        showSnackbar('문서가 추가되었습니다.');
      }
    } catch (error) {
      console.error('Document picker error:', error);
      Alert.alert('오류', '문서 선택 중 오류가 발생했습니다.');
    }
  };
  
  // 첨부파일 삭제
  const removeAttachment = (id) => {
    setAttachments(prev => prev.filter(att => att.id !== id));
    showSnackbar('첨부파일이 삭제되었습니다.');
  };
  
  // 스낵바 표시
  const showSnackbar = (message) => {
    setSnackbarMessage(message);
    setSnackbarVisible(true);
  };
  
  // 유효성 검사
  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.title.trim()) {
      newErrors.title = '제목을 입력해주세요.';
    }
    
    if (!formData.description.trim()) {
      newErrors.description = '내용을 입력해주세요.';
    }
    
    if (!formData.category) {
      newErrors.category = '카테고리를 선택해주세요.';
    }
    
    if (!formData.contact_info.trim()) {
      newErrors.contact_info = '연락처를 입력해주세요.';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  
  // 실제 API를 통한 민원 제출 (CSRF 우회 포함)
  const handleSubmit = async () => {
    if (!validateForm()) {
      Alert.alert('입력 오류', '필수 항목을 모두 입력해주세요.');
      return;
    }
    
    try {
      setIsSubmitting(true);
      
      // 민원 등록 API 호출
      console.log('📤 민원 등록 API 호출 시작...', formData);
      
      const response = await complaintAPI.createComplaint({
        title: formData.title,
        description: formData.description,
        category: formData.category,
        priority: formData.priority,
        contact_info: formData.contact_info,
        attachments: attachments,
      });
      
      console.log('📥 민원 등록 API 응답:', response);
      
      if (response.success) {
        Alert.alert(
          '등록 완료',
          '민원이 성공적으로 등록되었습니다.',
          [
            {
              text: '확인',
              onPress: () => navigation.goBack(),
            },
          ]
        );
      } else {
        // API 실패 처리
        const errorMessage = response.message || '민원 등록에 실패했습니다.';
        Alert.alert('등록 실패', errorMessage);
      }
      
    } catch (error) {
      console.error('❌ 민원 등록 오류:', error);
      Alert.alert('네트워크 오류', `서버 연결에 실패했습니다.\n\n오류: ${error.message}`);
    } finally {
      setIsSubmitting(false);
    }
  };
  
  // 이미지 첨부 옵션
  const showImagePicker = () => {
    Alert.alert(
      '이미지 선택',
      '사진을 어떻게 추가하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        { text: '갤러리에서 선택', onPress: pickImage },
        { text: '카메라로 촬영', onPress: takePicture },
      ],
      { cancelable: true }
    );
  };
  
  // 네트워크 상태 색상
  const getNetworkStatusColor = () => {
    switch (networkStatus) {
      case 'good': return theme.colors.primary;
      case 'partial': return theme.colors.warning || '#FF9800';
      case 'poor': return theme.colors.error;
      default: return theme.colors.onSurfaceVariant;
    }
  };
  
  // 네트워크 상태 텍스트
  const getNetworkStatusText = () => {
    switch (networkStatus) {
      case 'good': return '양호';
      case 'partial': return '제한적';
      case 'poor': return '불량';
      default: return '확인중';
    }
  };
  
  const availableCategories = getAvailableCategories();
  const selectedCategory = availableCategories.find(cat => cat.id === formData.category);
  const selectedPriority = PRIORITY_LEVELS.find(opt => opt.id === formData.priority);
  
  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardAvoid}
      >
        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          {/* 헤더 */}
          <View style={styles.header}>
            <View style={styles.headerContent}>
              <Text variant="headlineSmall" style={[styles.title, { color: theme.colors.primary }]}>
                민원 등록
              </Text>
              <View style={styles.networkStatus}>
                <Badge 
                  size={8} 
                  style={{ backgroundColor: getNetworkStatusColor() }}
                />
                <Text 
                  variant="bodySmall" 
                  style={{ color: getNetworkStatusColor(), marginLeft: 6 }}
                >
                  네트워크: {getNetworkStatusText()}
                </Text>
              </View>
            </View>
            <Text variant="bodyMedium" style={[styles.subtitle, { color: theme.colors.onSurfaceVariant }]}>
              자세한 내용을 입력해주세요
            </Text>
          </View>
          
          {/* 디버깅 버튼 */}
          <Card style={[styles.section, { backgroundColor: theme.colors.secondaryContainer }]}>
            <Card.Content>
              <View style={styles.debugSection}>
                <Text variant="bodyMedium" style={styles.debugTitle}>
                  🔧 네트워크 진단
                </Text>
                <Button
                  mode="outlined"
                  onPress={runNetworkDiagnosis}
                  loading={isDebugging}
                  disabled={isDebugging}
                  icon="network"
                  style={styles.debugButton}
                >
                  {isDebugging ? '진단 중...' : '연결 상태 확인'}
                </Button>
              </View>
            </Card.Content>
          </Card>
          
          {/* 카테고리 선택 */}
          <Card style={styles.section}>
            <Card.Content>
              <Text variant="titleMedium" style={styles.sectionTitle}>
                카테고리 선택 *
              </Text>
              <Button
                mode="outlined"
                onPress={() => setShowCategoryDialog(true)}
                style={[
                  styles.selectButton,
                  errors.category && { borderColor: theme.colors.error }
                ]}
                contentStyle={styles.selectButtonContent}
                icon={selectedCategory?.icon}
              >
                {selectedCategory ? selectedCategory.label : '카테고리를 선택하세요'}
              </Button>
              {errors.category && (
                <HelperText type="error" visible={!!errors.category}>
                  {errors.category}
                </HelperText>
              )}
            </Card.Content>
          </Card>
          
          {/* 우선순위 선택 */}
          <Card style={styles.section}>
            <Card.Content>
              <Text variant="titleMedium" style={styles.sectionTitle}>
                우선순위
              </Text>
              <Button
                mode="outlined"
                onPress={() => setShowPriorityDialog(true)}
                style={styles.selectButton}
                contentStyle={styles.selectButtonContent}
                icon={selectedPriority?.icon}
              >
                <Text style={{ color: selectedPriority?.color }}>
                  {selectedPriority?.label || '보통'}
                </Text>
              </Button>
            </Card.Content>
          </Card>
          
          {/* 제목 입력 */}
          <Card style={styles.section}>
            <Card.Content>
              <TextInput
                label="제목 *"
                value={formData.title}
                onChangeText={(text) => {
                  setFormData(prev => ({ ...prev, title: text }));
                  if (errors.title) {
                    setErrors(prev => ({ ...prev, title: null }));
                  }
                }}
                mode="outlined"
                style={styles.textInput}
                maxLength={100}
                error={!!errors.title}
                right={<TextInput.Affix text={`${formData.title.length}/100`} />}
              />
              {errors.title && (
                <HelperText type="error" visible={!!errors.title}>
                  {errors.title}
                </HelperText>
              )}
            </Card.Content>
          </Card>
          
          {/* 내용 입력 */}
          <Card style={styles.section}>
            <Card.Content>
              <TextInput
                label="내용 *"
                value={formData.description}
                onChangeText={(text) => {
                  setFormData(prev => ({ ...prev, description: text }));
                  if (errors.description) {
                    setErrors(prev => ({ ...prev, description: null }));
                  }
                }}
                mode="outlined"
                multiline
                numberOfLines={8}
                style={styles.textAreaInput}
                maxLength={1000}
                error={!!errors.description}
                right={<TextInput.Affix text={`${formData.description.length}/1000`} />}
              />
              {errors.description && (
                <HelperText type="error" visible={!!errors.description}>
                  {errors.description}
                </HelperText>
              )}
            </Card.Content>
          </Card>
          
          {/* 연락처 정보 */}
          <Card style={styles.section}>
            <Card.Content>
              <TextInput
                label="연락처 *"
                value={formData.contact_info}
                onChangeText={(text) => {
                  setFormData(prev => ({ ...prev, contact_info: text }));
                  if (errors.contact_info) {
                    setErrors(prev => ({ ...prev, contact_info: null }));
                  }
                }}
                mode="outlined"
                style={styles.textInput}
                keyboardType="phone-pad"
                error={!!errors.contact_info}
                placeholder="예: 010-1234-5678"
              />
              {errors.contact_info && (
                <HelperText type="error" visible={!!errors.contact_info}>
                  {errors.contact_info}
                </HelperText>
              )}
            </Card.Content>
          </Card>
          
          {/* 첨부파일 */}
          <Card style={styles.section}>
            <Card.Content>
              <Text variant="titleMedium" style={styles.sectionTitle}>
                첨부파일 ({attachments.length}/5)
              </Text>
              <View style={styles.attachmentButtons}>
                <Button
                  mode="outlined"
                  onPress={showImagePicker}
                  icon="camera"
                  style={styles.attachmentButton}
                  disabled={attachments.length >= 5}
                >
                  사진 첨부
                </Button>
                <Button
                  mode="outlined"
                  onPress={pickDocument}
                  icon="file-document"
                  style={styles.attachmentButton}
                  disabled={attachments.length >= 5}
                >
                  문서 첨부
                </Button>
              </View>
              
              {/* 첨부파일 목록 */}
              {attachments.map((attachment) => (
                <Surface key={attachment.id} style={styles.attachmentItem}>
                  <View style={styles.attachmentInfo}>
                    {attachment.type === 'image' && (
                      <Image source={{ uri: attachment.uri }} style={styles.attachmentPreview} />
                    )}
                    <View style={styles.attachmentDetails}>
                      <Text variant="bodyMedium" numberOfLines={1}>
                        {attachment.name}
                      </Text>
                      <Text variant="bodySmall" style={styles.attachmentSize}>
                        {Math.round(attachment.size / 1024)} KB • {attachment.type === 'image' ? '이미지' : '문서'}
                      </Text>
                    </View>
                  </View>
                  <IconButton
                    icon="close"
                    size={20}
                    onPress={() => removeAttachment(attachment.id)}
                  />
                </Surface>
              ))}
            </Card.Content>
          </Card>
          
          {/* 제출 버튼 */}
          <Button
            mode="contained"
            onPress={handleSubmit}
            loading={isSubmitting}
            disabled={isSubmitting}
            style={styles.submitButton}
            contentStyle={styles.submitButtonContent}
          >
            {isSubmitting ? '등록 중...' : '민원 등록'}
          </Button>
        </ScrollView>
      </KeyboardAvoidingView>
      
      {/* 카테고리 선택 다이얼로그 */}
      <Portal>
        <Dialog visible={showCategoryDialog} onDismiss={() => setShowCategoryDialog(false)}>
          <Dialog.Title>카테고리 선택</Dialog.Title>
          <Dialog.Content>
            <ScrollView style={styles.dialogContent}>
              {availableCategories.map((category) => (
                <List.Item
                  key={category.id}
                  title={category.label}
                  description={category.description}
                  left={(props) => <List.Icon {...props} icon={category.icon} color={category.color} />}
                  onPress={() => {
                    applyCategoryTemplate(category);
                    setShowCategoryDialog(false);
                  }}
                  style={styles.categoryItem}
                />
              ))}
            </ScrollView>
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setShowCategoryDialog(false)}>취소</Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
      
      {/* 우선순위 선택 다이얼로그 */}
      <Portal>
        <Dialog visible={showPriorityDialog} onDismiss={() => setShowPriorityDialog(false)}>
          <Dialog.Title>우선순위 선택</Dialog.Title>
          <Dialog.Content>
            {PRIORITY_LEVELS.map((option) => (
              <List.Item
                key={option.id}
                title={option.label}
                description={option.description}
                left={(props) => (
                  <List.Icon {...props} icon={option.icon} color={option.color} />
                )}
                onPress={() => {
                  setFormData(prev => ({ ...prev, priority: option.id }));
                  setShowPriorityDialog(false);
                }}
                style={styles.priorityItem}
              />
            ))}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setShowPriorityDialog(false)}>취소</Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
      
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
  keyboardAvoid: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    marginBottom: 24,
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  title: {
    fontWeight: 'bold',
  },
  subtitle: {
    opacity: 0.7,
  },
  networkStatus: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  debugSection: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  debugTitle: {
    fontWeight: 'bold',
  },
  debugButton: {
    minWidth: 140,
  },
  section: {
    marginBottom: 16,
    elevation: 2,
  },
  sectionTitle: {
    marginBottom: 12,
    fontWeight: 'bold',
  },
  selectButton: {
    marginBottom: 8,
  },
  selectButtonContent: {
    height: 48,
  },
  textInput: {
    backgroundColor: 'transparent',
  },
  textAreaInput: {
    backgroundColor: 'transparent',
    minHeight: 120,
  },
  attachmentButtons: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 16,
  },
  attachmentButton: {
    flex: 1,
  },
  attachmentItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    marginBottom: 8,
    borderRadius: 8,
    elevation: 1,
  },
  attachmentInfo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
  },
  attachmentPreview: {
    width: 40,
    height: 40,
    borderRadius: 8,
    marginRight: 12,
  },
  attachmentDetails: {
    flex: 1,
  },
  attachmentSize: {
    opacity: 0.7,
    marginTop: 2,
  },
  submitButton: {
    marginTop: 16,
  },
  submitButtonContent: {
    height: 48,
  },
  dialogContent: {
    maxHeight: 300,
  },
  categoryItem: {
    paddingVertical: 8,
  },
  priorityItem: {
    paddingVertical: 8,
  },
  snackbar: {
    bottom: 16,
  },
});
