import type { StatusBarStyle } from 'expo-status-bar';
import type { ColorSchemeName } from 'react-native';

type ThemeColors = {
  bg: string;
  surface: string;
  card: string;
  border: string;
  primary: string;
  primaryLight: string;
  primaryGlow: string;
  primaryText: string;
  accent: string;
  accentGlow: string;
  text: string;
  muted: string;
  subtle: string;
  success: string;
  danger: string;
};

export type AppTheme = {
  name: 'light' | 'dark';
  statusBar: StatusBarStyle;
  radii: { sm: number; md: number; lg: number };
  colors: ThemeColors;
};

const sharedRadii = { sm: 12, md: 20, lg: 28 };

const darkTheme: AppTheme = {
  name: 'dark',
  statusBar: 'light',
  radii: sharedRadii,
  colors: {
    bg: '#06060f',
    surface: '#0d0d1f',
    card: '#12122a',
    border: '#ffffff14',
    primary: '#6366f1',
    primaryLight: '#818cf8',
    primaryGlow: '#6366f1',
    primaryText: '#ffffff',
    accent: '#a78bfa',
    accentGlow: '#a78bfa',
    text: '#f1f1f5',
    muted: '#8b8fa8',
    subtle: '#3d3f5c',
    success: '#4ade80',
    danger: '#f87171',
  },
};

const lightTheme: AppTheme = {
  name: 'light',
  statusBar: 'dark',
  radii: sharedRadii,
  colors: {
    bg: '#fafafa',
    surface: '#f3f4f8',
    card: '#ffffff',
    border: '#e4e6ef',
    primary: '#6366f1',
    primaryLight: '#818cf8',
    primaryGlow: '#6366f1',
    primaryText: '#ffffff',
    accent: '#8b5cf6',
    accentGlow: '#8b5cf6',
    text: '#0f0f23',
    muted: '#6b7280',
    subtle: '#b0b4c8',
    success: '#22c55e',
    danger: '#ef4444',
  },
};

export function getTheme(colorScheme: ColorSchemeName): AppTheme {
  return colorScheme === 'light' ? lightTheme : darkTheme;
}
