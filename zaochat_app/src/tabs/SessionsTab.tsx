import { Ionicons } from '@expo/vector-icons';
import { useEffect, useRef, useState } from 'react';
import {
  useColorScheme,
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { ErrorBanner, SuccessBanner } from '../components/Banners';
import {
  getSessionMessages,
  releaseSessionTakeover,
  sendSessionMessage,
  takeoverSession,
  type MobileSession,
  type MobileSessionMessage,
} from '../lib/mobileApi';
import { getTheme } from '../theme';
import { formatRelativeTime } from '../utils/time';

// Module-level callback so the notification listener in App can instantly
// trigger a message refresh inside SessionsTab when a takeover_reply arrives.
export let onPushRefresh: (() => void) | null = null; // NOSONAR

type Props = {
  sessions: MobileSession[];
  token: string;
  onSessionsChange: (sessions: MobileSession[]) => void;
};

export function SessionsTab({ sessions, token, onSessionsChange }: Props) {
  const theme = getTheme(useColorScheme());
  const [selectedSession, setSelectedSession] = useState<MobileSession | null>(null);
  const [messages, setMessages] = useState<MobileSessionMessage[]>([]);
  const [loading, setLoading] = useState(false);
  const [savingTakeover, setSavingTakeover] = useState(false);
  const [sendingMessage, setSendingMessage] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const selectedSessionRef = useRef<MobileSession | null>(null);

  selectedSessionRef.current = selectedSession;

  function stopPolling(): void {
    if (pollRef.current) {
      clearInterval(pollRef.current);
      pollRef.current = null;
    }
  }

  function startPolling(): void {
    stopPolling();
    pollRef.current = setInterval(async () => {
      const session = selectedSessionRef.current;
      if (!session) return;
      try {
        const data = await getSessionMessages(token, session.id);
        setMessages((prev) => {
          if (data.length !== prev.length) return data;
          const lastPrev = prev[prev.length - 1];
          const lastNew  = data[data.length - 1];
          return lastPrev?.id !== lastNew?.id ? data : prev;
        });
      } catch {
        // silent — keep showing existing messages
      }
    }, 3000);
  }

  useEffect(() => () => {
    stopPolling();
    onPushRefresh = null;
  }, []);

  useEffect(() => {
    onPushRefresh = () => {
      const session = selectedSessionRef.current;
      if (!session) return;
      getSessionMessages(token, session.id)
        .then((data) => setMessages(data))
        .catch(() => {});
    };
  }, [token]);

  function syncSession(updated: MobileSession): void {
    onSessionsChange(sessions.map((item) => (item.id === updated.id ? updated : item)));
    setSelectedSession((current) => (current?.id === updated.id ? updated : current));
  }

  async function openSession(session: MobileSession): Promise<void> {
    setSelectedSession(session);
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const data = await getSessionMessages(token, session.id);
      setMessages(data);
      startPolling();
    } catch (err) {
      setMessages([]);
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setLoading(false);
    }
  }

  async function handleTakeover(): Promise<void> {
    if (!selectedSession) return;
    setSavingTakeover(true);
    setError(null);
    setSuccess(null);
    try {
      const updated = selectedSession.is_human_takeover
        ? await releaseSessionTakeover(token, selectedSession.id)
        : await takeoverSession(token, selectedSession.id);
      syncSession(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setSavingTakeover(false);
    }
  }

  async function handleSendMessage(): Promise<void> {
    if (!selectedSession || !replyText.trim()) return;
    setSendingMessage(true);
    setError(null);
    setSuccess(null);
    try {
      const response = await sendSessionMessage(token, selectedSession.id, replyText.trim());
      syncSession(response.session);
      setMessages((current) => [...current, response.message]);
      setReplyText('');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setSendingMessage(false);
    }
  }

  // ── Thread view ──────────────────────────────────────────────────────────────
  if (selectedSession) {
    return (
      <View style={styles.threadScreen}>
        {/* Header */}
        <View style={[styles.threadHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <Pressable
            style={styles.backBtn}
            onPress={() => {
              stopPolling();
              setSelectedSession(null);
              setMessages([]);
              setError(null);
              setSuccess(null);
            }}
          >
            <Ionicons name="chevron-back" size={20} color={theme.colors.primary} />
            <Text style={[styles.backText, { color: theme.colors.primary }]}>Sessions</Text>
          </Pressable>

          <View style={styles.threadCenter}>
            <View style={[styles.threadAvatar, { backgroundColor: theme.colors.primary + '22' }]}>
              <Ionicons name="person" size={16} color={theme.colors.primary} />
            </View>
            <View style={{ gap: 1 }}>
              <Text style={[styles.threadName, { color: theme.colors.text }]} numberOfLines={1}>
                {selectedSession.visitor_identifier || selectedSession.visitor_ip || 'Anonymous'}
              </Text>
              <Text style={[styles.threadMeta, { color: theme.colors.muted }]}>
                {selectedSession.message_count} messages
              </Text>
            </View>
          </View>

          <Pressable
            style={[
              styles.takeoverPill,
              {
                backgroundColor: selectedSession.is_human_takeover ? theme.colors.primary : theme.colors.surface,
                borderColor: selectedSession.is_human_takeover ? theme.colors.primary : theme.colors.border,
                opacity: savingTakeover ? 0.6 : 1,
              },
            ]}
            disabled={savingTakeover}
            onPress={handleTakeover}
          >
            <Ionicons
              name={selectedSession.is_human_takeover ? 'hand-left' : 'hardware-chip-outline'}
              size={13}
              color={selectedSession.is_human_takeover ? '#fff' : theme.colors.muted}
            />
            <Text style={[styles.takeoverPillText, { color: selectedSession.is_human_takeover ? '#fff' : theme.colors.muted }]}>
              {savingTakeover ? '...' : selectedSession.is_human_takeover ? 'Live' : 'AI'}
            </Text>
          </Pressable>
        </View>

        {/* Status banner */}
        {selectedSession.is_human_takeover ? (
          <View style={[styles.statusBanner, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}>
            <Ionicons name="hand-left" size={14} color={theme.colors.primary} />
            <Text style={[styles.statusBannerText, { color: theme.colors.primary }]}>
              You are live — replies go directly to the visitor
            </Text>
          </View>
        ) : (
          <View style={[styles.statusBanner, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
            <Ionicons name="hardware-chip-outline" size={14} color={theme.colors.muted} />
            <Text style={[styles.statusBannerText, { color: theme.colors.muted }]}>
              AI is active — tap Live to take over this conversation
            </Text>
          </View>
        )}

        {error   ? <ErrorBanner   text={error}   /> : null}
        {success ? <SuccessBanner text={success} /> : null}

        {/* Messages */}
        <ScrollView style={styles.scrollArea} contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {loading ? (
            <View style={styles.loadingWrap}>
              <ActivityIndicator color={theme.colors.primary} />
              <Text style={[styles.loadingText, { color: theme.colors.muted }]}>Loading messages...</Text>
            </View>
          ) : messages.length === 0 ? (
            <View style={styles.emptyWrap}>
              <Ionicons name="chatbubbles-outline" size={36} color={theme.colors.subtle} />
              <Text style={[styles.emptyText, { color: theme.colors.muted }]}>No messages yet</Text>
            </View>
          ) : (
            <View style={styles.msgColumn}>
              {messages.filter((m) => m.source !== 'takeover_notice').map((message) => {
                const isVisitor = message.role === 'user';
                const isHuman   = message.role === 'assistant' && message.human_takeover;
                const isAI      = message.role === 'assistant' && !message.human_takeover;

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
                        color: isHuman ? theme.colors.accent : isAI ? theme.colors.primary : theme.colors.muted,
                        textAlign: isVisitor ? 'left' : 'right',
                      }]}>
                        {isVisitor ? 'Visitor' : isHuman ? (message.sent_by_name || 'You') : 'ZaoChat AI'}
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

        {/* Reply area or takeover CTA */}
        {selectedSession.is_human_takeover ? (
          <View style={{ gap: 8 }}>
            <View style={[styles.replyBox, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
              <TextInput
                style={[styles.replyInput, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border, color: theme.colors.text }]}
                placeholder="Type your reply..."
                placeholderTextColor={theme.colors.subtle}
                value={replyText}
                onChangeText={setReplyText}
                multiline
                maxLength={2000}
              />
              <Pressable
                style={[styles.sendBtn, { backgroundColor: replyText.trim() && !sendingMessage ? theme.colors.primary : theme.colors.subtle }]}
                disabled={!replyText.trim() || sendingMessage}
                onPress={handleSendMessage}
              >
                {sendingMessage
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Ionicons name="send" size={18} color="#fff" />
                }
              </Pressable>
            </View>
            <Pressable
              style={[styles.handBackBtn, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border, opacity: savingTakeover ? 0.6 : 1 }]}
              disabled={savingTakeover}
              onPress={async () => {
                stopPolling();
                await handleTakeover();
                setSelectedSession(null);
                setMessages([]);
                setError(null);
                setSuccess(null);
              }}
            >
              <Ionicons name="hardware-chip-outline" size={15} color={theme.colors.muted} />
              <Text style={[styles.handBackText, { color: theme.colors.muted }]}>
                {savingTakeover ? 'Releasing...' : 'Hand back to AI & exit'}
              </Text>
            </Pressable>
          </View>
        ) : (
          <Pressable
            style={[styles.takeoverCta, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}
            onPress={handleTakeover}
          >
            <Ionicons name="hand-left-outline" size={16} color={theme.colors.primary} />
            <Text style={[styles.takeoverCtaText, { color: theme.colors.primary }]}>
              Tap to take over and reply as yourself
            </Text>
          </Pressable>
        )}
      </View>
    );
  }

  // ── Session list ─────────────────────────────────────────────────────────────
  const liveSessions    = sessions.filter((s) =>  s.is_human_takeover ||  s.is_active);
  const historySessions = sessions.filter((s) => !s.is_human_takeover && !s.is_active);

  function renderLiveCard(session: MobileSession) {
    const isTakenOver     = session.is_human_takeover;
    const visitorName     = session.visitor_identifier || session.visitor_ip || 'Anonymous visitor';
    const preview         = session.first_message || 'No preview';
    const domain          = session.page_url ? (() => { try { return new URL(session.page_url).hostname; } catch { return session.page_url; } })() : null;
    const accentColor     = isTakenOver ? theme.colors.primary : '#f59e0b';
    const accentFaint     = isTakenOver ? theme.colors.primary + '14' : '#f59e0b14';
    const statusLabel     = isTakenOver ? "You're live" : 'Visitor active';
    const statusIcon: keyof typeof Ionicons.glyphMap = isTakenOver ? 'hand-left' : 'ellipsis-horizontal';

    return (
      <Pressable
        key={session.id}
        style={[
          styles.liveCard,
          { backgroundColor: theme.colors.card, borderColor: accentColor + '30' },
          isTakenOver && { shadowColor: theme.colors.primary, shadowOpacity: 0.18, shadowRadius: 16, shadowOffset: { width: 0, height: 6 }, elevation: 6 },
        ]}
        onPress={() => openSession(session)}
      >
        <View style={[styles.liveStripe, { backgroundColor: accentColor }]} />
        <View style={styles.liveInner}>
          <View style={styles.liveTopRow}>
            <View style={[styles.liveStatusPill, { backgroundColor: accentFaint }]}>
              <View style={[styles.livePulseDot, { backgroundColor: accentColor }]} />
              <Ionicons name={statusIcon} size={11} color={accentColor} />
              <Text style={[styles.liveStatusText, { color: accentColor }]}>{statusLabel}</Text>
            </View>
            <Text style={[styles.liveTime, { color: theme.colors.subtle }]}>{formatRelativeTime(session.last_activity_at)}</Text>
          </View>
          <Text style={[styles.liveName, { color: theme.colors.text }]} numberOfLines={1}>{visitorName}</Text>
          <Text style={[styles.livePreview, { color: theme.colors.muted }]} numberOfLines={2}>{preview}</Text>
          <View style={styles.liveBottomRow}>
            <View style={[styles.liveChip, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
              <Ionicons name="chatbubble-outline" size={10} color={theme.colors.subtle} />
              <Text style={[styles.liveChipText, { color: theme.colors.muted }]}>{session.message_count} msgs</Text>
            </View>
            {domain ? (
              <View style={[styles.liveChip, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                <Ionicons name="globe-outline" size={10} color={theme.colors.subtle} />
                <Text style={[styles.liveChipText, { color: theme.colors.muted }]} numberOfLines={1}>{domain}</Text>
              </View>
            ) : null}
            <View style={{ flex: 1 }} />
            <Ionicons name="chevron-forward" size={14} color={accentColor + '80'} />
          </View>
        </View>
      </Pressable>
    );
  }

  function renderHistoryCard(session: MobileSession) {
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
  }

  return (
    <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.listContent} showsVerticalScrollIndicator={false}>
      <View style={styles.listColumn}>
        {sessions.length === 0 ? (
          <View style={[styles.emptyCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
            <Ionicons name="chatbubbles-outline" size={40} color={theme.colors.subtle} />
            <Text style={[styles.emptyTitle, { color: theme.colors.text }]}>No sessions yet</Text>
            <Text style={[styles.emptyBody,  { color: theme.colors.muted }]}>
              Sessions appear here as soon as visitors start chatting on your widget.
            </Text>
          </View>
        ) : (
          <>
            {liveSessions.length > 0 ? (
              <>
                <View style={styles.dividerRow}>
                  <View style={[styles.dividerLine, { backgroundColor: theme.colors.border }]} />
                  <View style={[styles.dividerChip, { backgroundColor: '#4ade8018', borderColor: '#4ade8040' }]}>
                    <View style={styles.dividerDot} />
                    <Text style={[styles.dividerLabel, { color: '#4ade80' }]}>{liveSessions.length} active</Text>
                  </View>
                  <View style={[styles.dividerLine, { backgroundColor: theme.colors.border }]} />
                </View>
                {liveSessions.map(renderLiveCard)}
              </>
            ) : null}
            {historySessions.length > 0 ? (
              <>
                <View style={styles.dividerRow}>
                  <View style={[styles.dividerLine, { backgroundColor: theme.colors.border }]} />
                  <Text style={[styles.dividerLabelPlain, { color: theme.colors.subtle }]}>recent</Text>
                  <View style={[styles.dividerLine, { backgroundColor: theme.colors.border }]} />
                </View>
                {historySessions.map(renderHistoryCard)}
              </>
            ) : null}
          </>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  listContent: { paddingHorizontal: 16, paddingTop: 16, paddingBottom: 32, gap: 14 },
  listColumn: { gap: 12 },

  // Thread screen
  threadScreen: { flex: 1, gap: 10, paddingHorizontal: 20, paddingTop: 18 },
  scrollArea: { flex: 1 },
  scrollContent: { paddingBottom: 16 },
  threadHeader: { flexDirection: 'row', alignItems: 'center', gap: 10, borderWidth: 1, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 12 },
  backBtn: { flexDirection: 'row', alignItems: 'center', gap: 2, flexShrink: 0 },
  backText: { fontFamily: 'Inter_700Bold', fontSize: 13 },
  threadCenter: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: 10, minWidth: 0 },
  threadAvatar: { width: 36, height: 36, borderRadius: 999, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  threadName: { fontFamily: 'Inter_700Bold', fontSize: 14, letterSpacing: -0.2 },
  threadMeta: { fontFamily: 'Inter_400Regular', fontSize: 11, marginTop: 1 },
  takeoverPill: { flexDirection: 'row', alignItems: 'center', gap: 5, borderWidth: 1, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 7, flexShrink: 0 },
  takeoverPillText: { fontFamily: 'Inter_700Bold', fontSize: 12, letterSpacing: 0.2 },
  statusBanner: { flexDirection: 'row', alignItems: 'center', gap: 8, borderWidth: 1, borderRadius: 100, paddingHorizontal: 16, paddingVertical: 10 },
  statusBannerText: { fontFamily: 'Inter_600SemiBold', fontSize: 12, flex: 1, letterSpacing: 0.1 },

  // Messages
  loadingWrap: { alignItems: 'center', gap: 10, paddingVertical: 40 },
  loadingText: { fontFamily: 'Inter_500Medium', fontSize: 13 },
  emptyWrap: { alignItems: 'center', gap: 10, paddingVertical: 48 },
  emptyText: { fontFamily: 'Inter_500Medium', fontSize: 14 },
  msgColumn: { gap: 12 },
  bubbleRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 8 },
  bubbleRowLeft:  { justifyContent: 'flex-start' },
  bubbleRowRight: { justifyContent: 'flex-end' },
  avatarSmall: { width: 26, height: 26, borderRadius: 999, borderWidth: 1, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  senderLabel: { fontFamily: 'Inter_600SemiBold', fontSize: 10, textTransform: 'uppercase', letterSpacing: 0.4 },
  bubble: { borderWidth: 1, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 10 },
  bubbleVisitor: { borderBottomLeftRadius: 4 },
  bubbleAI:      { borderBottomRightRadius: 4 },
  bubbleHuman:   { borderBottomRightRadius: 4 },
  bubbleText: { fontFamily: 'Inter_400Regular', fontSize: 14, lineHeight: 22 },

  // Reply box
  replyBox: { flexDirection: 'row', alignItems: 'flex-end', gap: 10, borderWidth: 1, borderRadius: 22, padding: 10 },
  replyInput: { flex: 1, borderWidth: 1, borderRadius: 16, paddingHorizontal: 14, paddingVertical: 10, fontFamily: 'Inter_400Regular', fontSize: 14, maxHeight: 120, textAlignVertical: 'top' },
  sendBtn: { width: 44, height: 44, borderRadius: 999, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  takeoverCta: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, borderWidth: 1, borderRadius: 100, paddingHorizontal: 20, paddingVertical: 14 },
  takeoverCtaText: { fontFamily: 'Inter_700Bold', fontSize: 14 },
  handBackBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, borderWidth: 1, borderRadius: 100, paddingHorizontal: 20, paddingVertical: 12, marginBottom: 4 },
  handBackText: { fontFamily: 'Inter_600SemiBold', fontSize: 13 },

  // Live cards
  liveCard: { flexDirection: 'row', borderWidth: 1, borderRadius: 20, overflow: 'hidden' },
  liveStripe: { width: 4, flexShrink: 0 },
  liveInner: { flex: 1, padding: 14, gap: 6 },
  liveTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  liveStatusPill: { flexDirection: 'row', alignItems: 'center', gap: 5, borderRadius: 999, paddingHorizontal: 9, paddingVertical: 4 },
  livePulseDot: { width: 6, height: 6, borderRadius: 999 },
  liveStatusText: { fontFamily: 'Inter_700Bold', fontSize: 11, letterSpacing: 0.2 },
  liveTime: { fontFamily: 'Inter_400Regular', fontSize: 11 },
  liveName: { fontFamily: 'Inter_700Bold', fontSize: 15, letterSpacing: -0.2 },
  livePreview: { fontFamily: 'Inter_400Regular', fontSize: 13, lineHeight: 19 },
  liveBottomRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 2 },
  liveChip: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderRadius: 999, paddingHorizontal: 8, paddingVertical: 3 },
  liveChipText: { fontFamily: 'Inter_500Medium', fontSize: 11 },

  // History cards
  historyCard: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 16, paddingHorizontal: 14, paddingVertical: 12 },
  historyAvatar: { width: 36, height: 36, borderRadius: 999, borderWidth: 1, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  historyBody: { flex: 1, gap: 3, minWidth: 0 },
  historyTopRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  historyName: { fontFamily: 'Inter_600SemiBold', fontSize: 13, flex: 1 },
  historyTime: { fontFamily: 'Inter_400Regular', fontSize: 11, flexShrink: 0 },
  historyPreview: { fontFamily: 'Inter_400Regular', fontSize: 12, lineHeight: 17 },
  historyChips: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 2 },
  historyChip: { flexDirection: 'row', alignItems: 'center', gap: 3, borderWidth: 1, borderRadius: 999, paddingHorizontal: 7, paddingVertical: 2 },
  historyChipText: { fontFamily: 'Inter_500Medium', fontSize: 10 },

  // Dividers
  dividerRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 4 },
  dividerLine: { flex: 1, height: 1 },
  dividerChip: { flexDirection: 'row', alignItems: 'center', gap: 5, borderWidth: 1, borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4 },
  dividerDot: { width: 6, height: 6, borderRadius: 999, backgroundColor: '#4ade80' },
  dividerLabel: { fontFamily: 'Inter_700Bold', fontSize: 11, letterSpacing: 0.3 },
  dividerLabelPlain: { fontFamily: 'Inter_500Medium', fontSize: 11, letterSpacing: 0.5, textTransform: 'uppercase' },

  // Empty state
  emptyCard: { alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 24, padding: 48 },
  emptyTitle: { fontFamily: 'Inter_700Bold', fontSize: 16, letterSpacing: -0.3 },
  emptyBody: { fontFamily: 'Inter_400Regular', fontSize: 13, lineHeight: 21, textAlign: 'center' },
});
