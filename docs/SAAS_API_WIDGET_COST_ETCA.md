## 1. SaaS Service Agreement (Condensed)

*This document protects you from legal liability if the AI makes a mistake.*

**Key Clauses:**

* **Ownership of Data:** The Client owns all uploaded documents. You own the Chatbot software and unique algorithms.
* **AI Accuracy Disclaimer:** "The Service utilizes Artificial Intelligence. Supplier does not guarantee the 100% accuracy of responses. The Client is responsible for reviewing the Knowledge Base for accuracy."
* **Data Usage:** You agree *not* to use the client's private documents to train public models (OpenAI's API terms already guarantee this, but your clients need to see it in your contract).
* **Usage Limits:** Access is granted based on the "Message/Token Quota" purchased. Excess usage may result in service suspension or overage fees.

---

## 2. API Cost & Pricing Calculator (2026 Models)

To stay profitable, you must charge more than you spend on OpenAI. We will use **GPT-4o-mini** and  **Embeddings-3-small** .

| **Task**              | **Model**                | **Actual Cost (Est.)** | **Client Quota Example** |
| --------------------- | ------------------------ | ---------------------- | ------------------------ |
| **Document Training** | `text-embedding-3-small` | **$0.02**per 1M tokens | Unlimited (Included)     |
| **Chat Interaction**  | `gpt-4o-mini`            | **$0.15**per 1M tokens | 1,000 Messages / Mo      |
| **Total Cost to You** |                          | ~$0.0002 per message   | **Your Price:**$29/mo    |  |

**Calculation Logic for your Backend:**

* **Profit Margin:** Aim for a 10x margin. If a message costs you $0.0002, you are charging roughly $0.02 per message.
* **The "Safety" Factor:** Always calculate costs based on "Max Context" (sending the full 3 chunks) to ensure your $29/mo plan never loses money.

---

## 3. Widget Style Map (3 Presets)

Your Laravel/Inertia dashboard will allow clients to toggle between these three visual identities.

| **Style**      | **Visual Design**                                                                                                    | **Best For...**                     |
| -------------- | -------------------------------------------------------------------------------------------------------------------- | ----------------------------------- |
| **1. Classic** | Traditional rounded bubble in the bottom right. Solid colors, clear shadows, and a "Support" label.                  | E-commerce & Service businesses.    |
| **2. Modern**  | Edge-to-edge minimalist design. No bubble—just a sleek "Ask anything" input bar that expands into a clean, white UI. | Tech startups & SaaS landing pages. |
| **3. Glass**   | Uses `backdrop-filter: blur()`. Semi-transparent background with a neon or soft pastel glow. High-end aesthetic.     | Creative agencies & Luxury brands.  |

---

## 4. Cost Optimization Strategy (How we keep it cheap)

This is the most important part of your backend "secret sauce."

### A. Semantic Caching

Before calling OpenAI, your Laravel app checks the `chat_logs` for similar questions asked in the last 24 hours.

* **How:** If a new question has a **95% vector similarity** to a previous question, we return the *cached* answer.
* **Savings:** **100% savings** on those repetitive questions.

### B. Prompt Caching (Native)

By 2026, OpenAI automatically caches "Prefixes." By keeping the "System Prompt" and "Knowledge Context" identical for every message in a session, OpenAI gives you a **50%–90% discount** on input tokens.

* **The Rule:** Always put the "Knowledge Context" *before* the user's question in the API array.

### C. Vector "Summarization"

If a client uploads a 200-page PDF, we don't index every single word. We use a "Cleaning Script" to remove stop-words and duplicate sentences before embedding, reducing your database size and search latency.

### D. Model Routing

* **Simple Task:** (e.g., "Hello", "Thanks") → Use a local regex or a tiny open-source model.
* **Knowledge Task:** (e.g., "What is your price?") → Use `gpt-4o-mini`.
* **Complex Task:** (Only if the user asks for logic/math) → Route to `gpt-4o`.
