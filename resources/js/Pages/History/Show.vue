<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    session: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="session.title ?? 'Riwayat konsultasi'" />

        <template #header>
            <p class="eyebrow">Riwayat · hanya baca</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">
                {{ session.title ?? 'Percakapan tanpa judul' }}
            </h1>
            <p class="mt-2 text-caption text-slate-500">Mulai {{ session.created_at }}</p>
        </template>

        <div class="mx-auto max-w-[880px] px-6 py-10">
            <ol class="space-y-6">
                <li
                    v-for="message in messages"
                    :key="message.id"
                    :class="message.role === 'user' ? 'flex justify-end' : 'flex'"
                >
                    <div class="w-full max-w-[88%]">
                        <div
                            class="rounded-card px-5 py-4"
                            :class="
                                message.role === 'user'
                                    ? 'bg-gradient-to-r from-indigo-600 to-violet-600 shadow-button'
                                    : message.degraded
                                      ? 'border border-slate-300 bg-white shadow-soft'
                                      : 'border border-slate-200 bg-white shadow-soft'
                            "
                        >
                            <p
                                class="whitespace-pre-line text-body-sm"
                                :class="message.role === 'user' ? 'text-white' : 'text-slate-900'"
                            >
                                {{ message.body }}
                            </p>
                        </div>

                        <div
                            v-if="message.sources.length"
                            class="mt-3 rounded-card border border-slate-100 bg-slate-50 p-5"
                        >
                            <p class="eyebrow">Referensi</p>
                            <ol class="mt-3 space-y-2">
                                <li
                                    v-for="(source, index) in message.sources"
                                    :key="index"
                                    class="text-caption text-slate-500"
                                >
                                    <span class="font-medium text-slate-700"
                                        >[{{ index + 1 }}] {{ source.title }}</span
                                    >
                                    <span v-if="source.snippet"> — {{ source.snippet }}</span>
                                </li>
                            </ol>
                        </div>

                        <p
                            v-if="message.role === 'assistant' && message.degraded"
                            class="mt-3 text-caption text-slate-700"
                        >
                            Jawaban cadangan saat layanan AI tidak tersedia.
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </AuthenticatedLayout>
</template>
