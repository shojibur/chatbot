import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import {
  useColorScheme,
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ErrorBanner } from '../components/Banners';
import { getSessionMessages, type MobileSession, type MobileSessionMessage } from '../lib/mobileApi';
import { getTheme } from '../theme';
import { formatRelativeTime } from '../utils/time';

type Props = {
  sessions: MobileSession[];
  token: string;
};

export function HistoryTab({ sessions, token }: Props) {
  const theme = getTheme(useColorScheme());
  const [selectedSession, setSelectedSession] = useState<MobileSession | null>(null);
  const [messages, setMessages] = useState<MobileSessionMessage[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function openSession(session: MobileSession): Promise<void> {
    setSelectedSession(session);
    setLoading(true);
    setError(null);
    setMessages([]);
    try {
      const data = await getSessionMessages(token, session.id);
      setMessages(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setLoading(false);
    }
  }

  function closeSession(): void {
    setSelectedSession(null);
    setMessages([]);
    setError(null);
  }

  // ── Thread view ──────────────────────────────────────────────────────────────
  if (selectedSession) {
    const hadHumanTakeover  = messages.some((m) => m.human_takeover);
    const visibleMessages   = messages.filter((m) => m.source !== 'takeover_notice');

    return (
      <View style={styles.threadScreen}>
        {/* Header */}
        <View style={[styles.threadHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <Pressable style={styles.backBtn} onPress={closeSession}>
            <Ionicons name="chevron-back" size={20} color={theme.colors.primary} />
            <Text style={[styles.backText, { color: theme.colors.primary }]}>History</Text>
          </Pressable>

          <View style={styles.threadCenter}>
            <View style={[styles.threadAvatar, { backgroundColor: theme.colors.surface }]}>
              <Ionicons name="person" size={16} color={theme.colors.muted} />
            </View>
            <View style={{ gap: 1 }}>
              <Text style={[styles.threadName, { color: theme.colors.text }]} numberOfLines={1}>
                {selectedSession.visitor_identifier || selectedSession.visitor_ip || 'Anonymous'}
              </Text>
              <Text style={[styles.threadMeta, { color: theme.colors.muted }]}>
                {selectedSession.message_count} messages · {formatRelativeTime(selectedSession.last_activity_at)}
              </Text>
            </View>
          </View>

          {hadHumanTakeover ? (
            <View style={[styles.badge, { backgroundColor: theme.colors.accent + '22', borderColor: theme.colors.accent + '55' }]}>
              <Ionicons name="person" size={11} color={theme.colors.accent} />
              <Text style={[styles.badgeText, { color: theme.colors.accent }]}>Human</Text>
            </View>
          ) : (
            <View style={[styles.badge, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}>
              <Ionicons name="hardware-chip-outline" size={11} color={theme.colors.primary} />
              <Text style={[styles.badgeText, { color: theme.colors.primary }]}>AI</Text>
            </View>
          )}
        </View>

        {selectedSession.page_url ? (
          <View style={[styles.urlStrip, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
            <Ionicons name="globe-outline" size={11} color={theme.colors.subtle} />
            <Text style={[styles.urlText, { color: theme.colors.subtle }]} numberOfLines={1}>
              {selectedSession.page_url}
            </Text>
          </View>
        ) : null}

        {error ? <ErrorBanner text={error} /> : null}

        <ScrollView style={styles.scrollArea} contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {loading ? (
            <View style={styles.loadingWrap}>
              <ActivityIndicator color={theme.colors.primary} />
              <Text style={[styles.loadingText, { color: theme.colors.muted }]}>Loading messages…</Text>
            </View>
          ) : visibleMessages.length === 0 ? (
            <View style={styles.emptyWrap}>
              <Ionicons name="chatbubbles-outline" size={36} color={theme.colors.subtle} />
              <Text style={[styles.emptyText, { color: theme.colors.muted }]}>No messages recorded</Text>
            </View>
          ) : (
            <View style={styles.msgColumn}>
              {visibleMessages.map((message) => {
                const isVisitor = message.role === 'user';
                const isHuman   = message.role === 'assistant' && message.human_takeover;

                return (
                  <View
                    key={message.id}
                    style={[styles.bubbleRow, isVisitor ? styles.bubbleRowLeft : styles.bubbleRowRight]}
                  >
                    {isVisitor ? (
                      <View style={[styles.avatarSmall, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                        <Ionicons name="person-outline" size={11} color={theme.colors.muted} />
                      </View>
                    ) : null}

                    <View style={{ maxWidth: '75%', gap: 3 }}>
                      <Text style={[styles.senderLabel, {
                        color: isHuman ? theme.colors.accent : isVisitor ? theme.colors.muted : theme.colors.primary,
                        textAlign: isVisitor ? 'left' : 'right',
                      }]}>
                        {isVisitor ? 'Visitor' : isHuman ? (message.sent_by_name || 'Agent') : 'ZaoChat AI'}
                      </Text>
                      <View style={[
                        styles.bubble,
                        isVisitor
                          ? [styles.bubbleVisitor, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]
                          : isHuman
                          ? [styles.bubbleHuman, { backgroundColor: theme.colors.accent + 'ee', borderColor: theme.colors.accent }]
                          : [styles.bubbleAI, { backgroundColor: theme.colors.primary, borderColor: theme.colors.primary }],
                      ]}>
                        <Text style={[styles.bubbleText, { color: isVisitor ? theme.colors.text : '#ffffff' }]}>
                          {message.content}
                        </Text>
                      </View>
                      {message.created_at ? (
                        <Text style={{ fontSize: 10, color: theme.colors.subtle, textAlign: isVisitor ? 'left' : 'right', paddingHorizontal: 2 }}>
                          {formatRelativeTime(message.created_at)}
                        </Text>
                      ) : null}
                    </View>

                    {!isVisitor ? (
                      <View style={[styles.avatarSmall, {
                        backgroundColor: isHuman ? theme.colors.accent + '22' : theme.colors.primary + '22',
                        borderColor:     isHuman ? theme.colors.accent + '44' : theme.colors.primary + '44',
                      }]}>
                        <Ionicons name={isHuman ? 'person' : 'hardware-chip-outline'} size={11} color={isHuman ? theme.colors.accent : theme.colors.primary} />
                      </View>
                    ) : null}
                  </View>
                );
              })}
            </View>
          )}
        </ScrollView>

        <View style={[styles.readOnlyBar, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
          <Ionicons name="lock-closed-outline" size={13} color={theme.colors.subtle} />
          <Text style={[styles.readOnlyText, { color: theme.colors.subtle }]}>Read-only — session has ended</Text>
        </View>
      </View>
    );
  }

  // ── Session list ─────────────────────────────────────────────────────────────
  return (
    <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.listContent} showsVerticalScrollIndicator={false}>
      <View style={styles.listColumn}>
        <View style={[styles.listHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <View style={{ gap: 4 }}>
            <Text style={[styles.listHeaderTitle, { color: theme.colors.text }]}>History</Text>
            <Text style={[styles.listHeaderSub,   { color: theme.colors.muted }]}>
              {sessions.length === 0 ? 'No past sessions' : `${sessions.length} past conversation${sessions.length === 1 ? '' : 's'}`}
            </Text>
          </View>
          <View style={[styles.badge, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
            <Ionicons name="time-outline" size={12} color={theme.colors.muted} />
            <Text style={[styles.badgeText, { color: theme.colors.muted }]}>Ended</Text>
          </View>
        </View>

        {sessions.length === 0 ? (
          <View style={[styles.emptyCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
            <Ionicons name="time-outline" size={40} color={theme.colors.subtle} />
            <Text style={[styles.emptyTitle, { color: theme.colors.text }]}>No history yet</Text>
            <Text style={[styles.emptyBody,  { color: theme.colors.muted }]}>
              Past conversations will appear here once sessions end.
            </Text>
          </View>
        ) : (
          sessions.map((session) => {
            const visitorName = session.visitor_identifier || session.visitor_ip || 'Anonymous visitor';
            const preview     = session.first_message || 'No preview';
            const domain      = session.page_url ? (() => { try { return new URL(session.page_url).hostname; } catch { return session.page_url; } })() : null;

            return (
              <Pressable
                key={session.id}
                style={[styles.historyCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}
                onPress={() => openSession(session)}
              >
                <View style={[styles.historyAvatar, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                  <Ionicons name="person" size={16} color={theme.colors.muted} />
                </View>
                <View style={styles.historyBody}>
                  <View style={styles.historyTopRow}>
                    <Text style={[styles.historyName, { color: theme.colors.text }]} numberOfLines={1}>{visitorName}</Text>
                    <Text style={[styles.historyTime, { color: theme.colors.subtle }]}>{formatRelativeTime(session.last_activity_at)}</Text>
                  </View>
                  <Text style={[styles.historyPreview, { color: theme.colors.muted }]} numberOfLines={1}>{preview}</Text>
                  <View style={styles.historyChips}>
                    <View style={[styles.historyChip, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                      <Ionicons name="chatbubble-outline" size={9} color={theme.colors.subtle} />
                      <Text style={[styles.historyChipText, { color: theme.colors.muted }]}>{session.message_count} msgs</Text>
                    </View>
                    {domain ? (
                      <View style={[styles.historyChip, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                        <Ionicons name="globe-outline" size={9} color={theme.colors.subtle} />
                        <Text style={[styles.historyChipText, { color: theme.colors.muted }]} numberOfLines={1}>{domain}</Text>
                      </View>
                    ) : null}
                  </View>
                </View>
                <Ionicons name="chevron-forward" size={14} color={theme.colors.subtle} />
              </Pressable>
            );
          })
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  listContent: { paddingHorizontal: 16, paddingTop: 16, paddingBottom: 32, gap: 14 },
  listColumn:  { gap: 12 },
  listHeader:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderWidth: 1, borderRadius: 20, paddingHorizontal: 20, paddingVertical: 16 },
  listHeaderTitle: { fontFamily: 'Inter_800ExtraBold', fontSize: 20, letterSpacing: -0.5 },
  listHeaderSub:   { fontFamily: 'Inter_400Regular', fontSize: 13, marginTop: 2 },

  // Badge / pill
  badge:     { flexDirection: 'row', alignItems: 'center', gap: 5, borderWidth: 1, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 7, flexShrink: 0 },
  badgeText: { fontFamily: 'Inter_700Bold', fontSize: 12, letterSpacing: 0.2 },

  // Thread screen
  threadScreen:  { flex: 1, gap: 10, paddingHorizontal: 20, paddingTop: 18 },
  scrollArea:    { flex: 1 },
  scrollContent: { paddingBottom: 16 },
  threadHeader:  { flexDirection: 'row', alignItems: 'center', gap: 10, borderWidth: 1, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 12 },
  backBtn:       { flexDirection: 'row', alignItems: 'center', gap: 2, flexShrink: 0 },
  backText:      { fontFamily: 'Inter_700Bold', fontSize: 13 },
  threadCenter:  { flex: 1, flexDirection: 'row', alignItems: 'center', gap: 10, minWidth: 0 },
  threadAvatar:  { width: 36, height: 36, borderRadius: 999, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  threadName:    { fontFamily: 'Inter_700Bold', fontSize: 14, letterSpacing: -0.2 },
  threadMeta:    { fontFamily: 'Inter_400Regular', fontSize: 11, marginTop: 1 },
  urlStrip:      { flexDirection: 'row', alignItems: 'center', gap: 6, borderBottomWidth: 1, paddingHorizontal: 16, paddingVertical: 7 },
  urlText:       { fontFamily: 'Inter_400Regular', fontSize: 11, flex: 1 },
  readOnlyBar:   { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderTopWidth: 1, paddingVertical: 12 },
  readOnlyText:  { fontFamily: 'Inter_400Regular', fontSize: 12 },

  // Messages
  loadingWrap: { alignItems: 'center', gap: 10, paddingVertical: 40 },
  loadingText: { fontFamily: 'Inter_500Medium', fontSize: 13 },
  emptyWrap:   { alignItems: 'center', gap: 10, paddingVertical: 48 },
  emptyText:   { fontFamily: 'Inter_500Medium', fontSize: 14 },
  msgColumn:   { gap: 12 },
  bubbleRow:      { flexDirection: 'row', alignItems: 'flex-end', gap: 8 },
  bubbleRowLeft:  { justifyContent: 'flex-start' },
  bubbleRowRight: { justifyContent: 'flex-end' },
  avatarSmall: { width: 26, height: 26, borderRadius: 999, borderWidth: 1, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  senderLabel: { fontFamily: 'Inter_600SemiBold', fontSize: 10, textTransform: 'uppercase', letterSpacing: 0.4 },
  bubble:        { borderWidth: 1, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 10 },
  bubbleVisitor: { borderBottomLeftRadius: 4 },
  bubbleAI:      { borderBottomRightRadius: 4 },
  bubbleHuman:   { borderBottomRightRadius: 4 },
  bubbleText:    { fontFamily: 'Inter_400Regular', fontSize: 14, lineHeight: 22 },

  // History list cards
  historyCard:    { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 16, paddingHorizontal: 14, paddingVertical: 12 },
  historyAvatar:  { width: 36, height: 36, borderRadius: 999, borderWidth: 1, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  historyBody:    { flex: 1, gap: 3, minWidth: 0 },
  historyTopRow:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  historyName:    { fontFamily: 'Inter_600SemiBold', fontSize: 13, flex: 1 },
  historyTime:    { fontFamily: 'Inter_400Regular', fontSize: 11, flexShrink: 0 },
  historyPreview: { fontFamily: 'Inter_400Regular', fontSize: 12, lineHeight: 17 },
  historyChips:   { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 2 },
  historyChip:     { flexDirection: 'row', alignItems: 'center', gap: 3, borderWidth: 1, borderRadius: 999, paddingHorizontal: 7, paddingVertical: 2 },
  historyChipText: { fontFamily: 'Inter_500Medium', fontSize: 10 },

  // Empty state
  emptyCard:  { alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 24, padding: 48 },
  emptyTitle: { fontFamily: 'Inter_700Bold', fontSize: 16, letterSpacing: -0.3 },
  emptyBody:  { fontFamily: 'Inter_400Regular', fontSize: 13, lineHeight: 21, textAlign: 'center' },
});
