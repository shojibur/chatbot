# 🎯 Intent-Based Lead Capture — Implementation Plan
### Zao Chat · Feature Design Document

> **Status:** DRAFT — Ready for development  
> **Stack:** Laravel 12 · Vue 3 · Inertia.js · pgvector  
> **Estimated effort:** 3–5 developer days

---

## 1. What We Are Building

When a website visitor shows **buying intent** (e.g. asks for a price, quote, or availability), or when the chatbot **cannot answer** a question, the widget should smoothly transition into a **step-by-step lead capture flow** and save the lead to the database.

The admin dashboard gets a **Leads** section to view and manage captured contacts per client.

---

## 2. How It Fits Into the Existing Architecture

```
Visitor types message
        │
        ▼
ChatController::chat()
        │
        ├─ [NEW] IntentDetectionService::hasIntent($message) ──► true
        │         └─ Return { lead_capture: true } to widget
        │
        ├─ OR: AI answer signals it doesn't know
        │         └─ Return { lead_capture: true } to widget
        │
        └─ Normal RAG answer ──► widget shows answer normally
                                          │
                    If lead_capture = true, widget starts step-by-step flow
                                          │
                              Visitor provides name → contact → notes
                                          │
                              [NEW] POST /api/v1/leads saves to DB
                                          │
                    Widget shows: "✅ Our team will contact you soon."
```

---

## 3. Database Changes

### 3.1 New Migration: `create_leads_table`

**File:** `database/migrations/2026_04_07_000000_create_leads_table.php`

```php
Schema::create('leads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
    $table->foreignId('chat_session_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->string('contact');                   // phone OR email
    $table->text('user_request')->nullable();    // the message that triggered capture
    $table->text('notes')->nullable();           // optional step 4 "what do you need?"
    $table->json('conversation_snapshot')->nullable(); // last N messages for context
    $table->string('trigger')->default('intent'); // 'intent' | 'no_answer' | 'manual'
    $table->string('status')->default('new');    // new | contacted | closed
    $table->timestamps();
});
```

### 3.2 No changes to existing tables

The `chat_sessions` and `chat_messages` tables are unchanged. The `chat_session_id` FK links to the full conversation automatically.

---

## 4. New Files to Create

### 4.1 `app/Models/Lead.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'client_id', 'chat_session_id', 'name', 'contact',
        'user_request', 'notes', 'conversation_snapshot',
        'trigger', 'status',
    ];

    protected function casts(): array
    {
        return ['conversation_snapshot' => 'array'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }
}
```

### 4.2 `app/Services/IntentDetectionService.php`

Zero extra API cost — pure keyword matching, runs in microseconds.

```php
<?php

namespace App\Services;

class IntentDetectionService
{
    private const INTENT_PATTERNS = [
        // Price / quote
        'how much', 'what is the price', 'pricing', 'how much does',
        'what does it cost', 'cost of', 'quote', 'get a quote',
        'price list', 'rate', 'rates', 'do you charge',
        // Availability
        'are you available', 'availability', 'when can',
        'do you offer', 'do you provide',
        // Service / help request
        'i need', 'i want', 'looking for', 'interested in',
        'can someone contact', 'can someone call', 'contact me',
        'i need help', 'can you help', 'help me with',
        'i would like', 'i am looking',
        // Purchase intent
        'buy', 'purchase', 'order', 'sign up', 'get started',
        'book', 'schedule', 'appointment',
    ];

    public function hasIntent(string $message): bool
    {
        $lower = mb_strtolower($message);
        foreach (self::INTENT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
```

> **Design note:** This list can later be moved to the `clients` table as a per-client JSON config, allowing each business to customize their own trigger keywords.

### 4.3 `app/Http/Controllers/Api/LeadController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_code'   => 'required|string',
            'session_token' => 'nullable|string',
            'name'          => 'required|string|max:255',
            'contact'       => 'required|string|max:255',
            'user_request'  => 'nullable|string|max:1000',
            'notes'         => 'nullable|string|max:1000',
            'trigger'       => 'nullable|in:intent,no_answer,manual',
        ]);

