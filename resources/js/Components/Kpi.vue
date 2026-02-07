<script setup>
defineProps({
    title: String,
    value: [String, Number],
    suffix: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div
        class="rounded-xl bg-gradient-to-br from-white to-gray-50 p-4 shadow-md ring-1 ring-gray-100 hover:shadow-lg transition"
    >
        <div class="text-xs uppercase tracking-wide text-gray-500">
            {{ title }}
        </div>

        <div class="mt-1 text-2xl font-bold text-gray-900">
            {{ value }}
            <span v-if="suffix" class="ml-1 text-sm text-gray-500">
        {{ suffix }}
      </span>
        </div>
    </div>
</template>
