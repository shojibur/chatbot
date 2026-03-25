<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { ref } from 'vue';

const showContactModal = ref(false);
</script>

<template>
    <Head title="AI Chatbot for Your Business">
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-950 text-white"
        style="font-family: Inter, sans-serif"
    >
        <!-- Navigation -->
        <nav
            class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:py-6"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 shadow-lg shadow-blue-500/20"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="white"
                        class="h-5 w-5"
                    >
                        <path
                            d="M12 2C6.48 2 2 5.58 2 10c0 2.24 1.12 4.27 2.94 5.72-.18 1.34-.78 2.6-1.72 3.56a.5.5 0 0 0 .36.85c2.17-.05 4.12-.88 5.54-2.17.6.1 1.23.15 1.88.15 5.52 0 10-3.58 10-8s-4.48-8-10-8Zm-3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"
                        />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight"
                    >HawaiiChatBot</span
                >
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="showContactModal = true"
                    class="hidden rounded-lg px-4 py-2 text-sm font-medium text-white/70 transition hover:text-white sm:inline-flex"
                >
                    Contact Us
                </button>
                <Link
                    :href="login.url()"
                    class="rounded-lg border border-white/10 bg-white/5 px-5 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10"
                >
                    Log in
                </Link>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden">
            <!-- Background decoration -->
            <div class="absolute inset-0 overflow-hidden">
                <div
                    class="absolute -top-40 right-0 h-[600px] w-[600px] rounded-full bg-blue-500/8 blur-3xl"
                ></div>
                <div
                    class="absolute top-1/2 -left-40 h-[500px] w-[500px] rounded-full bg-cyan-500/6 blur-3xl"
                ></div>
                <div
                    class="absolute right-1/3 bottom-0 h-[400px] w-[400px] rounded-full bg-indigo-500/6 blur-3xl"
                ></div>
            </div>

            <div
                class="relative mx-auto max-w-7xl px-6 pt-16 pb-20 sm:pt-24 lg:pt-32"
            >
                <div class="mx-auto max-w-3xl text-center">
                    <div
                        class="mb-8 inline-flex items-center rounded-full border border-blue-400/20 bg-blue-500/10 px-4 py-1.5 text-sm font-medium text-blue-300 backdrop-blur-sm"
                    >
                        <span
                            class="mr-2 inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-blue-400"
                        ></span>
                        AI-Powered Customer Support
                    </div>

                    <h1
                        class="text-4xl !leading-[1.1] font-extrabold tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        Turn your knowledge into an
                        <span
                            class="bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-300 bg-clip-text text-transparent"
                        >
                            instant AI assistant
                        </span>
                    </h1>

                    <p
                        class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-400 sm:text-lg"
                    >
                        HawaiiChatBot learns from your documents and website to
                        answer customer questions 24/7. Reduce support tickets,
                        delight customers, and free up your team.
                    </p>

                    <div
                        class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
                    >
                        <button
                            @click="showContactModal = true"
                            class="group inline-flex items-center rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:shadow-xl hover:shadow-blue-500/30 hover:brightness-110"
                        >
                            Get Started
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"
                                />
                            </svg>
                        </button>
                        <a
                            href="#how-it-works"
                            class="inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-8 py-3.5 text-sm font-semibold text-white/90 backdrop-blur-sm transition hover:bg-white/10"
                        >
                            See How It Works
                        </a>
                    </div>
                </div>

                <!-- Chat Preview -->
                <div class="mx-auto mt-20 max-w-2xl">
                    <div
                        class="relative rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.02] p-1 shadow-2xl backdrop-blur-sm"
                    >
                        <div class="rounded-xl bg-slate-900/80 p-5">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-full bg-red-400/80"
                                    ></div>
                                    <div
                                        class="h-3 w-3 rounded-full bg-yellow-400/80"
                                    ></div>
                                    <div
                                        class="h-3 w-3 rounded-full bg-green-400/80"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium text-slate-500"
                                    >Live Preview</span
                                >
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-end">
                                    <div
                                        class="max-w-[80%] rounded-2xl rounded-br-md bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3 text-sm shadow-md"
                                    >
                                        What are your business hours?
                                    </div>
                                </div>
                                <div class="flex justify-start">
                                    <div
                                        class="max-w-[85%] rounded-2xl rounded-bl-md border border-white/5 bg-slate-800/80 px-4 py-3 text-sm leading-relaxed text-slate-200"
                                    >
                                        We're open Monday through Friday, 9 AM
                                        to 5 PM HST. On weekends, we have
                                        limited hours from 10 AM to 2 PM. Is
                                        there anything else I can help you with?
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <div
                                        class="max-w-[80%] rounded-2xl rounded-br-md bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3 text-sm shadow-md"
                                    >
                                        Do you offer free shipping?
                                    </div>
                                </div>
                                <div class="flex justify-start">
                                    <div
                                        class="max-w-[85%] rounded-2xl rounded-bl-md border border-white/5 bg-slate-800/80 px-4 py-3 text-sm leading-relaxed text-slate-200"
                                    >
                                        Yes! We offer free shipping on all
                                        orders over $50 within Hawaii. For
                                        mainland orders, flat-rate shipping
                                        starts at $9.99.
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <div
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-400"
                                    ></div>
                                    <span class="text-xs text-slate-500"
                                        >Answers pulled from your knowledge
                                        base</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Social Proof / Stats -->
        <section class="relative border-t border-white/5 py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white sm:text-4xl">
                            24/7
                        </div>
                        <p class="mt-1 text-sm text-slate-400">
                            Always Available
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white sm:text-4xl">
                            &lt;2s
                        </div>
                        <p class="mt-1 text-sm text-slate-400">Response Time</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white sm:text-4xl">
                            90%
                        </div>
                        <p class="mt-1 text-sm text-slate-400">
                            Questions Resolved
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white sm:text-4xl">
                            5 min
                        </div>
                        <p class="mt-1 text-sm text-slate-400">Setup Time</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="relative py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <p
                        class="text-sm font-semibold tracking-widest text-blue-400 uppercase"
                    >
                        Features
                    </p>
                    <h2
                        class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        Everything your business needs
                    </h2>
                    <p class="mt-4 text-base text-slate-400">
                        A smarter way to handle customer questions without
                        hiring more support staff.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-blue-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-blue-500/10 text-blue-400 ring-1 ring-blue-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                Learns From Your Content
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                Upload your PDFs, FAQs, or website content. The
                                bot trains itself on your actual business info.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-cyan-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-400 ring-1 ring-cyan-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                Accurate Answers
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                AI responses stay grounded in your content.
                                Customers get reliable answers, not generic
                                guesses.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-purple-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-purple-500/10 text-purple-400 ring-1 ring-purple-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                Matches Your Brand
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                Customize colors, style, and personality. The
                                widget looks and feels like part of your
                                website.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-green-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-green-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-green-500/10 text-green-400 ring-1 ring-green-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                Lightning Fast
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                Instant responses powered by smart caching.
                                Repeat questions get answered in milliseconds.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-orange-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-orange-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-orange-500/10 text-orange-400 ring-1 ring-orange-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                One-Line Install
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                Copy and paste a single script tag into your
                                website. No coding skills required.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div
                        class="group relative rounded-2xl border border-white/5 bg-white/[0.03] p-8 transition-all duration-300 hover:border-rose-500/20 hover:bg-white/[0.06]"
                    >
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-br from-rose-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                        <div class="relative">
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-lg bg-rose-500/10 text-rose-400 ring-1 ring-rose-500/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold">
                                See What Customers Ask
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-slate-400"
                            >
                                Track conversations and see what your customers
                                care about most. Improve your business with real
                                insights.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section
            id="how-it-works"
            class="relative border-t border-white/5 py-24"
        >
            <div class="mx-auto max-w-7xl px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <p
                        class="text-sm font-semibold tracking-widest text-blue-400 uppercase"
                    >
                        How It Works
                    </p>
                    <h2
                        class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        Live in minutes, not months
                    </h2>
                </div>

                <div class="relative mt-16 grid gap-8 sm:grid-cols-3">
                    <!-- Connection line (desktop only) -->
                    <div
                        class="absolute top-10 right-[16.67%] left-[16.67%] hidden h-px bg-gradient-to-r from-blue-500/30 via-blue-500/50 to-blue-500/30 sm:block"
                    ></div>

                    <div class="relative text-center">
                        <div
                            class="relative z-10 mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-lg font-bold shadow-lg shadow-blue-500/20"
                        >
                            1
                        </div>
                        <h3 class="text-lg font-semibold">
                            Upload Your Content
                        </h3>
                        <p class="mt-2 text-sm text-slate-400">
                            Add your FAQs, product docs, policies, or any
                            business info you want the bot to know.
                        </p>
                    </div>
                    <div class="relative text-center">
                        <div
                            class="relative z-10 mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 text-lg font-bold shadow-lg shadow-cyan-500/20"
                        >
                            2
                        </div>
                        <h3 class="text-lg font-semibold">
                            Customize Your Bot
                        </h3>
                        <p class="mt-2 text-sm text-slate-400">
                            Choose your style and colors. Make the chatbot feel
                            like a natural part of your brand.
                        </p>
                    </div>
                    <div class="relative text-center">
                        <div
                            class="relative z-10 mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-cyan-400 text-lg font-bold shadow-lg shadow-cyan-500/20"
                        >
                            3
                        </div>
                        <h3 class="text-lg font-semibold">Embed & Go Live</h3>
                        <p class="mt-2 text-sm text-slate-400">
                            Paste one script tag on your site. Your AI assistant
                            starts helping customers immediately.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="relative py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-blue-600/20 via-blue-500/10 to-cyan-500/20 px-8 py-16 text-center sm:px-16"
                >
                    <div
                        class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-blue-500/10 via-transparent to-transparent"
                    ></div>
                    <div class="relative">
                        <h2
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Ready to automate your customer support?
                        </h2>
                        <p
                            class="mx-auto mt-4 max-w-xl text-base text-slate-300"
                        >
                            Get in touch with us to set up your custom AI
                            chatbot. We'll have you up and running in no time.
                        </p>
                        <div
                            class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
                        >
                            <button
                                @click="showContactModal = true"
                                class="group inline-flex items-center rounded-xl bg-white px-8 py-3.5 text-sm font-semibold text-slate-900 shadow-lg transition-all hover:bg-slate-100"
                            >
                                Contact Us Today
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                                    />
                                </svg>
                            </button>
                            <Link
                                :href="login.url()"
                                class="inline-flex items-center rounded-xl border border-white/20 px-8 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10"
                            >
                                Client Login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-white/5 py-12">
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="flex flex-col items-center justify-between gap-6 sm:flex-row"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-cyan-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="white"
                                class="h-4 w-4"
                            >
                                <path
                                    d="M12 2C6.48 2 2 5.58 2 10c0 2.24 1.12 4.27 2.94 5.72-.18 1.34-.78 2.6-1.72 3.56a.5.5 0 0 0 .36.85c2.17-.05 4.12-.88 5.54-2.17.6.1 1.23.15 1.88.15 5.52 0 10-3.58 10-8s-4.48-8-10-8Z"
                                />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold">HawaiiChatBot</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <a
                            href="mailto:info@thehawaiiagency.com"
                            class="text-sm text-slate-400 transition hover:text-white"
                        >
                            info@thehawaiiagency.com
                        </a>
                    </div>
                    <p class="text-sm text-slate-500">
                        &copy; {{ new Date().getFullYear() }} HawaiiChatBot. All
                        rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        <!-- Contact Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showContactModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                        @click="showContactModal = false"
                    ></div>

                    <!-- Modal -->
                    <Transition
                        enter-active-class="duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="showContactModal"
                            class="relative w-full max-w-md rounded-2xl border border-white/10 bg-slate-900 p-8 shadow-2xl"
                        >
                            <!-- Close button -->
                            <button
                                @click="showContactModal = false"
                                class="absolute top-4 right-4 rounded-lg p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>

                            <!-- Modal content -->
                            <div class="text-center">
                                <div
                                    class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 shadow-lg shadow-blue-500/20"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="white"
                                        class="h-7 w-7"
                                    >
                                        <path
                                            d="M12 2C6.48 2 2 5.58 2 10c0 2.24 1.12 4.27 2.94 5.72-.18 1.34-.78 2.6-1.72 3.56a.5.5 0 0 0 .36.85c2.17-.05 4.12-.88 5.54-2.17.6.1 1.23.15 1.88.15 5.52 0 10-3.58 10-8s-4.48-8-10-8Zm-3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"
                                        />
                                    </svg>
                                </div>

                                <h3 class="text-xl font-bold text-white">
                                    Get Started with HawaiiChatBot
                                </h3>
                                <p class="mt-2 text-sm text-slate-400">
                                    Reach out to us and we'll set up your custom
                                    AI chatbot.
                                </p>
                            </div>

                            <div class="mt-8 space-y-4">
                                <!-- Email -->
                                <a
                                    href="mailto:info@thehawaiiagency.com"
                                    class="flex items-center gap-4 rounded-xl border border-white/5 bg-white/[0.03] p-4 transition hover:border-blue-500/20 hover:bg-white/[0.06]"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-400"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            Email Us
                                        </p>
                                        <p class="text-sm text-blue-400">
                                            info@thehawaiiagency.com
                                        </p>
                                    </div>
                                </a>

                                <!-- Website -->
                                <a
                                    href="mailto:info@thehawaiiagency.com?subject=HawaiiChatBot%20Inquiry"
                                    class="flex items-center gap-4 rounded-xl border border-white/5 bg-white/[0.03] p-4 transition hover:border-blue-500/20 hover:bg-white/[0.06]"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-400"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p
                                            class="text-sm font-medium text-white"
                                        >
                                            Send an Inquiry
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            We'll get back to you within 24
                                            hours
                                        </p>
                                    </div>
                                </a>
                            </div>

                            <p class="mt-6 text-center text-xs text-slate-500">
                                Already a client?
                                <Link
                                    :href="login.url()"
                                    class="text-blue-400 hover:text-blue-300"
                                    >Log in here</Link
                                >
                            </p>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
