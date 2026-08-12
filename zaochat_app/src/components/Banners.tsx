import { useColorScheme, StyleSheet, Text, View } from 'react-native';
import { getTheme } from '../theme';

export function ErrorBanner({ text, inset = false }: { text: string; inset?: boolean }) {
  const theme = getTheme(useColorScheme());

  return (
    <View
      style={[
        styles.banner,
        {
          backgroundColor: theme.colors.surface,
          borderColor: theme.colors.border,
          marginHorizontal: inset ? 20 : 0,
        },
      ]}
    >
      <Text style={[styles.bannerText, { color: '#fca5a5' }]}>{text}</Text>
    </View>
  );
}

export function SuccessBanner({ text }: { text: string }) {
  const theme = getTheme(useColorScheme());

  return (
    <View
      style={[
        styles.banner,
        {
          backgroundColor: theme.colors.surface,
          borderColor: theme.colors.border,
        },
      ]}
    >
      <Text style={[styles.bannerText, { color: theme.colors.success }]}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    borderWidth: 1,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  bannerText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
    lineHeight: 19,
  },
});
