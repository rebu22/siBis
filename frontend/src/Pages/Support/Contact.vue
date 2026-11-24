<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    defaults: { type: Object, default: () => ({ name: '', email: '' }) },
    isGuest: { type: Boolean, default: false },
});

const form = useForm({
    name: props.defaults.name ?? '',
    email: props.defaults.email ?? '',
    subject: '',
    body: '',
});

function kirim() {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <PublicLayout>
        <Head title="Hubungi Admin" />

        <template #header>
            <p class="eyebrow">Bantuan</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Hubungi admin</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Sampaikan pertanyaan, saran, atau kendala non-teknis. Admin membaca
                pesan di dasbor dan membalas lewat email yang kamu isi.
            </p>
        </template>

        <div class="mx-auto max-w-[760px] px-6 py-10">
            <form
                class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="kirim"
            >
                <div class="grid gap-5">
                    <div v-if="isGuest" class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Nama" />
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="input mt-2"
                                maxlength="120"
                                required
                                placeholder="Nama kamu"
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="input mt-2"
                                maxlength="160"
                                required
                                placeholder="nama@email.com"
                            />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                    </div>

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
    </PublicLayout>
</template>
