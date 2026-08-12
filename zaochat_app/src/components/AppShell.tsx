import { Ionicons } from '@expo/vector-icons';
import {
  useColorScheme,
  ActivityIndicator,
  Image,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { TAB_ITEMS, type Tab } from '../constants/tabs';
import { SessionsTab } from '../tabs/SessionsTab';
import { HistoryTab } from '../tabs/HistoryTab';
import { LeadsTab } from '../tabs/LeadsTab';
import { SettingsTab } from '../tabs/SettingsTab';
import { ErrorBanner } from './Banners';
import { getTheme } from '../theme';
import type { MobileAppBootstrap, MobileUserContext } from '../lib/mobileApi';

const logo = require('../../assets/splash-icon.png');

export type AppShellProps = {
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

export function AppShell({
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
  const theme = getTheme(useColorScheme());
  const isDark = theme.name === 'dark';

  const tabContent = activeTab === 'sessions' ? (
    <SessionsTab
      sessions={appData.sessions.filter((s) => s.is_human_takeover || s.is_active)}
      token={token}
      onSessionsChange={(sessions) => onDataChange({ ...appData, sessions })}
    />
  ) : activeTab === 'history' ? (
    <HistoryTab
      sessions={appData.sessions.filter((s) => !s.is_human_takeover && !s.is_active)}
      token={token}
    />
  ) : activeTab === 'leads' ? (
    <LeadsTab
      leads={appData.leads}
      leadsMeta={appData.leadsMeta}
      token={token}
      onLeadsChange={(leads, leadsMeta) => onDataChange({ ...appData, leads, leadsMeta })}
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
    <View style={[styles.shell, { backgroundColor: theme.colors.bg }]}>

      {/* Header */}
      <View style={[styles.header, { backgroundColor: isDark ? theme.colors.card : theme.colors.bg, borderBottomColor: theme.colors.border }]}>
        <View style={styles.headerLeft}>
          <View style={[styles.logoWrap, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '30' }]}>
            <Image source={logo} style={styles.headerLogo} resizeMode="contain" />
          </View>
          <View style={styles.headerCopy}>
            <Text style={[styles.headerTitle, { color: theme.colors.text }]} numberOfLines={1}>
              {userContext.client.name}
            </Text>
            <Text style={[styles.headerSub, { color: theme.colors.muted }]} numberOfLines={1}>
              {userContext.user.email}
            </Text>
          </View>
        </View>

        <Pressable
          style={[styles.refreshBtn, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}
          onPress={onRefresh}
          disabled={appLoading}
        >
          {appLoading
            ? <ActivityIndicator size="small" color={theme.colors.primary} />
            : <Ionicons name="refresh-outline" size={17} color={theme.colors.muted} />
          }
        </Pressable>
      </View>

      {appError ? <ErrorBanner text={appError} inset /> : null}

      <View style={{ flex: 1 }}>{tabContent}</View>

      {/* Tab bar */}
      <View style={[styles.tabBar, { backgroundColor: isDark ? theme.colors.card : theme.colors.bg, borderTopColor: theme.colors.border }]}>
        {TAB_ITEMS.map((tab) => {
          const isActive = activeTab === tab.key;

          return (
            <Pressable key={tab.key} style={styles.tabBtn} onPress={() => onChangeTab(tab.key)}>
              <View style={[styles.tabIconWrap, isActive && { backgroundColor: theme.colors.primary + '1a' }]}>
                <Ionicons
                  name={isActive ? tab.iconActive : tab.icon}
                  size={21}
                  color={isActive ? theme.colors.primary : theme.colors.subtle}
                />
              </View>
              <Text
                style={[
                  styles.tabLabel,
                  { color: isActive ? theme.colors.primary : theme.colors.subtle },
                  isActive ? styles.tabLabelActive : null,
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

const styles = StyleSheet.create({
  shell: { flex: 1 },

  // Header
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  headerLeft: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingRight: 10,
    minWidth: 0,
  },
  logoWrap: {
    width: 36,
    height: 36,
    borderRadius: 10,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  headerLogo: {
    width: 22,
    height: 22,
  },
  headerCopy: {
    flex: 1,
    gap: 1,
    minWidth: 0,
  },
  headerTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
    letterSpacing: -0.2,
  },
  headerSub: {
    fontFamily: 'Inter_400Regular',
    fontSize: 11,
  },
  refreshBtn: {
    width: 34,
    height: 34,
    borderRadius: 10,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },

  // Tab bar
  tabBar: {
    flexDirection: 'row',
    paddingTop: 8,
    paddingBottom: 22,
    paddingHorizontal: 4,
    borderTopWidth: 1,
  },
  tabBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    paddingVertical: 2,
  },
  tabIconWrap: {
    width: 40,
    height: 32,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 10,
    letterSpacing: 0.1,
  },
  tabLabelActive: {
    fontFamily: 'Inter_700Bold',
  },
});
