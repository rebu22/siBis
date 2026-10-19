<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    messages: { type: Array, default: () => [] },
    quota: { type: Object, required: true },
});

const form = useForm({ question: '' });
const bottom = ref(null);

const REASONS = [
    ['salah_pasal', 'Menyebut pasal yang salah'],
    ['tidak_relevan', 'Tidak relevan dengan pertanyaan'],
    ['tidak_ada_sumber', 'Tidak ada rujukan sumber'],
    ['usang', 'Rujukan sudah usang'],
    ['lainnya', 'Lainnya'],
];

const openReport = ref(null);
const report = useForm({ reason: 'salah_pasal', note: '' });

const scrollDown = () => nextTick(() => bottom.value?.scrollIntoView({ block: 'end' }));

watch(() => props.messages.length, scrollDown);

function send() {
    form.post(route('consultation.ask'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('question');
            scrollDown();
        },
    });
}

function rate(messageId, score) {
    router.post(route('answers.rate', messageId), { score }, { preserveScroll: true });
}

function sendReport(messageId) {
    report.post(route('answers.report', messageId), {
        preserveScroll: true,
        onSuccess: () => {
            report.reset('note');
            openReport.value = null;
        },
    });
}

scrollDown();
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Konsultasi Hukum Bisnis" />

        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Konsultasi</p>
                    <h1 class="mt-3 text-h-sm font-bold text-slate-900">
                        Tanya hukum bisnis Anda
                    </h1>
                    <p class="mt-2 text-body-sm text-slate-500">
                        Jawaban bersifat edukasi awal, bukan nasihat mengikat.
                    </p>
                </div>
                <p class="text-caption text-slate-500">
                    Kuota hari ini: <span class="font-semibold text-slate-900">{{ quota.remaining }}</span> /
                    {{ quota.limit }} · paket {{ quota.plan }}
                </p>
            </div>
        </template>

        <div class="mx-auto max-w-[880px] px-6 py-10">
            <p
                v-if="!messages.length"
                class="rounded-card border border-slate-100 bg-white p-8 text-body-sm text-slate-500 shadow-soft"
            >
                Belum ada percakapan. Tulis pertanyaan pertama Anda, misalnya
                “Apa syarat sah perjanjian kerja sama usaha?” atau “Dokumen apa
                yang disiapkan untuk mendirikan PT Perorangan?”.
            </p>

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
                            <!-- Teks AI dirender sebagai teks, bukan HTML. -->
                            <p
                                class="whitespace-pre-line text-body-sm"
                                :class="message.role === 'user' ? 'text-white' : 'text-slate-900'"
                            >
                                {{ message.body }}
                            </p>
                        </div>

                        <div
                            v-if="message.role === 'assistant' && message.sources.length"
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
                                        >buka sumber</a
                                    >
                                </li>
                            </ol>
                        </div>

                        <p
                            v-if="message.role === 'assistant'"
                            class="mt-3 text-caption text-slate-500"
                        >
                            Informasi edukasi awal, bukan nasihat mengikat.
                        </p>

                        <div
                            v-if="message.role === 'assistant' && message.degraded"
                            class="mt-3 text-caption text-slate-700"
                        >
                            Jawaban cadangan saat layanan AI tidak tersedia. Kuota
                            Anda tidak dipotong.
                        </div>

                        <div
                            v-if="message.role === 'assistant' && !message.degraded"
                            class="mt-3 flex flex-wrap items-center gap-3"
                        >
                            <span class="text-caption text-slate-500">Seberapa membantu?</span>
                            <div class="flex gap-1">
                                <button
                                    v-for="score in 5"
                                    :key="score"
                                    type="button"
                                    :aria-label="`Nilai ${score} dari 5`"
                                    class="btn-sm h-11 w-11"
                                    :class="
                                        message.my_score === score
                                            ? 'btn-primary'
                                            : 'btn-ghost'
                                    "
                                    @click="rate(message.id, score)"
                                >
                                    {{ score }}
                                </button>
                            </div>
                            <button
                                type="button"
                                class="btn-quiet min-h-[44px] text-caption"
                                @click="openReport = openReport === message.id ? null : message.id"
                            >
                                Laporkan jawaban
                            </button>
                        </div>

                        <form
                            v-if="openReport === message.id"
                            class="mt-3 space-y-3 rounded-card border border-slate-200 bg-white p-5 shadow-soft"
                            @submit.prevent="sendReport(message.id)"
                        >
                            <label class="eyebrow" :for="`reason-${message.id}`">
                                Alasan laporan
                            </label>
                            <select
                                :id="`reason-${message.id}`"
                                v-model="report.reason"
                                class="input"
                            >
                                <option v-for="[value, label] in REASONS" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <textarea
                                v-model="report.note"
                                rows="2"
                                maxlength="500"
                                placeholder="Catatan tambahan (opsional)"
                                class="input"
                            />
                            <InputError :message="report.errors.reason || report.errors.note" />
                            <button
                                type="submit"
                                class="btn-primary btn-sm"
                                :disabled="report.processing"
                            >
                                Kirim laporan
                            </button>
                        </form>
                    </div>
                </li>
            </ol>

            <div ref="bottom" class="h-6" />

            <form
                class="mt-8 rounded-card border border-slate-100 bg-white p-6 shadow-soft"
                @submit.prevent="send"
            >
                <label for="question" class="eyebrow">Pertanyaan Anda</label>
                <textarea
                    id="question"
                    v-model="form.question"
                    rows="3"
                    maxlength="1000"
                    required
                    :disabled="quota.remaining < 1"
                    class="input mt-3"
                    placeholder="Tulis pertanyaan hukum bisnis Anda"
                    @keydown.enter.exact.prevent="send"
                />
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-caption text-slate-500">
                        {{ form.question.length }}/1000 · Enter mengirim, Shift+Enter
                        baris baru
                    </p>
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="form.processing || quota.remaining < 1"
                    >
                        {{ form.processing ? 'Memproses…' : 'Kirim pertanyaan' }}
                    </button>
                </div>
                <InputError class="mt-3" :message="form.errors.question" />

                <p v-if="quota.remaining < 1" class="mt-4 text-body-sm text-slate-700">
                    Kuota harian habis. Riwayat tetap bisa dibaca. Paket Premium
                    menaikkan kuota menjadi 100 pertanyaan/hari dan belum diaktifkan.
                </p>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
