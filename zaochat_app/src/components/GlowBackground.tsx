import { useColorScheme, StyleSheet, View } from 'react-native';
import { getTheme } from '../theme';

export function GlowBackground() {
  const theme = getTheme(useColorScheme());

  return (
    <>
      <View style={[styles.glow, styles.glowPrimary, { backgroundColor: theme.colors.primaryGlow }]} />
      <View style={[styles.glow, styles.glowAccent,  { backgroundColor: theme.colors.accentGlow }]} />
    </>
  );
}

const styles = StyleSheet.create({
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
});
