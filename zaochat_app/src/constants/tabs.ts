import { Ionicons } from '@expo/vector-icons';

export type Screen = 'splash' | 'onboarding' | 'login' | 'app';
export type Tab = 'sessions' | 'history' | 'leads' | 'settings';

export type TabItem = {
  key: Tab;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  iconActive: keyof typeof Ionicons.glyphMap;
};

export const TAB_ITEMS: TabItem[] = [
  { key: 'sessions', label: 'Live',     icon: 'radio-outline',    iconActive: 'radio' },
  { key: 'history',  label: 'History',  icon: 'time-outline',     iconActive: 'time' },
  { key: 'leads',    label: 'Leads',    icon: 'flash-outline',    iconActive: 'flash' },
  { key: 'settings', label: 'Settings', icon: 'settings-outline', iconActive: 'settings' },
];
