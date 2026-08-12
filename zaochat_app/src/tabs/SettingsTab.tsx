import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import {
  useColorScheme,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ErrorBanner, SuccessBanner } from '../components/Banners';
import { ChoiceRow } from '../components/ChoiceRow';
import { Field } from '../components/Field';
import { ToggleRow } from '../components/ToggleRow';
import {
  changePassword,
  updateWidgetSettings,
  type MobileSettingsPayload,
  type MobileUserContext,
} from '../lib/mobileApi';
import { getTheme } from '../theme';

type Props = {
  settings: MobileSettingsPayload;
  token: string;
  userContext: MobileUserContext;
  onLogout: () => void;
  onSettingsChange: (settings: MobileSettingsPayload) => void;
};

export function SettingsTab({ settings, token, userContext, onLogout, onSettingsChange }: Props) {
  const theme = getTheme(useColorScheme());
  const [form,    setForm]    = useState(settings.widget);
  const [saving,  setSaving]  = useState(false);
  const [error,   setError]   = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const [showPasswordForm,  setShowPasswordForm]  = useState(false);
  const [currentPassword,   setCurrentPassword]   = useState('');
  const [newPassword,       setNewPassword]       = useState('');
  const [confirmPassword,   setConfirmPassword]   = useState('');
  const [savingPassword,    setSavingPassword]    = useState(false);
  const [passwordError,     setPasswordError]     = useState<string | null>(null);
  const [passwordSuccess,   setPasswordSuccess]   = useState<string | null>(null);

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
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setSaving(false);
    }
  }

  async function handleChangePassword(): Promise<void> {
    if (newPassword !== confirmPassword) { setPasswordError('New passwords do not match.'); return; }
    if (newPassword.length < 8)          { setPasswordError('Password must be at least 8 characters.'); return; }

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
      setPasswordError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setSavingPassword(false);
    }
  }

  return (
    <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
      <View style={styles.column}>
        {/* Profile card */}
        <View style={[styles.card, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <View style={styles.profileRow}>
            <View style={[styles.profileAvatar, { backgroundColor: theme.colors.primary + '22' }]}>
              <Ionicons name="person" size={26} color={theme.colors.primary} />
            </View>
            <View style={styles.profileCopy}>
              <Text style={[styles.cardTitle, { color: theme.colors.text }]}>{userContext.user.name}</Text>
              <Text style={[styles.cardSub,   { color: theme.colors.muted }]}>{userContext.user.email}</Text>
            </View>
          </View>

          <Pressable
            style={[styles.editBtn, { borderColor: theme.colors.border, backgroundColor: theme.colors.surface }]}
            onPress={() => {
              setShowPasswordForm((v) => !v);
              setPasswordError(null);
              setPasswordSuccess(null);
            }}
          >
            <Ionicons name={showPasswordForm ? 'chevron-up' : 'lock-closed-outline'} size={16} color={theme.colors.accent} />
            <Text style={[styles.editBtnText, { color: theme.colors.accent }]}>
              {showPasswordForm ? 'Cancel' : 'Change Password'}
            </Text>
          </Pressable>

          {showPasswordForm ? (
            <View style={styles.passwordForm}>
              {passwordError   ? <ErrorBanner   text={passwordError}   /> : null}
              {passwordSuccess ? <SuccessBanner text={passwordSuccess} /> : null}
              <Field label="Current password" value={currentPassword} onChangeText={setCurrentPassword} placeholder="Enter current password" secureTextEntry />
              <Field label="New password"     value={newPassword}     onChangeText={setNewPassword}     placeholder="At least 8 characters" secureTextEntry />
              <Field label="Confirm new password" value={confirmPassword} onChangeText={setConfirmPassword} placeholder="Repeat new password" secureTextEntry />
              <Pressable
                style={[styles.primaryBtn, { backgroundColor: theme.colors.primary, opacity: savingPassword || !currentPassword || !newPassword || !confirmPassword ? 0.6 : 1 }]}
                disabled={savingPassword || !currentPassword || !newPassword || !confirmPassword}
                onPress={handleChangePassword}
              >
                <Text style={[styles.primaryBtnText, { color: theme.colors.primaryText }]}>
                  {savingPassword ? 'Saving...' : 'Update Password'}
                </Text>
              </Pressable>
            </View>
          ) : null}
        </View>

        {error   ? <ErrorBanner   text={error}   /> : null}
        {success ? <SuccessBanner text={success} /> : null}

        {/* Widget settings card */}
        <View style={[styles.card, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <Text style={[styles.cardTitle, { color: theme.colors.text }]}>Widget settings</Text>
          <Text style={[styles.cardSub,   { color: theme.colors.muted }]}>
            {settings.client.contact_email || userContext.user.email}
          </Text>

          <Field label="Welcome message" value={form.welcome_message} onChangeText={(v) => setForm((f) => ({ ...f, welcome_message: v }))} placeholder="Ask us anything." />
          <Field label="Toggle text"     value={form.toggle_text}     onChangeText={(v) => setForm((f) => ({ ...f, toggle_text: v }))}     placeholder="Ask anything about this business" />
          <Field label="Primary color"   value={form.primary_color}   onChangeText={(v) => setForm((f) => ({ ...f, primary_color: v }))}   placeholder="#6366f1" autoCapitalize="characters" />
          <Field label="Accent color"    value={form.accent_color}    onChangeText={(v) => setForm((f) => ({ ...f, accent_color: v }))}    placeholder="#8b5cf6" autoCapitalize="characters" />

          <ChoiceRow label="Widget style" value={form.widget_style} options={['classic', 'modern', 'glass']} onChange={(v) => setForm((f) => ({ ...f, widget_style: v }))} />
          <ChoiceRow label="Theme mode"   value={form.theme_mode}   options={['system', 'light', 'dark']}    onChange={(v) => setForm((f) => ({ ...f, theme_mode: v }))} />
          <ChoiceRow label="Position"     value={form.position}     options={['right', 'left']}               onChange={(v) => setForm((f) => ({ ...f, position: v }))} />

          <ToggleRow label="Show branding"     value={form.show_branding}     onValueChange={(v) => setForm((f) => ({ ...f, show_branding: v }))} />
          <ToggleRow label="Default expanded"  value={form.default_expanded}  onValueChange={(v) => setForm((f) => ({ ...f, default_expanded: v }))} />

          <Pressable
            style={[styles.primaryBtn, { backgroundColor: theme.colors.primary, opacity: saving ? 0.7 : 1 }]}
            disabled={saving}
            onPress={save}
          >
            <Text style={[styles.primaryBtnText, { color: theme.colors.primaryText }]}>
              {saving ? 'Saving...' : 'Save Widget Settings'}
            </Text>
          </Pressable>
        </View>

        <Pressable
          style={[styles.secondaryBtn, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}
          onPress={onLogout}
        >
          <Text style={[styles.primaryBtnText, { color: theme.colors.text }]}>Logout</Text>
        </Pressable>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { paddingHorizontal: 16, paddingTop: 16, paddingBottom: 32, gap: 14 },
  column:  { gap: 12 },
  card:    { borderWidth: 1, borderRadius: 20, padding: 20, gap: 10 },
  cardTitle: { fontFamily: 'Inter_800ExtraBold', fontSize: 18, letterSpacing: -0.4 },
  cardSub:   { fontFamily: 'Inter_400Regular', fontSize: 14, lineHeight: 22 },

  profileRow:   { flexDirection: 'row', alignItems: 'center', gap: 14 },
  profileAvatar: { width: 52, height: 52, borderRadius: 999, alignItems: 'center', justifyContent: 'center' },
  profileCopy:  { flex: 1, gap: 2 },

  editBtn:     { flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start', gap: 6, borderWidth: 1, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 9, marginTop: 4 },
  editBtnText: { fontFamily: 'Inter_600SemiBold', fontSize: 13 },

  passwordForm: { gap: 12, marginTop: 4 },

  primaryBtn:     { width: '100%', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 18, paddingVertical: 16, borderRadius: 100 },
  primaryBtnText: { fontFamily: 'Inter_700Bold', fontSize: 14 },
  secondaryBtn:   { width: '100%', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 18, paddingVertical: 16, borderRadius: 100, borderWidth: 1 },
});