        $client = Client::where('unique_code', $data['client_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $session = $data['session_token']
            ? ChatSession::where('client_id', $client->id)
                ->where('session_token', $data['session_token'])
                ->first()
            : null;

        // Snapshot last 10 messages for context
        $snapshot = $session
            ? $session->messages()
                ->orderByDesc('id')->limit(10)
                ->get(['role', 'content'])
                ->reverse()->values()->toArray()
            : [];

        Lead::create([
            'client_id'             => $client->id,
            'chat_session_id'       => $session?->id,
            'name'                  => $data['name'],
            'contact'               => $data['contact'],
            'user_request'          => $data['user_request'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'conversation_snapshot' => $snapshot,
            'trigger'               => $data['trigger'] ?? 'intent',
        ]);

        return response()->json(['success' => true]);
    }
}
```

### 4.4 `app/Http/Controllers/Admin/LeadController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = Lead::with('client')
            ->when($request->input('client_id'), fn ($q, $id) => $q->where('client_id', $id))
            ->when($request->input('status'),    fn ($q, $s)  => $q->where('status', $s))
            ->latest()
            ->paginate(25);

        return Inertia::render('leads/Index', [
            'leads'   => $leads,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Lead $lead): Response
    {
        return Inertia::render('leads/Show', [
            'lead' => $lead->load('client', 'chatSession'),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $lead->update([
            'status' => $request->validate([
                'status' => 'required|in:new,contacted,closed',
            ])['status'],
        ]);
        return back();
    }
}
```

---

## 5. Files to Modify

### 5.1 `app/Http/Controllers/Api/ChatController.php`

**Step 1:** Add to constructor:
```php
private readonly IntentDetectionService $intentService,
```

**Step 2:** After building `$answer`, add:
```php
$needsLeadCapture = $this->intentService->hasIntent($message)
    || $this->aiAnswerIsUnknown($answer);
```

**Step 3:** Add `lead_capture` to return JSON:
```php
return response()->json([
    'answer'        => $answer,
    'cached'        => false,
    'session_token' => $chatSession->session_token,
    'lead_capture'  => $needsLeadCapture,  // ← NEW
]);
```

**Step 4:** Add private helper method:
```php
private function aiAnswerIsUnknown(string $answer): bool
{
    $unknownPhrases = [
        "i don't have", "i do not have", "i'm not sure",
        "i don't know", "i cannot find", "no information",
        "not in my knowledge", "contact us directly",
        "reach out to", "please contact",
    ];
    $lower = mb_strtolower($answer);
    foreach ($unknownPhrases as $phrase) {
        if (str_contains($lower, $phrase)) return true;
    }
    return false;
}
```

> Apply the same `lead_capture` logic to the cache-hit return path too.

### 5.2 `routes/api.php`

```php
use App\Http\Controllers\Api\LeadController as ApiLeadController;

Route::post('leads', [ApiLeadController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.leads.store');
```

### 5.3 `routes/web.php`

Inside the existing auth middleware group:
```php
use App\Http\Controllers\Admin\LeadController as AdminLeadController;

Route::resource('leads', AdminLeadController::class)->only(['index', 'show']);
Route::patch('leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])
    ->name('leads.status');
```

### 5.4 `app/Models/Client.php`

```php
public function leads(): HasMany
{
    return $this->hasMany(Lead::class);
}
```

### 5.5 `resources/js/widget/ChatWidget.vue`

Add new reactive state and a lead capture flow. The widget intercepts user input when `leadStep` is active:

```js
// --- NEW STATE ---
const leadStep = ref(null)   // null | 'ask_name' | 'ask_contact' | 'ask_notes' | 'done'
const leadData = ref({ name: '', contact: '', notes: '', triggerMessage: '' })

// --- After receiving API response with lead_capture: true ---
if (data.lead_capture && !leadStep.value) {
  leadData.value.triggerMessage = userMessage
  leadStep.value = 'ask_name'
  addBotMessage("I can help with that! May I get your name first?")
  return
}

// --- In handleSend(), BEFORE sending to API ---
if (leadStep.value === 'ask_name') {
  leadData.value.name = userInput.trim()
  leadStep.value = 'ask_contact'
  addBotMessage(`Thanks ${leadData.value.name}! What's the best phone number or email to reach you?`)
  return
}

if (leadStep.value === 'ask_contact') {
  leadData.value.contact = userInput.trim()
  leadStep.value = 'ask_notes'
  addBotMessage("Got it! Can you briefly tell us what you need help with?")
  return
}

if (leadStep.value === 'ask_notes') {
  leadData.value.notes = userInput.trim()
  await saveLead()
  leadStep.value = 'done'
  addBotMessage("✅ Thank you! Our team will contact you soon.")
  setTimeout(() => { leadStep.value = null }, 3000)
  return
}

// --- saveLead() function ---
async function saveLead() {
  await fetch(`${apiBase}/api/v1/leads`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      client_code:   clientCode,
      session_token: sessionToken.value,
      name:          leadData.value.name,
      contact:       leadData.value.contact,
      user_request:  leadData.value.triggerMessage,
      notes:         leadData.value.notes,
      trigger:       'intent',
    }),
  })
}
```

**Important UX note:** Lead capture bot messages should be visually distinct (e.g. different bubble color or soft highlight) so users understand it's a special flow.

---

## 6. Admin Dashboard Pages

### 6.1 `resources/js/pages/leads/Index.vue`

Table columns:

| Name | Contact | Client | Trigger Message | Trigger Type | Status | Date |
|------|---------|--------|-----------------|--------------|--------|------|
| John Smith | john@email.com | Acme Corp | How much does it cost? | `intent` 🟡 | `new` | Apr 6 |

- **Filters:** Client dropdown · Status filter · Date range
- **Status badges:** `new` 🟡 · `contacted` 🔵 · `closed` 🟢
- Click row → `leads/Show`

### 6.2 `resources/js/pages/leads/Show.vue`

- Lead info card (name, contact, request, notes, trigger, date)
- **Conversation replay** — renders `conversation_snapshot` as chat bubbles
- **Status dropdown** — update to contacted/closed
- Link to full `ChatSession` detail

---

## 7. Implementation Phases

| Phase | Task | Est. Time |
|-------|------|-----------|
| **9A** | Migration + `Lead` model | 0.5 day |
| **9B** | `IntentDetectionService` + `ChatController` changes | 0.5 day |
| **9C** | `Api/LeadController` + API route | 0.5 day |
| **9D** | Widget lead capture flow (`ChatWidget.vue`) | 1 day |
| **9E** | Admin controllers + Index/Show pages | 1 day |
| **9F** | Testing & polish | 0.5 day |

**Total: ~4 developer days**

---

## 8. Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| Keyword-based intent detection (not AI) | Zero extra cost, instant, expandable per-client later |
| Step-by-step in widget (not a popup form) | Feels like texting — much higher completion rate |
| Notes step keeps the widget asking naturally | Doesn't interrupt if not needed |
| `lead_capture` flag in API response | Widget owns the UX; backend purely signals |
| Phone OR email (not both required) | Less friction = more leads captured |
| Conversation snapshot saved with lead | Sales team gets full context without extra DB queries |
| Per-client leads in admin panel | Multi-tenant aware from day one |

---

## 9. Example API Response (with lead trigger)

```json
{
  "answer": "I don't have specific pricing info. Our team can provide a custom quote.",
  "cached": false,
  "session_token": "abc123xyz...",
  "lead_capture": true
}
```

## 10. Example Lead Save Request (from widget)

```json
POST /api/v1/leads
{
  "client_code":   "ZAO_DEMO_001",
  "session_token": "abc123xyz...",
  "name":          "John Smith",
  "contact":       "john@gmail.com",
  "user_request":  "How much does your service cost?",
  "notes":         "Looking for monthly SEO package",
  "trigger":       "intent"
}
```

---

## 11. All Files at a Glance

### New Files
```
database/migrations/2026_04_07_000000_create_leads_table.php
app/Models/Lead.php
app/Services/IntentDetectionService.php
app/Http/Controllers/Api/LeadController.php
app/Http/Controllers/Admin/LeadController.php
resources/js/pages/leads/Index.vue
resources/js/pages/leads/Show.vue
```

### Modified Files
```
app/Http/Controllers/Api/ChatController.php  — intent check + lead_capture signal
app/Models/Client.php                        — leads() HasMany relationship
routes/api.php                               — POST /api/v1/leads
routes/web.php                               — /leads admin routes
resources/js/widget/ChatWidget.vue           — step-by-step lead capture flow
```
