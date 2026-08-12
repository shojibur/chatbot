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
  const canSubmit = Boolean(email.trim() && password.trim() && !isSubmitting);

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={[styles.screen, { backgroundColor: theme.colors.bg }]}
    >
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <Image source={logo} style={styles.logo} resizeMode="contain" />
        <Text style={[styles.title,    { color: theme.colors.text }]}>Sign in to your client portal</Text>
        <Text style={[styles.subtitle, { color: theme.colors.muted }]}>
          Access sessions, leads, knowledge, and settings from one place.
        </Text>

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
              {rememberMe ? <View style={[styles.checkboxDot, { backgroundColor: theme.colors.primaryText }]} /> : null}
            </View>
            <Text style={[styles.rememberText, { color: theme.colors.muted }]}>
              Keep me signed in on this device
            </Text>
          </Pressable>

          <Pressable
            style={[
              styles.primaryBtn,
              {
                backgroundColor: canSubmit ? theme.colors.primary : theme.colors.subtle,
                opacity: canSubmit ? 1 : 0.7,
              },
            ]}
            disabled={!canSubmit}
            onPress={onLogin}
          >
            {isSubmitting ? (
              <View style={styles.btnRow}>
                <ActivityIndicator color={theme.colors.primaryText} />
                <Text style={[styles.primaryBtnText, { color: theme.colors.primaryText }]}>Signing In...</Text>
              </View>
            ) : (
              <Text style={[styles.primaryBtnText, { color: theme.colors.primaryText }]}>Sign In</Text>
            )}
          </Pressable>

          <Pressable style={styles.backBtn} onPress={onBack}>
            <Text style={[styles.backBtnText, { color: theme.colors.accent }]}>Back to onboarding</Text>
          </Pressable>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  content: {
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 36,
    gap: 20,
  },
  logo: {
    width: 86,
    height: 86,
    marginTop: 18,
    marginBottom: 4,
    alignSelf: 'center',
  },
  title: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 32,
    lineHeight: 38,
    letterSpacing: -1,
  },
  subtitle: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 24,
  },
  card: {
    borderWidth: 1,
    borderRadius: 24,
    padding: 20,
    gap: 16,
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
  checkboxDot: {
    width: 8,
    height: 8,
    borderRadius: 999,
  },
  rememberText: {
    fontFamily: 'Inter_500Medium',
    fontSize: 13,
    flex: 1,
  },
  primaryBtn: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderRadius: 100,
  },
  btnRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  primaryBtnText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
  },
  backBtn: {
    alignItems: 'center',
    paddingTop: 2,
  },
  backBtnText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
});
