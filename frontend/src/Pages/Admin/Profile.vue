<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: { type: Object, default: null },
});

// Profil selalu satu baris; kalau seeder belum jalan, form dimulai kosong.
const form = useForm({
    name: props.profile?.name ?? '',
    vision: props.profile?.vision ?? '',
    mission: props.profile?.mission ?? '',
    services: props.profile?.services ?? '',
    contact_email: props.profile?.contact_email ?? '',
    contact_phone: props.profile?.contact_phone ?? '',
    address: props.profile?.address ?? '',
});

function simpan() {
    form.patch(route('admin.profile.update'), { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Profil Perusahaan" />

        <template #header>
            <p class="eyebrow">Admin</p>
            <h1 class="mt-3 text-h-sm font-bold text-slate-900">Profil perusahaan</h1>
            <p class="mt-2 max-w-2xl text-body-sm text-slate-500">
                Teks ini tampil di beranda dan footer semua halaman publik. Perubahan
                langsung terlihat tanpa mengubah kode atau deploy ulang.
            </p>
        </template>

        <div class="mx-auto max-w-[880px] px-6 py-10">
            <form
                class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8"
                @submit.prevent="simpan"
            >
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="name" value="Nama perusahaan" />
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="input mt-2"
                            maxlength="120"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="vision" value="Visi" />
                        <textarea
                            id="vision"
                            v-model="form.vision"
                            rows="3"
                            class="input mt-2"
                            maxlength="1000"
                        />
                        <InputError :message="form.errors.vision" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="mission" value="Misi" />
                        <textarea
                            id="mission"
                            v-model="form.mission"
                            rows="4"
                            class="input mt-2"
                            maxlength="2000"
                        />
                        <InputError :message="form.errors.mission" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="services" value="Layanan (satu per baris)" />
                        <textarea
                            id="services"
                            v-model="form.services"
                            rows="4"
                            class="input mt-2"
                            maxlength="2000"
                            placeholder="Konsultasi hukum bisnis berbasis AI&#10;Kebijakan &amp; regulasi"
                        />
                        <InputError :message="form.errors.services" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="contact_email" value="Email kontak" />
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            class="input mt-2"
                            maxlength="160"
                        />
                        <InputError :message="form.errors.contact_email" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="contact_phone" value="Telepon kontak" />
                        <input
                            id="contact_phone"
                            v-model="form.contact_phone"
                            type="text"
                            class="input mt-2"
                            maxlength="40"
                        />
                        <InputError :message="form.errors.contact_phone" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="address" value="Alamat" />
                        <input
                            id="address"
                            v-model="form.address"
                            type="text"
                            class="input mt-2"
                            maxlength="255"
                        />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit" class="btn-primary btn-sm" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan perubahan' }}
                    </button>
                    <p class="text-caption text-slate-500">
                        Setiap perubahan dicatat di audit log.
                    </p>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
