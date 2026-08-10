import {
  Inter_400Regular,
  Inter_500Medium,
  Inter_600SemiBold,
  Inter_700Bold,
  Inter_800ExtraBold,
  useFonts,
} from '@expo-google-fonts/inter';
import { Ionicons } from '@expo/vector-icons';
import * as SecureStore from 'expo-secure-store';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  useColorScheme,
  View,
} from 'react-native';
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context';
import {
  changePassword,
  fetchKnowledgePage,
  getSessionMessages,
  getLeadDetail,
  loadMobileAppData,
  login as loginRequest,
  logout as logoutRequest,
  releaseSessionTakeover,
  sendSessionMessage,
  takeoverSession,
  updateLeadStatus,
  updateWidgetSettings,
  type MobileAppBootstrap,
  type MobileKnowledgeSource,
  type MobileLead,
  type MobileLeadDetail,
  type MobilePaginatedKnowledge,
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

type TabItem = { key: Tab; label: string; icon: keyof typeof Ionicons.glyphMap; iconActive: keyof typeof Ionicons.glyphMap };

const TAB_ITEMS: TabItem[] = [
  { key: 'sessions',  label: 'Sessions',  icon: 'chatbubble-outline',  iconActive: 'chatbubble' },
  { key: 'leads',     label: 'Leads',     icon: 'flash-outline',       iconActive: 'flash' },
  { key: 'knowledge', label: 'Knowledge', icon: 'library-outline',     iconActive: 'library' },
  { key: 'settings',  label: 'Settings',  icon: 'settings-outline',    iconActive: 'settings' },
];

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

      const storedToken = await SecureStore.getItemAsync(TOKEN_STORAGE_KEY);

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
        await SecureStore.deleteItemAsync(TOKEN_STORAGE_KEY);

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
        await SecureStore.setItemAsync(TOKEN_STORAGE_KEY, auth.token);
      } else {
        await SecureStore.deleteItemAsync(TOKEN_STORAGE_KEY);
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

    await SecureStore.deleteItemAsync(TOKEN_STORAGE_KEY);
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
    <SafeAreaProvider>
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
    </SafeAreaProvider>
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
        <Text style={[styles.headline, { color: theme.colors.text }]}>
          Stay on top of every conversation.
        </Text>
        <Text style={[styles.subhead, { color: theme.colors.muted }]}>
          Monitor chats, capture leads, and step in when a human reply matters.
        </Text>
        <View style={styles.cardsColumn}>
          {[
            ['Live sessions', 'See active threads and recent messages fast.'],
            ['Lead follow-up', 'Review leads and move quickly on high-intent visitors.'],
            ['Human takeover', 'Pause AI and reply directly from your mobile device.'],
          ].map(([title, body]) => (
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
              {body}
            </Text>
          </View>
          ))}
        </View>
      </View>

      <Pressable
        style={[
          styles.fullWidthPrimaryButton,
          { backgroundColor: theme.colors.primary, shadowColor: theme.colors.primary },
        ]}
        onPress={onContinue}
      >
        <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
          Sign In
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
  const canSubmit = Boolean(email.trim() && password.trim() && !isSubmitting);

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
          Access sessions, leads, knowledge, and settings from one place.
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
              {
                backgroundColor: canSubmit ? theme.colors.primary : theme.colors.subtle,
                opacity: canSubmit ? 1 : 0.7,
              },
            ]}
            disabled={!canSubmit}
            onPress={onLogin}
          >
            {isSubmitting ? (
              <View style={styles.buttonLoadingRow}>
                <ActivityIndicator color={theme.colors.primaryText} />
                <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
                  Signing In...
                </Text>
              </View>
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
  const [sessionThreadOpen, setSessionThreadOpen] = useState(false);

  const tabContent = activeTab === 'sessions' ? (
    <SessionsTab
      sessions={appData.sessions}
      token={token}
      onSessionsChange={(sessions) => onDataChange({ ...appData, sessions })}
      onThreadOpen={() => setSessionThreadOpen(true)}
      onThreadClose={() => setSessionThreadOpen(false)}
    />
  ) : activeTab === 'leads' ? (
    <LeadsTab
      leads={appData.leads}
      token={token}
      onLeadsChange={(leads) => onDataChange({ ...appData, leads })}
    />
  ) : activeTab === 'knowledge' ? (
    <KnowledgeTab
      initialSources={appData.knowledgeSources}
      initialMeta={appData.knowledgeMeta}
      token={token}
    />
  ) : (
    <SettingsTab
      settings={appData.settings}
      token={token}
      userContext={userContext}
      onLogout={onLogout}
      onSettingsChange={(settings) => onDataChange({ ...appData, settings })}
    />
  );

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

      {sessionThreadOpen ? (
        <View style={{ flex: 1 }}>{tabContent}</View>
      ) : (
        <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {tabContent}
        </ScrollView>
      )}

      <View
        style={[
          styles.tabBar,
          {
            backgroundColor: theme.colors.card,
            borderTopWidth: 1,
            borderTopColor: theme.colors.border,
          },
        ]}
      >
        {TAB_ITEMS.map((tab) => {
          const isActive = activeTab === tab.key;
          return (
            <Pressable
              key={tab.key}
              style={styles.tabButton}
              onPress={() => onChangeTab(tab.key)}
            >
              <View
                style={[
                  styles.tabIconPill,
                  isActive && {
                    backgroundColor: theme.colors.primary,
                  },
                ]}
              >
                <Ionicons
                  name={isActive ? tab.iconActive : tab.icon}
                  size={22}
                  color={isActive ? '#ffffff' : theme.colors.muted}
                />
              </View>
              <Text
                style={[
                  styles.tabLabel,
                  { color: isActive ? theme.colors.primary : theme.colors.muted },
                ]}
              >
                {tab.label}
              </Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

function SessionsTab({
  sessions,
  token,
  onSessionsChange,
  onThreadOpen,
  onThreadClose,
}: {
  sessions: MobileSession[];
  token: string;
  onSessionsChange: (sessions: MobileSession[]) => void;
  onThreadOpen: () => void;
  onThreadClose: () => void;
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
    onThreadOpen();
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

  // When a session is open, render the full chat thread view
  if (selectedSession) {
    return (
      <View style={styles.threadScreen}>
        {/* Sticky header */}
        <View style={[styles.threadHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <Pressable style={styles.threadBackBtn} onPress={() => {
            setSelectedSession(null);
            setMessages([]);
            setError(null);
            setSuccess(null);
            onThreadClose();
          }}>
            <Ionicons name="chevron-back" size={20} color={theme.colors.primary} />
            <Text style={[styles.threadBackText, { color: theme.colors.primary }]}>Sessions</Text>
          </Pressable>

          <View style={styles.threadHeaderCenter}>
            <View style={[styles.threadAvatar, { backgroundColor: theme.colors.primary + '22' }]}>
              <Ionicons name="person" size={16} color={theme.colors.primary} />
            </View>
            <View style={{ gap: 1 }}>
              <Text style={[styles.threadHeaderName, { color: theme.colors.text }]} numberOfLines={1}>
                {selectedSession.visitor_identifier || selectedSession.visitor_ip || 'Anonymous'}
              </Text>
              <Text style={[styles.threadHeaderMeta, { color: theme.colors.muted }]}>
                {selectedSession.message_count} messages
              </Text>
            </View>
          </View>

          {/* Takeover toggle pill */}
          <Pressable
            style={[
              styles.takeoverPill,
              {
                backgroundColor: selectedSession.is_human_takeover ? theme.colors.primary : theme.colors.surface,
                borderColor: selectedSession.is_human_takeover ? theme.colors.primary : theme.colors.border,
                opacity: savingTakeover ? 0.6 : 1,
              },
            ]}
            disabled={savingTakeover}
            onPress={handleTakeover}
          >
            <Ionicons
              name={selectedSession.is_human_takeover ? 'hand-left' : 'hardware-chip-outline'}
              size={13}
              color={selectedSession.is_human_takeover ? '#fff' : theme.colors.muted}
            />
            <Text style={[styles.takeoverPillText, { color: selectedSession.is_human_takeover ? '#fff' : theme.colors.muted }]}>
              {savingTakeover ? '...' : selectedSession.is_human_takeover ? 'Live' : 'AI'}
            </Text>
          </Pressable>
        </View>

        {/* Status banner */}
        {selectedSession.is_human_takeover ? (
          <View style={[styles.takeoverBanner, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}>
            <Ionicons name="hand-left" size={14} color={theme.colors.primary} />
            <Text style={[styles.takeoverBannerText, { color: theme.colors.primary }]}>
              You are live — replies go directly to the visitor
            </Text>
          </View>
        ) : (
          <View style={[styles.takeoverBanner, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
            <Ionicons name="hardware-chip-outline" size={14} color={theme.colors.muted} />
            <Text style={[styles.takeoverBannerText, { color: theme.colors.muted }]}>
              AI is active — tap Live to take over this conversation
            </Text>
          </View>
        )}

        {error ? <ErrorBanner text={error} /> : null}
        {success ? <SuccessBanner text={success} /> : null}

        {/* Scrollable messages */}
        <ScrollView style={styles.threadScrollArea} contentContainerStyle={styles.threadScrollContent} showsVerticalScrollIndicator={false}>
          {loading ? (
            <View style={styles.threadLoadingWrap}>
              <ActivityIndicator color={theme.colors.primary} />
              <Text style={[styles.threadLoadingText, { color: theme.colors.muted }]}>Loading messages...</Text>
            </View>
          ) : messages.length === 0 ? (
            <View style={styles.threadEmptyWrap}>
              <Ionicons name="chatbubbles-outline" size={36} color={theme.colors.subtle} />
              <Text style={[styles.threadEmptyText, { color: theme.colors.muted }]}>No messages yet</Text>
            </View>
          ) : (
            <View style={styles.messagesColumn}>
              {messages.map((message) => {
                const isVisitor = message.role === 'user';
                const isHuman = message.role === 'assistant' && message.human_takeover;
                const isAI = message.role === 'assistant' && !message.human_takeover;

                return (
                  <View
                    key={message.id}
                    style={[
                      styles.chatBubbleRow,
                      isVisitor ? styles.chatBubbleRowLeft : styles.chatBubbleRowRight,
                    ]}
                  >
                    {isVisitor ? (
                      <View style={[styles.chatAvatarSmall, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                        <Ionicons name="person-outline" size={11} color={theme.colors.muted} />
                      </View>
                    ) : null}

                    <View style={{ maxWidth: '75%', gap: 4 }}>
                      <Text style={[styles.chatSenderLabel, { color: isHuman ? theme.colors.accent : isAI ? theme.colors.primary : theme.colors.muted, textAlign: isVisitor ? 'left' : 'right' }]}>
                        {isVisitor ? 'Visitor' : isHuman ? (message.sent_by_name || 'You') : 'ZaoChat AI'}
                      </Text>
                      <View
                        style={[
                          styles.chatBubble,
                          isVisitor
                            ? [styles.chatBubbleVisitor, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]
                            : isHuman
                            ? [styles.chatBubbleHuman, { backgroundColor: theme.colors.accent + 'ee', borderColor: theme.colors.accent }]
                            : [styles.chatBubbleAI, { backgroundColor: theme.colors.primary, borderColor: theme.colors.primary }],
                        ]}
                      >
                        <Text
                          style={[
                            styles.chatBubbleText,
                            { color: isVisitor ? theme.colors.text : '#ffffff' },
                          ]}
                        >
                          {message.content}
                        </Text>
                      </View>
                    </View>

                    {!isVisitor ? (
                      <View style={[
                        styles.chatAvatarSmall,
                        {
                          backgroundColor: isHuman ? theme.colors.accent + '22' : theme.colors.primary + '22',
                          borderColor: isHuman ? theme.colors.accent + '44' : theme.colors.primary + '44',
                        },
                      ]}>
                        <Ionicons name={isHuman ? 'person' : 'hardware-chip-outline'} size={11} color={isHuman ? theme.colors.accent : theme.colors.primary} />
                      </View>
                    ) : null}
                  </View>
                );
              })}
            </View>
          )}
        </ScrollView>

        {/* Sticky footer — reply box or takeover CTA */}
        {selectedSession.is_human_takeover ? (
          <View style={{ gap: 8 }}>
            <View style={[styles.replyBox, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
              <TextInput
                style={[styles.replyInput, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border, color: theme.colors.text }]}
                placeholder="Type your reply..."
                placeholderTextColor={theme.colors.subtle}
                value={replyText}
                onChangeText={setReplyText}
                multiline
                maxLength={2000}
              />
              <Pressable
                style={[
                  styles.replySendBtn,
                  {
                    backgroundColor: replyText.trim() && !sendingMessage ? theme.colors.primary : theme.colors.subtle,
                  },
                ]}
                disabled={!replyText.trim() || sendingMessage}
                onPress={handleSendMessage}
              >
                {sendingMessage
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Ionicons name="send" size={18} color="#fff" />
                }
              </Pressable>
            </View>
            <Pressable
              style={[styles.handBackBtn, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border, opacity: savingTakeover ? 0.6 : 1 }]}
              disabled={savingTakeover}
              onPress={async () => {
                await handleTakeover();
                setSelectedSession(null);
                setMessages([]);
                setError(null);
                setSuccess(null);
                onThreadClose();
              }}
            >
              <Ionicons name="hardware-chip-outline" size={15} color={theme.colors.muted} />
              <Text style={[styles.handBackText, { color: theme.colors.muted }]}>
                {savingTakeover ? 'Releasing...' : 'Hand back to AI & exit'}
              </Text>
            </Pressable>
          </View>
        ) : (
          <Pressable
            style={[styles.takeoverCta, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}
            onPress={handleTakeover}
          >
            <Ionicons name="hand-left-outline" size={16} color={theme.colors.primary} />
            <Text style={[styles.takeoverCtaText, { color: theme.colors.primary }]}>
              Tap to take over and reply as yourself
            </Text>
          </Pressable>
        )}
      </View>
    );
  }

  // Session list view
  const liveCount = sessions.filter((s) => s.is_human_takeover).length;

  return (
    <View style={styles.sectionColumn}>
      {/* Header */}
      <View style={[styles.sessionsHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
        <View style={{ gap: 4 }}>
          <Text style={[styles.sessionsHeaderTitle, { color: theme.colors.text }]}>Live Sessions</Text>
          <Text style={[styles.sessionsHeaderSub, { color: theme.colors.muted }]}>
            {sessions.length === 0 ? 'No active sessions' : `${sessions.length} session${sessions.length === 1 ? '' : 's'}${liveCount > 0 ? ` · ${liveCount} live` : ''}`}
          </Text>
        </View>
        {liveCount > 0 ? (
          <View style={[styles.liveChip, { backgroundColor: '#4ade8022', borderColor: '#4ade8055' }]}>
            <View style={styles.liveDot} />
            <Text style={[styles.liveChipText, { color: '#4ade80' }]}>{liveCount} Live</Text>
          </View>
        ) : null}
      </View>

      {sessions.length === 0 ? (
        <View style={[styles.sessionsEmpty, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <Ionicons name="chatbubbles-outline" size={40} color={theme.colors.subtle} />
          <Text style={[styles.sessionsEmptyTitle, { color: theme.colors.text }]}>No sessions yet</Text>
          <Text style={[styles.sessionsEmptyBody, { color: theme.colors.muted }]}>
            Sessions appear here as soon as visitors start chatting on your widget.
          </Text>
        </View>
      ) : (
        sessions.map((session) => {
          const isLive = session.is_human_takeover;
          const visitorName = session.visitor_identifier || session.visitor_ip || 'Anonymous visitor';
          const preview = session.first_message || session.page_url || 'No preview';

          return (
            <Pressable
              key={session.id}
              style={[
                styles.sessionCard,
                {
                  backgroundColor: theme.colors.card,
                  borderColor: isLive ? theme.colors.primary + '60' : theme.colors.border,
                },
                isLive && { shadowColor: theme.colors.primary, shadowOpacity: 0.15, shadowRadius: 12, shadowOffset: { width: 0, height: 4 }, elevation: 4 },
              ]}
              onPress={() => openSession(session)}
            >
              <View style={styles.sessionCardLeft}>
                <View style={[
                  styles.sessionCardAvatar,
                  { backgroundColor: isLive ? theme.colors.primary + '22' : theme.colors.surface, borderColor: isLive ? theme.colors.primary + '44' : theme.colors.border },
                ]}>
                  <Ionicons name="person" size={18} color={isLive ? theme.colors.primary : theme.colors.muted} />
                  {isLive ? <View style={styles.sessionCardLiveDot} /> : null}
                </View>
              </View>

              <View style={styles.sessionCardBody}>
                <View style={styles.sessionCardTopRow}>
                  <Text style={[styles.sessionCardName, { color: theme.colors.text }]} numberOfLines={1}>
                    {visitorName}
                  </Text>
                  <View style={[
                    styles.sessionCardBadge,
                    { backgroundColor: isLive ? theme.colors.primary + '22' : theme.colors.surface, borderColor: isLive ? theme.colors.primary + '44' : theme.colors.border },
                  ]}>
                    <Text style={[styles.sessionCardBadgeText, { color: isLive ? theme.colors.primary : theme.colors.muted }]}>
                      {isLive ? 'You\'re live' : `${session.message_count} msgs`}
                    </Text>
                  </View>
                </View>
                <Text style={[styles.sessionCardPreview, { color: theme.colors.muted }]} numberOfLines={1}>
                  {preview}
                </Text>
                {session.page_url ? (
                  <Text style={[styles.sessionCardUrl, { color: theme.colors.subtle }]} numberOfLines={1}>
                    {session.page_url}
                  </Text>
                ) : null}
              </View>

              <Ionicons name="chevron-forward" size={16} color={theme.colors.subtle} />
            </Pressable>
          );
        })
      )}
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

const WEB_APP_URL = 'https://app.zaochat.com';

const STATUS_COLORS: Record<string, string> = {
  ready: '#4ade80',
  processing: '#fbbf24',
  queued: '#60a5fa',
  failed: '#f87171',
  draft: '#9ca3af',
};

const FALLBACK_META: MobilePaginatedKnowledge['meta'] = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  has_more: false,
};

function KnowledgeTab({
  initialSources,
  initialMeta,
  token,
}: {
  initialSources: MobileKnowledgeSource[];
  initialMeta: MobilePaginatedKnowledge['meta'] | undefined;
  token: string;
}) {
  const scheme = useColorScheme();
  const theme = getTheme(scheme);

  const [sources, setSources] = useState<MobileKnowledgeSource[]>(initialSources ?? []);
  const [meta, setMeta] = useState<MobilePaginatedKnowledge['meta']>(initialMeta ?? { ...FALLBACK_META, total: (initialSources ?? []).length });
  const [loadingPage, setLoadingPage] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function loadPage(page: number): Promise<void> {
    setLoadingPage(true);
    setError(null);
    try {
      const result = await fetchKnowledgePage(token, page);
      setSources(result.knowledge_sources);
      setMeta(result.meta);
    } catch (err) {
      setError(resolveErrorMessage(err));
    } finally {
      setLoadingPage(false);
    }
  }

  const readyCount = sources.filter((s) => s.status === 'ready').length;

  return (
    <View style={styles.sectionColumn}>
      {/* Web callout */}
      <Pressable
        style={[
          styles.knowledgeCallout,
          { backgroundColor: theme.colors.primary + '14', borderColor: theme.colors.primary + '35' },
        ]}
        onPress={() => Linking.openURL(WEB_APP_URL + '/knowledge')}
      >
        <View style={[styles.knowledgeCalloutIcon, { backgroundColor: theme.colors.primary + '20' }]}>
          <Ionicons name="desktop-outline" size={22} color={theme.colors.primary} />
        </View>
        <View style={styles.knowledgeCalloutCopy}>
          <Text style={[styles.knowledgeCalloutTitle, { color: theme.colors.text }]}>
            Manage on the web app
          </Text>
          <Text style={[styles.knowledgeCalloutBody, { color: theme.colors.muted }]}>
            Add, edit, or delete sources from the web dashboard.
          </Text>
          <View style={styles.knowledgeCalloutLink}>
            <Text style={[styles.knowledgeCalloutUrl, { color: theme.colors.primary }]}>
              app.zaochat.com
            </Text>
            <Ionicons name="arrow-forward" size={12} color={theme.colors.primary} />
          </View>
        </View>
      </Pressable>

      {/* Summary bar */}
      <View
        style={[styles.sectionCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Knowledge base</Text>
        {meta.total === 0 ? (
          <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
            No sources yet. Visit the web app to add your first document or URL.
          </Text>
        ) : (
          <View style={styles.knowledgeStatsRow}>
            <View style={styles.knowledgeStat}>
              <Text style={[styles.knowledgeStatNum, { color: theme.colors.text }]}>{meta.total}</Text>
              <Text style={[styles.knowledgeStatLabel, { color: theme.colors.muted }]}>Total</Text>
            </View>
            <View style={[styles.knowledgeStatDivider, { backgroundColor: theme.colors.border }]} />
            <View style={styles.knowledgeStat}>
              <Text style={[styles.knowledgeStatNum, { color: '#4ade80' }]}>{readyCount}</Text>
              <Text style={[styles.knowledgeStatLabel, { color: theme.colors.muted }]}>Ready</Text>
            </View>
            <View style={[styles.knowledgeStatDivider, { backgroundColor: theme.colors.border }]} />
            <View style={styles.knowledgeStat}>
              <Text style={[styles.knowledgeStatNum, { color: theme.colors.text }]}>
                {meta.last_page > 1 ? `${meta.current_page}/${meta.last_page}` : '—'}
              </Text>
              <Text style={[styles.knowledgeStatLabel, { color: theme.colors.muted }]}>Page</Text>
            </View>
          </View>
        )}
      </View>

      {error ? <ErrorBanner text={error} /> : null}

      {/* Source list */}
      {sources.map((source) => (
        <View
          key={source.id}
          style={[
            styles.knowledgeItem,
            { backgroundColor: theme.colors.card, borderColor: theme.colors.border },
          ]}
        >
          <View style={styles.knowledgeItemHeader}>
            <View style={[styles.knowledgeTypeTag, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
              <Ionicons
                name={source.source_type === 'url' ? 'link-outline' : source.source_type === 'file' ? 'document-outline' : 'create-outline'}
                size={12}
                color={theme.colors.muted}
              />
              <Text style={[styles.knowledgeTypeText, { color: theme.colors.muted }]}>
                {capitalize(source.source_type)}
              </Text>
            </View>
            <View style={[styles.knowledgeStatusDot, { backgroundColor: STATUS_COLORS[source.status] ?? '#9ca3af' }]} />
            <Text style={[styles.knowledgeStatusText, { color: STATUS_COLORS[source.status] ?? theme.colors.muted }]}>
              {capitalize(source.status)}
            </Text>
          </View>

          <Text style={[styles.knowledgeItemTitle, { color: theme.colors.text }]} numberOfLines={2}>
            {source.title}
          </Text>

          {source.source_url ? (
            <Text style={[styles.knowledgeItemMeta, { color: theme.colors.muted }]} numberOfLines={1}>
              {source.source_url}
            </Text>
          ) : source.file_name ? (
            <Text style={[styles.knowledgeItemMeta, { color: theme.colors.muted }]} numberOfLines={1}>
              {source.file_name}
            </Text>
          ) : null}

          <View style={styles.knowledgeItemFooter}>
            <Text style={[styles.knowledgeItemChunks, { color: theme.colors.subtle }]}>
              {source.chunk_count} chunk{source.chunk_count === 1 ? '' : 's'}
            </Text>
            {source.last_synced_at ? (
              <Text style={[styles.knowledgeItemChunks, { color: theme.colors.subtle }]}>
                Synced {new Date(source.last_synced_at).toLocaleDateString()}
              </Text>
            ) : null}
            {source.processing_error ? (
              <Text style={[styles.knowledgeItemChunks, { color: '#f87171' }]} numberOfLines={1}>
                {source.processing_error}
              </Text>
            ) : null}
          </View>
        </View>
      ))}

      {/* Pagination controls */}
      {meta.last_page > 1 ? (
        <View style={styles.paginationRow}>
          <Pressable
            style={[
              styles.pageButton,
              {
                backgroundColor: meta.current_page <= 1 ? theme.colors.surface : theme.colors.card,
                borderColor: theme.colors.border,
                opacity: meta.current_page <= 1 || loadingPage ? 0.4 : 1,
              },
            ]}
            disabled={meta.current_page <= 1 || loadingPage}
            onPress={() => loadPage(meta.current_page - 1)}
          >
            <Ionicons name="chevron-back" size={16} color={theme.colors.text} />
            <Text style={[styles.pageButtonText, { color: theme.colors.text }]}>Prev</Text>
          </Pressable>

          <Text style={[styles.pageIndicator, { color: theme.colors.muted }]}>
            {loadingPage ? 'Loading...' : `${meta.current_page} of ${meta.last_page}`}
          </Text>

          <Pressable
            style={[
              styles.pageButton,
              {
                backgroundColor: !meta.has_more ? theme.colors.surface : theme.colors.card,
                borderColor: theme.colors.border,
                opacity: !meta.has_more || loadingPage ? 0.4 : 1,
              },
            ]}
            disabled={!meta.has_more || loadingPage}
            onPress={() => loadPage(meta.current_page + 1)}
          >
            <Text style={[styles.pageButtonText, { color: theme.colors.text }]}>Next</Text>
            <Ionicons name="chevron-forward" size={16} color={theme.colors.text} />
          </Pressable>
        </View>
      ) : null}
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

  const [showPasswordForm, setShowPasswordForm] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [passwordSuccess, setPasswordSuccess] = useState<string | null>(null);

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

  async function handleChangePassword(): Promise<void> {
    if (newPassword !== confirmPassword) {
      setPasswordError('New passwords do not match.');
      return;
    }
    if (newPassword.length < 8) {
      setPasswordError('Password must be at least 8 characters.');
      return;
    }

    setSavingPassword(true);
    setPasswordError(null);
    setPasswordSuccess(null);

    try {
      await changePassword(token, currentPassword, newPassword, confirmPassword);
      setPasswordSuccess('Password changed successfully.');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setShowPasswordForm(false);
    } catch (err) {
      setPasswordError(resolveErrorMessage(err));
    } finally {
      setSavingPassword(false);
    }
  }

  return (
    <View style={styles.sectionColumn}>
      {/* Profile card */}
      <View
        style={[
          styles.sectionCard,
          { backgroundColor: theme.colors.card, borderColor: theme.colors.border },
        ]}
      >
        <View style={styles.profileRow}>
          <View style={[styles.profileAvatar, { backgroundColor: theme.colors.primary + '22' }]}>
            <Ionicons name="person" size={26} color={theme.colors.primary} />
          </View>
          <View style={styles.profileCopy}>
            <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>
              {userContext.user.name}
            </Text>
            <Text style={[styles.sectionIntro, { color: theme.colors.muted }]}>
              {userContext.user.email}
            </Text>
          </View>
        </View>

        <Pressable
          style={[
            styles.editProfileButton,
            { borderColor: theme.colors.border, backgroundColor: theme.colors.surface },
          ]}
          onPress={() => {
            setShowPasswordForm((v) => !v);
            setPasswordError(null);
            setPasswordSuccess(null);
          }}
        >
          <Ionicons
            name={showPasswordForm ? 'chevron-up' : 'lock-closed-outline'}
            size={16}
            color={theme.colors.accent}
          />
          <Text style={[styles.editProfileButtonText, { color: theme.colors.accent }]}>
            {showPasswordForm ? 'Cancel' : 'Change Password'}
          </Text>
        </Pressable>

        {showPasswordForm ? (
          <View style={styles.passwordForm}>
            {passwordError ? <ErrorBanner text={passwordError} /> : null}
            {passwordSuccess ? <SuccessBanner text={passwordSuccess} /> : null}

            <Field
              label="Current password"
              value={currentPassword}
              onChangeText={setCurrentPassword}
              placeholder="Enter current password"
              secureTextEntry
            />
            <Field
              label="New password"
              value={newPassword}
              onChangeText={setNewPassword}
              placeholder="At least 8 characters"
              secureTextEntry
            />
            <Field
              label="Confirm new password"
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              placeholder="Repeat new password"
              secureTextEntry
            />

            <Pressable
              style={[
                styles.fullWidthPrimaryButton,
                {
                  backgroundColor: theme.colors.primary,
                  opacity: savingPassword || !currentPassword || !newPassword || !confirmPassword ? 0.6 : 1,
                },
              ]}
              disabled={savingPassword || !currentPassword || !newPassword || !confirmPassword}
              onPress={handleChangePassword}
            >
              <Text style={[styles.primaryButtonText, { color: theme.colors.primaryText }]}>
                {savingPassword ? 'Saving...' : 'Update Password'}
              </Text>
            </Pressable>
          </View>
        ) : null}
      </View>

      {error ? <ErrorBanner text={error} /> : null}
      {success ? <SuccessBanner text={success} /> : null}

      {/* Widget settings card */}
      <View
        style={[
          styles.sectionCard,
          { backgroundColor: theme.colors.card, borderColor: theme.colors.border },
        ]}
      >
        <Text style={[styles.sectionTitle, { color: theme.colors.text }]}>Widget settings</Text>
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
    paddingTop: 12,
    paddingBottom: 20,
    paddingHorizontal: 8,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  tabIconPill: {
    width: 52,
    height: 32,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 10,
    letterSpacing: 0.1,
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
    paddingBottom: 32,
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
  buttonLoadingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
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
  // Session list
  sessionsHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderRadius: 20,
    padding: 18,
  },
  sessionsHeaderTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 18,
  },
  sessionsHeaderSub: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
  },
  liveChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  liveDot: {
    width: 7,
    height: 7,
    borderRadius: 999,
    backgroundColor: '#4ade80',
  },
  liveChipText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 12,
  },
  sessionsEmpty: {
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderRadius: 24,
    padding: 40,
  },
  sessionsEmptyTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 16,
  },
  sessionsEmptyBody: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
    lineHeight: 20,
    textAlign: 'center',
  },
  sessionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderWidth: 1,
    borderRadius: 20,
    padding: 14,
  },
  sessionCardLeft: {
    flexShrink: 0,
  },
  sessionCardAvatar: {
    width: 44,
    height: 44,
    borderRadius: 999,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sessionCardLiveDot: {
    position: 'absolute',
    bottom: 1,
    right: 1,
    width: 11,
    height: 11,
    borderRadius: 999,
    backgroundColor: '#4ade80',
    borderWidth: 2,
    borderColor: '#07070f',
  },
  sessionCardBody: {
    flex: 1,
    gap: 4,
    minWidth: 0,
  },
  sessionCardTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  sessionCardName: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 14,
    flex: 1,
  },
  sessionCardBadge: {
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 2,
    flexShrink: 0,
  },
  sessionCardBadgeText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 10,
  },
  sessionCardPreview: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
    lineHeight: 18,
  },
  sessionCardUrl: {
    fontFamily: 'Inter_400Regular',
    fontSize: 11,
  },
  // Thread / chat view
  threadScreen: {
    flex: 1,
    gap: 8,
    paddingHorizontal: 20,
    paddingTop: 18,
  },
  threadScrollArea: {
    flex: 1,
  },
  threadScrollContent: {
    paddingBottom: 16,
    gap: 0,
  },
  threadHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderRadius: 20,
    padding: 12,
  },
  threadBackBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
    flexShrink: 0,
  },
  threadBackText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  threadHeaderCenter: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    minWidth: 0,
  },
  threadAvatar: {
    width: 34,
    height: 34,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  threadHeaderName: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  threadHeaderMeta: {
    fontFamily: 'Inter_400Regular',
    fontSize: 11,
  },
  takeoverPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    flexShrink: 0,
  },
  takeoverPillText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 11,
  },
  takeoverBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  takeoverBannerText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 12,
    flex: 1,
  },
  threadLoadingWrap: {
    alignItems: 'center',
    gap: 10,
    paddingVertical: 32,
  },
  threadLoadingText: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
  },
  threadEmptyWrap: {
    alignItems: 'center',
    gap: 8,
    paddingVertical: 40,
  },
  threadEmptyText: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
  },
  messagesColumn: {
    gap: 12,
  },
  chatBubbleRow: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
  },
  chatBubbleRowLeft: {
    justifyContent: 'flex-start',
  },
  chatBubbleRowRight: {
    justifyContent: 'flex-end',
  },
  chatAvatarSmall: {
    width: 26,
    height: 26,
    borderRadius: 999,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  chatSenderLabel: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 10,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  chatBubble: {
    borderWidth: 1,
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  chatBubbleVisitor: {
    borderBottomLeftRadius: 4,
  },
  chatBubbleAI: {
    borderBottomRightRadius: 4,
  },
  chatBubbleHuman: {
    borderBottomRightRadius: 4,
  },
  chatBubbleText: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 21,
  },
  replyBox: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
    borderWidth: 1,
    borderRadius: 20,
    padding: 10,
  },
  replyInput: {
    flex: 1,
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    maxHeight: 120,
    textAlignVertical: 'top',
  },
  replySendBtn: {
    width: 42,
    height: 42,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  takeoverCta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  takeoverCtaText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  handBackBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  handBackText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
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
  knowledgeCallout: {
    flexDirection: 'row',
    gap: 14,
    borderWidth: 1,
    borderRadius: 20,
    padding: 16,
  },
  knowledgeCalloutIcon: {
    width: 44,
    height: 44,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  knowledgeCalloutCopy: {
    flex: 1,
    gap: 3,
  },
  knowledgeCalloutTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
  },
  knowledgeCalloutBody: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
    lineHeight: 18,
  },
  knowledgeCalloutLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 4,
  },
  knowledgeCalloutUrl: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
  knowledgeStatsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 0,
  },
  knowledgeStat: {
    flex: 1,
    alignItems: 'center',
    gap: 2,
  },
  knowledgeStatNum: {
    fontFamily: 'Inter_700Bold',
    fontSize: 20,
  },
  knowledgeStatLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 11,
  },
  knowledgeStatDivider: {
    width: 1,
    height: 32,
    marginHorizontal: 4,
  },
  knowledgeItem: {
    borderWidth: 1,
    borderRadius: 20,
    padding: 16,
    gap: 8,
  },
  knowledgeItemHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  knowledgeTypeTag: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  knowledgeTypeText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 11,
  },
  knowledgeStatusDot: {
    width: 6,
    height: 6,
    borderRadius: 999,
    marginLeft: 4,
  },
  knowledgeStatusText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 11,
  },
  knowledgeItemTitle: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 14,
    lineHeight: 20,
  },
  knowledgeItemMeta: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
    lineHeight: 17,
  },
  knowledgeItemFooter: {
    flexDirection: 'row',
    gap: 10,
    flexWrap: 'wrap',
  },
  knowledgeItemChunks: {
    fontFamily: 'Inter_400Regular',
    fontSize: 11,
  },
  paginationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  pageButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  pageButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  pageIndicator: {
    fontFamily: 'Inter_500Medium',
    fontSize: 13,
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
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  profileAvatar: {
    width: 52,
    height: 52,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileCopy: {
    flex: 1,
    gap: 2,
  },
  editProfileButton: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 6,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 9,
    marginTop: 4,
  },
  editProfileButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  passwordForm: {
    gap: 12,
    marginTop: 4,
  },
});
