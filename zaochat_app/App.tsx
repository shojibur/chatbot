import {
  Inter_400Regular,
  Inter_500Medium,
  Inter_600SemiBold,
  Inter_700Bold,
  Inter_800ExtraBold,
  useFonts,
} from '@expo-google-fonts/inter';
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, useColorScheme, View } from 'react-native';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';

import { AppShell } from './src/components/AppShell';
import type { Screen, Tab } from './src/constants/tabs';
import type { MobileAppBootstrap, MobileUserContext } from './src/lib/mobileApi';
import {
  deletePushToken,
  loadMobileAppData,
  login as loginRequest,
  logout as logoutRequest,
  savePushToken,
} from './src/lib/mobileApi';
import { LoginScreen } from './src/screens/LoginScreen';
import { OnboardingScreen } from './src/screens/OnboardingScreen';
import { SplashScreen } from './src/screens/SplashScreen';
import { onPushRefresh } from './src/tabs/SessionsTab';
import { getTheme } from './src/theme';

const TOKEN_KEY = 'zaochat.mobile.auth_token';

const isExpoGo = Constants.executionEnvironment === 'storeClient';

if (!isExpoGo) {
  Notifications.setNotificationHandler({
    handleNotification: async () => ({
      shouldShowAlert: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
      shouldShowBanner: true,
      shouldShowList: true,
    }),
  });
}

async function registerForPushNotifications(): Promise<string | null> {
  if (isExpoGo || !Device.isDevice) {
    return null;
  }

  const { status: existing } = await Notifications.getPermissionsAsync();
  let finalStatus = existing;

  if (existing !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    return null;
  }

  try {
    const result = await Notifications.getDevicePushTokenAsync();
    return result.data as string;
  } catch {
    return null;
  }
}

