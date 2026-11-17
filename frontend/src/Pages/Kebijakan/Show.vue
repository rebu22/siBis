<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ regulation: { type: Object, required: true } });
</script>

<template>
    <PublicLayout>
        <Head :title="regulation.title" />

        <article class="mx-auto max-w-[900px] px-6 py-16 sm:py-20 lg:py-24">
            <Link :href="route('regulations.index')" class="link-quiet text-body-sm"
                >← Semua kebijakan</Link
            >

            <p class="eyebrow mt-10">
                {{ regulation.category ?? 'Umum' }} · {{ regulation.published_at }} ·
                {{ regulation.size_kb }} KB
            </p>

            <h1 class="mt-4 text-h-sm font-bold text-slate-900 sm:text-h-md">
                {{ regulation.title }}
            </h1>

            <p v-if="regulation.excerpt" class="mt-4 max-w-2xl text-body text-slate-500">
                {{ regulation.excerpt }}
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a
                    :href="regulation.file_url"
                    download
                    class="btn-primary btn-sm"
                    >Unduh PDF</a
                >
                <a
                    :href="regulation.file_url"
                    target="_blank"
                    rel="noopener"
                    class="btn-ghost btn-sm"
                    >Buka di tab baru</a
                >
            </div>

            <!-- PDF pratinjau. Peramban yang tidak bisa menampilkannya tetap punya tombol unduh/buka di atas. -->
            <iframe
                v-if="regulation.file_url"
                :src="regulation.file_url"
                :title="`Pratinjau PDF: ${regulation.title}`"
                class="mt-8 h-[80vh] min-h-[480px] w-full rounded-card border border-slate-200 bg-white shadow-soft"
            />

            <div class="mt-12 rounded-card border border-slate-100 bg-white p-6 shadow-soft">
                <p class="eyebrow">Catatan</p>
                <p class="mt-3 text-body-sm text-slate-500">
                    Dokumen ini dipakai chatbot sebagai rujukan jawaban. Isinya
                    informasi umum, bukan nasihat hukum untuk kasus tertentu.
                    Konsultasikan dokumen Anda ke advokat berlisensi.
                </p>
            </div>
        </article>
    </PublicLayout>
</template>
