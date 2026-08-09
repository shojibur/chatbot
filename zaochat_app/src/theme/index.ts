import type { StatusBarStyle } from 'expo-status-bar';
import type { ColorSchemeName } from 'react-native';

type ThemeColors = {
  bg: string;
  surface: string;
  card: string;
  border: string;
  primary: string;
  primaryGlow: string;
  primaryText: string;
  accent: string;
  accentGlow: string;
  text: string;
  muted: string;
  subtle: string;
  success: string;
};

export type AppTheme = {
  name: 'light' | 'dark';
  statusBar: StatusBarStyle;
  radii: {
    sm: number;
    md: number;
    lg: number;
  };
  colors: ThemeColors;
};

const sharedRadii = {
  sm: 12,
  md: 20,
  lg: 28,
};

const darkTheme: AppTheme = {
  name: 'dark',
  statusBar: 'light',
  radii: sharedRadii,
  colors: {
    bg: '#07070f',
    surface: '#0f0f1a',
    card: '#141428',
    border: '#ffffff12',
    primary: '#6366f1',
    primaryGlow: '#6366f1',
    primaryText: '#f8fafc',
    accent: '#a78bfa',
    accentGlow: '#a78bfa',
    text: '#f3f4f6',
    muted: '#9ca3af',
    subtle: '#4b5563',
    success: '#4ade80',
  },
};

const lightTheme: AppTheme = {
  name: 'light',
  statusBar: 'dark',
  radii: sharedRadii,
  colors: {
    bg: '#ffffff',
    surface: '#f9fafb',
    card: '#f3f4f6',
    border: '#e5e7eb',
    primary: '#6366f1',
    primaryGlow: '#6366f1',
    primaryText: '#f8fafc',
    accent: '#8b5cf6',
    accentGlow: '#8b5cf6',
    text: '#111827',
    muted: '#6b7280',
    subtle: '#9ca3af',
    success: '#22c55e',
  },
};

export function getTheme(colorScheme: ColorSchemeName): AppTheme {
  return colorScheme === 'light' ? lightTheme : darkTheme;
}
