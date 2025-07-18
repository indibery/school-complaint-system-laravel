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
  TextInput,
  List,
  Switch,
  Chip,
  Divider,
  useTheme,
  Surface,
  IconButton,
  HelperText,
  Snackbar,
} from 'react-native-paper';
import { SafeAreaView } from 'react-native-safe-area-context';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiManager, checkApiConnection } from '../services/ApiManager';
import { apiDiscovery } from '../utils/apiDiscovery';

export default function ApiSettingsScreen({ navigation }) {
  const theme = useTheme();
  
  // 상태 관리
  const [currentApiUrl, setCurrentApiUrl] = useState('');
  const [manualApiUrl, setManualApiUrl] = useState('');
  const [availableUrls, setAvailableUrls] = useState([]);
  const [isTestingConnection, setIsTestingConnection] = useState(false);
  const [autoDiscoveryEnabled, setAutoDiscoveryEnabled] = useState(true);
  const [connectionStatus, setConnectionStatus] = useState('unknown');
  const [snackbarVisible, setSnackbarVisible] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState('');
  const [lastUpdateTime, setLastUpdateTime] = useState(null);

  // 컴포넌트 마운트 시 초기화
  useEffect(() => {
    loadCurrentSettings();
    checkCurrentConnection();
  }, []);

  // 현재 설정 로드
  const loadCurrentSettings = async () => {
    try {
      const currentUrl = apiManager.getCurrentUrl();
      const userUrl = await AsyncStorage.getItem('USER_API_URL');
      const autoDiscovery = await AsyncStorage.getItem('AUTO_DISCOVERY_ENABLED');
      
      setCurrentApiUrl(currentUrl || 'URL 감지 중...');
      setManualApiUrl(userUrl || '');
      setAutoDiscoveryEnabled(autoDiscovery !== 'false');
    } catch (error) {
      console.error('설정 로드 실패:', error);
    }
  };

  // 현재 연결 상태 확인
  const checkCurrentConnection = async () => {
    try {
      const isConnected = await checkApiConnection();
      setConnectionStatus(isConnected ? 'connected' : 'disconnected');
    } catch (error) {
      setConnectionStatus('error');
    }
  };

  // 자동 API 발견 실행
  const performAutoDiscovery = async () => {
    try {
      setIsTestingConnection(true);
      showSnackbar('API 서버 자동 발견 중...');
      
      const discoveredUrl = await apiDiscovery.discoverApiEndpoint();
      setAvailableUrls([discoveredUrl]);
      setCurrentApiUrl(discoveredUrl);
      setConnectionStatus('connected');
      setLastUpdateTime(new Date());
      
      showSnackbar(`API 서버 발견: ${discoveredUrl}`);
    } catch (error) {
      console.error('자동 발견 실패:', error);
      setConnectionStatus('error');
      showSnackbar('API 서버를 찾을 수 없습니다');
    } finally {
      setIsTestingConnection(false);
    }
  };

  // 수동 URL 설정
  const setManualUrl = async () => {
    if (!manualApiUrl.trim()) {
      Alert.alert('오류', 'API URL을 입력해주세요.');
      return;
    }

    try {
      setIsTestingConnection(true);
      showSnackbar('API 연결 테스트 중...');
      
      await apiManager.setManualUrl(manualApiUrl);
      setCurrentApiUrl(manualApiUrl);
      setConnectionStatus('connected');
      setLastUpdateTime(new Date());
      
      showSnackbar('API URL이 성공적으로 설정되었습니다');
    } catch (error) {
      console.error('수동 URL 설정 실패:', error);
      setConnectionStatus('error');
      Alert.alert('연결 실패', `설정한 URL에 연결할 수 없습니다: ${error.message}`);
    } finally {
      setIsTestingConnection(false);
    }
  };

  // 연결 테스트
  const testConnection = async () => {
    try {
      setIsTestingConnection(true);
      showSnackbar('연결 테스트 중...');
      
      const isConnected = await checkApiConnection();
      setConnectionStatus(isConnected ? 'connected' : 'disconnected');
      
      if (isConnected) {
        showSnackbar('✅ 연결 성공');
      } else {
        showSnackbar('❌ 연결 실패');
      }
    } catch (error) {
      setConnectionStatus('error');
      showSnackbar('연결 테스트 실패');
    } finally {
      setIsTestingConnection(false);
    }
  };

  // API 매니저 재설정
  const resetApiManager = async () => {
    Alert.alert(
      '재설정 확인',
      'API 설정을 초기화하고 자동 발견을 다시 시도하시겠습니까?',
      [
        { text: '취소', style: 'cancel' },
        {
          text: '재설정',
          onPress: async () => {
            try {
              setIsTestingConnection(true);
              showSnackbar('API 매니저 재설정 중...');
              
              await apiManager.reset();
              await loadCurrentSettings();
              await checkCurrentConnection();
              
              showSnackbar('API 매니저가 재설정되었습니다');
            } catch (error) {
              showSnackbar('재설정 실패');
            } finally {
              setIsTestingConnection(false);
            }
          }
        }
      ]
    );
  };

  // 자동 발견 토글
  const toggleAutoDiscovery = async (enabled) => {
    setAutoDiscoveryEnabled(enabled);
    await AsyncStorage.setItem('AUTO_DISCOVERY_ENABLED', enabled.toString());
    
    if (enabled) {
      performAutoDiscovery();
    }
  };

  // 스낵바 표시
  const showSnackbar = (message) => {
    setSnackbarMessage(message);
    setSnackbarVisible(true);
  };

  // 연결 상태 표시 컴포넌트
  const ConnectionStatusChip = () => {
    const statusConfig = {
      connected: { label: '연결됨', color: '#4CAF50', icon: 'check-circle' },
      disconnected: { label: '연결 안됨', color: '#FF9800', icon: 'alert-circle' },
      error: { label: '오류', color: '#F44336', icon: 'close-circle' },
      unknown: { label: '상태 확인 중', color: '#9E9E9E', icon: 'help-circle' },
    };

    const config = statusConfig[connectionStatus];
    
    return (
      <Chip
        icon={config.icon}
        style={[styles.statusChip, { backgroundColor: config.color }]}
        textStyle={{ color: 'white' }}
      >
        {config.label}
      </Chip>
    );
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.colors.background }]}>
      <ScrollView style={styles.scrollView}>
        {/* 헤더 */}
        <View style={styles.header}>
          <Text style={[styles.title, { color: theme.colors.onBackground }]}>
            API 설정
          </Text>
          <Text style={[styles.subtitle, { color: theme.colors.onSurfaceVariant }]}>
            서버 연결 및 네트워크 설정
          </Text>
        </View>

        {/* 현재 연결 상태 */}
        <Card style={styles.card}>
          <Card.Content>
            <View style={styles.statusContainer}>
              <Text style={[styles.sectionTitle, { color: theme.colors.onSurface }]}>
                현재 연결 상태
              </Text>
              <ConnectionStatusChip />
            </View>
            
            <View style={styles.urlContainer}>
              <Text style={[styles.urlLabel, { color: theme.colors.onSurfaceVariant }]}>
                API URL:
              </Text>
              <Text style={[styles.urlText, { color: theme.colors.onSurface }]}>
                {currentApiUrl}
              </Text>
            </View>
            
            {lastUpdateTime && (
              <Text style={[styles.updateTime, { color: theme.colors.onSurfaceVariant }]}>
                마지막 업데이트: {lastUpdateTime.toLocaleString()}
              </Text>
            )}
            
            <View style={styles.buttonRow}>
              <Button
                mode="outlined"
                onPress={testConnection}
                loading={isTestingConnection}
                disabled={isTestingConnection}
                style={styles.testButton}
              >
                연결 테스트
              </Button>
              <Button
                mode="outlined"
                onPress={checkCurrentConnection}
                disabled={isTestingConnection}
                style={styles.refreshButton}
              >
                새로고침
              </Button>
            </View>
          </Card.Content>
        </Card>

        {/* 자동 발견 설정 */}
        <Card style={styles.card}>
          <Card.Content>
            <View style={styles.switchContainer}>
              <View style={styles.switchText}>
                <Text style={[styles.sectionTitle, { color: theme.colors.onSurface }]}>
                  자동 API 발견
                </Text>
                <Text style={[styles.description, { color: theme.colors.onSurfaceVariant }]}>
                  네트워크에서 자동으로 API 서버를 찾습니다
                </Text>
              </View>
              <Switch
                value={autoDiscoveryEnabled}
                onValueChange={toggleAutoDiscovery}
                disabled={isTestingConnection}
              />
            </View>
            
            <Button
              mode="contained"
              onPress={performAutoDiscovery}
              loading={isTestingConnection}
              disabled={isTestingConnection || !autoDiscoveryEnabled}
              style={styles.discoveryButton}
            >
              자동 발견 실행
            </Button>
          </Card.Content>
        </Card>

        {/* 수동 URL 설정 */}
        <Card style={styles.card}>
          <Card.Content>
            <Text style={[styles.sectionTitle, { color: theme.colors.onSurface }]}>
              수동 API URL 설정
            </Text>
            <Text style={[styles.description, { color: theme.colors.onSurfaceVariant }]}>
              직접 API 서버 주소를 입력하여 연결할 수 있습니다
            </Text>
            
            <TextInput
              label="API URL"
              value={manualApiUrl}
              onChangeText={setManualApiUrl}
              placeholder="예: http://192.168.1.100:8000/api"
              style={styles.urlInput}
              disabled={isTestingConnection}
            />
            
            <HelperText type="info">
              포트 번호와 /api 경로를 포함해서 입력하세요
            </HelperText>
            
            <Button
              mode="contained"
              onPress={setManualUrl}
              loading={isTestingConnection}
              disabled={isTestingConnection || !manualApiUrl.trim()}
              style={styles.setUrlButton}
            >
              URL 설정
            </Button>
          </Card.Content>
        </Card>

        {/* 고급 설정 */}
        <Card style={styles.card}>
          <Card.Content>
            <Text style={[styles.sectionTitle, { color: theme.colors.onSurface }]}>
              고급 설정
            </Text>
            
            <List.Item
              title="API 매니저 재설정"
              description="모든 설정을 초기화하고 다시 시작"
              left={(props) => <List.Icon {...props} icon="refresh" />}
              onPress={resetApiManager}
              disabled={isTestingConnection}
            />
            
            <Divider style={styles.divider} />
            
            <List.Item
              title="네트워크 진단"
              description="네트워크 상태 및 연결 문제 진단"
              left={(props) => <List.Icon {...props} icon="network" />}
              onPress={() => {
                // 네트워크 진단 로직 구현
                Alert.alert('준비 중', '네트워크 진단 기능을 준비 중입니다.');
              }}
              disabled={isTestingConnection}
            />
          </Card.Content>
        </Card>
      </ScrollView>

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
  scrollView: {
    flex: 1,
  },
  header: {
    padding: 20,
    paddingBottom: 10,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  subtitle: {
    fontSize: 16,
  },
  card: {
    margin: 16,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 8,
  },
  description: {
    fontSize: 14,
    marginBottom: 16,
  },
  statusContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  statusChip: {
    borderRadius: 20,
  },
  urlContainer: {
    marginBottom: 8,
  },
  urlLabel: {
    fontSize: 12,
    fontWeight: '500',
    marginBottom: 4,
  },
  urlText: {
    fontSize: 14,
    fontFamily: 'monospace',
    backgroundColor: 'rgba(0,0,0,0.05)',
    padding: 8,
    borderRadius: 4,
  },
  updateTime: {
    fontSize: 12,
    marginBottom: 16,
  },
  buttonRow: {
    flexDirection: 'row',
    gap: 8,
  },
  testButton: {
    flex: 1,
  },
  refreshButton: {
    flex: 1,
  },
  switchContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 16,
  },
  switchText: {
    flex: 1,
    marginRight: 16,
  },
  discoveryButton: {
    marginTop: 8,
  },
  urlInput: {
    marginBottom: 8,
  },
  setUrlButton: {
    marginTop: 8,
  },
  divider: {
    marginVertical: 8,
  },
  snackbar: {
    marginBottom: 16,
  },
});