import React, { useState, useEffect } from 'react';
import { View, Text, Button, TextInput, Alert, ScrollView, StyleSheet } from 'react-native';

const SimpleTestApp = () => {
  const [apiUrl, setApiUrl] = useState('http://127.0.0.1:8000/api');
  const [healthStatus, setHealthStatus] = useState('테스트 전');
  const [loginEmail, setLoginEmail] = useState('parent@test.com');
  const [loginPassword, setLoginPassword] = useState('password123');
  const [loginStatus, setLoginStatus] = useState('로그인 전');
  const [token, setToken] = useState('');

  // API 헬스 체크
  const testHealth = async () => {
    try {
      setHealthStatus('테스트 중...');
      const response = await fetch(`${apiUrl}/health`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        timeout: 10000,
      });
      
      if (response.ok) {
        const data = await response.json();
        setHealthStatus(`✅ 성공: ${data.status} (${data.server}:${data.port})`);
      } else {
        setHealthStatus(`❌ 실패: ${response.status}`);
      }
    } catch (error) {
      setHealthStatus(`❌ 오류: ${error.message}`);
    }
  };

  // 로그인 테스트
  const testLogin = async () => {
    try {
      setLoginStatus('로그인 중...');
      const response = await fetch(`${apiUrl}/test-login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          email: loginEmail,
          password: loginPassword,
        }),
      });
      
      const data = await response.json();
      
      if (response.ok && data.token) {
        setToken(data.token);
        setLoginStatus(`✅ 로그인 성공: ${data.user?.name || '사용자'}`);
      } else {
        setLoginStatus(`❌ 로그인 실패: ${data.message || '알 수 없는 오류'}`);
      }
    } catch (error) {
      setLoginStatus(`❌ 로그인 오류: ${error.message}`);
    }
  };

  // 민원 생성 테스트
  const testCreateComplaint = async () => {

    try {
      const response = await fetch(`${apiUrl}/v1/create-simple-complaint-no-csrf`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          title: '모바일 테스트 민원',
          content: '모바일 앱에서 생성한 테스트 민원입니다',
          category_id: 1,
          priority: 'normal',
        }),
      });
      
      const data = await response.json();
      
      if (response.ok) {
        Alert.alert('성공', '민원이 성공적으로 생성되었습니다!');
      } else {
        Alert.alert('실패', `민원 생성 실패: ${data.message || '알 수 없는 오류'}`);
      }
    } catch (error) {
      Alert.alert('오류', `민원 생성 오류: ${error.message}`);
    }
  };

  useEffect(() => {
    // 앱 시작 시 자동으로 헬스 체크
    testHealth();
  }, []);

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.title}>🏫 학교 민원 시스템 테스트</Text>
      
      {/* API URL 설정 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>1. API URL</Text>
        <TextInput
          style={styles.input}
          value={apiUrl}
          onChangeText={setApiUrl}
          placeholder="API URL"
        />
        <Button title="헬스 체크" onPress={testHealth} />
        <Text style={styles.status}>{healthStatus}</Text>
      </View>

      {/* 로그인 테스트 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>2. 로그인 테스트</Text>
        <TextInput
          style={styles.input}
          value={loginEmail}
          onChangeText={setLoginEmail}
          placeholder="이메일"
          keyboardType="email-address"
        />
        <TextInput
          style={styles.input}
          value={loginPassword}
          onChangeText={setLoginPassword}
          placeholder="비밀번호"
          secureTextEntry
        />
        <Button title="로그인" onPress={testLogin} />
        <Text style={styles.status}>{loginStatus}</Text>
        {token && <Text style={styles.token}>토큰: {token.substring(0, 20)}...</Text>}
      </View>

      {/* 민원 생성 테스트 */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>3. 민원 생성 테스트</Text>
        <Button 
          title="테스트 민원 생성" 
          onPress={testCreateComplaint}
        />
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    backgroundColor: '#f5f5f5',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 30,
    marginTop: 40,
  },
  section: {
    marginBottom: 30,
    padding: 15,
    backgroundColor: 'white',
    borderRadius: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 10,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    padding: 10,
    marginBottom: 10,
    borderRadius: 5,
  },
  status: {
    marginTop: 10,
    fontSize: 14,
    fontStyle: 'italic',
  },
  token: {
    marginTop: 5,
    fontSize: 12,
    color: '#666',
  },
});

export default SimpleTestApp;