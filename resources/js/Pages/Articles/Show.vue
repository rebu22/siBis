<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ article: { type: Object, required: true } });

const paragraphs = computed(() =>
    props.article.body.split(/\n{2,}/).filter((text) => text.trim() !== ''),
);
</script>

<template>
    <PublicLayout>
        <Head :title="article.title" />

        <article class="mx-auto max-w-[760px] px-6 py-16 sm:py-20 lg:py-24">
            <Link :href="route('articles.index')" class="link-quiet text-body-sm"
                >← Semua artikel</Link
            >

            <p class="eyebrow mt-10">
                {{ article.category ?? 'Umum' }} · {{ article.published_at }}
                <span v-if="article.author"> · {{ article.author }}</span>
            </p>

            <h1 class="mt-4 text-h-sm font-bold text-slate-900 sm:text-h-md">
                {{ article.title }}
            </h1>

            <!-- Teks biasa di-escape Vue; tidak ada v-html (anti-XSS). -->
            <div class="mt-10 space-y-6">
                <p
                    v-for="(paragraph, index) in paragraphs"
                    :key="index"
                    class="text-body text-slate-700"
                >
                    {{ paragraph }}
                </p>
            </div>

            <div class="mt-12 rounded-card border border-slate-100 bg-white p-6 shadow-soft">
                <p class="eyebrow">Catatan</p>
                <p class="mt-3 text-body-sm text-slate-500">
                    Artikel ini materi edukasi awal, bukan nasihat hukum untuk
                    kasus tertentu. Konsultasikan dokumen Anda ke advokat
                    berlisensi.
                </p>
            </div>
        </article>
    </PublicLayout>
</template>
