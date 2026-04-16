# Zao Chat - Implementation TODO

> Auto-generated implementation plan. Each phase builds on the previous.
> Status: [ ] = pending, [x] = done, [~] = in progress

---

## Phase 1: Dependencies & Configuration

- [x] Install `openai-php/laravel` package
- [x] Install `pgvector/pgvector` PHP package
- [x] Install `spatie/pdf-to-text` package
- [x] Add OpenAI config to `config/services.php`
- [x] Add `OPENAI_API_KEY` to `.env` and `.env.example`
- [x] Verify pgvector extension is enabled in PostgreSQL

---

## Phase 2: Embedding Generation Service

- [x] Create `app/Services/EmbeddingService.php`
  - [x] `generate(string $text): array` — call OpenAI embeddings API (text-embedding-3-small, 1536 dims)
  - [x] `generateBatch(array $texts): array` — batch embed multiple texts
  - [x] Log token usage to `usage_logs` table (interaction_type = 'embedding')
- [x] Update `KnowledgeMemoryService::syncChunks()` to call EmbeddingService after chunking
- [x] Store embeddings in `knowledge_chunks.embedding_vector` column (pgvector)
- [x] Update KnowledgeChunk model with vector casting/accessor (HasNeighbors, Vector cast)

---

## Phase 3: Vector Similarity Search

- [x] Create `app/Services/RetrievalService.php`
  - [x] `search(Client $client, string $query, int $limit): Collection` — embed query, run pgvector cosine similarity search
  - [x] Return top-N chunks with similarity scores
  - [x] Log retrieval usage (interaction_type = 'retrieval')
  - [x] `buildContext(Collection $chunks): string` — format chunks for RAG prompt
- [ ] Add pgvector index on `knowledge_chunks.embedding_vector` (ivfflat or hnsw) — do after data exists

---

## Phase 4: Chat Endpoint with RAG

- [x] Create `app/Http/Controllers/Api/ChatController.php`
  - [x] `chat(Request $request)` — main endpoint
  - [x] Validate: `client_code`, `message` (required)
  - [x] Resolve client by `unique_code`, check status = active
  - [x] Check monthly token limit not exceeded
  - [x] Check `ConversationCacheService::find()` for cached answer
  - [x] If cache miss: retrieve chunks via RetrievalService
  - [x] Build RAG prompt: system_prompt + context chunks + user question
  - [x] Call OpenAI chat completion (client's chat_model)
  - [x] Cache the response via `ConversationCacheService::remember()`
  - [x] Log usage (interaction_type = 'chat' or 'cache_hit')
  - [x] Return JSON response `{ answer, cached }`
- [x] Create `app/Http/Requests/Api/ChatRequest.php` for validation
- [x] Add API routes: `POST /api/v1/chat`, `GET /api/v1/widget-config/{clientCode}`
- [x] Register API routes in `bootstrap/app.php`
- [x] Add rate limiting middleware (throttle:60,1 for chat, throttle:120,1 for config)
- [x] Add CORS config (`config/cors.php`) + HandleCors middleware for API

---

## Phase 5: Async Knowledge Processing (Queue Jobs)

- [x] Create `app/Jobs/ProcessKnowledgeSource.php`
  - [x] Extract content (PDF via spatie, URL via HTTP, file via filesystem)
  - [x] Chunk content via KnowledgeChunkingService
  - [x] Generate embeddings via EmbeddingService (called through syncChunks)
  - [x] Store chunks + vectors
  - [x] Update knowledge_source status (processing -> ready / failed)
  - [x] Store processing_error on failure
  - [x] 3 retries with 60s backoff
- [x] Update `KnowledgeSourceController::store()` to dispatch job for queued types
- [x] Create `app/Services/ContentExtractorService.php`
  - [x] `extractFromPdf(string $path): string`
  - [x] `extractFromUrl(string $url): string` (HTML to text, strips scripts/nav/footer)
  - [x] `extractFromFile(string $path, string $mime): string`

---

## Phase 6: Embeddable Chat Widget

- [x] Create `resources/js/widget/ChatWidget.vue` (standalone, no Inertia)
  - [x] Floating button + expandable chat panel
  - [x] Message input, message list, typing indicator
  - [x] Support 3 styles: classic, modern, glass
  - [x] Apply widget_settings (colors, position, welcome message, branding)
  - [x] API calls to `/api/v1/chat` with client_code
- [x] Create `resources/js/widget/widget-entry.ts` (self-executing embed script)
  - [x] Read `data-client-code` from script tag
  - [x] Fetch widget config from `/api/v1/widget-config/{code}`
  - [x] Mount ChatWidget into shadow DOM (style isolation)
- [x] Add Vite build config for `widget-entry.ts` (added to laravel input array)
- [x] `widgetConfig()` endpoint in ChatController
- [ ] Add embed code generator in admin dashboard (clients/Show.vue)

---

## Phase 7: Usage Enforcement & Dashboard Analytics

- [x] Token limit check before chat completion (in ChatController)
  - [x] Sum current month's `usage_logs.total_tokens` for client
  - [x] Return 429 with message if exceeded
- [ ] Add usage analytics to admin dashboard (clients/Show.vue)
  - [ ] Daily token usage chart (last 30 days)
  - [ ] Cost breakdown by interaction type
  - [ ] Cache hit rate percentage
  - [ ] Top questions (from conversation_caches)
- [ ] Add plan limit warnings (approaching 80%, 95%)

---

## Phase 8: Polish & Hardening (Future)

- [ ] Add streaming support to chat endpoint (SSE)
- [ ] Add conversation history support (multi-turn chat)
- [ ] Error handling for OpenAI API failures (retry, fallback message)
- [ ] Add health check endpoint for monitoring
- [ ] Add client API key authentication (alternative to domain whitelisting)
- [ ] Security audit: CORS, CSP, rate limiting, input sanitization
- [ ] Add delete knowledge source (with cascade chunk cleanup)
- [ ] Add re-sync knowledge source (re-chunk + re-embed)

---

## Files Created

```
app/Services/EmbeddingService.php          — Phase 2
app/Services/RetrievalService.php          — Phase 3
app/Services/ContentExtractorService.php   — Phase 5
app/Http/Controllers/Api/ChatController.php — Phase 4
app/Http/Requests/Api/ChatRequest.php      — Phase 4
app/Jobs/ProcessKnowledgeSource.php        — Phase 5
resources/js/widget/ChatWidget.vue         — Phase 6
resources/js/widget/widget-entry.ts        — Phase 6
routes/api.php                             — Phase 4
config/cors.php                            — Phase 4
```

## Files Modified

```
config/services.php                        — Phase 1 (added openai config)
.env.example                               — Phase 1 (added OPENAI_API_KEY)
bootstrap/app.php                          — Phase 4 (registered API routes + CORS)
app/Models/KnowledgeChunk.php              — Phase 2 (added HasNeighbors, Vector cast)
app/Services/KnowledgeMemoryService.php    — Phase 2 (embedding generation in syncChunks)
app/Http/Controllers/Admin/KnowledgeSourceController.php — Phase 5 (dispatch ProcessKnowledgeSource)
vite.config.ts                             — Phase 6 (added widget entry point)
database/migrations/2026_03_14_020000_*    — Fixed pgsql driver check
```
