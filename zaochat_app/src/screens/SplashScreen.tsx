import { useColorScheme, Image, StyleSheet, Text, View } from 'react-native';
import { getTheme } from '../theme';

const logo = require('../../assets/splash-icon.png');

export function SplashScreen() {
  const theme = getTheme(useColorScheme());
  const isDark = theme.name === 'dark';

  return (
    <View style={[styles.container, { backgroundColor: theme.colors.bg }]}>
      {/* Background blobs */}
      <View style={[styles.blob1, { backgroundColor: theme.colors.primary, opacity: isDark ? 0.22 : 0.12 }]} />
      <View style={[styles.blob2, { backgroundColor: theme.colors.accent, opacity: isDark ? 0.18 : 0.10 }]} />
      <View style={[styles.blob3, { backgroundColor: theme.colors.primaryLight, opacity: isDark ? 0.12 : 0.08 }]} />

      {/* Logo with glow ring */}
      <View style={styles.logoWrap}>
        <View style={[styles.glowRing, { backgroundColor: theme.colors.primary, opacity: isDark ? 0.25 : 0.15 }]} />
        <View style={[styles.logoContainer, { backgroundColor: theme.colors.card, borderColor: isDark ? theme.colors.primary + '40' : theme.colors.border }]}>
          <Image source={logo} style={styles.logo} resizeMode="contain" />
        </View>
      </View>

      {/* Wordmark */}
      <Text style={[styles.wordmark, { color: theme.colors.text }]}>ZaoChat</Text>

      {/* Tagline */}
      <View style={[styles.taglineWrap, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '30' }]}>
        <View style={[styles.taglineDot, { backgroundColor: theme.colors.primary }]} />
        <Text style={[styles.tagline, { color: theme.colors.primary }]}>
          AI secretary for hot leads
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    gap: 20,
  },

  // Background blobs
  blob1: {
    position: 'absolute',
    top: -60,
    left: -80,
    width: 320,
    height: 320,
    borderRadius: 999,
  },
  blob2: {
    position: 'absolute',
    bottom: -40,
    right: -60,
    width: 260,
    height: 260,
    borderRadius: 999,
  },
  blob3: {
    position: 'absolute',
    top: '50%',
    right: -100,
    width: 200,
    height: 200,
    borderRadius: 999,
  },

  // Logo
  logoWrap: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  glowRing: {
    position: 'absolute',
    width: 120,
    height: 120,
    borderRadius: 999,
  },
  logoContainer: {
    width: 90,
    height: 90,
    borderRadius: 26,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#6366f1',
    shadowOpacity: 0.4,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 8 },
    elevation: 12,
  },
  logo: {
    width: 56,
    height: 56,
  },

  // Text
  wordmark: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 38,
    letterSpacing: -1.5,
    marginTop: 4,
  },
  taglineWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 7,
  },
  taglineDot: {
    width: 6,
    height: 6,
    borderRadius: 999,
  },
  tagline: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
    letterSpacing: 0.1,
  },
});
