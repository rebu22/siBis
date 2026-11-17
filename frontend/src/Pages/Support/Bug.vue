<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    defaults: { type: Object, default: () => ({ name: '', email: '' }) },
});

const berkas = ref(null);

const form = useForm({
    name: props.defaults.name ?? '',
    email: props.defaults.email ?? '',
    description: '',
    screenshot: null,
});

function pilih(event) {
    form.screenshot = event.target.files?.[0] ?? null;
}

function kirim() {
    form.post(route('bug.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('description', 'screenshot');
            if (berkas.value) berkas.value.value = '';
        },
    });
}
</script>

<template>
    <PublicLayout>
        <Head title="Laporkan Bug" />

        <section class="mx-auto max-w-[760px] px-4 py-12 sm:px-6 sm:py-16">
            <p class="eyebrow">Bantuan</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900 sm:text-h-md">
                Laporkan bug
            </h1>
            <p class="mt-3 max-w-2xl text-body-sm text-slate-500">
                Ceritakan apa yang tidak berjalan: halaman apa, langkah yang kamu
                lakukan, dan apa yang muncul. Lampiran screenshot sangat membantu
                kami memperbaiki lebih cepat.
            </p>

            <form
                class="mt-8 rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="kirim"
            >
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Nama (opsional)" />
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="input mt-2"
                            maxlength="120"
                            autocomplete="name"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email untuk balasan" />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="input mt-2"
                            maxlength="160"
                            required
                            autocomplete="email"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="description" value="Deskripsi masalah" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="6"
                            class="input mt-2"
                            maxlength="3000"
                            required
                            placeholder="Contoh: saat menekan tombol Kirim di halaman konsultasi, muncul halaman galat 500."
                        />
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="screenshot" value="Screenshot (opsional, maks. 5 MB)" />
                        <input
                            id="screenshot"
                            ref="berkas"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="input mt-2 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-1.5 file:text-caption file:font-semibold file:text-indigo-700"
                            @change="pilih"
                        />
                        <InputError :message="form.errors.screenshot" class="mt-2" />
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit" class="btn-primary btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Mengirim…' : 'Kirim laporan' }}
                    </button>
                    <p class="text-caption text-slate-500">
                        Laporan dibaca admin. Kami tidak membalas otomatis.
                    </p>
                </div>
            </form>
        </section>
    </PublicLayout>
</template>
