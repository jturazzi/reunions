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

            <!-- Statistiques invitations -->
            <div>
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Invitations</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-col gap-1">
                        <span class="text-2xl font-bold text-gray-900">{{ invitationStats.total.toLocaleString('fr-FR') }}</span>
                        <span class="text-xs text-gray-500">Total envoyées<br><span class="text-gray-400">(sur {{ invitationStats.events }} événement{{ invitationStats.events !== 1 ? 's' : '' }})</span></span>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-col gap-1">
                        <span class="text-2xl font-bold text-green-600">{{ invitationStats.synced.toLocaleString('fr-FR') }}</span>
                        <span class="text-xs text-gray-500">Synchronisées</span>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-col gap-1">
                        <span class="text-2xl font-bold text-amber-500">{{ invitationStats.pending.toLocaleString('fr-FR') }}</span>
                        <span class="text-xs text-gray-500">En attente</span>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4 flex flex-col gap-1">
                        <span class="text-2xl font-bold text-red-500">{{ invitationStats.failed.toLocaleString('fr-FR') }}</span>
                        <span class="text-xs text-gray-500">Échouées</span>
                    </div>
                </div>
            </div>

            <!-- Infos générales -->
            <div>
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Environnement</h2>
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

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    info: Object,
    invitationStats: Object,
});
</script>

