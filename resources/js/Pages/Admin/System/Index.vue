<template>
    <AppLayout title="Informations système">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Informations système</span>
            </nav>
        </template>

        <div class="max-w-lg space-y-6">
            <h1 class="text-xl font-semibold text-gray-900">Informations système</h1>

            <!-- Infos générales -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <dl class="divide-y divide-gray-100">
                    <div v-for="(value, label) in info" :key="label" class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-gray-500">{{ label }}</dt>
                        <dd class="text-sm font-medium text-gray-900 font-mono">
                            <span v-if="label === 'Environnement'"
                                  :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                           value === 'production' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
                                {{ value }}
                            </span>
                            <template v-else>{{ value }}</template>
                        </dd>
                    </div>
                </dl>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    info: Object,
});
</script>

