import { useColorScheme, StyleSheet, Switch, Text, View } from 'react-native';
import { getTheme } from '../theme';

type Props = {
  label: string;
  value: boolean;
  onValueChange: (value: boolean) => void;
};

export function ToggleRow({ label, value, onValueChange }: Props) {
  const theme = getTheme(useColorScheme());

  return (
    <View style={styles.toggleRow}>
      <Text style={[styles.fieldLabel, { color: theme.colors.text }]}>{label}</Text>
      <Switch
        value={value}
        onValueChange={onValueChange}
        trackColor={{ false: theme.colors.subtle, true: theme.colors.primary }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
  fieldLabel: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
});
