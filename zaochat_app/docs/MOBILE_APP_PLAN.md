# ZaoChat Mobile App Plan

## Goal

Build an Android-first React Native Expo client app for ZaoChat customers, reusing the current Laravel backend where possible and documenting the backend work required for true live operator takeover.

## Current source of truth

Existing client portal features found in Laravel:

- `portal/dashboard`
- `portal/chat-history`
- `portal/leads`
- `portal/knowledge-sources`
- `portal/widget`
- `portal/playground`
- `portal/subscription`

Relevant controllers:

- `app/Http/Controllers/Portal/DashboardController.php`
- `app/Http/Controllers/Portal/ChatHistoryController.php`
- `app/Http/Controllers/Portal/LeadController.php`
- `app/Http/Controllers/Portal/KnowledgeSourceController.php`
- `app/Http/Controllers/Portal/WidgetController.php`
- `app/Http/Controllers/Portal/PlaygroundController.php`
- `app/Http/Controllers/Portal/PlaygroundChatController.php`
- `app/Http/Controllers/Portal/SubscriptionController.php`

## Important backend finding

The current backend supports:

- client login through the existing web auth system
- reading chat sessions and messages
- reading and updating leads
- reading dashboard metrics
- reading and updating widget settings
- uploading and managing knowledge sources
- playground chat testing

The current backend does not yet expose a real operator takeover flow for live visitor conversations.

What is missing for takeover:

- no session ownership or claim model
- no session mode like `ai`, `human`, `handoff_pending`, `closed`
- no endpoint for client-side message send into a live visitor session
- no endpoint for pausing AI replies on a session
- no websocket or polling contract for live message delivery
- no explicit audit trail for when a client takes over from AI

## Recommended mobile release phases

### Phase 1

Ship with backend reuse and low risk:

- login
- dashboard summary
- session list and message history read-only
- leads list and lead detail
- lead status update
- subscription view
- widget settings view and edit

### Phase 2

Add customer operations:

- knowledge source list
- upload manual, url, and file knowledge sources
- retry failed processing
- playground chat testing

### Phase 3

Add real live takeover:

- live session queue
- claim or release conversation
- human send message
- stop or resume AI replies
- live presence and unread state
- push notifications for new hot sessions

## Mobile information architecture

Recommended bottom tabs:

- `Home`
- `Sessions`
- `Leads`
- `Knowledge`
- `Settings`

Recommended stacks:

- `AuthStack`
  - Login
- `HomeStack`
  - Dashboard
- `SessionsStack`
  - Sessions list
  - Session detail
- `LeadsStack`
  - Leads list
  - Lead detail
- `KnowledgeStack`
  - Knowledge source list
  - Add source
  - Source detail
- `SettingsStack`
  - Widget settings
  - Subscription
  - Profile

## Backend reuse map

### Can be reused quickly

- dashboard aggregation from `Portal\\DashboardController`
- sessions list from `Portal\\ChatHistoryController@index`
- session messages from `Portal\\ChatHistoryController@messages`
- leads from `Portal\\LeadController`
- subscription from `Portal\\SubscriptionController`
- widget settings from `Portal\\WidgetController`
- playground chat from `Portal\\PlaygroundChatController`

### Should be converted to API endpoints

The current portal routes are Inertia-first. For the mobile app, create dedicated JSON APIs under something like:

- `POST /api/mobile/auth/login`
- `POST /api/mobile/auth/logout`
- `GET /api/mobile/me`
- `GET /api/mobile/dashboard`
- `GET /api/mobile/sessions`
- `GET /api/mobile/sessions/{id}`
- `GET /api/mobile/sessions/{id}/messages`
- `GET /api/mobile/leads`
- `GET /api/mobile/leads/{id}`
- `PATCH /api/mobile/leads/{id}/status`
- `GET /api/mobile/subscription`
- `GET /api/mobile/widget`
- `PATCH /api/mobile/widget`
- `GET /api/mobile/knowledge-sources`
- `POST /api/mobile/knowledge-sources`
- `PATCH /api/mobile/knowledge-sources/{id}`
- `DELETE /api/mobile/knowledge-sources/{id}`
- `POST /api/mobile/knowledge-sources/{id}/retry`
- `GET /api/mobile/knowledge-sources/{id}/chunks`
- `POST /api/mobile/playground/chat`

## Recommended auth approach

Preferred option:

- Laravel Sanctum token-based auth for mobile

Why:

- easier than cookie and session auth in Expo
- clean separation between web portal and mobile app
- supports future push token registration and device management

Suggested additions:

- personal access token issuance for client users
- revoke token on logout
- endpoint to register device push token

## Recommended data contracts

### Session list item

```json
{
  "id": 123,
  "session_token": "abc",
  "visitor_identifier": "John from Miami",
  "page_url": "https://example.com/services",
  "message_count": 8,
  "last_activity_at": "2026-08-09 10:10:00",
  "first_message": "Can you help with pricing?",
  "mode": "ai",
  "claimed_by_user_id": null,
  "unread_count": 2
}
```

### Lead item

```json
{
  "id": 99,
  "name": "Jane Smith",
  "contact": "+1 555 123 4567",
  "user_request": "Need a quote for installation",
  "trigger": "ai",
  "status": "new",
  "created_at": "2026-08-09 09:00:00"
}
```

## New backend work for takeover

### Data model additions

- `chat_sessions.mode`
- `chat_sessions.claimed_by_user_id`
- `chat_sessions.claimed_at`
- `chat_sessions.ai_paused_at`
- `chat_messages.sender_type`
  Values: `visitor`, `ai`, `client_user`, `system`

### API additions

- `POST /api/mobile/sessions/{id}/claim`
- `POST /api/mobile/sessions/{id}/release`
- `POST /api/mobile/sessions/{id}/pause-ai`
- `POST /api/mobile/sessions/{id}/resume-ai`
- `POST /api/mobile/sessions/{id}/messages`
- `GET /api/mobile/sessions/{id}/events`

### Real-time delivery options

Priority order:

1. WebSocket via Laravel Reverb or Pusher-compatible broadcaster
2. Short polling fallback every 5-10 seconds
3. Push notifications for new conversation or lead escalation

## Suggested first engineering sequence

1. Add Sanctum-based mobile auth endpoints.
2. Add read-only JSON endpoints for dashboard, sessions, leads, subscription, and widget settings.
3. Build Expo navigation and shared UI kit from the extracted theme.
4. Implement session history and lead management screens.
5. Add knowledge source APIs and screens.
6. Design and implement takeover data model and real-time transport.

## Immediate next repo tasks

- add Expo navigation structure
- add API client layer with auth token persistence
- add login screen
- add dashboard, sessions, and leads screens
- add Laravel `api/mobile/*` routes and controllers

## Notes

- The marketing site was checked on August 9, 2026.
- The mobile app should remain Android-first, but nothing in this plan blocks iOS later.
- The current Expo scaffold now has a theme starter, not production navigation yet.
