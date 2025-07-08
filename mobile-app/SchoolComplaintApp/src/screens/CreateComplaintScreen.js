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
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import * as Location from 'expo-location';
import { AuthContext } from '../context/AuthContext';
import { complaintService } from '../services/api';
import { COMPLAINT_CATEGORIES, PRIORITY_LEVELS } from '../constants/categories';

export default function CreateComplaintScreen({ navigation }) {
  const theme = useTheme();
  const { user } = useContext(AuthContext);
  
  // 상태 관리
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    category: '',
    priority: 'medium',
    location: '',
    contact_info: user?.phone || '',
  });
  
  const [attachments, setAttachments] = useState([]);
  const [selectedLocation, setSelectedLocation] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showCategoryDialog, setShowCategoryDialog] = useState(false);
  const [showPriorityDialog, setShowPriorityDialog] = useState(false);
  const [isLocationLoading, setIsLocationLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  
  // 사용자 타입별 카테고리 필터링
  const getAvailableCategories = () => {
    if (user?.type === 'school_guard') {
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
        contact_info: '학부모 연락처를 입력해주세요.',
      },
      life: {
        title: '학교생활 관련 민원',
        description: '문제 상황:\n\n발생 장소:\n\n관련 학생:\n\n목격자:\n\n요청 사항:\n\n',
        contact_info: '학부모 연락처를 입력해주세요.',
      },
      safety: {
        title: '안전 관련 민원',
        description: '안전 문제:\n\n발생 위치:\n\n위험 정도:\n\n긴급성:\n\n요청 조치:\n\n',
        contact_info: '즉시 연락 가능한 번호를 입력해주세요.',
      },
      facility: {
        title: '시설 관련 민원',
        description: '시설 문제:\n\n위치:\n\n손상 정도:\n\n사용 불가 여부:\n\n수리 요청:\n\n',
        contact_info: '담당자 연락처를 입력해주세요.',
      },
      environment: {
        title: '환경 관련 민원',
        description: '환경 문제:\n\n발생 구역:\n\n문제 정도:\n\n개선 요청:\n\n',
        contact_info: '담당자 연락처를 입력해주세요.',
      },
      other: {
        title: '기타 민원',
        description: '민원 내용을 자세히 작성해주세요:\n\n\n\n',
        contact_info: '연락처를 입력해주세요.',
      },
    };
    
    const template = templates[category.id];
    if (template) {
      setFormData(prev => ({
        ...prev,
        category: category.id,
        title: template.title,
        description: template.description,
        contact_info: template.contact_info,
      }));
      
      // 에러 초기화
      setErrors(prev => ({
        ...prev,
        category: null,
      }));
    }
  };
  
  // 이미지 선택
  const pickImage = async () => {
    try {
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('권한 필요', '사진 선택을 위해 갤러리 접근 권한이 필요합니다.');
        return;
      }
      
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
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
  
  // 카메라 촬영
  const takePicture = async () => {
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('권한 필요', '사진 촬영을 위해 카메라 접근 권한이 필요합니다.');
        return;
      }
      
      const result = await ImagePicker.launchCameraAsync({
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
  
  // 현재 위치 가져오기
  const getCurrentLocation = async () => {
    try {
      setIsLocationLoading(true);
      
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('권한 필요', '위치 정보를 가져오기 위해 위치 접근 권한이 필요합니다.');
        return;
      }
      
      const location = await Location.getCurrentPositionAsync({});
      const address = await Location.reverseGeocodeAsync({
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      });
      
      if (address[0]) {
        const locationString = `${address[0].region || ''} ${address[0].city || ''} ${address[0].street || ''} ${address[0].name || ''}`.trim();
        setFormData(prev => ({
          ...prev,
          location: locationString,
        }));
        setSelectedLocation(location.coords);
        showSnackbar('현재 위치가 설정되었습니다.');
      }
    } catch (error) {
      console.error('Location error:', error);
      Alert.alert('오류', '위치 정보를 가져올 수 없습니다.');
    } finally {
      setIsLocationLoading(false);
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
  
  // 민원 제출
  const handleSubmit = async () => {
    if (!validateForm()) {
      Alert.alert('입력 오류', '필수 항목을 모두 입력해주세요.');
      return;
    }
    
    try {
      setIsSubmitting(true);
      
      // 민원 생성
      const complaintData = {
        ...formData,
        priority: formData.priority,
        location: formData.location || null,
        coordinates: selectedLocation ? {
          latitude: selectedLocation.latitude,
          longitude: selectedLocation.longitude,
        } : null,
      };
      
      const response = await complaintService.createComplaint(complaintData);
      
      if (response.success && response.data) {
        const complaintId = response.data.id;
        
        // 첨부파일 업로드
        if (attachments.length > 0) {
          for (const attachment of attachments) {
            try {
              await complaintService.uploadAttachment(complaintId, attachment);
            } catch (error) {
              console.error('Attachment upload error:', error);
              // 첨부파일 업로드 실패해도 민원 등록은 성공으로 처리
            }
          }
        }
        
        Alert.alert(
          '등록 완료',
          '민원이 성공적으로 등록되었습니다.',
          [
            {
              text: '확인',
              onPress: () => {
                navigation.goBack();
                // 목록 화면 새로고침 트리거
                navigation.navigate('ComplaintList', { refresh: Date.now() });
              },
            },
          ]
        );
      } else {
        throw new Error(response.message || '민원 등록에 실패했습니다.');
      }
    } catch (error) {
      console.error('Submit error:', error);
      Alert.alert('오류', error.message || '민원 등록 중 오류가 발생했습니다.');
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
            <Text variant="headlineSmall" style={[styles.title, { color: theme.colors.primary }]}>
              민원 등록
            </Text>
            <Text variant="bodyMedium" style={[styles.subtitle, { color: theme.colors.onSurfaceVariant }]}>
              자세한 내용을 입력해주세요
            </Text>
          </View>
          
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
          
          {/* 위치 정보 */}
          <Card style={styles.section}>
            <Card.Content>
              <Text variant="titleMedium" style={styles.sectionTitle}>
                위치 정보
              </Text>
              <TextInput
                label="위치"
                value={formData.location}
                onChangeText={(text) => setFormData(prev => ({ ...prev, location: text }))}
                mode="outlined"
                style={styles.textInput}
                right={
                  <TextInput.Icon
                    icon="crosshairs-gps"
                    onPress={getCurrentLocation}
                    loading={isLocationLoading}
                  />
                }
              />
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
  title: {
    fontWeight: 'bold',
    marginBottom: 4,
  },
  subtitle: {
    opacity: 0.7,
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
