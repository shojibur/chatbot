import { useColorScheme, StyleSheet, Text, Pressable, View } from 'react-native';
import { getTheme } from '../theme';
import { capitalize } from '../utils/text';

type Props = {
  label: string;
  value: string;
  options: string[];
  onChange: (value: string) => void;
};

export function ChoiceRow({ label, value, options, onChange }: Props) {
  const theme = getTheme(useColorScheme());

  return (
    <View style={styles.fieldGroup}>
      <Text style={[styles.fieldLabel, { color: theme.colors.text }]}>{label}</Text>
      <View style={styles.optionRow}>
        {options.map((option) => (
          <Pressable
            key={option}
            style={[
              styles.optionButton,
              {
                backgroundColor: value === option ? theme.colors.primary : theme.colors.surface,
                borderColor: value === option ? theme.colors.primary : theme.colors.border,
              },
            ]}
            onPress={() => onChange(option)}
          >
            <Text
              style={[
                styles.optionButtonText,
                { color: value === option ? theme.colors.primaryText : theme.colors.text },
              ]}
            >
              {capitalize(option)}
            </Text>
          </Pressable>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  fieldGroup: {
    gap: 8,
  },
  fieldLabel: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 13,
  },
  optionRow: {
    flexDirection: 'row',
    gap: 8,
    flexWrap: 'wrap',
  },
  optionButton: {
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  optionButtonText: {
    fontFamily: 'Inter_600SemiBold',
    fontSize: 12,
  },
});
