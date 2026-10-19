<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    articles: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
});

const q = ref(props.filters.q ?? '');
const kategori = ref(props.filters.kategori ?? '');

function submit() {
    router.get(
        route('articles.index'),
        { q: q.value || undefined, kategori: kategori.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <PublicLayout>
        <Head title="Artikel Hukum Bisnis" />

        <div class="mx-auto max-w-[1000px] px-6 py-16 sm:py-20 lg:py-24">
            <p class="eyebrow">Basis pengetahuan</p>
            <h1 class="mt-4 text-h font-bold text-slate-900">
                Artikel <span class="gradient-text">hukum bisnis</span>
            </h1>
            <p class="mt-4 max-w-2xl text-body text-slate-500">
                Materi edukasi untuk menyiapkan pertanyaan sebelum berkonsultasi
                dengan advokat.
            </p>

            <form
                class="mt-10 flex flex-wrap gap-3 rounded-card border border-slate-100 bg-white p-4 shadow-soft"
                @submit.prevent="submit"
            >
                <input
                    v-model="q"
                    type="search"
                    maxlength="100"
                    placeholder="Cari judul atau isi artikel"
                    aria-label="Kata kunci pencarian artikel"
                    class="input flex-1 sm:min-w-[280px]"
                />
                <select
                    v-model="kategori"
                    aria-label="Filter kategori"
                    class="input sm:w-56"
                >
                    <option value="">Semua kategori</option>
                    <option v-for="item in categories" :key="item" :value="item">
                        {{ item }}
                    </option>
                </select>
                <button type="submit" class="btn-primary">Cari</button>
            </form>

            <p class="mt-8 text-caption text-slate-500">
                {{ articles.total }} artikel ditemukan<span v-if="filters.q">
                    untuk “{{ filters.q }}”</span
                >.
            </p>

            <p v-if="!articles.data.length" class="mt-6 text-body-sm text-slate-500">
                Tidak ada artikel yang cocok. Coba kata kunci lain.
            </p>

            <ul class="mt-6 space-y-4">
                <li
                    v-for="article in articles.data"
                    :key="article.slug"
                    class="card p-6"
                >
                    <p class="eyebrow">
                        {{ article.category ?? 'Umum' }} · {{ article.published_at }}
                    </p>
                    <h2 class="mt-3 text-sub-sm font-semibold text-slate-900">
                        <Link
                            :href="route('articles.show', article.slug)"
                            class="rounded transition-colors duration-200 hover:text-indigo-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                            >{{ article.title }}</Link
                        >
                    </h2>
                    <p class="mt-3 max-w-2xl text-body-sm text-slate-500">
                        {{ article.excerpt }}
                    </p>
                </li>
            </ul>

            <nav
                v-if="articles.links.length > 3"
                class="mt-10 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in articles.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex min-h-[44px] items-center rounded-full border px-4 py-2 text-caption font-semibold transition-all duration-200"
                        :class="
                            link.active
                                ? 'border-transparent bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-button'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-600'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex min-h-[44px] items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-caption text-slate-400"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </PublicLayout>
</template>
