<template>
    <AppLayout :title="event.title">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.events.index')" class="hover:text-gray-600 transition-colors">Événements</Link>
                <span>/</span>
                <span>{{ event.title }}</span>
            </nav>
        </template>

        <div class="space-y-6 max-w-3xl">

            <!-- En-tête : titre + type + actions -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div v-if="event.event_type" class="mb-1.5">
                        <Link :href="route('admin.event-types.show', event.event_type.id)"
                              class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-200 transition-colors">
                            {{ event.event_type.name }}
                        </Link>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ event.title }}</h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ event.date_display }} · {{ event.time_range }}
                        <span v-if="event.location" class="ml-2 text-gray-400">· {{ event.location }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <Link :href="route('admin.events.index')"
                          class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                        ← Retour
                    </Link>
                </div>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                 class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ $page.props.flash.success }}
            </div>

            <!-- Informations complémentaires -->
            <div v-if="event.description || event.room_name"
                 class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-2">
                <p v-if="event.description" class="text-sm text-gray-600 whitespace-pre-line">{{ event.description }}</p>
                <p v-if="event.room_name" class="text-xs text-gray-400">Salle : {{ event.room_name }}</p>
            </div>

            <!-- Calendriers Outlook (Graph API) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Calendriers Outlook</h2>
                    <div class="flex items-center gap-3 text-xs text-gray-400">
                        <span v-if="syncedCount > 0" class="text-green-600 font-medium">
                            {{ syncedCount }} synchronisé{{ syncedCount > 1 ? 's' : '' }}
                        </span>
                        <span v-if="failedCount > 0" class="text-red-500 font-medium">
                            {{ failedCount }} en échec
                        </span>
                        <span v-if="pendingCount > 0" class="text-amber-500 font-medium">
                            {{ pendingCount }} en attente
                        </span>
                        <span>{{ calendarEntries.length }} personne{{ calendarEntries.length !== 1 ? 's' : '' }}</span>
                    </div>
                </div>


                <!-- Tableau des destinataires -->
                <div v-if="calendarEntries.length === 0" class="p-10 text-center">
                    <p class="text-sm text-gray-400">Aucune entrée calendrier pour cet événement.</p>
                    <p class="text-xs text-gray-300 mt-1">Les entrées apparaissent après traitement de la file d'attente.</p>
                </div>
                <table v-else class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Destinataire</th>
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Synchronisé le</th>
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-2.5 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="entry in calendarEntries" :key="entry.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ entry.recipient_name }}</p>
                                <p class="text-xs text-gray-400">{{ entry.recipient_email }}</p>
                                <p v-if="entry.graph_event_id" class="text-[10px] text-gray-300 font-mono truncate max-w-[180px]" :title="entry.graph_event_id">
                                    {{ entry.graph_event_id.substring(0, 20) }}…
                                </p>
                            </td>
                            <td class="px-5 py-3 hidden md:table-cell">
                                <p class="text-xs text-gray-500">{{ formatDate(entry.synced_at) }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="entry.status === 'synced'"
                                      class="inline-flex items-center gap-1 rounded-full bg-green-50 border border-green-200 px-2 py-0.5 text-xs text-green-700">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    Synchronisé
                                </span>
                                <span v-else-if="entry.status === 'pending'"
                                      class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-xs text-amber-700">
                                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                    </svg>
                                    En attente
                                </span>
                                <span v-else-if="entry.status === 'deleted'"
                                      class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                                    Supprimé
                                </span>
                                <div v-else>
                                    <span class="inline-flex items-center rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-xs text-red-600">
                                        Échec
                                    </span>
                                    <p v-if="entry.error_message" class="mt-0.5 text-[10px] text-red-400 max-w-[200px] truncate" :title="entry.error_message">
                                        {{ entry.error_message }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <button @click="resync(entry)"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors">
                                    Resync
                                </button>
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
import { computed } from 'vue';

const props = defineProps({
    event:           Object,
    calendarEntries: Array,
});

const syncedCount  = computed(() => props.calendarEntries.filter(e => e.status === 'synced').length);
const failedCount  = computed(() => props.calendarEntries.filter(e => e.status === 'failed').length);
const pendingCount = computed(() => props.calendarEntries.filter(e => e.status === 'pending').length);

function resync(entry) {
    router.post(route('admin.events.resend', props.event.id), {
        name:  entry.recipient_name,
        email: entry.recipient_email,
    });
}

function formatDate(iso) {
    if (! iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>