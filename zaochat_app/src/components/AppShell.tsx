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
import type {
  MobileAppBootstrap,
  MobileUserContext,
} from '../lib/mobileApi';

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
      {/* Compact header */}
      <View style={[styles.header, { borderBottomColor: theme.colors.border }]}>
        <View style={styles.headerLeft}>
          <Image source={logo} style={styles.headerLogo} resizeMode="contain" />
          <Text style={[styles.headerTitle, { color: theme.colors.text }]} numberOfLines={1}>
            {userContext.client.name}
          </Text>
        </View>
        <Pressable
          style={[styles.refreshBtn, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}
          onPress={onRefresh}
          disabled={appLoading}
        >
          {appLoading
            ? <ActivityIndicator size="small" color={theme.colors.primary} />
            : <Ionicons name="refresh-outline" size={18} color={theme.colors.muted} />
          }
        </Pressable>
      </View>

      {appError ? <ErrorBanner text={appError} inset /> : null}

      <View style={{ flex: 1 }}>{tabContent}</View>

      {/* Tab bar */}
      <View style={[styles.tabBar, { backgroundColor: theme.colors.bg, borderTopColor: theme.colors.border }]}>
        {TAB_ITEMS.map((tab) => {
          const isActive = activeTab === tab.key;
          return (
            <Pressable key={tab.key} style={styles.tabBtn} onPress={() => onChangeTab(tab.key)}>
              <Ionicons
                name={isActive ? tab.iconActive : tab.icon}
                size={22}
                color={isActive ? theme.colors.primary : theme.colors.subtle}
              />
              <Text
                style={[
                  styles.tabLabel,
                  isActive ? styles.tabLabelActive : null,
                  { color: isActive ? theme.colors.primary : theme.colors.subtle },
                ]}
              >
                {tab.label}
              </Text>
              {isActive ? <View style={[styles.tabDot, { backgroundColor: theme.colors.primary }]} /> : null}
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
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  headerLeft: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingRight: 10,
    minWidth: 0,
  },
  headerLogo: {
    width: 28,
    height: 28,
    borderRadius: 6,
    flexShrink: 0,
  },
  headerTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 15,
    letterSpacing: -0.3,
    flex: 1,
  },
  refreshBtn: {
    width: 36,
    height: 36,
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
    paddingBottom: 20,
    paddingHorizontal: 8,
    borderTopWidth: 1,
  },
  tabBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    paddingVertical: 4,
  },
  tabLabel: {
    fontFamily: 'Inter_500Medium',
    fontSize: 10,
    letterSpacing: 0.1,
  },
  tabLabelActive: {
    fontFamily: 'Inter_600SemiBold',
  },
  tabDot: {
    width: 4,
    height: 4,
    borderRadius: 999,
    marginTop: 1,
  },
});
