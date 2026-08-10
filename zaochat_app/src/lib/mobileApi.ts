import type { DocumentPickerAsset } from 'expo-document-picker';

export type MobileSession = {
  id: number;
  session_token: string;
  visitor_ip: string | null;
  visitor_identifier: string | null;
  page_url: string | null;
  user_agent: string | null;
  message_count: number;
  total_tokens: number;
  last_activity_at: string | null;
  created_at: string | null;
  first_message: string | null;
  is_human_takeover: boolean;
  taken_over_by_user_id: number | null;
  taken_over_at: string | null;
};

export type MobileSessionMessage = {
  id: number;
  role: string;
  content: string;
  token_count: number;
  from_cache: boolean;
  source: string;
  sent_by_name: string | null;
  human_takeover: boolean;
  created_at: string | null;
};

export type MobileLead = {
  id: number;
  name: string;
  contact: string;
  user_request: string | null;
  trigger: string;
  status: string;
  notes: string | null;
  chat_session_id: number | null;
  created_at: string | null;
};

export type MobileLeadDetail = MobileLead & {
  conversation_snapshot: unknown;
};

export type MobileKnowledgeSource = {
  id: number;
  title: string;
  source_type: string;
  status: string;
  source_url: string | null;
  file_name: string | null;
  token_estimate: number;
  chunk_count: number;
  processing_error: string | null;
  last_synced_at: string | null;
  created_at: string | null;
};

export type MobilePlan = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  price_monthly: number;
  monthly_token_limit: number;
  monthly_message_limit: number | null;
  max_knowledge_sources: number;
  max_file_upload_mb: number;
  features: string[];
  is_active: boolean;
};

export type MobileSettingsPayload = {
  client: {
    id: number;
    name: string;
    status: string;
    contact_email: string | null;
    website_url: string | null;
  };
  widget: {
    widget_style: string;
    primary_color: string;
    accent_color: string;
    welcome_message: string;
    toggle_text: string;
    position: string;
    theme_mode: string;
    show_branding: boolean;
    default_expanded: boolean;
  };
  subscription: {
    plan: MobilePlan | null;
    usage: {
      monthly_tokens: number;
      knowledge_sources: number;
    };
    limits: {
      max_knowledge_sources: number;
      max_file_upload_mb: number;
      monthly_token_limit: number;
      monthly_message_limit: number;
    };
  };
};

export type MobileUserContext = {
  user: {
    id: number;
    name: string;
    email: string;
    user_type: string;
  };
  client: {
    id: number;
    name: string;
    status: string;
    contact_email: string | null;
    website_url: string | null;
    plan: MobilePlan | null;
  };
};

export type MobileDashboard = {
  usage_summary: {
    current_period_tokens: number;
    current_period_cached_tokens: number;
    current_period_cost: number;
    current_period_requests: number;
  };
  counts: {
    open_sessions: number;
    leads: number;
    knowledge_sources: number;
    ready_knowledge_sources: number;
  };
};

export type MobilePaginatedKnowledge = {
  knowledge_sources: MobileKnowledgeSource[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    has_more: boolean;
  };
};

export type MobilePaginatedLeads = {
  leads: MobileLead[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    has_more: boolean;
  };
};

export type MobileAppBootstrap = {
  me: MobileUserContext;
  dashboard: MobileDashboard;
  sessions: MobileSession[];
  leads: MobileLead[];
  leadsMeta: MobilePaginatedLeads['meta'];
  knowledgeSources: MobileKnowledgeSource[];
  knowledgeMeta: MobilePaginatedKnowledge['meta'];
  settings: MobileSettingsPayload;
};

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  token?: string;
  body?: Record<string, unknown>;
};

const API_BASE_URL = (process.env.EXPO_PUBLIC_API_BASE_URL ?? 'https://app.zaochat.com/api/mobile').replace(/\/$/, '');

