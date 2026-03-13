# 📑 Project Documentation: Custom AI Chatbot Platform

## 1. Project Overview

This platform is a  **Multi-Tenant AI-as-a-Service (SaaS)** . It allows business owners to create a custom AI chatbot that "learns" their specific business data (PDFs, website content, FAQs) and provides instant support to their website visitors.

### The "Big Idea"

Unlike generic ChatGPT, this chatbot is  **grounded** . It only answers questions based on the documents provided by the client. It is designed to be  **cost-effective** , **easy to embed** (via a single line of code), and **fully customizable** in appearance.

---

## 2. Key User Roles

* **System Admin (You):** Manage clients, monitor total API usage, and control billing.
* **Client (The Business Owner):** Uploads documents, crawls their website, chooses a widget style, and views chat logs.
* **End User (The Website Visitor):** Interacts with the chatbot on the client's website to get instant answers.

---

## 3. Technical Architecture (The "How It Works")

### A. The Tech Stack

* **Backend:** Laravel 12 (PHP 8.2+)
* **Frontend:** Inertia.js 2.0 + Vue 3 (Dashboard) & Standalone Vue 3 (Widget)
* **Styling:** Tailwind CSS 4.0
* **Database:** PostgreSQL with **pgvector** (for AI "memory" searches)
* **AI Engine:** OpenAI API (`gpt-4o-mini` for chat, `text-embedding-3-small` for knowledge)

### B. Retrieval-Augmented Generation (RAG)

To keep costs minimal, we don't send all client data to OpenAI every time. We use a process called  **RAG** :

1. **Ingestion:** When a client uploads a file, we split the text into  **Chunks** .
2. **Vectorizing:** Each chunk is converted into a list of numbers (an  **Embedding** ) and stored in our PostgreSQL database.
3. **Retrieval:** When a visitor asks a question, we search our database for the **3 most relevant chunks** only.
4. **Generation:** We send *only* those 3 chunks + the question to ChatGPT. This uses very few tokens and costs fractions of a cent.

---

## 4. Implementation Details

### Database Schema Highlights

* **`clients`** : Tracks name, `api_key`, `widget_config`, and `monthly_token_limit`.
* **`knowledge_base`** : Stores the raw text and the `vector` embedding for fast searching.
* **`conversations`** : Stores chat history for the admin to review.

### Feature: The 3-Style Widget System

The Admin Dashboard allows clients to toggle between three UI presets:

1. **Classic:** A standard circular bubble that opens a rectangular chat.
2. **Modern:** A sleek, borderless window with glassmorphism effects.
3. **Minimalist:** A simple "Ask us anything" text bar at the bottom.

### Feature: Usage Tracking

Every time the AI responds, Laravel captures the `usage` object from the OpenAI API response (prompt tokens + completion tokens). This is saved to a `usage_logs` table, allowing the system to:

* Stop the bot if the client exceeds their monthly limit.
* Provide a "Cost vs Savings" chart in the dashboard.

---

## 5. Deployment & Integration

The widget is compiled into a single, lightweight JavaScript file (`widget.js`) using  **Vite** .

**How a client installs it:**
They simply paste this into their WordPress or HTML site before the closing `</body>` tag:

**HTML**

```
<script 
    src="https://your-platform.com/widget.js" 
    data-client-id="UNIQUE_CLIENT_CODE">
</script>
```
