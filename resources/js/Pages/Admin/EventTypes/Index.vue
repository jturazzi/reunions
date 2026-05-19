<template>
    <AppLayout title="Types d'événements">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Types d'événements</span>
            </nav>
        </template>

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900">Types d'événements</h1>
                <Link :href="route('admin.event-types.create')"
                      class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                    + Nouveau type
                </Link>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="eventTypes.length === 0" class="p-12 text-center">
                    <p class="text-sm text-gray-500">
                        Aucun type configuré.
                        <Link :href="route('admin.event-types.create')" class="text-indigo-600 hover:underline">Créez le premier !</Link>
                    </p>
                </div>
                <table v-else class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Événements</th>
                            <th class="px-5 py-2.5 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="type in eventTypes" :key="type.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-medium text-gray-900">{{ type.name }}</p>
                                <p v-if="type.description" class="text-xs text-gray-400 mt-0.5">{{ type.description }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{ type.events_count }} événement{{ type.events_count > 1 ? 's' : '' }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="route('admin.event-types.show', type.id)"
                                          class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                                          title="Gérer les listes">
                                        Gérer
                                    </Link>
                                    <button @click="deleteType(type)"
                                            class="rounded-lg p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Supprimer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    eventTypes: Array,
});

function deleteType(type) {
    if (confirm('Supprimer ce type ? Les événements associés seront également supprimés.')) {
        router.delete(route('admin.event-types.destroy', type.id));
    }
}
</script>
