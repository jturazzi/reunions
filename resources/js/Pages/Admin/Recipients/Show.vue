<template>
    <AppLayout :title="`${name} — Événements`">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Destinataires</span>
                <span>/</span>
                <span>{{ name }}</span>
            </nav>
        </template>

        <div class="space-y-6 max-w-4xl">

            <!-- En-tête -->
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-gray-900 truncate">{{ name }}</h1>
                    <p class="mt-0.5 text-sm text-gray-400 truncate">{{ email }}</p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <button @click="ensureAll"
                            :disabled="ensureForm.processing || activeEntries.length === 0"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <svg v-if="ensureForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Vérifier &amp; synchroniser ({{ activeEntries.length }})
                    </button>
                    <Link :href="route('admin.distribution-lists.index')"
                          class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                        ← Retour
                    </Link>
                </div>
            </div>

            <!-- Flash success -->
            <div v-if="$page.props.flash?.success"
                 class="flex items-center gap-2 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                <svg class="h-4 w-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                {{ $page.props.flash.success }}
            </div>

            <!-- Flash error -->
            <div v-if="$page.props.flash?.error"
                 class="flex items-center gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                <svg class="h-4 w-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                {{ $page.props.flash.error }}
            </div>

            <!-- Stats rapides -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="stat in stats" :key="stat.label"
                     class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 text-center">
                    <p class="text-2xl font-bold" :class="stat.color">{{ stat.value }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Tableau des événements -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Événements</h2>
                    <span class="text-xs text-gray-400">{{ entries.length }} au total</span>
                </div>

                <div v-if="entries.length === 0" class="p-10 text-center">
                    <p class="text-sm text-gray-400">Aucun événement trouvé pour ce destinataire.</p>
                </div>

                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="entry in entries" :key="entry.id"
                        class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/60 transition-colors">

                        <!-- Statut icône -->
                        <div class="flex-shrink-0 mt-0.5">
                            <span v-if="entry.status === 'synced'"
                                  class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100" title="Synchronisé">
                                <svg class="h-3.5 w-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </span>
                            <span v-else-if="entry.status === 'failed'"
                                  class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100" title="Échec">
                                <svg class="h-3.5 w-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </span>
                            <span v-else-if="entry.status === 'deleted'"
                                  class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100" title="Supprimé">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </span>
                            <span v-else
                                  class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100" title="En attente">
                                <svg class="h-3.5 w-3.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </span>
                        </div>

                        <!-- Infos événement -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-gray-900 truncate" :class="{ 'line-through text-gray-400': entry.event_deleted }">
                                    {{ entry.event_title }}
                                </p>
                                <span v-if="entry.event_deleted"
                                      class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                                    Supprimé
                                </span>
                                <span v-if="entry.event_type"
                                      class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-600">
                                    {{ entry.event_type }}
                                </span>
                            </div>
                            <p v-if="entry.event_start" class="text-xs text-gray-400 mt-0.5">
                                {{ formatDate(entry.event_start) }}
                                <span v-if="entry.event_end"> → {{ formatDate(entry.event_end) }}</span>
                            </p>
                            <p v-if="entry.error_message" class="mt-1 text-xs text-red-500 truncate" :title="entry.error_message">
                                {{ entry.error_message }}
                            </p>
                        </div>

                        <!-- Sync info + badge -->
                        <div class="flex-shrink-0 text-right">
                            <span :class="statusClass(entry.status)"
                                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                {{ statusLabel(entry.status) }}
                            </span>
                            <p v-if="entry.synced_at" class="mt-1 text-xs text-gray-400">
                                {{ formatDate(entry.synced_at) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    email:   { type: String, required: true },
    name:    { type: String, required: true },
    entries: { type: Array,  default: () => [] },
});

// Événements actifs non supprimés (pour resync)
const activeEntries = computed(() =>
    props.entries.filter(e => !e.event_deleted && e.status !== 'deleted')
);

// Entrées avec un graph_event_id (pour la vérification calendrier)
const syncedEntries = computed(() =>
    props.entries.filter(e => e.graph_event_id)
);

const stats = computed(() => [
    {
        label: 'Total',
        value: props.entries.length,
        color: 'text-gray-800',
    },
    {
        label: 'Synchronisés',
        value: props.entries.filter(e => e.status === 'synced').length,
        color: 'text-green-600',
    },
    {
        label: 'En échec / Absents',
        value: props.entries.filter(e => e.status === 'failed' || e.status === 'missing').length,
        color: 'text-red-600',
    },
    {
        label: 'En attente',
        value: props.entries.filter(e => e.status === 'pending').length,
        color: 'text-yellow-600',
    },
]);

const resyncForm = useForm({});
const checkForm  = useForm({});
const ensureForm = useForm({});

function resyncAll() {
    resyncForm.post(route('admin.recipients.resync', props.email));
}

function checkCalendar() {
    checkForm.post(route('admin.recipients.check', props.email));
}

function ensureAll() {
    ensureForm.post(route('admin.recipients.ensure', props.email));
}

function formatDate(iso) {
    if (!iso) return '';
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    }).format(new Date(iso));
}

function statusLabel(status) {
    return { synced: 'Synchronisé', failed: 'Échec', deleted: 'Supprimé', pending: 'En attente', missing: 'Absent Outlook' }[status] ?? status;
}

function statusClass(status) {
    return {
        synced:  'bg-green-100 text-green-700',
        failed:  'bg-red-100 text-red-700',
        missing: 'bg-orange-100 text-orange-700',
        deleted: 'bg-gray-100 text-gray-500',
        pending: 'bg-yellow-100 text-yellow-700',
    }[status] ?? 'bg-gray-100 text-gray-500';
}
</script>
