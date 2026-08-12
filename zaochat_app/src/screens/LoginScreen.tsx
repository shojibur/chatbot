import { Ionicons } from '@expo/vector-icons';
import {
  useColorScheme,
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ErrorBanner } from '../components/Banners';
import { Field } from '../components/Field';
import { getTheme } from '../theme';

const logo = require('../../assets/splash-icon.png');

export type LoginScreenProps = {
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

export function LoginScreen({
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
}: LoginScreenProps) {
  const theme = getTheme(useColorScheme());
  const isDark = theme.name === 'dark';
  const canSubmit = Boolean(email.trim() && password.trim() && !isSubmitting);

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={[styles.screen, { backgroundColor: theme.colors.bg }]}
    >
      {/* Decorative blobs */}
      <View style={[styles.blob1, { backgroundColor: theme.colors.primary, opacity: isDark ? 0.18 : 0.10 }]} />
      <View style={[styles.blob2, { backgroundColor: theme.colors.accent, opacity: isDark ? 0.14 : 0.08 }]} />

      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        {/* Back link */}
        <Pressable style={styles.backBtn} onPress={onBack}>
          <Ionicons name="chevron-back" size={16} color={theme.colors.muted} />
          <Text style={[styles.backText, { color: theme.colors.muted }]}>Back</Text>
        </Pressable>

        {/* Header */}
        <View style={styles.header}>
          <View style={[styles.logoBadge, { backgroundColor: theme.colors.card, borderColor: isDark ? theme.colors.primary + '44' : theme.colors.border }]}>
            <Image source={logo} style={styles.logo} resizeMode="contain" />
          </View>
          <Text style={[styles.title, { color: theme.colors.text }]}>Welcome back</Text>
          <Text style={[styles.subtitle, { color: theme.colors.muted }]}>
            Sign in to manage your chatbot
          </Text>
        </View>

        {/* Form card */}
        <View style={[styles.card, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
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

          {loginError ? <ErrorBanner text={loginError} /> : null}

          {/* Remember me */}
          <Pressable style={styles.rememberRow} onPress={onRememberToggle}>
            <View
              style={[
                styles.checkbox,
                {
                  borderColor: rememberMe ? theme.colors.primary : theme.colors.border,
                  backgroundColor: rememberMe ? theme.colors.primary : 'transparent',
                },
              ]}
            >
              {rememberMe ? <Ionicons name="checkmark" size={13} color="#fff" /> : null}
            </View>
            <Text style={[styles.rememberText, { color: theme.colors.muted }]}>
              Keep me signed in
            </Text>
          </Pressable>

          {/* Sign in button */}
          <Pressable
            style={[
              styles.primaryBtn,
              {
                backgroundColor: canSubmit ? theme.colors.primary : theme.colors.subtle,
                opacity: canSubmit ? 1 : 0.55,
                shadowColor: canSubmit ? theme.colors.primary : 'transparent',
              },
            ]}
            disabled={!canSubmit}
            onPress={onLogin}
          >
            {isSubmitting ? (
              <View style={styles.btnRow}>
                <ActivityIndicator color="#fff" size="small" />
                <Text style={styles.primaryBtnText}>Signing in…</Text>
              </View>
            ) : (
              <Text style={styles.primaryBtnText}>Sign In</Text>
            )}
          </Pressable>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },

  // Background blobs
  blob1: {
    position: 'absolute',
    top: -80,
    right: -60,
    width: 280,
    height: 280,
    borderRadius: 999,
  },
  blob2: {
    position: 'absolute',
    bottom: 60,
    left: -80,
    width: 220,
    height: 220,
    borderRadius: 999,
  },

  content: {
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 48,
    gap: 24,
  },

  // Back
  backBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
    alignSelf: 'flex-start',
    paddingVertical: 4,
  },
  backText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 14,
  },

  // Header
  header: {
    alignItems: 'center',
    gap: 10,
    paddingTop: 12,
  },
  logoBadge: {
    width: 68,
    height: 68,
    borderRadius: 20,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#6366f1',
    shadowOpacity: 0.3,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 6 },
    elevation: 8,
  },
  logo: {
    width: 42,
    height: 42,
  },
  title: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 28,
    letterSpacing: -0.8,
    marginTop: 4,
  },
  subtitle: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 22,
  },

  // Card
  card: {
    borderWidth: 1,
    borderRadius: 24,
    padding: 22,
    gap: 18,
  },

  // Remember me
  rememberRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderWidth: 1.5,
    borderRadius: 7,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rememberText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 14,
  },

  // Primary button
  primaryBtn: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 17,
    borderRadius: 100,
    shadowOpacity: 0.35,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
    marginTop: 4,
  },
  btnRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  primaryBtnText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 16,
    color: '#ffffff',
    letterSpacing: 0.1,
  },
});
