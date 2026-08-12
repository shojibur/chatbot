import { useColorScheme, Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { GlowBackground } from '../components/GlowBackground';
import { getTheme } from '../theme';

const logo = require('../../assets/splash-icon.png');

const FEATURES: [string, string][] = [
  ['Live sessions',   'See active threads and recent messages fast.'],
  ['Lead follow-up',  'Review leads and move quickly on high-intent visitors.'],
  ['Human takeover',  'Pause AI and reply directly from your mobile device.'],
];

type Props = { onContinue: () => void };

export function OnboardingScreen({ onContinue }: Props) {
  const theme = getTheme(useColorScheme());

  return (
    <ScrollView
      style={{ flex: 1, backgroundColor: theme.colors.bg }}
      contentContainerStyle={styles.content}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.hero}>
        <GlowBackground />
        <Image source={logo} style={styles.logo} resizeMode="contain" />
        <Text style={[styles.headline, { color: theme.colors.text }]}>
          Stay on top of every conversation.
        </Text>
        <Text style={[styles.subhead, { color: theme.colors.muted }]}>
          Monitor chats, capture leads, and step in when a human reply matters.
        </Text>
        <View style={styles.cards}>
          {FEATURES.map(([title, body]) => (
            <View
              key={title}
              style={[styles.card, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}
            >
              <Text style={[styles.cardTitle, { color: theme.colors.text }]}>{title}</Text>
              <Text style={[styles.cardBody,  { color: theme.colors.muted }]}>{body}</Text>
            </View>
          ))}
        </View>
      </View>

      <Pressable
        style={[styles.button, { backgroundColor: theme.colors.primary, shadowColor: theme.colors.primary }]}
        onPress={onContinue}
      >
        <Text style={[styles.buttonText, { color: theme.colors.primaryText }]}>Sign In</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 36,
    gap: 18,
  },
  hero: {
    position: 'relative',
    overflow: 'hidden',
    gap: 16,
    padding: 24,
    borderRadius: 28,
  },
  logo: {
    width: 82,
    height: 82,
  },
  headline: {
    fontFamily: 'Inter_800ExtraBold',
    fontSize: 34,
    lineHeight: 40,
    letterSpacing: -1,
    maxWidth: 330,
  },
  subhead: {
    fontFamily: 'Inter_400Regular',
    fontSize: 15,
    lineHeight: 24,
    maxWidth: 330,
  },
  cards: {
    gap: 12,
  },
  card: {
    borderWidth: 1,
    borderRadius: 22,
    padding: 18,
    gap: 8,
  },
  cardTitle: {
    fontFamily: 'Inter_700Bold',
    fontSize: 17,
    lineHeight: 23,
  },
  cardBody: {
    fontFamily: 'Inter_400Regular',
    fontSize: 14,
    lineHeight: 22,
  },
  button: {
    width: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderRadius: 100,
    shadowOpacity: 0.28,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 8 },
    elevation: 6,
  },
  buttonText: {
    fontFamily: 'Inter_700Bold',
    fontSize: 14,
  },
});
