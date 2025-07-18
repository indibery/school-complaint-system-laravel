import React, { useState, useEffect } from 'react';
import {
  View,
  ScrollView,
  Alert,
  Dimensions,
  StyleSheet,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  RadioButton,
  Checkbox,
  useTheme,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { USER_TYPES } from '../constants/config';

const { width } = Dimensions.get('window');

export default function LoginScreen({ navigation }) {
  const theme = useTheme();
  const { login, isLoading, error, clearError } = useAuth();
  
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    userType: USER_TYPES.PARENT.id,
    rememberMe: false,
  });

  const [formErrors, setFormErrors] = useState({});
  const [showPassword, setShowPassword] = useState(false);
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');

  useEffect(() => {
    if (error) {
      setSnackbarMessage(error);
      setSnackbarVisible(true);
      clearError();
    }
  }, [error, clearError]);

  const validateForm = () => {
    const errors = {};

    if (!formData.email) {
      errors.email = '이메일을 입력해주세요.';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      errors.email = '올바른 이메일 형식을 입력해주세요.';
    }

    if (!formData.password) {
      errors.password = '비밀번호를 입력해주세요.';
    } else if (formData.password.length < 6) {
      errors.password = '비밀번호는 6자 이상이어야 합니다.';
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleLogin = async () => {
    if (!validateForm()) {
      return;
    }

    try {
      await login({
        email: formData.email.trim(),
        password: formData.password,
        user_type: formData.userType,
        remember_me: formData.rememberMe,
      });
      
      setSnackbarMessage('로그인되었습니다.');
      setSnackbarVisible(true);
    } catch (error) {
      console.error('Login error:', error);
    }
  };

  const updateFormData = (key, value) => {
    setFormData(prev => ({ ...prev, [key]: value }));
    
    if (formErrors[key]) {
      setFormErrors(prev => ({ ...prev, [key]: undefined }));
    }
  };

  const handleDemoLogin = (userType) => {
    const demoCredentials = {
      [USER_TYPES.PARENT.id]: {
        email: 'parent@demo.com',
        password: 'demo123',
      },
      [USER_TYPES.GUARD.id]: {
        email: 'guard@demo.com',
        password: 'demo123',
      },
    };

    const credentials = demoCredentials[userType];
    if (credentials) {
      setFormData(prev => ({
        ...prev,
        email: credentials.email,
        password: credentials.password,
        userType: userType,
      }));
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <ScrollView 
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* 헤더 */}
        <View style={styles.header}>
          <View style={[styles.iconContainer, { backgroundColor: theme.colors.primaryContainer }]}>
            <MaterialIcons 
              name="school" 
              size={48} 
              color={theme.colors.primary} 
            />
          </View>
          <Text 
            variant="headlineLarge" 
            style={[styles.title, { color: theme.colors.primary }]}
          >
            학교 민원 시스템
          </Text>
          <Text 
            variant="bodyLarge" 
            style={[styles.subtitle, { color: theme.colors.onSurfaceVariant }]}
          >
            로그인하여 민원을 등록하고 관리해보세요
          </Text>
        </View>

        {/* 로그인 카드 */}
        <Card style={styles.card}>
          <Card.Content style={styles.cardContent}>
            {/* 이메일 입력 */}
            <View style={styles.inputSection}>
              <TextInput
                label="이메일"
                value={formData.email}
                onChangeText={(text) => updateFormData('email', text)}
                keyboardType="email-address"
                autoCapitalize="none"
                autoComplete="email"
                textContentType="emailAddress"
                error={!!formErrors.email}
                left={<TextInput.Icon icon="email" />}
                style={styles.input}
              />
              {formErrors.email && (
                <Text 
                  variant="bodySmall" 
                  style={[styles.errorText, { color: theme.colors.error }]}
                >
                  {formErrors.email}
                </Text>
              )}
            </View>

            {/* 비밀번호 입력 */}
            <View style={styles.inputSection}>
              <TextInput
                label="비밀번호"
                value={formData.password}
                onChangeText={(text) => updateFormData('password', text)}
                secureTextEntry={!showPassword}
                autoComplete="password"
                textContentType="password"
                error={!!formErrors.password}
                left={<TextInput.Icon icon="lock" />}
                right={
                  <TextInput.Icon
                    icon={showPassword ? "eye-off" : "eye"}
                    onPress={() => setShowPassword(!showPassword)}
                  />
                }
                style={styles.input}
              />
              {formErrors.password && (
                <Text 
                  variant="bodySmall" 
                  style={[styles.errorText, { color: theme.colors.error }]}
                >
                  {formErrors.password}
                </Text>
              )}
            </View>

            {/* 사용자 구분 */}
            <View style={styles.userTypeSection}>
              <Text variant="titleMedium" style={styles.sectionTitle}>
                사용자 구분
              </Text>
              <RadioButton.Group
                onValueChange={(value) => updateFormData('userType', value)}
                value={formData.userType}
              >
                {/* 학부모 선택 */}
                <Card 
                  style={[
                    styles.userTypeCard,
                    formData.userType === USER_TYPES.PARENT.id && {
                      backgroundColor: theme.colors.primaryContainer,
                      borderColor: theme.colors.primary,
                    }
                  ]}
                  onPress={() => updateFormData('userType', USER_TYPES.PARENT.id)}
                >
                  <Card.Content style={styles.userTypeContent}>
                    <View style={styles.userTypeRow}>
                      <View style={styles.userTypeInfo}>
                        <MaterialIcons 
                          name="family-restroom" 
                          size={24} 
                          color={theme.colors.primary} 
                        />
                        <View style={styles.userTypeText}>
                          <Text variant="titleMedium">{USER_TYPES.PARENT.name}</Text>
                          <Text 
                            variant="bodySmall" 
                            style={styles.userTypeDescription}
                          >
                            {USER_TYPES.PARENT.description}
                          </Text>
                        </View>
                      </View>
                      <RadioButton value={USER_TYPES.PARENT.id} />
                    </View>
                  </Card.Content>
                </Card>

                {/* 학교지킴이 선택 */}
                <Card 
                  style={[
                    styles.userTypeCard,
                    formData.userType === USER_TYPES.GUARD.id && {
                      backgroundColor: theme.colors.primaryContainer,
                      borderColor: theme.colors.primary,
                    }
                  ]}
                  onPress={() => updateFormData('userType', USER_TYPES.GUARD.id)}
                >
                  <Card.Content style={styles.userTypeContent}>
                    <View style={styles.userTypeRow}>
                      <View style={styles.userTypeInfo}>
                        <MaterialIcons 
                          name="security" 
                          size={24} 
                          color={theme.colors.primary} 
                        />
                        <View style={styles.userTypeText}>
                          <Text variant="titleMedium">{USER_TYPES.GUARD.name}</Text>
                          <Text 
                            variant="bodySmall" 
                            style={styles.userTypeDescription}
                          >
                            {USER_TYPES.GUARD.description}
                          </Text>
                        </View>
                      </View>
                      <RadioButton value={USER_TYPES.GUARD.id} />
                    </View>
                  </Card.Content>
                </Card>
              </RadioButton.Group>
            </View>

            {/* 로그인 상태 유지 */}
            <View style={styles.checkboxRow}>
              <Checkbox
                status={formData.rememberMe ? 'checked' : 'unchecked'}
                onPress={() => updateFormData('rememberMe', !formData.rememberMe)}
              />
              <Text style={styles.checkboxText}>로그인 상태 유지</Text>
            </View>

            {/* 로그인 버튼 */}
            <Button
              mode="contained"
              onPress={handleLogin}
              loading={isLoading}
              disabled={isLoading}
              style={styles.loginButton}
              contentStyle={styles.loginButtonContent}
            >
              {isLoading ? '로그인 중...' : '로그인'}
            </Button>

            {/* 하단 버튼들 */}
            <View style={styles.bottomButtons}>
              <Button
                mode="text"
                onPress={() => navigation.navigate('Register')}
                style={styles.bottomButton}
              >
                회원가입
              </Button>
              <Button
                mode="text"
                onPress={() => Alert.alert('비밀번호 찾기', '준비 중인 기능입니다.')}
                style={styles.bottomButton}
              >
                비밀번호 찾기
              </Button>
            </View>

            {/* 데모 로그인 버튼 */}
            {__DEV__ && (
              <View style={[styles.demoSection, { backgroundColor: theme.colors.surfaceVariant }]}>
                <Text 
                  variant="bodySmall" 
                  style={styles.demoTitle}
                >
                  데모 계정으로 로그인:
                </Text>
                <View style={styles.demoButtons}>
                  <Button
                    mode="outlined"
                    onPress={() => handleDemoLogin(USER_TYPES.PARENT.id)}
                    style={styles.demoButton}
                    compact
                  >
                    학부모
                  </Button>
                  <Button
                    mode="outlined"
                    onPress={() => handleDemoLogin(USER_TYPES.GUARD.id)}
                    style={styles.demoButton}
                    compact
                  >
                    학교지킴이
                  </Button>
                </View>
              </View>
            )}
          </Card.Content>
        </Card>
      </ScrollView>

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
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
    paddingVertical: 32,
  },
  header: {
    alignItems: 'center',
    marginBottom: 32,
  },
  iconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: {
    textAlign: 'center',
    marginBottom: 8,
    fontWeight: 'bold',
  },
  subtitle: {
    textAlign: 'center',
    opacity: 0.7,
  },
  card: {
    elevation: 4,
    borderRadius: 12,
  },
  cardContent: {
    padding: 24,
  },
  inputSection: {
    marginBottom: 20,
  },
  input: {
    backgroundColor: 'transparent',
  },
  errorText: {
    marginTop: 4,
    marginLeft: 16,
  },
  userTypeSection: {
    marginTop: 8,
    marginBottom: 20,
  },
  sectionTitle: {
    marginBottom: 12,
    fontWeight: 'bold',
  },
  userTypeCard: {
    borderWidth: 1,
    borderColor: '#e0e0e0',
    marginBottom: 12,
  },
  userTypeContent: {
    paddingVertical: 12,
  },
  userTypeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  userTypeInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  userTypeText: {
    marginLeft: 12,
    flex: 1,
  },
  userTypeDescription: {
    marginTop: 4,
    opacity: 0.7,
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  checkboxText: {
    flex: 1,
    marginLeft: 8,
  },
  loginButton: {
    marginTop: 24,
    elevation: 2,
  },
  loginButtonContent: {
    paddingVertical: 8,
  },
  bottomButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 16,
  },
  bottomButton: {
    flex: 1,
  },
  demoSection: {
    marginTop: 24,
    padding: 16,
    borderRadius: 8,
  },
  demoTitle: {
    textAlign: 'center',
    marginBottom: 8,
    opacity: 0.7,
  },
  demoButtons: {
    flexDirection: 'row',
    gap: 8,
  },
  demoButton: {
    flex: 1,
  },
  snackbar: {
    margin: 16,
  },
});
