<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="mt-1 text-body-sm text-red-600">
            {{ message }}
        </p>
    </div>
</template>
