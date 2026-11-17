<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    regulations: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const terbuka = ref(props.regulations.data.length === 0);
const sedangDiubah = ref(null);
const berkas = ref(null);

const q = ref(props.filters.q ?? '');

// Pencarian lewat server: daftar bisa panjang, jadi tidak disaring di browser.
function cari() {
    router.get(
        route('admin.regulations'),
        { q: q.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const form = useForm({
    file: null,
    title: '',
    slug: '',
    category: '',
    excerpt: '',
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

function pilih(event) {
    const file = event.target.files?.[0] ?? null;
    form.file = file;
    if (file && !form.title) form.title = file.name.replace(/\.pdf$/i, '');
}

function tulisBaru() {
    sedangDiubah.value = null;
    form.reset();
    form.clearErrors();
    if (berkas.value) berkas.value.value = '';
    terbuka.value = true;
}

function ubah(item) {
    sedangDiubah.value = item.slug;
    form.title = item.title;
    form.slug = item.slug;
    form.category = item.category ?? '';
    form.excerpt = item.excerpt ?? '';
    form.status = item.status;
    form.file = null;
    form.clearErrors();
    if (berkas.value) berkas.value.value = '';
    terbuka.value = true;
}

function batal() {
    sedangDiubah.value = null;
    form.reset();
    form.clearErrors();
    if (berkas.value) berkas.value.value = '';
    terbuka.value = false;
}

function simpan() {
    const opsi = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            if (!sedangDiubah.value) batal();
        },
    };

    if (sedangDiubah.value) {
        form.patch(route('admin.regulations.update', sedangDiubah.value), opsi);
    } else {
        form.post(route('admin.regulations.store'), opsi);
    }
}

function hapus(item) {
    if (!confirm(`Hapus kebijakan "${item.title}"? PDF dan rujukannya di chatbot ikut dibuang.`)) return;

    router.delete(route('admin.regulations.destroy', item.slug), { preserveScroll: true });
}

/** Ambil ulang teks dari PDF yang sudah tersimpan, tanpa perlu unggah berkas lagi. */
function indeksUlang(item) {
    router.post(route('admin.regulations.reindex', item.slug), {}, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Kebijakan" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Kebijakan</h1>
            <p class="mt-2 text-body-sm text-slate-500">
                Unggah PDF regulasi. Sekali unggah, dokumen tampil di halaman publik
                dan otomatis jadi rujukan jawaban chatbot.
            </p>
        </template>

        <div class="mx-auto max-w-[1040px] px-6 py-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-body-sm text-slate-500">
                    {{ regulations.total }} kebijakan tersimpan<span v-if="filters.q">
                        cocok dengan “{{ filters.q }}”</span
                    >.
                </p>
                <button v-if="!terbuka" type="button" class="btn-primary btn-sm" @click="tulisBaru">
                    Unggah kebijakan
                </button>
            </div>

            <form class="mt-4 flex flex-wrap gap-3" @submit.prevent="cari">
                <input
                    v-model="q"
                    type="search"
                    maxlength="100"
                    placeholder="Cari judul atau ringkasan"
                    aria-label="Cari kebijakan"
                    class="input flex-1 sm:min-w-[280px]"
                />
                <button type="submit" class="btn-ghost">Cari</button>
            </form>

            <form
                v-if="terbuka"
                class="mt-6 rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="simpan"
            >
                <p class="text-sub-sm font-bold text-slate-900">
                    {{ sedangDiubah ? 'Ubah kebijakan' : 'Kebijakan baru' }}
                </p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel
                            for="file"
                            :value="
                                sedangDiubah
                                    ? 'Ganti PDF (kosongkan kalau tidak diganti)'
                                    : 'Berkas PDF (maks. 20 MB)'
                            "
                        />
                        <input
                            id="file"
                            ref="berkas"
                            type="file"
                            accept="application/pdf"
                            class="input mt-2 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-1.5 file:text-caption file:font-semibold file:text-indigo-700"
                            @change="pilih"
                        />
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="title" value="Judul kebijakan" />
                        <input id="title" v-model="form.title" type="text" class="input mt-2" maxlength="160" />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="category" value="Kategori" />
                        <input
                            id="category"
                            v-model="form.category"
                            type="text"
                            class="input mt-2"
                            maxlength="60"
                            placeholder="Perizinan, ketenagakerjaan, data pribadi…"
                        />
                        <InputError :message="form.errors.category" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="excerpt" value="Ringkasan" />
                        <textarea
                            id="excerpt"
                            v-model="form.excerpt"
                            rows="3"
                            class="input mt-2"
                            maxlength="300"
                            placeholder="Satu-dua kalimat: dokumen ini mengatur apa."
                        />
                        <InputError :message="form.errors.excerpt" class="mt-2" />
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
                        {{ form.processing ? 'Mengindeks…' : 'Simpan' }}
                    </button>
                    <button type="button" class="btn-ghost btn-sm" @click="batal">Batal</button>
                    <p v-if="form.progress" class="text-caption text-slate-500">
                        {{ form.progress.percentage }}% terkirim — ekstrak teks bisa sampai
                        satu menit untuk PDF tebal.
                    </p>
                </div>
            </form>

            <p v-if="!regulations.data.length" class="mt-8 text-body-sm text-slate-500">
                {{
                    filters.q
                        ? 'Tidak ada kebijakan yang cocok. Coba kata kunci lain.'
                        : 'Belum ada kebijakan. Unggah satu lewat tombol di atas.'
                }}
            </p>

            <div
                v-else
                class="mt-8 overflow-x-auto rounded-card border border-slate-100 bg-white shadow-soft"
            >
                <table class="w-full min-w-[760px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="eyebrow px-6 py-4 font-semibold">Judul</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Kategori</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Status</th>
                            <th class="eyebrow px-6 py-4 font-semibold">Chatbot</th>
                            <th class="eyebrow px-6 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in regulations.data"
                            :key="item.slug"
                            class="border-b border-slate-100 last:border-0"
                        >
                            <td class="px-6 py-4">
                                <p class="text-body-sm font-medium text-slate-800">
                                    {{ item.title }}
                                </p>
                                <p class="mt-1 text-caption text-slate-500">
                                    {{ item.size_kb }} KB · terbit {{ tanggal(item.published_at) }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-500">
                                {{ item.category ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge">{{
                                    item.status === 'published' ? 'Terbit' : 'Draf'
                                }}</span>
                            </td>
                            <td class="px-6 py-4 text-body-sm">
                                <span v-if="item.indexed" class="text-emerald-700">Terindeks</span>
                                <span v-else class="text-red-600">Belum terindeks</span>
                            </td>
                            <td class="space-x-3 whitespace-nowrap px-6 py-4 text-right">
                                <button
                                    v-if="!item.indexed"
                                    type="button"
                                    class="btn-quiet btn-sm"
                                    @click="indeksUlang(item)"
                                >
                                    Indeks ulang
                                </button>
                                <button type="button" class="btn-quiet btn-sm" @click="ubah(item)">
                                    Ubah
                                </button>
                                <button type="button" class="btn-quiet btn-sm" @click="hapus(item)">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="regulations.links.length > 3"
                class="mt-8 flex flex-wrap gap-2"
                aria-label="Navigasi halaman"
            >
                <template v-for="link in regulations.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-scroll
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
    </AuthenticatedLayout>
</template>
