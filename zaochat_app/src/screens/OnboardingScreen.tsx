import { Ionicons } from '@expo/vector-icons';
import { useColorScheme, Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { getTheme } from '../theme';

const logo = require('../../assets/splash-icon.png');

type Feature = { icon: keyof typeof Ionicons.glyphMap; title: string; body: string; color: string };

const FEATURES: Feature[] = [
  { icon: 'radio',          title: 'Live sessions',   body: 'See active threads and reply in real time.',            color: '#6366f1' },
  { icon: 'flash',          title: 'Lead follow-up',  body: 'Move fast on high-intent visitors before they go cold.', color: '#f59e0b' },
  { icon: 'hand-left',      title: 'Human takeover',  body: 'Pause AI and step in personally when it counts.',       color: '#10b981' },
];

type Props = { onContinue: () => void };

export function OnboardingScreen({ onContinue }: Props) {
  const theme = getTheme(useColorScheme());
  const isDark = theme.name === 'dark';

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: theme.colors.bg }}
      contentContainerStyle={styles.content}
      showsVerticalScrollIndicator={false}
    >
      {/* Hero */}
      <View style={[styles.hero, { backgroundColor: isDark ? theme.colors.card : '#f0f0ff', borderColor: theme.colors.border }]}>
        {/* Decorative blobs */}
        <View style={[styles.heroBlob1, { backgroundColor: theme.colors.primary, opacity: isDark ? 0.25 : 0.15 }]} />
        <View style={[styles.heroBlob2, { backgroundColor: theme.colors.accent, opacity: isDark ? 0.20 : 0.12 }]} />

        {/* Logo badge */}
        <View style={[styles.logoBadge, { backgroundColor: theme.colors.bg, borderColor: isDark ? theme.colors.primary + '44' : theme.colors.border }]}>
          <Image source={logo} style={styles.logo} resizeMode="contain" />
        </View>

        <Text style={[styles.headline, { color: theme.colors.text }]}>
          Stay on top of{'\n'}every conversation.
        </Text>
        <Text style={[styles.subhead, { color: theme.colors.muted }]}>
          Monitor chats, capture leads, and step in when a human touch matters.
        </Text>
      </View>

      {/* Feature list */}
      <View style={styles.features}>
        {FEATURES.map((f) => (
          <View
            key={f.title}
            style={[styles.featureRow, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}
          >
            <View style={[styles.featureIcon, { backgroundColor: f.color + '1a' }]}>
              <Ionicons name={f.icon} size={20} color={f.color} />
            </View>
            <View style={styles.featureCopy}>
              <Text style={[styles.featureTitle, { color: theme.colors.text }]}>{f.title}</Text>
              <Text style={[styles.featureBody,  { color: theme.colors.muted }]}>{f.body}</Text>
            </View>
          </View>
        ))}
      </View>

      {/* CTA */}
      <Pressable
        style={[styles.cta, { backgroundColor: theme.colors.primary, shadowColor: theme.colors.primary }]}
        onPress={onContinue}
      >
        <Text style={[styles.ctaText, { color: theme.colors.primaryText }]}>Get started</Text>
        <View style={[styles.ctaArrow, { backgroundColor: '#ffffff22' }]}>
          <Ionicons name="arrow-forward" size={16} color="#ffffff" />
        </View>
      </Pressable>

      <Text style={[styles.footNote, { color: theme.colors.subtle }]}>
        Sign in with your ZaoChat account
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 40,
    gap: 16,
  },

  // Hero block
  hero: {
    borderWidth: 1,
    borderRadius: 28,
    padding: 28,
    gap: 16,
    overflow: 'hidden',
    position: 'relative',
  },
  heroBlob1: {
    position: 'absolute',
    top: -60,
    right: -40,
    width: 200,
    height: 200,
    borderRadius: 999,
  },
  heroBlob2: {
    position: 'absolute',
    bottom: -40,
    left: -30,
    width: 160,
    height: 160,
    borderRadius: 999,
  },
  logoBadge: {
    width: 60,
    height: 60,
    borderRadius: 18,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#6366f1',
    shadowOpacity: 0.25,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
  logo: {
    width: 38,
    height: 38,
  },
  headline: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 32,
    lineHeight: 38,
    letterSpacing: -1,
  },
  subhead: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 23,
  },

  // Feature rows
  features: {
    gap: 10,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    borderWidth: 1,
    borderRadius: 18,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  featureIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  featureCopy: {
    flex: 1,
    gap: 3,
  },
  featureTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 15,
  },
  featureBody: {
    fontFamily: 'Inter_400Regular',
    fontSize: 13,
    lineHeight: 19,
  },

  // CTA
  cta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    paddingVertical: 17,
    paddingHorizontal: 28,
    borderRadius: 100,
    shadowOpacity: 0.35,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 10 },
    elevation: 8,
    marginTop: 4,
  },
  ctaText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 16,
    letterSpacing: 0.1,
  },
  ctaArrow: {
    width: 28,
    height: 28,
    borderRadius: 999,
    alignItems: 'center',
    justifyContent: 'center',
  },

  footNote: {
    fontFamily: 'Inter_400Regular',
    fontSize: 12,
    textAlign: 'center',
    marginTop: -4,
  },
});
