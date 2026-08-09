import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  Inter_400Regular,
  Inter_500Medium,
  Inter_600SemiBold,
  Inter_700Bold,
  Inter_800ExtraBold,
  useFonts,
} from '@expo-google-fonts/inter';
import * as DocumentPicker from 'expo-document-picker';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  useColorScheme,
  View,
} from 'react-native';
import {
  createKnowledgeSource,
  deleteKnowledgeSource,
  getSessionMessages,
  getLeadDetail,
  loadMobileAppData,
  login as loginRequest,
  logout as logoutRequest,
  releaseSessionTakeover,
  retryKnowledgeSource,
  sendSessionMessage,
  takeoverSession,
  updateLeadStatus,
  updateWidgetSettings,
  type MobileAppBootstrap,
  type MobileKnowledgeSource,
  type MobileLead,
  type MobileLeadDetail,
  type MobileSession,
  type MobileSessionMessage,
  type MobileSettingsPayload,
  type MobileUserContext,
} from './src/lib/mobileApi';
import { getTheme } from './src/theme';

const logo = require('./assets/splash-icon.png');
const TOKEN_STORAGE_KEY = 'zaochat.mobile.auth_token';

type Screen = 'splash' | 'onboarding' | 'login' | 'app';
type Tab = 'sessions' | 'leads' | 'knowledge' | 'settings';

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
  const [fontsLoaded] = useFonts({
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
    Inter_800ExtraBold,
  });

  useEffect(() => {
    if (!fontsLoaded) return;

    let cancelled = false;

    async function bootstrap(): Promise<void> {
      await new Promise((resolve) => setTimeout(resolve, 1200));

      const storedToken = await AsyncStorage.getItem(TOKEN_STORAGE_KEY);

      if (!storedToken) {
        if (!cancelled) setScreen('onboarding');
        return;
      }

      if (!cancelled) {
        setToken(storedToken);
        setAppLoading(true);
      }

      try {
        const data = await loadMobileAppData(storedToken);

        if (cancelled) return;

        setUserContext(data.me);
        setAppData(data);
        setScreen('app');
      } catch (error) {
        await AsyncStorage.removeItem(TOKEN_STORAGE_KEY);

        if (cancelled) return;

        setToken(null);
        setUserContext(null);
        setAppData(null);
        setScreen('login');
        setLoginError(resolveErrorMessage(error));
      } finally {
        if (!cancelled) setAppLoading(false);
      }
    }

    void bootstrap();

    return () => {
      cancelled = true;
    };
  }, [fontsLoaded]);

  async function handleLogin(): Promise<void> {
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
        await AsyncStorage.setItem(TOKEN_STORAGE_KEY, auth.token);
      } else {
        await AsyncStorage.removeItem(TOKEN_STORAGE_KEY);
      }

      setScreen('app');
    } catch (error) {
      setLoginError(resolveErrorMessage(error));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleRefresh(): Promise<void> {
    if (!token) return;

    setAppLoading(true);
    setAppError(null);

    try {
      const bootstrap = await loadMobileAppData(token);
      setUserContext(bootstrap.me);
      setAppData(bootstrap);
    } catch (error) {
      setAppError(resolveErrorMessage(error));
    } finally {
      setAppLoading(false);
    }
  }

  async function handleLogout(): Promise<void> {
    if (token) {
      try {
        await logoutRequest(token);
      } catch {
        // Keep local logout resilient.
      }
    }

    await AsyncStorage.removeItem(TOKEN_STORAGE_KEY);
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
      <View style={[styles.loader, { backgroundColor: theme.colors.bg }]}>
        <ActivityIndicator color={theme.colors.primary} size="large" />
      </View>
    );
  }

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.colors.bg }]}>
      <StatusBar style={theme.statusBar} />
      {screen === 'splash' ? <SplashScreen /> : null}
      {screen === 'onboarding' ? (
        <OnboardingScreen onContinue={() => setScreen('login')} />
      ) : null}
      {screen === 'login' ? (
        <LoginScreen
          email={email}
          password={password}
          rememberMe={rememberMe}
          isSubmitting={isSubmitting}
          loginError={loginError}
          onEmailChange={setEmail}
          onPasswordChange={setPassword}
          onRememberToggle={() => setRememberMe((value) => !value)}
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
  );
}

function SplashScreen() {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View style={[styles.centeredScreen, { backgroundColor: theme.colors.bg }]}>
      <View style={[styles.splashOrb, { backgroundColor: theme.colors.primaryGlow }]} />
      <View style={[styles.splashOrbSecondary, { backgroundColor: theme.colors.accentGlow }]} />
      <Image source={logo} style={styles.splashLogo} resizeMode="contain" />
      <Text style={[styles.splashWordmark, { color: theme.colors.text }]}>ZaoChat</Text>
      <Text style={[styles.splashTagline, { color: theme.colors.muted }]}>
        AI secretary for hot leads and live takeover
      </Text>
    </View>
  );
}

