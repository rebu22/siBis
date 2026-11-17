<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ subject: '', body: '' });

function kirim() {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Hubungi Admin" />

        <template #header>
            <p class="eyebrow">Bantuan</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Hubungi admin</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Sampaikan pertanyaan, saran, atau kendala non-teknis. Admin membaca
                pesan di dasbor dan membalas lewat email terdaftar.
            </p>
        </template>

        <div class="mx-auto max-w-[760px] px-6 py-10">
            <form
                class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="kirim"
            >
                <div class="grid gap-5">
                    <div>
                        <InputLabel for="subject" value="Subjek" />
                        <input
                            id="subject"
                            v-model="form.subject"
                            type="text"
                            class="input mt-2"
                            maxlength="160"
                            required
                            placeholder="Contoh: Pertanyaan tentang kuota konsultasi"
                        />
                        <InputError :message="form.errors.subject" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="body" value="Isi pesan" />
                        <textarea
                            id="body"
                            v-model="form.body"
                            rows="7"
                            class="input mt-2"
                            maxlength="3000"
                            required
                            placeholder="Tulis pesan dengan jelas agar admin bisa menindaklanjuti."
                        />
                        <InputError :message="form.errors.body" class="mt-2" />
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit" class="btn-primary btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Mengirim…' : 'Kirim pesan' }}
                    </button>
                    <p class="text-caption text-slate-500">
                        Untuk kendala teknis, gunakan
                        <a :href="route('bug.create')" class="link-quiet">lapor bug</a>.
                    </p>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
