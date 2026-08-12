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
import {
  fetchLeadsPage,
  getLeadDetail,
  getSessionMessages,
  updateLeadStatus,
  type MobileLead,
  type MobileLeadDetail,
  type MobilePaginatedLeads,
  type MobileSessionMessage,
} from '../lib/mobileApi';
import { getTheme } from '../theme';
import { capitalize } from '../utils/text';
import { formatRelativeTime } from '../utils/time';

type Props = {
  leads: MobileLead[];
  leadsMeta: MobilePaginatedLeads['meta'];
  token: string;
  onLeadsChange: (leads: MobileLead[], meta: MobilePaginatedLeads['meta']) => void;
};

export function LeadsTab({ leads, leadsMeta, token, onLeadsChange }: Props) {
  const theme = getTheme(useColorScheme());
  const [selectedLeadId,       setSelectedLeadId]       = useState<number | null>(null);
  const [selectedLeadDetail,   setSelectedLeadDetail]   = useState<MobileLeadDetail | null>(null);
  const [savingLeadId,         setSavingLeadId]         = useState<number | null>(null);
  const [loadingLeadId,        setLoadingLeadId]        = useState<number | null>(null);
  const [linkedMessages,       setLinkedMessages]       = useState<MobileSessionMessage[]>([]);
  const [loadingLinkedMessages, setLoadingLinkedMessages] = useState(false);
  const [loadingPage,          setLoadingPage]          = useState(false);
  const [error,                setError]                = useState<string | null>(null);

  async function handleLeadPress(lead: MobileLead): Promise<void> {
    if (selectedLeadId === lead.id) {
      setSelectedLeadId(null);
      setSelectedLeadDetail(null);
      setLinkedMessages([]);
      setError(null);
      return;
    }
    setSelectedLeadId(lead.id);
    setSelectedLeadDetail(null);
    setLoadingLeadId(lead.id);
    setLinkedMessages([]);
    setError(null);
    try {
      const detail = await getLeadDetail(token, lead.id);
      setSelectedLeadDetail(detail);
    } catch (err) {
      setSelectedLeadDetail(null);
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setLoadingLeadId(null);
    }
  }

  async function changeStatus(lead: MobileLead, status: 'new' | 'contacted' | 'closed'): Promise<void> {
    setSavingLeadId(lead.id);
    setError(null);
    try {
      const updated = await updateLeadStatus(token, lead.id, status);
      onLeadsChange(leads.map((item) => (item.id === updated.id ? updated : item)), leadsMeta);
      if (selectedLeadId === updated.id) setSelectedLeadDetail(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setSavingLeadId(null);
    }
  }

  async function openLinkedSession(): Promise<void> {
    if (!selectedLeadDetail?.chat_session_id) return;
    setLoadingLinkedMessages(true);
    setError(null);
    try {
      const msgs = await getSessionMessages(token, selectedLeadDetail.chat_session_id);
      setLinkedMessages(msgs);
    } catch (err) {
      setLinkedMessages([]);
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setLoadingLinkedMessages(false);
    }
  }

  async function loadPage(page: number): Promise<void> {
    setLoadingPage(true);
    setSelectedLeadId(null);
    setSelectedLeadDetail(null);
    setLinkedMessages([]);
    setError(null);
    try {
      const result = await fetchLeadsPage(token, page);
      onLeadsChange(result.leads, result.meta);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Something went wrong.');
    } finally {
      setLoadingPage(false);
    }
  }

  return (
    <ScrollView style={{ flex: 1 }} contentContainerStyle={styles.listContent} showsVerticalScrollIndicator={false}>
      <View style={styles.listColumn}>
        {/* Header */}
        <View style={[styles.listHeader, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
          <View style={{ gap: 4 }}>
            <Text style={[styles.listHeaderTitle, { color: theme.colors.text }]}>Leads</Text>
            <Text style={[styles.listHeaderSub,   { color: theme.colors.muted }]}>
              {leadsMeta.total === 0 ? 'No leads yet' : `${leadsMeta.total} lead${leadsMeta.total === 1 ? '' : 's'}`}
            </Text>
          </View>
        </View>

        {error ? <ErrorBanner text={error} /> : null}

        {leads.length === 0 ? (
          <View style={[styles.emptyCard, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
            <Ionicons name="flash-outline" size={40} color={theme.colors.subtle} />
            <Text style={[styles.emptyTitle, { color: theme.colors.text }]}>No leads yet</Text>
            <Text style={[styles.emptyBody,  { color: theme.colors.muted }]}>
              Leads captured by the chatbot will appear here.
            </Text>
          </View>
        ) : (
          leads.map((lead) => {
            const isSelected = selectedLeadId === lead.id;

            return (
              <View key={lead.id}>
                <Pressable
                  style={[
                    styles.leadCard,
                    {
                      backgroundColor: isSelected ? theme.colors.surface : theme.colors.card,
                      borderColor: isSelected ? theme.colors.primary + '60' : theme.colors.border,
                    },
                  ]}
                  onPress={() => handleLeadPress(lead)}
                >
                  <View style={[
                    styles.leadAvatar,
                    { backgroundColor: isSelected ? theme.colors.primary + '22' : theme.colors.surface, borderColor: isSelected ? theme.colors.primary + '44' : theme.colors.border },
                  ]}>
                    <Ionicons name="person" size={16} color={isSelected ? theme.colors.primary : theme.colors.muted} />
                  </View>
                  <View style={styles.leadBody}>
                    <View style={styles.leadTopRow}>
                      <Text style={[styles.leadName, { color: theme.colors.text }]} numberOfLines={1}>{lead.name}</Text>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                        <Text style={[styles.leadTime, { color: theme.colors.subtle }]}>{formatRelativeTime(lead.created_at)}</Text>
                        <View style={[
                          styles.statusChip,
                          {
                            backgroundColor: lead.status === 'new' ? theme.colors.primary + '22' : theme.colors.surface,
                            borderColor: lead.status === 'new' ? theme.colors.primary + '44' : theme.colors.border,
                          },
                        ]}>
                          <Text style={[styles.statusChipText, { color: lead.status === 'new' ? theme.colors.primary : theme.colors.muted }]}>
                            {capitalize(lead.status)}
                          </Text>
                        </View>
                      </View>
                    </View>
                    <Text style={[styles.leadPreview, { color: theme.colors.muted }]} numberOfLines={1}>
                      {lead.user_request || lead.contact}
                    </Text>
                  </View>
                  <Ionicons name={isSelected ? 'chevron-up' : 'chevron-down'} size={16} color={theme.colors.subtle} />
                </Pressable>

                {/* Inline detail panel */}
                {isSelected ? (() => {
                  const parts          = lead.contact.split(',').map((p) => p.trim());
                  const email          = parts.find((p) => p.includes('@'));
                  const phone          = parts.find((p) => /[0-9]{7,}/.test(p));
                  const userRequest    = selectedLeadDetail?.user_request || lead.user_request;
                  const reqIsContact   = userRequest ? parts.some((p) => userRequest.includes(p)) && parts.length > 1 : false;

                  return (
                    <View style={[styles.detailPanel, { backgroundColor: theme.colors.surface, borderColor: theme.colors.border }]}>
                      <View style={styles.contactRow}>
                        {email ? (
                          <View style={[styles.contactItem, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
                            <Ionicons name="mail-outline" size={12} color={theme.colors.muted} />
                            <Text style={[styles.contactText, { color: theme.colors.text }]} numberOfLines={1}>{email}</Text>
                          </View>
                        ) : null}
                        {phone ? (
                          <View style={[styles.contactItem, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
                            <Ionicons name="call-outline" size={12} color={theme.colors.muted} />
                            <Text style={[styles.contactText, { color: theme.colors.text }]}>{phone}</Text>
                          </View>
                        ) : null}
                        <View style={[styles.triggerBadge, { backgroundColor: theme.colors.primary + '18', borderColor: theme.colors.primary + '40' }]}>
                          <Text style={[styles.triggerText, { color: theme.colors.primary }]}>{capitalize(lead.trigger)}</Text>
                        </View>
                      </View>

                      {loadingLeadId === lead.id ? (
                        <ActivityIndicator color={theme.colors.primary} style={{ alignSelf: 'flex-start' }} />
                      ) : (
                        <>
                          {userRequest && !reqIsContact ? (
                            <View style={[styles.requestBox, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}>
                              <Text style={[styles.requestLabel, { color: theme.colors.muted }]}>Request</Text>
                              <Text style={[styles.requestText,  { color: theme.colors.text }]}>{userRequest}</Text>
                            </View>
                          ) : null}

                          {selectedLeadDetail?.chat_session_id ? (
                            <Pressable
                              style={[styles.viewConvoBtn, { backgroundColor: theme.colors.card, borderColor: theme.colors.border }]}
                              onPress={openLinkedSession}
                            >
                              <Ionicons name="chatbubbles-outline" size={14} color={theme.colors.accent} />
                              <Text style={[styles.viewConvoBtnText, { color: theme.colors.accent }]}>
                                {loadingLinkedMessages ? 'Loading...' : 'View conversation'}
                              </Text>
                            </Pressable>
                          ) : null}

                          {linkedMessages.length > 0 ? (
                            <View style={{ gap: 8 }}>
                              {linkedMessages.map((message) => (
                                <View
                                  key={message.id}
                                  style={[styles.msgBubble, {
                                    backgroundColor: message.role === 'assistant' ? theme.colors.card : theme.colors.primary,
                                    borderColor:     message.role === 'assistant' ? theme.colors.border : theme.colors.primary,
                                  }]}
                                >
                                  <Text style={[styles.msgRole, { color: message.role === 'assistant' ? theme.colors.accent : theme.colors.primaryText }]}>
                                    {message.role === 'assistant' ? 'AI' : 'Visitor'}
                                  </Text>
                                  <Text style={[styles.msgContent, { color: message.role === 'assistant' ? theme.colors.text : theme.colors.primaryText }]}>
                                    {message.content}
                                  </Text>
                                </View>
                              ))}
                            </View>
                          ) : null}
                        </>
                      )}

                      {/* Status buttons */}
                      <View style={styles.statusRow}>
                        {(['new', 'contacted', 'closed'] as const).map((status) => (
                          <Pressable
                            key={status}
                            style={[
                              styles.statusBtn,
                              {
                                backgroundColor: lead.status === status ? theme.colors.primary : theme.colors.card,
                                borderColor:     lead.status === status ? theme.colors.primary : theme.colors.border,
                                opacity: savingLeadId === lead.id ? 0.65 : 1,
                              },
                            ]}
                            disabled={savingLeadId === lead.id}
                            onPress={() => changeStatus(lead, status)}
                          >
                            <Text style={[styles.statusBtnText, { color: lead.status === status ? theme.colors.primaryText : theme.colors.text }]}>
                              {savingLeadId === lead.id && lead.status !== status ? 'Saving...' : capitalize(status)}
                            </Text>
                          </Pressable>
                        ))}
                      </View>
                    </View>
                  );
                })() : null}
              </View>
            );
          })
        )}

        {leadsMeta.last_page > 1 ? (
          <View style={styles.paginationRow}>
            <Pressable
              style={[styles.pageBtn, { backgroundColor: theme.colors.card, borderColor: theme.colors.border, opacity: leadsMeta.current_page <= 1 || loadingPage ? 0.4 : 1 }]}
              disabled={leadsMeta.current_page <= 1 || loadingPage}
              onPress={() => loadPage(leadsMeta.current_page - 1)}
            >
              <Ionicons name="chevron-back" size={16} color={theme.colors.text} />
              <Text style={[styles.pageBtnText, { color: theme.colors.text }]}>Prev</Text>
            </Pressable>
            <Text style={[styles.pageIndicator, { color: theme.colors.muted }]}>
              {loadingPage ? 'Loading...' : `${leadsMeta.current_page} of ${leadsMeta.last_page}`}
            </Text>
            <Pressable
              style={[styles.pageBtn, { backgroundColor: theme.colors.card, borderColor: theme.colors.border, opacity: !leadsMeta.has_more || loadingPage ? 0.4 : 1 }]}
              disabled={!leadsMeta.has_more || loadingPage}
              onPress={() => loadPage(leadsMeta.current_page + 1)}
            >
              <Text style={[styles.pageBtnText, { color: theme.colors.text }]}>Next</Text>
              <Ionicons name="chevron-forward" size={16} color={theme.colors.text} />
            </Pressable>
          </View>
        ) : null}
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

  // Lead card
  leadCard:    { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 16, paddingHorizontal: 14, paddingVertical: 12 },
  leadAvatar:  { width: 36, height: 36, borderRadius: 999, borderWidth: 1, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  leadBody:    { flex: 1, gap: 3, minWidth: 0 },
  leadTopRow:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  leadName:    { fontFamily: 'Inter_600SemiBold', fontSize: 13, flex: 1 },
  leadTime:    { fontFamily: 'Inter_400Regular', fontSize: 11, flexShrink: 0 },
  leadPreview: { fontFamily: 'Inter_400Regular', fontSize: 12, lineHeight: 17 },
  statusChip:     { flexDirection: 'row', alignItems: 'center', gap: 3, borderWidth: 1, borderRadius: 999, paddingHorizontal: 7, paddingVertical: 2 },
  statusChipText: { fontFamily: 'Inter_500Medium', fontSize: 10 },

  // Detail panel
  detailPanel:  { borderWidth: 1, borderTopWidth: 0, borderRadius: 20, borderTopLeftRadius: 0, borderTopRightRadius: 0, padding: 16, gap: 12, marginTop: -8 },
  contactRow:   { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  contactItem:  { flexDirection: 'row', alignItems: 'center', gap: 5, borderWidth: 1, borderRadius: 100, paddingHorizontal: 10, paddingVertical: 5 },
  contactText:  { fontFamily: 'Inter_400Regular', fontSize: 13 },
  triggerBadge: { borderWidth: 1, borderRadius: 100, paddingHorizontal: 10, paddingVertical: 4, flexShrink: 0 },
  triggerText:  { fontFamily: 'Inter_600SemiBold', fontSize: 11, textTransform: 'capitalize' },
  requestBox:   { borderWidth: 1, borderRadius: 14, paddingHorizontal: 14, paddingVertical: 12, gap: 6 },
  requestLabel: { fontFamily: 'Inter_600SemiBold', fontSize: 10, textTransform: 'uppercase', letterSpacing: 0.6 },
  requestText:  { fontFamily: 'Inter_400Regular', fontSize: 14, lineHeight: 21 },
  viewConvoBtn:     { flexDirection: 'row', alignItems: 'center', gap: 6, borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 10, alignSelf: 'flex-start' },
  viewConvoBtnText: { fontFamily: 'Inter_600SemiBold', fontSize: 12 },
  msgBubble:  { borderWidth: 1, borderRadius: 16, padding: 12, gap: 6 },
  msgRole:    { fontFamily: 'Inter_700Bold', fontSize: 11, textTransform: 'uppercase' },
  msgContent: { fontFamily: 'Inter_400Regular', fontSize: 14, lineHeight: 20 },
  statusRow:    { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  statusBtn:    { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 10 },
  statusBtnText: { fontFamily: 'Inter_600SemiBold', fontSize: 12 },

  // Pagination
  paginationRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 4 },
  pageBtn:       { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 10 },
  pageBtnText:   { fontFamily: 'Inter_600SemiBold', fontSize: 13 },
  pageIndicator: { fontFamily: 'Inter_500Medium', fontSize: 13 },

  // Empty state
  emptyCard:  { alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 24, padding: 48 },
  emptyTitle: { fontFamily: 'Inter_700Bold', fontSize: 16, letterSpacing: -0.3 },
  emptyBody:  { fontFamily: 'Inter_400Regular', fontSize: 13, lineHeight: 21, textAlign: 'center' },
});
