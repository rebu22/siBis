<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-sub-sm font-bold text-slate-900">Hapus akun</h2>

            <p class="mt-1 max-w-xl text-body-sm text-slate-500">
                Setelah dihapus, seluruh data akun Anda hilang permanen. Unduh
                dulu data yang ingin Anda simpan.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Hapus akun</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-sub-sm font-bold text-slate-900">
                    Yakin ingin menghapus akun?
                </h2>

                <p class="mt-1 max-w-xl text-body-sm text-slate-500">
                    Seluruh data akun akan hilang permanen. Masukkan kata sandi
                    untuk mengonfirmasi.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Kata sandi"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Kata sandi"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Hapus akun
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
