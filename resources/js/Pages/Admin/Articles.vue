<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    articles: { type: Array, default: () => [] },
});

const terbuka = ref(props.articles.length === 0);
const sedangDiubah = ref(null);

const form = useForm({
    title: '',
    slug: '',
    category: '',
    excerpt: '',
    body: '',
    status: 'draft',
});

function tanggal(iso) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(iso));
}

function tulisBaru() {
    sedangDiubah.value = null;
    form.reset();
    form.clearErrors();
    terbuka.value = true;
}

function ubah(article) {
    sedangDiubah.value = article.slug;
    form.title = article.title;
    form.slug = article.slug;
    form.category = article.category ?? '';
    form.excerpt = article.excerpt ?? '';
    form.body = article.body ?? '';
    form.status = article.status;
    form.clearErrors();
    terbuka.value = true;
}

function batal() {
    sedangDiubah.value = null;
    form.reset();
    form.clearErrors();
    terbuka.value = false;
}

function simpan() {
    const opsi = {
        preserveScroll: true,
        onSuccess: () => {
            if (!sedangDiubah.value) batal();
        },
    };

    if (sedangDiubah.value) {
        form.patch(route('admin.articles.update', sedangDiubah.value), opsi);
    } else {
        form.post(route('admin.articles.store'), opsi);
    }
}

function hapus(article) {
    if (!confirm(`Hapus artikel "${article.title}"? Tindakan ini tidak bisa dibatalkan.`)) return;

    router.delete(route('admin.articles.destroy', article.slug), { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Artikel" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Artikel</h1>
            <p class="mt-2 text-body-sm text-slate-500">
                Tulisan di sini yang tampil pada halaman artikel publik.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-body-sm text-slate-500">
                    {{ articles.length }} artikel tersimpan.
                </p>
                <button v-if="!terbuka" type="button" class="btn-primary btn-sm" @click="tulisBaru">
                    Tulis artikel
                </button>
            </div>

            <form
                v-if="terbuka"
                class="mt-6 rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="simpan"
            >
                <p class="text-sub-sm font-bold text-slate-900">
                    {{ sedangDiubah ? 'Ubah artikel' : 'Artikel baru' }}
                </p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="title" value="Judul" />
                        <input id="title" v-model="form.title" type="text" class="input mt-2" maxlength="160" />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug (opsional)" />
                        <input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="input mt-2"
                            maxlength="160"
                            placeholder="diisi otomatis dari judul"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="category" value="Kategori" />
                        <input
                            id="category"
                            v-model="form.category"
                            type="text"
                            class="input mt-2"
                            maxlength="60"
                            placeholder="Badan usaha, kontrak, data pribadi…"
                        />
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="excerpt" value="Ringkasan" />
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            rows="2"
                            class="input mt-2"
                            maxlength="300"
                        />
                        <InputError :message="form.errors.excerpt" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="body" value="Isi artikel" />
                        <textarea id="body" v-model="form.body" rows="12" class="input mt-2 font-mono text-caption" />
                        <p class="mt-2 text-caption text-slate-500">
                            Teks biasa. Baris kosong memisahkan paragraf.
                        </p>
                        <InputError :message="form.errors.body" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="status" value="Status" />
                        <select id="status" v-model="form.status" class="input mt-2">
                            <option value="draft">Draf (belum tampil)</option>
                            <option value="published">Terbit</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit" class="btn-primary btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                    </button>
                    <button type="button" class="btn-ghost btn-sm" @click="batal">Batal</button>
                </div>
            </form>

            <p v-if="!articles.length" class="mt-8 text-body-sm text-slate-500">
                Belum ada artikel. Tulis satu lewat tombol di atas.
            </p>

            <div
                v-else
                class="mt-8 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
            >
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="eyebrow px-6 py-4 font-semibold">Judul</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Kategori</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Status</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Terbit</th>
                            <th class="eyebrow px-6 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="article in articles" :key="article.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <Link
                                    v-if="article.status === 'published' && article.published_at"
                                    :href="route('articles.show', article.slug)"
                                    class="text-sub-sm font-semibold text-slate-900 hover:text-indigo-600"
                                    >{{ article.title }}</Link
                                >
                                <span v-else class="text-sub-sm font-semibold text-slate-900">
                                    {{ article.title }}
                                </span>
                                <p class="mt-1 text-caption text-slate-500">/artikel/{{ article.slug }}</p>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                {{ article.category || '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="badge"
                                    :class="article.status === 'published' ? 'bg-emerald-50 text-emerald-700' : ''"
                                    >{{ article.status === 'published' ? 'terbit' : 'draf' }}</span
                                >
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-700">
                                {{ tanggal(article.published_at) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" class="btn-quiet" @click="ubah(article)">Ubah</button>
                                <button
                                    type="button"
                                    class="btn-quiet ml-3 text-red-600 hover:text-red-700"
                                    @click="hapus(article)"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