export default function App() {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  const [screen, setScreen] = useState<Screen>('splash');
  const [activeTab, setActiveTab] = useState<Tab>('sessions');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [appLoading, setAppLoading] = useState(false);
  const [appError, setAppError] = useState<string | null>(null);
  const [loginError, setLoginError] = useState<string | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [userContext, setUserContext] = useState<MobileUserContext | null>(null);
  const [appData, setAppData] = useState<MobileAppBootstrap | null>(null);

  const notificationListener = useRef<Notifications.EventSubscription | null>(null);
  const responseListener = useRef<Notifications.EventSubscription | null>(null);
  const authTokenRef = useRef<string | null>(null);

  const [fontsLoaded] = useFonts({
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
    Inter_800ExtraBold,
  });

  // Push notification listeners
  useEffect(() => {
    notificationListener.current = Notifications.addNotificationReceivedListener((notification) => {
      const type = (notification.request.content.data as Record<string, string>)?.type;

      if (type === 'new_session' || type === 'takeover_reply') {
        onPushRefresh?.();
      }
    });

    responseListener.current = Notifications.addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data as Record<string, string>;

      if (data?.type === 'new_session' || data?.type === 'takeover_reply') {
        setActiveTab('sessions');
      } else if (data?.type === 'new_lead') {
        setActiveTab('leads');
      }
    });

    return () => {
      notificationListener.current?.remove();
      responseListener.current?.remove();
    };
  }, []);

  // Register push token when auth changes
  useEffect(() => {
    authTokenRef.current = token;

    if (!token) {
      return;
    }

    registerForPushNotifications()
      .then((fcmToken) => {
        if (fcmToken && authTokenRef.current) {
          void savePushToken(authTokenRef.current, fcmToken);
        }
      })
      .catch(() => {});
  }, [token]);

  // Bootstrap: try stored token → load data → choose screen
  useEffect(() => {
    if (!fontsLoaded) {
      return;
    }

    let cancelled = false;

    async function bootstrap(): Promise<void> {
      await new Promise((resolve) => setTimeout(resolve, 1200));

      const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);

      if (!storedToken) {
        if (!cancelled) {
          setScreen('onboarding');
        }

        return;
      }

      if (!cancelled) {
        setToken(storedToken);
        setAppLoading(true);
      }

      try {
        const data = await loadMobileAppData(storedToken);

        if (cancelled) {
          return;
        }

        setUserContext(data.me);
        setAppData(data);
        setScreen('app');
      } catch (error) {
        await SecureStore.deleteItemAsync(TOKEN_KEY);

        if (cancelled) {
          return;
        }

        setToken(null);
        setUserContext(null);
        setAppData(null);
        setScreen('login');
        setLoginError(error instanceof Error ? error.message : 'Something went wrong.');
      } finally {
        if (!cancelled) {
          setAppLoading(false);
        }
      }
    }

    void bootstrap();

    return () => {
      cancelled = true;
    };
  }, [fontsLoaded]);

  async function handleLogin(): Promise<void> {
    if (isSubmitting) {
      return;
    }

    setIsSubmitting(true);
    setLoginError(null);
    setAppError(null);

    try {
      const auth = await loginRequest(email, password);
      const bootstrap = await loadMobileAppData(auth.token);

      setToken(auth.token);
      setUserContext(bootstrap.me);
      setAppData(bootstrap);
      setActiveTab('sessions');

      if (rememberMe) {
        await SecureStore.setItemAsync(TOKEN_KEY, auth.token);
      } else {
        await SecureStore.deleteItemAsync(TOKEN_KEY);
      }

      setScreen('app');
    } catch (error) {
      setLoginError(error instanceof Error ? error.message : 'Something went wrong.');
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleRefresh(): Promise<void> {
    if (!token) {
      return;
    }

    setAppLoading(true);
    setAppError(null);

    try {
      const bootstrap = await loadMobileAppData(token);

      setUserContext(bootstrap.me);
      setAppData(bootstrap);
    } catch (error) {
      setAppError(error instanceof Error ? error.message : 'Something went wrong.');
    } finally {
      setAppLoading(false);
    }
  }

  async function handleLogout(): Promise<void> {
    if (token) {
      try {
        await deletePushToken(token);
        await logoutRequest(token);
      } catch {
        // resilient — local logout always proceeds
      }
    }

    await SecureStore.deleteItemAsync(TOKEN_KEY);

    setToken(null);
    setUserContext(null);
    setAppData(null);
    setEmail('');
    setPassword('');
    setActiveTab('sessions');
    setScreen('login');
  }

  if (!fontsLoaded) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: theme.colors.bg }}>
        <ActivityIndicator color={theme.colors.primary} size="large" />
      </View>
    );
  }

  return (
    <SafeAreaProvider>
      <SafeAreaView style={{ flex: 1, backgroundColor: theme.colors.bg }}>
        <StatusBar style={theme.statusBar} />
        {screen === 'splash' ? <SplashScreen /> : null}
        {screen === 'onboarding' ? <OnboardingScreen onContinue={() => setScreen('login')} /> : null}
        {screen === 'login' ? (
          <LoginScreen
            email={email}
            password={password}
            rememberMe={rememberMe}
            isSubmitting={isSubmitting}
            loginError={loginError}
            onEmailChange={setEmail}
            onPasswordChange={setPassword}
            onRememberToggle={() => setRememberMe((v) => !v)}
            onLogin={handleLogin}
            onBack={() => setScreen('onboarding')}
          />
        ) : null}
        {screen === 'app' && userContext && appData && token ? (
          <AppShell
            activeTab={activeTab}
            appData={appData}
            appError={appError}
            appLoading={appLoading}
            token={token}
            onChangeTab={setActiveTab}
            onDataChange={setAppData}
            onLogout={handleLogout}
            onRefresh={handleRefresh}
            userContext={userContext}
          />
        ) : null}
      </SafeAreaView>
    </SafeAreaProvider>
  );
}