export async function login(email: string, password: string): Promise<{ token: string }> {
  return request('/auth/login', {
    method: 'POST',
    body: {
      email,
      password,
      device_name: 'zaochat-expo-android',
    },
  });
}

export async function logout(token: string): Promise<void> {
  await request('/auth/logout', {
    method: 'POST',
    token,
  });
}

export async function loadMobileAppData(token: string): Promise<MobileAppBootstrap> {
  const [me, dashboard, sessions, leads, knowledge, settings] = await Promise.all([
    request<MobileUserContext>('/me', { token }),
    request<MobileDashboard>('/dashboard', { token }),
    request<{ sessions: MobileSession[] }>('/sessions', { token }),
    request<MobilePaginatedLeads>('/leads', { token }),
    request<MobilePaginatedKnowledge>('/knowledge-sources', { token }),
    request<MobileSettingsPayload>('/settings', { token }),
  ]);

  const knowledgeSources = knowledge.knowledge_sources ?? [];
  const knowledgeMeta: MobilePaginatedKnowledge['meta'] = knowledge.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: knowledgeSources.length,
    has_more: false,
  };

  const leadsData = leads as MobilePaginatedLeads;
  const leadsMeta: MobilePaginatedLeads['meta'] = leadsData.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: (leadsData.leads ?? []).length,
    has_more: false,
  };

  return {
    me,
    dashboard,
    sessions: sessions.sessions,
    leads: leadsData.leads ?? [],
    leadsMeta,
    knowledgeSources,
    knowledgeMeta,
    settings,
  };
}

export async function fetchLeadsPage(token: string, page: number): Promise<MobilePaginatedLeads> {
  const raw = await request<MobilePaginatedLeads>(`/leads?page=${page}`, { token });
  return {
    leads: raw.leads ?? [],
    meta: raw.meta ?? {
      current_page: page,
      last_page: 1,
      per_page: 20,
      total: (raw.leads ?? []).length,
      has_more: false,
    },
  };
}

export async function fetchKnowledgePage(token: string, page: number): Promise<MobilePaginatedKnowledge> {
  const raw = await request<any>(`/knowledge-sources?page=${page}`, { token });
  const sources = raw.knowledge_sources ?? [];
  return {
    knowledge_sources: sources,
    meta: raw.meta ?? {
      current_page: page,
      last_page: 1,
      per_page: 25,
      total: sources.length,
      has_more: false,
    },
  };
}

export async function getSessionMessages(token: string, sessionId: number): Promise<MobileSessionMessage[]> {
  const response = await request<{ session_id: number; session: MobileSession; messages: MobileSessionMessage[] }>(
    `/sessions/${sessionId}/messages`,
    { token },
  );

  return response.messages;
}

export async function takeoverSession(token: string, sessionId: number): Promise<MobileSession> {
  const response = await request<{ session: MobileSession }>(`/sessions/${sessionId}/takeover`, {
    method: 'POST',
    token,
  });

  return response.session;
}

export async function releaseSessionTakeover(token: string, sessionId: number): Promise<MobileSession> {
  const response = await request<{ session: MobileSession }>(`/sessions/${sessionId}/release`, {
    method: 'POST',
    token,
  });

  return response.session;
}

export async function sendSessionMessage(
  token: string,
  sessionId: number,
  content: string,
): Promise<{ session: MobileSession; message: MobileSessionMessage }> {
  return request<{ session: MobileSession; message: MobileSessionMessage }>(`/sessions/${sessionId}/messages`, {
    method: 'POST',
    token,
    body: { content, send_as: 'assistant' },
  });
}

export async function updateLeadStatus(
  token: string,
  leadId: number,
  status: 'new' | 'contacted' | 'closed',
): Promise<MobileLeadDetail> {
  const response = await request<{ lead: MobileLeadDetail }>(`/leads/${leadId}/status`, {
    method: 'PATCH',
    token,
    body: { status },
  });

  return response.lead;
}

