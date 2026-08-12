import { useColorScheme, Image, StyleSheet, Text, View } from 'react-native';
import { getTheme } from '../theme';

const logo = require('../../assets/splash-icon.png');

export function SplashScreen() {
  const theme = getTheme(useColorScheme());

  return (
    <View style={[styles.container, { backgroundColor: theme.colors.bg }]}>
      <View style={[styles.orb, { backgroundColor: theme.colors.primaryGlow }]} />
      <View style={[styles.orbSecondary, { backgroundColor: theme.colors.accentGlow }]} />
      <Image source={logo} style={styles.logo} resizeMode="contain" />
      <Text style={[styles.wordmark, { color: theme.colors.text }]}>ZaoChat</Text>
      <Text style={[styles.tagline, { color: theme.colors.muted }]}>
        AI secretary for hot leads and live takeover
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  orb: {
    position: 'absolute',
    top: 120,
    width: 260,
    height: 260,
    borderRadius: 999,
    opacity: 0.18,
  },
  orbSecondary: {
    position: 'absolute',
    bottom: 120,
    width: 180,
    height: 180,
    borderRadius: 999,
    opacity: 0.16,
  },
  logo: {
    width: 132,
    height: 132,
    marginBottom: 18,
  },
  wordmark: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 34,
    letterSpacing: -1.2,
  },
  tagline: {
    marginTop: 10,
    fontFamily: 'Inter_500Medium',
    fontSize: 14,
    textAlign: 'center',
  },
});
