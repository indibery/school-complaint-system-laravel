import React, { useState, useEffect } from 'react';
import {
  View,
  ScrollView,
  Alert,
  Dimensions,
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
import { validation, errorUtils } from '../utils/helpers';
import { colors, tw, componentStyles } from '../utils/tailwind';

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
      errorUtils.logError(error, 'Login');
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
    <SafeAreaView style={[tw.flex(1), tw.bg(colors.gray[50])]}>
      <ScrollView 
        contentContainerStyle={[tw.flexCol, tw.justifyCenter, tw.px(5), tw.py(8)]}
        showsVerticalScrollIndicator={false}
      >
        {/* 헤더 */}
        <View style={[tw.itemsCenter, tw.mb(8)]}>
          <View style={[
            tw.w(20), 
            tw.h(20), 
            tw.rounded('full'), 
            tw.bg(colors.primary[100]),
            tw.itemsCenter,
            tw.justifyCenter,
            tw.mb(4)
          ]}>
            <MaterialIcons 
              name="school" 
              size={48} 
              color={colors.primary[600]} 
            />
          </View>
          <Text 
            variant="headlineLarge" 
            style={[tw.textCenter, tw.mb(2), { color: colors.primary[600] }]}
          >
            학교 민원 시스템
          </Text>
          <Text 
            variant="bodyLarge" 
            style={[tw.textCenter, tw.opacity(0.7), { color: colors.gray[600] }]}
          >
            로그인하여 민원을 등록하고 관리해보세요
          </Text>
        </View>

        {/* 로그인 카드 */}
        <Card style={[componentStyles.card, tw.shadow('lg')]}>
          <Card.Content style={tw.p(6)}>
            <View style={[tw.flexCol, { gap: 20 }]}>
              {/* 이메일 입력 */}
              <View>
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
                  style={[componentStyles.input, tw.bg(colors.white)]}
                />
                {formErrors.email && (
                  <Text 
                    variant="bodySmall" 
                    style={[tw.mt(1), tw.ml(4), { color: colors.danger[500] }]}
                  >
                    {formErrors.email}
                  </Text>
                )}
              </View>

              {/* 비밀번호 입력 */}
              <View>
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
                  style={[componentStyles.input, tw.bg(colors.white)]}
                />
                {formErrors.password && (
                  <Text 
                    variant="bodySmall" 
                    style={[tw.mt(1), tw.ml(4), { color: colors.danger[500] }]}
                  >
                    {formErrors.password}
                  </Text>
                )}
              </View>

              {/* 사용자 구분 */}
              <View style={tw.mt(2)}>
                <Text variant="titleMedium" style={[tw.mb(3), { color: colors.gray[800] }]}>
                  사용자 구분
                </Text>
                <RadioButton.Group
                  onValueChange={(value) => updateFormData('userType', value)}
                  value={formData.userType}
                >
                  <View style={[tw.flexCol, { gap: 12 }]}>
                    {/* 학부모 선택 */}
                    <Card 
                      style={[
                        tw.border(colors.gray[200]),
                        tw.borderW(1),
                        formData.userType === USER_TYPES.PARENT.id && {
                          backgroundColor: colors.primary[50],
                          borderColor: colors.primary[300],
                        }
                      ]}
                      onPress={() => updateFormData('userType', USER_TYPES.PARENT.id)}
                    >
                      <Card.Content style={tw.py(3)}>
                        <View style={[tw.flexRow, tw.itemsCenter, tw.justifyBetween]}>
                          <View style={[tw.flexRow, tw.itemsCenter, { gap: 12 }]}>
                            <MaterialIcons 
                              name="family-restroom" 
                              size={24} 
                              color={colors.primary[600]} 
                            />
                            <View>
                              <Text variant="titleMedium">{USER_TYPES.PARENT.name}</Text>
                              <Text 
                                variant="bodySmall" 
                                style={[tw.mt(1), tw.opacity(0.7)]}
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
                        tw.border(colors.gray[200]),
                        tw.borderW(1),
                        formData.userType === USER_TYPES.GUARD.id && {
                          backgroundColor: colors.primary[50],
                          borderColor: colors.primary[300],
                        }
                      ]}
                      onPress={() => updateFormData('userType', USER_TYPES.GUARD.id)}
                    >
                      <Card.Content style={tw.py(3)}>
                        <View style={[tw.flexRow, tw.itemsCenter, tw.justifyBetween]}>
                          <View style={[tw.flexRow, tw.itemsCenter, { gap: 12 }]}>
                            <MaterialIcons 
                              name="security" 
                              size={24} 
                              color={colors.primary[600]} 
                            />
                            <View>
                              <Text variant="titleMedium">{USER_TYPES.GUARD.name}</Text>
                              <Text 
                                variant="bodySmall" 
                                style={[tw.mt(1), tw.opacity(0.7)]}
                              >
                                {USER_TYPES.GUARD.description}
                              </Text>
                            </View>
                          </View>
                          <RadioButton value={USER_TYPES.GUARD.id} />
                        </View>
                      </Card.Content>
                    </Card>
                  </View>
                </RadioButton.Group>
              </View>

              {/* 로그인 상태 유지 */}
              <View style={[tw.flexRow, tw.itemsCenter, { gap: 8 }]}>
                <Checkbox
                  status={formData.rememberMe ? 'checked' : 'unchecked'}
                  onPress={() => updateFormData('rememberMe', !formData.rememberMe)}
                />
                <Text style={tw.flex(1)}>로그인 상태 유지</Text>
              </View>

              {/* 로그인 버튼 */}
              <Button
                mode="contained"
                onPress={handleLogin}
                loading={isLoading}
                disabled={isLoading}
                style={[
                  componentStyles.button.primary,
                  tw.mt(6),
                  tw.shadow('md'),
                  { backgroundColor: colors.primary[600] }
                ]}
                contentStyle={tw.py(2)}
              >
                {isLoading ? '로그인 중...' : '로그인'}
              </Button>

              {/* 하단 버튼들 */}
              <View style={[tw.flexRow, tw.justifyBetween, tw.mt(4)]} >
                <Button
                  mode="text"
                  onPress={() => navigation.navigate('Register')}
                  style={tw.flex(1)}
                >
                  회원가입
                </Button>
                <Button
                  mode="text"
                  onPress={() => Alert.alert('비밀번호 찾기', '준비 중인 기능입니다.')}
                  style={tw.flex(1)}
                >
                  비밀번호 찾기
                </Button>
              </View>

              {/* 데모 로그인 버튼 */}
              {__DEV__ && (
                <View style={[
                  tw.mt(6), 
                  tw.p(4), 
                  tw.bg(colors.gray[100]),
                  tw.rounded('md')
                ]}>
                  <Text 
                    variant="bodySmall" 
                    style={[tw.textCenter, tw.mb(2), tw.opacity(0.7)]}
                  >
                    데모 계정으로 로그인:
                  </Text>
                  <View style={[tw.flexRow, { gap: 8 }]}>
                    <Button
                      mode="outlined"
                      onPress={() => handleDemoLogin(USER_TYPES.PARENT.id)}
                      style={tw.flex(1)}
                      compact
                    >
                      학부모
                    </Button>
                    <Button
                      mode="outlined"
                      onPress={() => handleDemoLogin(USER_TYPES.GUARD.id)}
                      style={tw.flex(1)}
                      compact
                    >
                      학교지킴이
                    </Button>
                  </View>
                </View>
              )}
            </View>
          </Card.Content>
        </Card>
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
