<template>
    <!-- Aksi hapus: netral secara default, merah hanya saat hover (prompt: hindari merah menyala). -->
    <button
        class="btn-ghost border-red-200 hover:border-red-300 hover:bg-red-50 hover:text-red-600"
    >
        <slot />
    </button>
</template>
