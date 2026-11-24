<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    session: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
});

function hapus() {
    if (!confirm('Hapus percakapan ini beserta seluruh pesannya? Tindakan ini tidak bisa dibatalkan.')) {
        return;
    }

    router.delete(route('history.destroy', props.session.id));
}
</script>

<template>
    <PublicLayout>
        <Head :title="session.title ?? 'Riwayat konsultasi'" />

        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="eyebrow">Riwayat · hanya baca</p>
                    <h1 class="mt-3 text-h-sm font-bold text-slate-900">
                        {{ session.title ?? 'Percakapan tanpa judul' }}
                    </h1>
                    <p class="mt-2 text-caption text-slate-500">
                        Mulai {{ session.created_at }}
                    </p>
                </div>
                <button type="button" class="btn-quiet btn-sm" @click="hapus">
                    Hapus percakapan
                </button>
            </div>
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
                                    <a
                                        v-if="source.url"
                                        :href="source.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="link-quiet ml-1 text-caption"
                                        >buka PDF</a
                                    >
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
    </PublicLayout>
</template>
