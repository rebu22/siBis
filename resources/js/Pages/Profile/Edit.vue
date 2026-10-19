<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-sub-sm font-bold text-slate-900">Profil</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-6">
                <div class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="rounded-card border border-slate-100 bg-white p-6 shadow-soft sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