export async function getLeadDetail(token: string, leadId: number): Promise<MobileLeadDetail> {
  const response = await request<{ lead: MobileLeadDetail }>(`/leads/${leadId}`, {
    token,
  });

  return response.lead;
}

export async function updateWidgetSettings(
  token: string,
  widget: MobileSettingsPayload['widget'],
): Promise<MobileSettingsPayload> {
  return request<MobileSettingsPayload>('/settings/widget', {
    method: 'PATCH',
    token,
    body: widget,
  });
}

export async function createKnowledgeSource(
  token: string,
  payload: {
    title: string;
    source_type: 'manual' | 'url' | 'file';
    content?: string;
    source_url?: string;
    source_file?: DocumentPickerAsset | null;
  },
): Promise<MobileKnowledgeSource> {
  const hasFile = payload.source_type === 'file' && payload.source_file;

  const response = hasFile
    ? await multipartRequest<{ knowledge_source: MobileKnowledgeSource }>('/knowledge-sources', {
        token,
        fields: payload,
      })
    : await request<{ knowledge_source: MobileKnowledgeSource }>('/knowledge-sources', {
        method: 'POST',
        token,
        body: payload,
      });

  return response.knowledge_source;
}

export async function deleteKnowledgeSource(token: string, id: number): Promise<void> {
  await request(`/knowledge-sources/${id}`, {
    method: 'DELETE',
    token,
  });
}

export async function savePushToken(token: string, fcmToken: string): Promise<void> {
  await request('/push-token', {
    method: 'POST',
    token,
    body: { fcm_token: fcmToken },
  });
}

export async function deletePushToken(token: string): Promise<void> {
  await request('/push-token', {
    method: 'DELETE',
    token,
  });
}

export async function changePassword(
  token: string,
  currentPassword: string,
  newPassword: string,
  newPasswordConfirmation: string,
): Promise<void> {
  await request('/auth/password', {
    method: 'PATCH',
    token,
    body: {
      current_password: currentPassword,
      password: newPassword,
      password_confirmation: newPasswordConfirmation,
    },
  });
}

export async function retryKnowledgeSource(token: string, id: number): Promise<MobileKnowledgeSource> {
  const response = await request<{ knowledge_source: MobileKnowledgeSource }>(`/knowledge-sources/${id}/retry`, {
    method: 'POST',
    token,
  });

  return response.knowledge_source;
}

async function request<T = any>(path: string, options: RequestOptions = {}): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: options.method ?? 'GET',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.token ? { Authorization: `Bearer ${options.token}` } : {}),
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    if (payload?.errors) {
      const firstKey = Object.keys(payload.errors)[0];
      const firstMessage = payload.errors[firstKey]?.[0];

      throw new Error(firstMessage || 'Request failed.');
    }

    throw new Error(payload?.message || `Request failed with status ${response.status}.`);
  }

  return payload as T;
}

async function multipartRequest<T = any>(
  path: string,
  options: {
    token: string;
    fields: Record<string, unknown>;
  },
): Promise<T> {
  const formData = new FormData();

  Object.entries(options.fields).forEach(([key, value]) => {
    if (value == null || value === '') {
      return;
    }

    if (key === 'source_file' && isDocumentPickerAsset(value)) {
      formData.append('source_file', {
        uri: value.uri,
        name: value.name,
        type: value.mimeType ?? 'application/octet-stream',
      } as any);
      return;
    }

    formData.append(key, String(value));
  });

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${options.token}`,
    },
    body: formData,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    if (payload?.errors) {
      const firstKey = Object.keys(payload.errors)[0];
      const firstMessage = payload.errors[firstKey]?.[0];

      throw new Error(firstMessage || 'Request failed.');
    }

    throw new Error(payload?.message || `Request failed with status ${response.status}.`);
  }

  return payload as T;
}

function isDocumentPickerAsset(value: unknown): value is DocumentPickerAsset {
  return typeof value === 'object' && value !== null && 'uri' in value && 'name' in value;
}