function OnboardingScreen({ onContinue }: { onContinue: () => void }) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: theme.colors.bg }}
      contentContainerStyle={styles.onboardingContent}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.heroWrap}>
        <GlowBackground />
        <Image source={logo} style={styles.heroLogo} resizeMode="contain" />
        <View
          style={[
            styles.badge,
            {
              backgroundColor: theme.colors.surface,
              borderColor: theme.colors.border,
            },
          ]}
        >
          <View style={[styles.badgeDot, { backgroundColor: theme.colors.success }]} />
          <Text style={[styles.badgeText, { color: theme.colors.muted }]}>
            Built from the live ZaoChat design system
          </Text>
        </View>

        <Text style={[styles.headline, { color: theme.colors.text }]}>
          Run client conversations from anywhere.
        </Text>
        <Text style={[styles.subhead, { color: theme.colors.muted }]}>
          Android-first mobile access for sessions, leads, takeover, widget controls,
          knowledge, and subscription management.
        </Text>
      </View>

      <View style={styles.cardsColumn}>
        {[
          'Monitor every active conversation',
          'Take over when timing matters',
          'Manage the full client portal from mobile',
        ].map((title, index) => (
          <View
            key={title}
            style={[
              styles.onboardingCard,
              {
                backgroundColor: theme.colors.card,
                borderColor: theme.colors.border,
              },
            ]}
          >
            <Text style={[styles.onboardingCardTitle, { color: theme.colors.text }]}>
              {title}
            </Text>
            <Text style={[styles.onboardingCardBody, { color: theme.colors.muted }]}>
              {index === 0
                ? 'Watch live session queues, review the full thread, and spot hot leads before they cool down.'
                : index === 1
                  ? 'Switch from AI to human follow-up when a visitor is ready to book, buy, or needs a callback now.'
                  : 'Leads, widget settings, knowledge sources, subscription, and testing stay within reach.'}
            </Text>
          </View>
        ))}
      </View>

      <Pressable
        style={[
          styles.fullWidthPrimaryButton,
          { backgroundColor: theme.colors.primary, shadowColor: theme.colors.primary },
        ]}
        onPress={onContinue}
      >
        <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
          Continue to Login
        </Text>
      </Pressable>
    </ScrollView>
  );
}

type LoginProps = {
  email: string;
  password: string;
  rememberMe: boolean;
  isSubmitting: boolean;
  loginError: string | null;
  onEmailChange: (value: string) => void;
  onPasswordChange: (value: string) => void;
  onRememberToggle: () => void;
  onLogin: () => void;
  onBack: () => void;
};

function LoginScreen({
  email,
  password,
  rememberMe,
  isSubmitting,
  loginError,
  onEmailChange,
  onPasswordChange,
  onRememberToggle,
  onLogin,
  onBack,
}: LoginProps) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={[styles.loginScreen, { backgroundColor: theme.colors.bg }]}
    >
      <ScrollView contentContainerStyle={styles.loginContent} showsVerticalScrollIndicator={false}>
        <Image source={logo} style={styles.loginLogo} resizeMode="contain" />
        <Text style={[styles.loginTitle, { color: theme.colors.text }]}>
          Sign in to your client portal
        </Text>
        <Text style={[styles.loginSubtitle, { color: theme.colors.muted }]}>
          Set `EXPO_PUBLIC_API_BASE_URL` to your Laravel server, for example
          `http://10.0.2.2:8000/api/mobile` on the Android emulator.
        </Text>

        <View
          style={[
            styles.loginCard,
            {
              backgroundColor: theme.colors.card,
              borderColor: theme.colors.border,
            },
          ]}
        >
          <Field
            label="Email"
            value={email}
            onChangeText={onEmailChange}
            placeholder="owner@business.com"
            autoCapitalize="none"
            keyboardType="email-address"
          />
          <Field
            label="Password"
            value={password}
            onChangeText={onPasswordChange}
            placeholder="Enter your password"
            secureTextEntry
          />

          {loginError ? (
            <ErrorBanner text={loginError} />
          ) : null}

          <Pressable style={styles.rememberRow} onPress={onRememberToggle}>
            <View
              style={[
                styles.checkbox,
                {
                  borderColor: theme.colors.border,
                  backgroundColor: rememberMe ? theme.colors.primary : theme.colors.surface,
                },
              ]}
            >
              {rememberMe ? (
                <View style={[styles.checkboxInner, { backgroundColor: theme.colors.primaryText }]} />
              ) : null}
            </View>
            <Text style={[styles.rememberText, { color: theme.colors.muted }]}>
              Keep me signed in on this device
            </Text>
          </Pressable>

          <Pressable
            style={[
              styles.fullWidthPrimaryButton,
              { backgroundColor: theme.colors.primary, opacity: isSubmitting ? 0.7 : 1 },
            ]}
            disabled={isSubmitting || !email || !password}
            onPress={onLogin}
          >
            {isSubmitting ? (
              <ActivityIndicator color={theme.colors.primaryText} />
            ) : (
              <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
                Sign In
              </Text>
            )}
          </Pressable>

          <Pressable style={styles.backButton} onPress={onBack}>
            <Text style={[styles.backButtonText, { color: theme.colors.accent }]}>
              Back to onboarding
            </Text>
          </Pressable>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

type AppShellProps = {
  activeTab: Tab;
  appData: MobileAppBootstrap;
  appError: string | null;
  appLoading: boolean;
  token: string;
  onChangeTab: (tab: Tab) => void;
  onDataChange: (data: MobileAppBootstrap) => void;
  onLogout: () => void;
  onRefresh: () => void;
  userContext: MobileUserContext;
};

