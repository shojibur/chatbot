## 1. Project Architecture & Requirements

To manage AI knowledge efficiently and keep costs low, we will implement **RAG (Retrieval-Augmented Generation)** using a vector database extension for PostgreSQL.

### Additional Dependencies

You need to install these to handle AI and PDF processing:

**Bash**

```
# AI & Vector Support
composer require openai-php/laravel
composer require pgvector/pgvector-php

# Document Processing
composer require spatie/pdf-to-text
```

---

## 2. Database Schema (Multi-Tenant AI)

We need to store client-specific knowledge so that ChatGPT only "sees" the data belonging to the active client.

### `clients` table

* `id`, `name`, `unique_code` (UUID for the widget)
* `website_url`
* `widget_settings` (JSON for colors, 1 of 3 styles, etc.)

### `knowledge_chunks` table

* `client_id` (Foreign Key)
* `content` (TEXT) - The actual text snippet.
* `embedding` (VECTOR, 1536) - The math representation for searching.

---

## 3. Backend Logic (Laravel)

### A. Knowledge Ingestion (The "Memory")

When you save text or a PDF in your Laravel/Inertia dashboard, you must convert it into a vector.

**PHP**

```
// App/Services/KnowledgeService.php
public function storeKnowledge($clientId, $text) {
    // 1. Get embedding from OpenAI (Cheap: $0.02 per 1M tokens)
    $response = OpenAI::embeddings()->create([
        'model' => 'text-embedding-3-small',
        'input' => $text,
    ]);

    // 2. Save to DB
    return KnowledgeChunk::create([
        'client_id' => $clientId,
        'content' => $text,
        'embedding' => $response->embeddings[0]->embedding,
    ]);
}
```

### B. The Chat Controller (Cost-Optimized)

This uses **Vector Search** to find the answer locally before calling ChatGPT.

**PHP**

```
// App/Http/Controllers/ChatController.php
public function answer(Request $request) {
    $client = Client::where('unique_code', $request->code)->firstOrFail();
  
    // 1. Vectorize the user's question
    $questionVector = $this->getVector($request->message);

    // 2. Find the 3 most relevant chunks for THIS client only
    $context = KnowledgeChunk::query()
        ->where('client_id', $client->id)
        ->orderByRaw('embedding <=> ?', [$questionVector]) // Nearest Neighbor Search
        ->limit(3)
        ->pluck('content')
        ->implode("\n");

    // 3. Send to GPT-4o-mini (Cheap & Fast)
    $ai = OpenAI::chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => "Answer based ONLY on: " . $context],
            ['role' => 'user', 'content' => $request->message],
        ],
    ]);

    return response()->json(['answer' => $ai->choices[0]->message->content]);
}
```

---

## 4. Admin Dashboard (Inertia + Vue)

Since you are using  **Inertia 2.0** , your admin dashboard will feel like a single-page app.

### Features to Build:

1. **Client Creator:** Generate the unique code.
2. **Knowledge Base Manager:** A page with a `textarea` and a file uploader.
3. **Widget Previewer:** A "Test Area" where you can toggle between 3 styles (Compact, Floating, or Full-screen) and see it live.

**Widget Style Toggle (Vue Component):**

**Code snippet**

```
<template>
  <div class="p-6">
    <h3 class="text-lg font-bold">Select Widget Style</h3>
    <div class="flex gap-4 mt-4">
      <button @click="form.style = 'classic'" :class="{'ring-2': form.style === 'classic'}">Classic</button>
      <button @click="form.style = 'modern'" :class="{'ring-2': form.style === 'modern'}">Modern</button>
      <button @click="form.style = 'glass'" :class="{'ring-2': form.style === 'glass'}">Glassmorphism</button>
    </div>
  
    <div class="mt-10 border p-10 bg-gray-100">
       <ChatWidget :style="form.style" :preview="true" />
    </div>
  </div>
</template>
```

---

## 5. Deployment for Clients (The Script)

To integrate into WordPress or any HTML site, you will serve a compiled JS file. In your `vite.config.ts`, you can create a second entry point just for the widget.

**The Client's Code:**

**HTML**

```
<script 
  src="https://your-laravel-site.com/js/widget.js" 
  data-id="CLIENT_UUID_123">
</script>
```

---

## Summary of Cost Saving Strategy

* **Storage:** Documents are stored as Vectors in *your* database (Zero monthly cost beyond DB hosting).
* **Search:** We use `pgvector` to find answers locally (Zero cost per search).
* **AI:** We only send the 3 most relevant paragraphs to ChatGPT ($0.15 per 1 million tokens—virtually free for small businesses).
* **Tracking:** Every AI call's `usage` object is saved to your `usage_logs` table so you can bill your clients accurately.
