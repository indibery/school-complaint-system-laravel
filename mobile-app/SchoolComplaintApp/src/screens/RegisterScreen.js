import React, { useState } from 'react';
import {
  View,
  ScrollView,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  Alert,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  RadioButton,
  useTheme,
  Snackbar,
  ProgressBar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { authAPI } from '../services/api';
import { USER_TYPES } from '../constants/config';
import { validation, errorUtils } from '../utils/helpers';

export default function RegisterScreen({ navigation }) {
  const theme = useTheme();
  const [currentStep, setCurrentStep] = useState(1);
  const [isLoading, setIsLoading] = useState(false);
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [formErrors, setFormErrors] = useState({});

  const [formData, setFormData] = useState({
    // 기본 정보
    name: '',
    email: '',
    password: '',
    passwordConfirm: '',
    phone: '',
    userType: USER_TYPES.PARENT.id,
    
    // 학부모용 추가 필드
    childName: '',
    childGrade: '',
    childClass: '',
    
    // 학교지킴이용 추가 필드
    employeeId: '',
    department: '',
  });

  const totalSteps = 3;

  const validateStep = (step) => {
    const errors = {};

    if (step === 1) {
      // 기본 정보 검증
      if (!validation.isRequired(formData.name)) {
        errors.name = '이름을 입력해주세요.';
      }

      if (!validation.isRequired(formData.email)) {
        errors.email = '이메일을 입력해주세요.';
      } else if (!validation.isValidEmail(formData.email)) {
        errors.email = '올바른 이메일 형식을 입력해주세요.';
      }

      if (!validation.isRequired(formData.password)) {
        errors.password = '비밀번호를 입력해주세요.';
      } else if (!validation.isValidPassword(formData.password)) {
        errors.password = '비밀번호는 6자 이상이어야 합니다.';
      }

      if (!validation.isRequired(formData.passwordConfirm)) {
        errors.passwordConfirm = '비밀번호 확인을 입력해주세요.';
      } else if (formData.password !== formData.passwordConfirm) {
        errors.passwordConfirm = '비밀번호가 일치하지 않습니다.';
      }

      if (formData.phone && !validation.isValidPhone(formData.phone)) {
        errors.phone = '올바른 전화번호 형식을 입력해주세요.';
      }
    }

    if (step === 3) {
      // 추가 정보 검증
      if (formData.userType === USER_TYPES.PARENT.id) {
        if (!validation.isRequired(formData.childName)) {
          errors.childName = '자녀 이름을 입력해주세요.';
        }
        if (!validation.isRequired(formData.childGrade)) {
          errors.childGrade = '학년을 입력해주세요.';
        } else if (!validation.isNumeric(formData.childGrade)) {
          errors.childGrade = '학년은 숫자만 입력해주세요.';
        }
        if (!validation.isRequired(formData.childClass)) {
          errors.childClass = '반을 입력해주세요.';
        } else if (!validation.isNumeric(formData.childClass)) {
          errors.childClass = '반은 숫자만 입력해주세요.';
        }
      } else if (formData.userType === USER_TYPES.GUARD.id) {
        if (!validation.isRequired(formData.employeeId)) {
          errors.employeeId = '직원번호를 입력해주세요.';
        }
        if (!validation.isRequired(formData.department)) {
          errors.department = '부서를 입력해주세요.';
        }
      }
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(currentStep + 1);
    }
  };

  const handleBack = () => {
    setCurrentStep(currentStep - 1);
  };

  const handleRegister = async () => {
    if (!validateStep(currentStep)) {
      return;
    }

    setIsLoading(true);
    try {
      const registerData = {
        name: formData.name.trim(),
        email: formData.email.trim(),
        password: formData.password,
        password_confirmation: formData.passwordConfirm,
        user_type: formData.userType,
        phone: formData.phone.trim(),
      };

      // 사용자 타입별 추가 데이터
      if (formData.userType === USER_TYPES.PARENT.id) {
        registerData.child_name = formData.childName.trim();
        registerData.child_grade = formData.childGrade;
        registerData.child_class = formData.childClass;
      } else if (formData.userType === USER_TYPES.GUARD.id) {
        registerData.employee_id = formData.employeeId.trim();
        registerData.department = formData.department.trim();
      }

      await authAPI.register(registerData);
      
      setSnackbarMessage('회원가입이 완료되었습니다!');
      setSnackbarVisible(true);
      
      // 3초 후 로그인 화면으로 이동
      setTimeout(() => {
        navigation.navigate('Login');
      }, 3000);
    } catch (error) {
      const errorMessage = errorUtils.getErrorMessage(error);
      setSnackbarMessage(errorMessage);
      setSnackbarVisible(true);
      errorUtils.logError(error, 'Register');
    } finally {
      setIsLoading(false);
    }
  };

  const updateFormData = (key, value) => {
    setFormData(prev => ({ ...prev, [key]: value }));
    
    // 필드 수정 시 해당 필드의 에러 클리어
    if (formErrors[key]) {
      setFormErrors(prev => ({ ...prev, [key]: undefined }));
    }
  };

  const renderStep1 = () => (
    <View style={styles.stepContainer}>
      <Text variant="headlineSmall" style={styles.stepTitle}>
        기본 정보
      </Text>
      <Text variant="bodyMedium" style={styles.stepDescription}>
        회원가입을 위한 기본 정보를 입력해주세요.
      </Text>

      <TextInput
        label="이름 *"
        value={formData.name}
        onChangeText={(text) => updateFormData('name', text)}
        error={!!formErrors.name}
        left={<TextInput.Icon icon="account" />}
        style={styles.input}
      />
      {formErrors.name && (
        <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
          {formErrors.name}
        </Text>
      )}

      <TextInput
        label="이메일 *"
        value={formData.email}
        onChangeText={(text) => updateFormData('email', text)}
        keyboardType="email-address"
        autoCapitalize="none"
        error={!!formErrors.email}
        left={<TextInput.Icon icon="email" />}
        style={styles.input}
      />
      {formErrors.email && (
        <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
          {formErrors.email}
        </Text>
      )}

      <TextInput
        label="전화번호"
        value={formData.phone}
        onChangeText={(text) => updateFormData('phone', text)}
        keyboardType="phone-pad"
        placeholder="010-1234-5678"
        error={!!formErrors.phone}
        left={<TextInput.Icon icon="phone" />}
        style={styles.input}
      />
      {formErrors.phone && (
        <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
          {formErrors.phone}
        </Text>
      )}

      <TextInput
        label="비밀번호 *"
        value={formData.password}
        onChangeText={(text) => updateFormData('password', text)}
        secureTextEntry
        error={!!formErrors.password}
        left={<TextInput.Icon icon="lock" />}
        style={styles.input}
      />
      {formErrors.password && (
        <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
          {formErrors.password}
        </Text>
      )}

      <TextInput
        label="비밀번호 확인 *"
        value={formData.passwordConfirm}
        onChangeText={(text) => updateFormData('passwordConfirm', text)}
        secureTextEntry
        error={!!formErrors.passwordConfirm}
        left={<TextInput.Icon icon="lock-check" />}
        style={styles.input}
      />
      {formErrors.passwordConfirm && (
        <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
          {formErrors.passwordConfirm}
        </Text>
      )}
    </View>
  );

  const renderStep2 = () => (
    <View style={styles.stepContainer}>
      <Text variant="headlineSmall" style={styles.stepTitle}>
        사용자 구분
      </Text>
      <Text variant="bodyMedium" style={styles.stepDescription}>
        해당하는 사용자 유형을 선택해주세요.
      </Text>

      <RadioButton.Group
        onValueChange={(value) => updateFormData('userType', value)}
        value={formData.userType}
      >
        <View style={styles.radioContainer}>
          <Card 
            style={[
              styles.radioCard,
              formData.userType === USER_TYPES.PARENT.id && {
                backgroundColor: theme.colors.primaryContainer,
              }
            ]}
            onPress={() => updateFormData('userType', USER_TYPES.PARENT.id)}
          >
            <Card.Content>
              <View style={styles.radioHeader}>
                <MaterialIcons 
                  name="family-restroom" 
                  size={32} 
                  color={theme.colors.primary} 
                />
                <RadioButton value={USER_TYPES.PARENT.id} />
              </View>
              <Text variant="titleLarge" style={styles.radioTitle}>
                {USER_TYPES.PARENT.name}
              </Text>
              <Text variant="bodyMedium" style={styles.radioDescription}>
                {USER_TYPES.PARENT.description}
              </Text>
            </Card.Content>
          </Card>

          <Card 
            style={[
              styles.radioCard,
              formData.userType === USER_TYPES.GUARD.id && {
                backgroundColor: theme.colors.primaryContainer,
              }
            ]}
            onPress={() => updateFormData('userType', USER_TYPES.GUARD.id)}
          >
            <Card.Content>
              <View style={styles.radioHeader}>
                <MaterialIcons 
                  name="security" 
                  size={32} 
                  color={theme.colors.primary} 
                />
                <RadioButton value={USER_TYPES.GUARD.id} />
              </View>
              <Text variant="titleLarge" style={styles.radioTitle}>
                {USER_TYPES.GUARD.name}
              </Text>
              <Text variant="bodyMedium" style={styles.radioDescription}>
                {USER_TYPES.GUARD.description}
              </Text>
            </Card.Content>
          </Card>
        </View>
      </RadioButton.Group>
    </View>
  );

  const renderStep3 = () => (
    <View style={styles.stepContainer}>
      <Text variant="headlineSmall" style={styles.stepTitle}>
        추가 정보
      </Text>
      <Text variant="bodyMedium" style={styles.stepDescription}>
        {formData.userType === USER_TYPES.PARENT.id 
          ? '자녀 정보를 입력해주세요.' 
          : '직원 정보를 입력해주세요.'}
      </Text>

      {formData.userType === USER_TYPES.PARENT.id ? (
        <>
          <TextInput
            label="자녀 이름 *"
            value={formData.childName}
            onChangeText={(text) => updateFormData('childName', text)}
            error={!!formErrors.childName}
            left={<TextInput.Icon icon="account-child" />}
            style={styles.input}
          />
          {formErrors.childName && (
            <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
              {formErrors.childName}
            </Text>
          )}

          <TextInput
            label="학년 *"
            value={formData.childGrade}
            onChangeText={(text) => updateFormData('childGrade', text)}
            keyboardType="numeric"
            placeholder="1"
            error={!!formErrors.childGrade}
            left={<TextInput.Icon icon="school" />}
            style={styles.input}
          />
          {formErrors.childGrade && (
            <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
              {formErrors.childGrade}
            </Text>
          )}

          <TextInput
            label="반 *"
            value={formData.childClass}
            onChangeText={(text) => updateFormData('childClass', text)}
            keyboardType="numeric"
            placeholder="1"
            error={!!formErrors.childClass}
            left={<TextInput.Icon icon="domain" />}
            style={styles.input}
          />
          {formErrors.childClass && (
            <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
              {formErrors.childClass}
            </Text>
          )}
        </>
      ) : (
        <>
          <TextInput
            label="직원번호 *"
            value={formData.employeeId}
            onChangeText={(text) => updateFormData('employeeId', text)}
            error={!!formErrors.employeeId}
            left={<TextInput.Icon icon="badge-account" />}
            style={styles.input}
          />
          {formErrors.employeeId && (
            <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
              {formErrors.employeeId}
            </Text>
          )}

          <TextInput
            label="부서 *"
            value={formData.department}
            onChangeText={(text) => updateFormData('department', text)}
            placeholder="예: 시설관리팀"
            error={!!formErrors.department}
            left={<TextInput.Icon icon="office-building" />}
            style={styles.input}
          />
          {formErrors.department && (
            <Text variant="bodySmall" style={[styles.errorText, { color: theme.colors.error }]}>
              {formErrors.department}
            </Text>
          )}
        </>
      )}
    </View>
  );

  const renderCurrentStep = () => {
    switch (currentStep) {
      case 1:
        return renderStep1();
      case 2:
        return renderStep2();
      case 3:
        return renderStep3();
      default:
        return renderStep1();
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardAvoidingView}
      >
        <View style={styles.header}>
          <Text variant="headlineMedium" style={[styles.title, { color: theme.colors.primary }]}>
            회원가입
          </Text>
          <Text variant="bodyMedium" style={styles.subtitle}>
            단계 {currentStep}/{totalSteps}
          </Text>
          <ProgressBar
            progress={currentStep / totalSteps}
            style={styles.progressBar}
            color={theme.colors.primary}
          />
        </View>

        <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
          <Card style={styles.card}>
            <Card.Content>
              {renderCurrentStep()}
            </Card.Content>
          </Card>
        </ScrollView>

        <View style={styles.footer}>
          <View style={styles.buttonContainer}>
            {currentStep > 1 && (
              <Button
                mode="outlined"
                onPress={handleBack}
                style={[styles.button, styles.backButton]}
              >
                이전
              </Button>
            )}
            
            {currentStep < totalSteps ? (
              <Button
                mode="contained"
                onPress={handleNext}
                style={[styles.button, styles.nextButton]}
              >
                다음
              </Button>
            ) : (
              <Button
                mode="contained"
                onPress={handleRegister}
                loading={isLoading}
                disabled={isLoading}
                style={[styles.button, styles.registerButton]}
              >
                {isLoading ? '가입 중...' : '회원가입 완료'}
              </Button>
            )}
          </View>

          <Button
            mode="text"
            onPress={() => navigation.navigate('Login')}
            style={styles.loginButton}
          >
            이미 계정이 있으신가요? 로그인
          </Button>
        </View>
      </KeyboardAvoidingView>

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
  keyboardAvoidingView: {
    flex: 1,
  },
  header: {
    padding: 20,
    paddingBottom: 10,
  },
  title: {
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    textAlign: 'center',
    marginBottom: 16,
  },
  progressBar: {
    height: 8,
    borderRadius: 4,
  },
  content: {
    flex: 1,
    paddingHorizontal: 20,
  },
  card: {
    marginBottom: 20,
  },
  stepContainer: {
    gap: 16,
  },
  stepTitle: {
    textAlign: 'center',
    marginBottom: 8,
  },
  stepDescription: {
    textAlign: 'center',
    opacity: 0.7,
    marginBottom: 16,
  },
  input: {
    backgroundColor: 'transparent',
  },
  errorText: {
    fontSize: 12,
    marginTop: -12,
    marginLeft: 16,
  },
  radioContainer: {
    gap: 16,
  },
  radioCard: {
    borderWidth: 1,
    borderColor: 'transparent',
  },
  radioHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  radioTitle: {
    marginBottom: 4,
  },
  radioDescription: {
    opacity: 0.7,
  },
  footer: {
    padding: 20,
    paddingTop: 10,
  },
  buttonContainer: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 16,
  },
  button: {
    flex: 1,
  },
  backButton: {
    // 기본 스타일 사용
  },
  nextButton: {
    // 기본 스타일 사용
  },
  registerButton: {
    // 기본 스타일 사용
  },
  loginButton: {
    // 기본 스타일 사용
  },
  snackbar: {
    margin: 16,
  },
});