function AppShell({
  activeTab,
  appData,
  appError,
  appLoading,
  token,
  onChangeTab,
  onDataChange,
  onLogout,
  onRefresh,
  userContext,
}: AppShellProps) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View style={[styles.appShell, { backgroundColor: theme.colors.bg }]}>
      <View style={styles.appHeader}>
        <View style={styles.appHeaderCopy}>
          <Text style={[styles.appHeaderTitle, { color: theme.colors.text }]}>
            {userContext.client.name}
          </Text>
          <Text style={[styles.appHeaderSubtitle, { color: theme.colors.muted }]}>
            {userContext.user.email}
          </Text>
        </View>
        <Pressable
          style={[
            styles.refreshButton,
            { backgroundColor: theme.colors.surface, borderColor: theme.colors.border },
          ]}
          onPress={onRefresh}
        >
          <Text style={[styles.refreshButtonText, { color: theme.colors.text }]}>
            {appLoading ? 'Loading...' : 'Refresh'}
          </Text>
        </Pressable>
      </View>

      {appError ? <ErrorBanner text={appError} inset /> : null}

      <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {activeTab === 'sessions' ? (
          <SessionsTab
            sessions={appData.sessions}
            token={token}
            onSessionsChange={(sessions) => onDataChange({ ...appData, sessions })}
          />
        ) : null}
        {activeTab === 'leads' ? (
          <LeadsTab
            leads={appData.leads}
            token={token}
            onLeadsChange={(leads) => onDataChange({ ...appData, leads })}
          />
        ) : null}
        {activeTab === 'knowledge' ? (
          <KnowledgeTab
            sources={appData.knowledgeSources}
            token={token}
            onSourcesChange={(knowledgeSources) => onDataChange({ ...appData, knowledgeSources })}
          />
        ) : null}
        {activeTab === 'settings' ? (
          <SettingsTab
            settings={appData.settings}
            token={token}
            userContext={userContext}
            onLogout={onLogout}
            onSettingsChange={(settings) => onDataChange({ ...appData, settings })}
          />
        ) : null}
      </ScrollView>

      <View
        style={[
          styles.tabBar,
          { backgroundColor: theme.colors.card, borderColor: theme.colors.border },
        ]}
      >
        {(['sessions', 'leads', 'knowledge', 'settings'] as const).map((tab) => (
          <Pressable
            key={tab}
            style={[
              styles.tabButton,
              { backgroundColor: activeTab === tab ? theme.colors.primary : 'transparent' },
            ]}
            onPress={() => onChangeTab(tab)}
          >
            <Text
              style={[
                styles.tabButtonText,
                { color: activeTab === tab ? theme.colors.primaryText : theme.colors.muted },
              ]}
            >
              {tab === 'knowledge' ? 'Knowledge' : capitalize(tab)}
            </Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

function SessionsTab({
  sessions,
  token,
  onSessionsChange,
}: {
  sessions: MobileSession[];
  token: string;
  onSessionsChange: (sessions: MobileSession[]) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);
  const [selectedSession, setSelectedSession] = useState<MobileSession | null>(null);
  const [messages, setMessages] = useState<MobileSessionMessage[]>([]);
  const [loading, setLoading] = useState(false);
  const [savingTakeover, setSavingTakeover] = useState(false);
  const [sendingMessage, setSendingMessage] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  function syncSession(updated: MobileSession): void {
    onSessionsChange(sessions.map((item) => (item.id === updated.id ? updated : item)));
    setSelectedSession((current) => (current?.id === updated.id ? updated : current));
  }

  async function openSession(session: MobileSession): Promise<void> {
    setSelectedSession(session);
    setLoading(true);
    setError(null);
    setSuccess(null);

    try {
      const data = await getSessionMessages(token, session.id);
      setMessages(data);
    } catch (err) {
      setMessages([]);
      setError(resolveErrorMessage(err));
    } finally {
      setLoading(false);
    }
  }

  async function handleTakeover(): Promise<void> {
    if (!selectedSession) return;

    setSavingTakeover(true);
    setError(null);
    setSuccess(null);

    try {
      const updated = selectedSession.is_human_takeover
        ? await releaseSessionTakeover(token, selectedSession.id)
        : await takeoverSession(token, selectedSession.id);

      syncSession(updated);
      setSuccess(
        updated.is_human_takeover
          ? 'Human takeover is active for this session.'
          : 'AI replies restored for this session.',
      );
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setSavingTakeover(false);
    }
  }

  async function handleSendMessage(): Promise<void> {
    if (!selectedSession || !replyText.trim()) return;

    setSendingMessage(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await sendSessionMessage(token, selectedSession.id, replyText.trim());
      syncSession(response.session);
      setMessages((current) => [...current, response.message]);
      setReplyText('');
      setSuccess('Reply sent to the session thread.');
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setSendingMessage(false);
    }
  }

  return (
    <View style={styles.sectionColumn}>
      <View style={styles.heroWrap}>
        <GlowBackground />
        <Image source={logo} style={styles.homeLogo} resizeMode="contain" />
        <Text style={[styles.headline, { color: theme.colors.text }]}>Sessions</Text>
        <Text style={[styles.subhead, { color: theme.colors.muted }]}>
          Review live threads, switch to human takeover, and send operator replies from mobile.
        </Text>
      </View>

      {sessions.map((session) => (
        <Pressable key={session.id} onPress={() => openSession(session)}>
          <CardRow
            title={session.visitor_identifier || session.visitor_ip || 'Anonymous visitor'}
            subtitle={session.first_message || session.page_url || 'No preview available'}
            meta={session.is_human_takeover ? 'Human live' : `${session.message_count} msgs`}
          />
        </Pressable>
      ))}

      {selectedSession ? (
        <View
          style={[
            styles.sectionCard,
            {
              backgroundColor: theme.colors.card,
              borderColor: theme.colors.border,
            },
          ]}
        >
          <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>
            {selectedSession.visitor_identifier || selectedSession.visitor_ip || 'Session detail'}
          </Text>
          <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
            {selectedSession.is_human_takeover
              ? 'AI is paused. Replies sent here will go out as the business operator.'
              : 'AI is active. Use takeover before replying manually.'}
          </Text>
          <View style={styles.statusRow}>
            <Pressable
              style={[
                styles.statusButton,
                {
                  backgroundColor: selectedSession.is_human_takeover
                    ? theme.colors.primary
                    : theme.colors.surface,
                  borderColor: selectedSession.is_human_takeover
                    ? theme.colors.primary
                    : theme.colors.border,
                  opacity: savingTakeover ? 0.7 : 1,
                },
              ]}
              disabled={savingTakeover}
              onPress={handleTakeover}
            >
              <Text
                style={[
                  styles.statusButtonText,
                  {
                    color: selectedSession.is_human_takeover
                      ? theme.colors.primaryText
                      : theme.colors.text,
                  },
                ]}
              >
                {savingTakeover
                  ? 'Saving...'
                  : selectedSession.is_human_takeover
                    ? 'Release to AI'
                    : 'Take Over'}
              </Text>
            </Pressable>
          </View>
          {error ? <ErrorBanner text={error} /> : null}
          {success ? <SuccessBanner text={success} /> : null}
          {loading ? (
            <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>Loading messages...</Text>
          ) : (
            <View style={styles.messagesColumn}>
              {messages.map((message) => (
                <View
                  key={message.id}
                  style={[
                    styles.messageBubble,
                    {
                      backgroundColor:
                        message.role === 'assistant'
                          ? message.human_takeover
                            ? theme.colors.card
                            : theme.colors.surface
                          : theme.colors.primary,
                      borderColor:
                        message.role === 'assistant'
                          ? message.human_takeover
                            ? theme.colors.accent
                            : theme.colors.border
                          : theme.colors.primary,
                    },
                  ]}
                >
                  <Text
                    style={[
                      styles.messageRole,
                      {
                        color:
                          message.role === 'assistant'
                            ? message.human_takeover
                              ? theme.colors.accent
                              : theme.colors.accent
                            : theme.colors.primaryText,
                      },
                    ]}
                  >
                    {message.role === 'assistant'
                      ? message.human_takeover
                        ? message.sent_by_name || 'Human operator'
                        : 'AI'
                      : 'Visitor'}
                  </Text>
                  <Text
                    style={[
                      styles.messageContent,
                      { color: message.role === 'assistant' ? theme.colors.text : theme.colors.primaryText },
                    ]}
                  >
                    {message.content}
                  </Text>
                </View>
              ))}
            </View>
          )}
          <Field
            label="Reply as business"
            value={replyText}
            onChangeText={setReplyText}
            placeholder="Type the human reply"
            multiline
          />
          <Pressable
            style={[
              styles.fullWidthPrimaryButton,
              {
                backgroundColor: theme.colors.primary,
                opacity: sendingMessage || !replyText.trim() ? 0.7 : 1,
              },
            ]}
            disabled={sendingMessage || !replyText.trim()}
            onPress={handleSendMessage}
          >
            <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
              {sendingMessage ? 'Sending...' : 'Send Human Reply'}
            </Text>
          </Pressable>
        </View>
      ) : null}
    </View>
  );
}

function LeadsTab({
  leads,
  token,
  onLeadsChange,
}: {
  leads: MobileLead[];
  token: string;
  onLeadsChange: (leads: MobileLead[]) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);
  const [selectedLeadId, setSelectedLeadId] = useState<number | null>(null);
  const [selectedLeadDetail, setSelectedLeadDetail] = useState<MobileLeadDetail | null>(null);
  const [savingLeadId, setSavingLeadId] = useState<number | null>(null);
  const [loadingLeadId, setLoadingLeadId] = useState<number | null>(null);
  const [linkedMessages, setLinkedMessages] = useState<MobileSessionMessage[]>([]);
  const [loadingLinkedMessages, setLoadingLinkedMessages] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function openLead(lead: MobileLead): Promise<void> {
    setSelectedLeadId(lead.id);
    setLoadingLeadId(lead.id);
    setError(null);
    setLinkedMessages([]);

    try {
      const detail = await getLeadDetail(token, lead.id);
      setSelectedLeadDetail(detail);
    } catch (err) {
      setSelectedLeadDetail(null);
      setError(resolveErrorMessage(err));
    } finally {
      setLoadingLeadId(null);
    }
  }

  async function changeStatus(lead: MobileLead, status: 'new' | 'contacted' | 'closed'): Promise<void> {
    setSavingLeadId(lead.id);
    setError(null);

    try {
      const updatedLead = await updateLeadStatus(token, lead.id, status);
      onLeadsChange(leads.map((item) => (item.id === updatedLead.id ? updatedLead : item)));
      if (selectedLeadId === updatedLead.id) {
        setSelectedLeadDetail(updatedLead);
      }
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setSavingLeadId(null);
    }
  }

  async function openLinkedSession(): Promise<void> {
    if (!selectedLeadDetail?.chat_session_id) return;

    setLoadingLinkedMessages(true);
    setError(null);

    try {
      const messages = await getSessionMessages(token, selectedLeadDetail.chat_session_id);
      setLinkedMessages(messages);
    } catch (err) {
      setLinkedMessages([]);
      setError(resolveErrorMessage(err));
    } finally {
      setLoadingLinkedMessages(false);
    }
  }

  const selectedLead = leads.find((lead) => lead.id === selectedLeadId) ?? null;

  return (
    <View style={styles.sectionColumn}>
      <View
        style={[
          styles.sectionCard,
          {
            backgroundColor: theme.colors.card,
            borderColor: theme.colors.border,
          },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Leads</Text>
        <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
          Tap a lead to inspect it, then push its status back to Laravel.
        </Text>
      </View>

      {error ? <ErrorBanner text={error} /> : null}

      {leads.map((lead) => (
        <Pressable key={lead.id} onPress={() => openLead(lead)}>
          <CardRow
            title={lead.name}
            subtitle={lead.user_request || lead.contact}
            meta={capitalize(lead.status)}
          />
        </Pressable>
      ))}

      {selectedLead ? (
        <View
          style={[
            styles.sectionCard,
            {
              backgroundColor: theme.colors.card,
              borderColor: theme.colors.border,
            },
          ]}
        >
          <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>{selectedLead.name}</Text>
          <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
            {selectedLead.contact}
          </Text>
          {loadingLeadId === selectedLead.id ? (
            <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>Loading lead detail...</Text>
          ) : (
            <>
              <Text style={[styles.detailText, { color: theme.colors.text }]}>
                {selectedLeadDetail?.user_request || selectedLead.user_request || 'No user request recorded.'}
              </Text>
              <Text style={[styles.detailMeta, { color: theme.colors.muted }]}>
                Trigger: {capitalize(selectedLead.trigger)}
              </Text>
              {selectedLeadDetail?.conversation_snapshot ? (
                <Text style={[styles.detailSnapshot, { color: theme.colors.text }]}>
                  Snapshot: {typeof selectedLeadDetail.conversation_snapshot === 'string'
                    ? selectedLeadDetail.conversation_snapshot
                    : JSON.stringify(selectedLeadDetail.conversation_snapshot)}
                </Text>
              ) : null}
              {selectedLeadDetail?.chat_session_id ? (
                <Pressable
                  style={[
                    styles.inlineActionButton,
                    {
                      backgroundColor: theme.colors.surface,
                      borderColor: theme.colors.border,
                    },
                  ]}
                  onPress={openLinkedSession}
                >
                  <Text style={[styles.inlineActionText, { color: theme.colors.accent }]}>
                    {loadingLinkedMessages ? 'Loading linked session...' : 'View linked session messages'}
                  </Text>
                </Pressable>
              ) : null}
              {linkedMessages.length > 0 ? (
                <View style={styles.messagesColumn}>
                  {linkedMessages.map((message) => (
                    <View
                      key={message.id}
                      style={[
                        styles.messageBubble,
                        {
                          backgroundColor:
                            message.role === 'assistant' ? theme.colors.surface : theme.colors.primary,
                          borderColor:
                            message.role === 'assistant' ? theme.colors.border : theme.colors.primary,
                        },
                      ]}
                    >
                      <Text
                        style={[
                          styles.messageRole,
                          {
                            color:
                              message.role === 'assistant'
                                ? theme.colors.accent
                                : theme.colors.primaryText,
                          },
                        ]}
                      >
                        {message.role === 'assistant' ? 'AI' : 'Visitor'}
                      </Text>
                      <Text
                        style={[
                          styles.messageContent,
                          {
                            color:
                              message.role === 'assistant'
                                ? theme.colors.text
                                : theme.colors.primaryText,
                          },
                        ]}
                      >
                        {message.content}
                      </Text>
                    </View>
                  ))}
                </View>
              ) : null}
            </>
          )}

          <View style={styles.statusRow}>
            {(['new', 'contacted', 'closed'] as const).map((status) => (
              <Pressable
                key={status}
                style={[
                  styles.statusButton,
                  {
                    backgroundColor:
                      selectedLead.status === status ? theme.colors.primary : theme.colors.surface,
                    borderColor:
                      selectedLead.status === status ? theme.colors.primary : theme.colors.border,
                    opacity: savingLeadId === selectedLead.id ? 0.65 : 1,
                  },
                ]}
                disabled={savingLeadId === selectedLead.id}
                onPress={() => changeStatus(selectedLead, status)}
              >
                <Text
                  style={[
                    styles.statusButtonText,
                    {
                      color:
                        selectedLead.status === status ? theme.colors.primaryText : theme.colors.text,
                    },
                  ]}
                >
                  {savingLeadId === selectedLead.id && selectedLead.status !== status
                    ? 'Saving...'
                    : capitalize(status)}
                </Text>
              </Pressable>
            ))}
          </View>
        </View>
      ) : null}
    </View>
  );
}

function KnowledgeTab({
  sources,
  token,
  onSourcesChange,
}: {
  sources: MobileKnowledgeSource[];
  token: string;
  onSourcesChange: (sources: MobileKnowledgeSource[]) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);
  const [sourceType, setSourceType] = useState<'manual' | 'url' | 'file'>('manual');
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [sourceUrl, setSourceUrl] = useState('');
  const [selectedFile, setSelectedFile] = useState<DocumentPicker.DocumentPickerAsset | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [workingId, setWorkingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  async function handlePickFile(): Promise<void> {
    setError(null);
    setSuccess(null);

    const result = await DocumentPicker.getDocumentAsync({
      multiple: false,
      copyToCacheDirectory: true,
      type: [
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      ],
    });

    if (result.canceled) {
      return;
    }

    setSelectedFile(result.assets[0] ?? null);
  }

  async function handleCreate(): Promise<void> {
    setSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      const created = await createKnowledgeSource(token, {
        title,
        source_type: sourceType,
        content: sourceType === 'manual' ? content : undefined,
        source_url: sourceType === 'url' ? sourceUrl : undefined,
        source_file: sourceType === 'file' ? selectedFile : undefined,
      });

      onSourcesChange([created, ...sources]);
      setTitle('');
      setContent('');
      setSourceUrl('');
      setSelectedFile(null);
      setSuccess('Knowledge source created.');
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  }

  async function handleRetry(source: MobileKnowledgeSource): Promise<void> {
    setWorkingId(source.id);
    setError(null);
    setSuccess(null);

    try {
      const updated = await retryKnowledgeSource(token, source.id);
      onSourcesChange(sources.map((item) => (item.id === updated.id ? updated : item)));
      setSuccess('Knowledge source queued again.');
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setWorkingId(null);
    }
  }

  async function handleDelete(source: MobileKnowledgeSource): Promise<void> {
    setWorkingId(source.id);
    setError(null);
    setSuccess(null);

    try {
      await deleteKnowledgeSource(token, source.id);
      onSourcesChange(sources.filter((item) => item.id !== source.id));
      setSuccess('Knowledge source deleted.');
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setWorkingId(null);
    }
  }

  return (
    <View style={styles.sectionColumn}>
      <View
        style={[
          styles.sectionCard,
          {
            backgroundColor: theme.colors.card,
            borderColor: theme.colors.border,
          },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Knowledge</Text>
        <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
          Existing sources available to the chatbot memory layer. Mobile supports manual notes, URLs, and document uploads.
        </Text>
      </View>

      {error ? <ErrorBanner text={error} /> : null}
      {success ? <SuccessBanner text={success} /> : null}

      <View
        style={[
          styles.sectionCard,
          {
            backgroundColor: theme.colors.card,
            borderColor: theme.colors.border,
          },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Add knowledge source</Text>

        <ChoiceRow
          label="Source type"
          value={sourceType}
          options={['manual', 'url', 'file']}
          onChange={(value) => setSourceType(value as 'manual' | 'url' | 'file')}
        />

        <Field
          label="Title"
          value={title}
          onChangeText={setTitle}
          placeholder="Pricing FAQ"
        />

        {sourceType === 'manual' ? (
          <Field
            label="Content"
            value={content}
            onChangeText={setContent}
            placeholder="Paste the knowledge content here"
            multiline
          />
        ) : sourceType === 'url' ? (
          <Field
            label="Source URL"
            value={sourceUrl}
            onChangeText={setSourceUrl}
            placeholder="https://example.com/faq"
            autoCapitalize="none"
          />
        ) : (
          <View style={styles.filePickerBlock}>
            <Text style={[styles.fieldLabel, { color: theme.colors.muted }]}>Source file</Text>
            <Pressable
              style={[
                styles.secondaryButton,
                {
                  backgroundColor: theme.colors.surface,
                  borderColor: theme.colors.border,
                  opacity: submitting ? 0.7 : 1,
                },
              ]}
              disabled={submitting}
              onPress={handlePickFile}
            >
              <Text style={[styles.secondaryButtonText, { color: theme.colors.text }]}>
                {selectedFile ? 'Change File' : 'Choose File'}
              </Text>
            </Pressable>
            <Text style={[styles.filePickerMeta, { color: theme.colors.muted }]}>
              {selectedFile ? selectedFile.name : 'PDF, TXT, DOC, or DOCX'}
            </Text>
          </View>
        )}

        <Pressable
          style={[
            styles.fullWidthPrimaryButton,
            { backgroundColor: theme.colors.primary, opacity: submitting ? 0.7 : 1 },
          ]}
          disabled={
            submitting ||
            !title ||
            (sourceType === 'manual' ? !content : sourceType === 'url' ? !sourceUrl : !selectedFile)
          }
          onPress={handleCreate}
        >
          <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
            {submitting ? 'Creating...' : 'Create Knowledge Source'}
          </Text>
        </Pressable>
      </View>

      {sources.map((source) => (
        <View key={source.id}>
          <CardRow
            title={source.title}
            subtitle={`${capitalize(source.source_type)} source · ${source.chunk_count} chunks`}
            meta={capitalize(source.status)}
          />
          <View style={styles.knowledgeActions}>
            {source.status === 'failed' ? (
              <Pressable
                style={[
                  styles.inlineActionButton,
                  {
                    backgroundColor: theme.colors.surface,
                    borderColor: theme.colors.border,
                    opacity: workingId === source.id ? 0.65 : 1,
                  },
                ]}
                disabled={workingId === source.id}
                onPress={() => handleRetry(source)}
              >
                <Text style={[styles.inlineActionText, { color: theme.colors.accent }]}>
                  {workingId === source.id ? 'Working...' : 'Retry'}
                </Text>
              </Pressable>
            ) : null}
            <Pressable
              style={[
                styles.inlineActionButton,
                {
                  backgroundColor: theme.colors.surface,
                  borderColor: theme.colors.border,
                  opacity: workingId === source.id ? 0.65 : 1,
                },
              ]}
              disabled={workingId === source.id}
              onPress={() => handleDelete(source)}
            >
              <Text style={[styles.inlineActionText, { color: '#fca5a5' }]}>
                {workingId === source.id ? 'Working...' : 'Delete'}
              </Text>
            </Pressable>
          </View>
        </View>
      ))}
    </View>
  );
}

function SettingsTab({
  settings,
  token,
  userContext,
  onLogout,
  onSettingsChange,
}: {
  settings: MobileSettingsPayload;
  token: string;
  userContext: MobileUserContext;
  onLogout: () => void;
  onSettingsChange: (settings: MobileSettingsPayload) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);
  const [form, setForm] = useState(settings.widget);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    setForm(settings.widget);
  }, [settings.widget]);

  async function save(): Promise<void> {
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const updated = await updateWidgetSettings(token, form);
      onSettingsChange(updated);
      setSuccess('Widget settings saved.');
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setSaving(false);
    }
  }

  return (
    <View style={styles.sectionColumn}>
      <View
        style={[
          styles.sectionCard,
          {
            backgroundColor: theme.colors.card,
            borderColor: theme.colors.border,
          },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Settings</Text>
        <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
          Save widget presentation changes back to the Laravel client record.
        </Text>
      </View>

      {error ? <ErrorBanner text={error} /> : null}
      {success ? <SuccessBanner text={success} /> : null}

      <View
        style={[
          styles.sectionCard,
          {
            backgroundColor: theme.colors.card,
            borderColor: theme.colors.border,
          },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>
          {userContext.client.name}
        </Text>
        <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
          {settings.client.contact_email || userContext.user.email}
        </Text>

        <Field
          label="Welcome message"
          value={form.welcome_message}
          onChangeText={(value) => setForm((current) => ({ ...current, welcome_message: value }))}
          placeholder="Ask us anything."
        />
        <Field
          label="Toggle text"
          value={form.toggle_text}
          onChangeText={(value) => setForm((current) => ({ ...current, toggle_text: value }))}
          placeholder="Ask anything about this business"
        />
        <Field
          label="Primary color"
          value={form.primary_color}
          onChangeText={(value) => setForm((current) => ({ ...current, primary_color: value }))}
          placeholder="#6366f1"
          autoCapitalize="characters"
        />
        <Field
          label="Accent color"
          value={form.accent_color}
          onChangeText={(value) => setForm((current) => ({ ...current, accent_color: value }))}
          placeholder="#8b5cf6"
          autoCapitalize="characters"
        />

        <ChoiceRow
          label="Widget style"
          value={form.widget_style}
          options={['classic', 'modern', 'glass']}
          onChange={(value) => setForm((current) => ({ ...current, widget_style: value }))}
        />
        <ChoiceRow
          label="Theme mode"
          value={form.theme_mode}
          options={['system', 'light', 'dark']}
          onChange={(value) => setForm((current) => ({ ...current, theme_mode: value }))}
        />
        <ChoiceRow
          label="Position"
          value={form.position}
          options={['right', 'left']}
          onChange={(value) => setForm((current) => ({ ...current, position: value }))}
        />

        <ToggleRow
          label="Show branding"
          value={form.show_branding}
          onValueChange={(value) => setForm((current) => ({ ...current, show_branding: value }))}
        />
        <ToggleRow
          label="Default expanded"
          value={form.default_expanded}
          onValueChange={(value) => setForm((current) => ({ ...current, default_expanded: value }))}
        />

        <Pressable
          style={[
            styles.fullWidthPrimaryButton,
            { backgroundColor: theme.colors.primary, opacity: saving ? 0.7 : 1 },
          ]}
          disabled={saving}
          onPress={save}
        >
          <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
            {saving ? 'Saving...' : 'Save Widget Settings'}
          </Text>
        </Pressable>
      </View>

      <Pressable
        style={[
          styles.fullWidthSecondaryButton,
          { backgroundColor: theme.colors.surface, borderColor: theme.colors.border },
        ]}
        onPress={onLogout}
      >
        <Text style={[styles.primaryButtonText, { color: theme.colors.text }]}>Logout</Text>
      </Pressable>
    </View>
  );
}

function Field({
  label,
  value,
  onChangeText,
  placeholder,
  secureTextEntry,
  autoCapitalize,
  keyboardType,
  multiline,
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder: string;
  secureTextEntry?: boolean;
  autoCapitalize?: 'none' | 'sentences' | 'words' | 'characters';
  keyboardType?: 'default' | 'email-address';
  multiline?: boolean;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View style={styles.fieldGroup}>
      <Text style={[styles.fieldLabel, { color: theme.colors.text }]}>{label}</Text>
      <TextInput
        secureTextEntry={secureTextEntry}
        autoCapitalize={autoCapitalize}
        keyboardType={keyboardType}
        autoCorrect={false}
        multiline={multiline}
        placeholder={placeholder}
        placeholderTextColor={theme.colors.subtle}
        style={[
          styles.input,
          multiline ? styles.multilineInput : null,
          {
            backgroundColor: theme.colors.surface,
            borderColor: theme.colors.border,
            color: theme.colors.text,
          },
        ]}
        value={value}
        onChangeText={onChangeText}
      />
    </View>
  );
}

function ChoiceRow({
  label,
  value,
  options,
  onChange,
}: {
  label: string;
  value: string;
  options: string[];
  onChange: (value: string) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View style={styles.fieldGroup}>
      <Text style={[styles.fieldLabel, { color: theme.colors.text }]}>{label}</Text>
      <View style={styles.optionRow}>
        {options.map((option) => (
          <Pressable
            key={option}
            style={[
              styles.optionButton,
              {
                backgroundColor: value === option ? theme.colors.primary : theme.colors.surface,
                borderColor: value === option ? theme.colors.primary : theme.colors.border,
              },
            ]}
            onPress={() => onChange(option)}
          >
            <Text
              style={[
                styles.optionButtonText,
                { color: value === option ? theme.colors.primaryText : theme.colors.text },
              ]}
            >
              {capitalize(option)}
            </Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

function ToggleRow({
  label,
  value,
  onValueChange,
}: {
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View style={styles.toggleRow}>
      <Text style={[styles.fieldLabel, { color: theme.colors.text }]}>{label}</Text>
      <Switch
        value={value}
        onValueChange={onValueChange}
        trackColor={{ false: theme.colors.subtle, true: theme.colors.primary }}
      />
    </View>
  );
}

function CardRow({
  title,
  subtitle,
  meta,
}: {
  title: string;
  subtitle: string;
  meta: string;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View
      style={[
        styles.sessionRow,
        {
          backgroundColor: theme.colors.card,
          borderColor: theme.colors.border,
        },
      ]}
    >
      <View style={[styles.sessionDot, { backgroundColor: theme.colors.accent }]} />
      <View style={styles.sessionCopy}>
        <Text style={[styles.sessionName, { color: theme.colors.text }]}>{title}</Text>
        <Text style={[styles.sessionDetail, { color: theme.colors.muted }]}>{subtitle}</Text>
      </View>
      <Text style={[styles.sessionStatus, { color: theme.colors.subtle }]}>{meta}</Text>
    </View>
  );
}

function ErrorBanner({ text, inset = false }: { text: string; inset?: boolean }) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View
      style={[
        styles.errorBanner,
        {
          backgroundColor: theme.colors.surface,
          borderColor: theme.colors.border,
          marginHorizontal: inset ? 20 : 0,
        },
      ]}
    >
      <Text style={[styles.errorText, { color: '#fca5a5' }]}>{text}</Text>
    </View>
  );
}

function SuccessBanner({ text }: { text: string }) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <View
      style={[
        styles.errorBanner,
        {
          backgroundColor: theme.colors.surface,
          borderColor: theme.colors.border,
        },
      ]}
    >
      <Text style={[styles.errorText, { color: theme.colors.success }]}>{text}</Text>
    </View>
  );
}

function GlowBackground() {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  return (
    <>
      <View
        style={[
          styles.glow,
          styles.glowPrimary,
          { backgroundColor: theme.colors.primaryGlow },
        ]}
      />
      <View
        style={[
          styles.glow,
          styles.glowAccent,
          { backgroundColor: theme.colors.accentGlow },
        ]}
      />
    </>
  );
}

function resolveErrorMessage(error: unknown): string {
  if (error instanceof Error) {
    return error.message;
  }

  return 'Something went wrong.';
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  appShell: {
    flex: 1,
  },
  appHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 14,
  },
  appHeaderCopy: {
    flex: 1,
    gap: 2,
    paddingRight: 12,
  },
  appHeaderTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 18,
  },
  appHeaderSubtitle: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
  },
  refreshButton: {
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  refreshButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
  tabBar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 12,
    gap: 8,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 8,
  },
  tabButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 11,
  },
  loader: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  centeredScreen: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  splashOrb: {
    position: 'absolute',
    top: 120,
    width: 260,
    height: 260,
    borderRadius: 999,
    opacity: 0.18,
  },
  splashOrbSecondary: {
    position: 'absolute',
    bottom: 120,
    width: 180,
    height: 180,
    borderRadius: 999,
    opacity: 0.16,
  },
  splashLogo: {
    width: 132,
    height: 132,
    marginBottom: 18,
  },
  splashWordmark: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 34,
    letterSpacing: -1.2,
  },
  splashTagline: {
    marginTop: 10,
    fontFamily: 'Inter_500Medium',
    fontSize: 14,
    textAlign: 'center',
  },
  onboardingContent: {
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 36,
    gap: 18,
  },
  loginScreen: {
    flex: 1,
  },
  loginContent: {
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 36,
    gap: 20,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 36,
    gap: 18,
  },
  sectionColumn: {
    gap: 12,
  },
  heroWrap: {
    position: 'relative',
    overflow: 'hidden',
    gap: 16,
    padding: 24,
    borderRadius: 28,
  },
  heroLogo: {
    width: 82,
    height: 82,
  },
  homeLogo: {
    width: 48,
    height: 48,
  },
  glow: {
    position: 'absolute',
    width: 220,
    height: 220,
    borderRadius: 999,
    opacity: 0.18,
  },
  glowPrimary: {
    top: -80,
    left: -60,
  },
  glowAccent: {
    right: -70,
    bottom: -90,
  },
  badge: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  badgeDot: {
    width: 8,
    height: 8,
    borderRadius: 999,
  },
  badgeText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 12,
  },
  headline: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 34,
    lineHeight: 40,
    letterSpacing: -1,
    maxWidth: 330,
  },
  subhead: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 24,
    maxWidth: 330,
  },
  cardsColumn: {
    gap: 12,
  },
  onboardingCard: {
    borderWidth: 1,
    borderRadius: 22,
    padding: 18,
    gap: 8,
  },
  onboardingCardTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 17,
    lineHeight: 23,
  },
  onboardingCardBody: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 22,
  },
  loginLogo: {
    width: 86,
    height: 86,
    marginTop: 18,
    marginBottom: 4,
    alignSelf: 'center',
  },
  loginTitle: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 30,
    lineHeight: 36,
    letterSpacing: -0.8,
  },
  loginSubtitle: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 24,
  },
  loginCard: {
    borderWidth: 1,
    borderRadius: 26,
    padding: 18,
    gap: 16,
  },
  fieldGroup: {
    gap: 8,
  },
  fieldLabel: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  input: {
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontFamily: 'Inter_500Medium',
    fontSize: 15,
  },
  rememberRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderWidth: 1,
    borderRadius: 7,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxInner: {
    width: 8,
    height: 8,
    borderRadius: 999,
  },
  rememberText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 13,
    flex: 1,
  },
  fullWidthPrimaryButton: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderRadius: 16,
    shadowOpacity: 0.22,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 10 },
    elevation: 6,
  },
  fullWidthSecondaryButton: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderRadius: 16,
    borderWidth: 1,
  },
  secondaryButton: {
    minHeight: 52,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 14,
    borderRadius: 16,
    borderWidth: 1,
  },
  secondaryButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 14,
  },
  primaryButtonText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
  },
  backButton: {
    alignItems: 'center',
    paddingTop: 2,
  },
  backButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  sectionCard: {
    borderWidth: 1,
    borderRadius: 24,
    padding: 18,
    gap: 10,
  },
  sectionTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 18,
  },
  sectionIntro: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 21,
  },
  filePickerBlock: {
    gap: 10,
  },
  filePickerMeta: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
    lineHeight: 19,
  },
  sessionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderWidth: 1,
    borderRadius: 18,
    padding: 14,
  },
  sessionDot: {
    width: 10,
    height: 10,
    borderRadius: 999,
  },
  sessionCopy: {
    flex: 1,
    gap: 4,
  },
  sessionName: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
  },
  sessionDetail: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
    lineHeight: 18,
  },
  sessionStatus: {
    maxWidth: 82,
    textAlign: 'right',
    fontFamily: 'Inter_600SemiBold',
    fontSize: 11,
    lineHeight: 15,
    textTransform: 'uppercase',
  },
  errorBanner: {
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  errorText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 13,
    lineHeight: 18,
  },
  messagesColumn: {
    gap: 10,
  },
  messageBubble: {
    borderWidth: 1,
    borderRadius: 16,
    padding: 12,
    gap: 6,
  },
  messageRole: {
    fontFamily: 'Inter_700Bold',
    fontSize: 11,
    textTransform: 'uppercase',
  },
  messageContent: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 20,
  },
  detailText: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 22,
  },
  detailMeta: {
    fontFamily: 'Inter_500Medium',
    fontSize: 12,
  },
  detailSnapshot: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
    lineHeight: 18,
  },
  knowledgeActions: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 8,
    marginLeft: 22,
    flexWrap: 'wrap',
  },
  statusRow: {
    flexDirection: 'row',
    gap: 8,
    flexWrap: 'wrap',
  },
  statusButton: {
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  statusButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
  optionRow: {
    flexDirection: 'row',
    gap: 8,
    flexWrap: 'wrap',
  },
  optionButton: {
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  optionButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
  inlineActionButton: {
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    alignSelf: 'flex-start',
  },
  inlineActionText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
  multilineInput: {
    minHeight: 120,
    textAlignVertical: 'top',
  },
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
});
