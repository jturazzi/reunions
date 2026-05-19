<template>
    <AppLayout title="Gestion des événements">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Événements</span>
            </nav>
        </template>

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Événements</h1>
                    <p class="text-sm text-gray-400 mt-0.5">{{ totalEvents }} événement{{ totalEvents > 1 ? 's' : '' }} au total</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('admin.event-types.index')"
                          class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Types
                    </Link>
                    <Link :href="route('admin.events.create')"
                          class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                        + Nouvel événement
                    </Link>
                </div>
            </div>

            <div v-if="totalEvents === 0" class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
                <p class="text-sm text-gray-500">
                    Aucun événement.
                    <Link :href="route('admin.events.create')" class="text-indigo-600 hover:underline">Créez le premier !</Link>
                </p>
            </div>

            <template v-else>
                <div v-for="type in eventTypes" :key="type.id">
                    <div v-if="type.events.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <!-- En-tête catégorie -->
                        <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100 bg-gray-50/60">
                            <h2 class="text-sm font-semibold text-gray-700">{{ type.name }}</h2>
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-600">
                                {{ type.events.length }}
                            </span>
                            <span v-if="type.description" class="ml-auto text-xs text-gray-400 hidden sm:block">{{ type.description }}</span>
                        </div>

                        <!-- Tableau -->
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Événement</th>
                                    <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Lieu</th>
                                    <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Invitations</th>
                                    <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Créé par</th>
                                    <th class="px-5 py-2.5 w-20"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="event in type.events" :key="event.id" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <Link :href="route('admin.events.show', event.id)"
                                              class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                            {{ event.title }}
                                        </Link>
                                        <p v-if="event.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ event.description }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <p class="text-sm text-gray-700">{{ event.date_display }}</p>
                                        <p class="text-xs text-gray-400">{{ event.time_range }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm text-gray-500 hidden sm:table-cell">{{ event.location || '—' }}</td>
                                    <td class="px-5 py-3.5 hidden lg:table-cell">
                                        <template v-if="event.graph_summary?.synced_count > 0 || event.graph_summary?.pending_count > 0">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 border border-green-200 px-2 py-0.5 text-xs text-green-700">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                </svg>
                                                {{ event.graph_summary.synced_count }}
                                            </span>
                                            <span v-if="event.graph_summary.pending_count > 0"
                                                  class="ml-1 inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-xs text-amber-600">
                                                {{ event.graph_summary.pending_count }} en attente
                                            </span>
                                            <span v-if="event.graph_summary.failed_count > 0"
                                                  class="ml-1 inline-flex items-center rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-xs text-red-600">
                                                {{ event.graph_summary.failed_count }} échec{{ event.graph_summary.failed_count > 1 ? 's' : '' }}
                                            </span>
                                        </template>
                                        <span v-else class="text-xs text-gray-300">—</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm text-gray-400 hidden md:table-cell">{{ event.creator?.name ?? '—' }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link :href="route('admin.events.show', event.id)"
                                                  class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                                  title="Voir les détails">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </Link>
                                            <button @click="deleteEvent(event)"
                                                    class="rounded-lg p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                    title="Annuler l'événement">
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
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    eventTypes: Array,
    totalEvents: Number,
});

function deleteEvent(event) {
    if (confirm('Supprimer définitivement cet événement ?\n\nLes abonnés en seront informés.')) {
        router.delete(route('admin.events.destroy', event.id));
    }
}
</script>
