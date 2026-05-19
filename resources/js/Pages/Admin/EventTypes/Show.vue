<template>
    <AppLayout :title="`Listes — ${eventType.name}`">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <Link :href="route('admin.event-types.index')" class="hover:text-gray-600 transition-colors">Types d'événements</Link>
                <span>/</span>
                <span>{{ eventType.name }}</span>
            </nav>
        </template>

        <div class="space-y-6 max-w-2xl">

            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ eventType.name }}</h1>
                    <p v-if="eventType.description" class="mt-0.5 text-sm text-gray-400">{{ eventType.description }}</p>
                </div>
                <Link :href="route('admin.event-types.index')"
                      class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                    ← Retour
                </Link>
            </div>

            <!-- Flash message -->
            <div v-if="$page.props.flash?.success"
                 class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ $page.props.flash.success }}
            </div>

            <!-- Ajouter une liste -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Assigner une liste de distribution</h2>

                <div v-if="availableLists.length === 0"
                     class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-400 text-center">
                    Toutes les listes sont déjà assignées ou
                    <Link :href="route('admin.distribution-lists.create')" class="text-indigo-500 hover:underline">créez-en une nouvelle</Link>.
                </div>

                <form v-else @submit.prevent="attachList" class="flex gap-3">
                    <select v-model="attachForm.distribution_list_id"
                            required
                            class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">-- Sélectionner une liste --</option>
                        <option v-for="list in availableLists" :key="list.id" :value="list.id">
                            {{ list.name }}
                        </option>
                    </select>
                    <button type="submit"
                            :disabled="attachForm.processing || !attachForm.distribution_list_id"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors whitespace-nowrap">
                        Assigner
                    </button>
                </form>
            </div>

            <!-- Listes assignées -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Listes assignées</h2>
                    <span class="text-xs text-gray-400">
                        {{ eventType.distribution_lists.length }}
                        liste{{ eventType.distribution_lists.length !== 1 ? 's' : '' }}
                        —
                        {{ totalRecipients }} destinataire{{ totalRecipients !== 1 ? 's' : '' }}
                    </span>
                </div>

                <div v-if="eventType.distribution_lists.length === 0" class="p-10 text-center">
                    <p class="text-sm text-gray-400">Aucune liste assignée. Les événements de ce type n'enverront pas d'invitation.</p>
                </div>

                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="list in eventType.distribution_lists" :key="list.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/60 transition-colors">
                        <!-- Icône -->
                        <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                            <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-900">{{ list.name }}</p>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                                    {{ list.recipients_count }} dest.
                                </span>
                            </div>
                            <p v-if="list.description" class="text-xs text-gray-400 mt-0.5 truncate">{{ list.description }}</p>
                        </div>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <Link :href="route('admin.distribution-lists.show', list.id)"
                                  class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors">
                                Voir
                            </Link>
                            <button @click="detachList(list)"
                                    class="rounded-lg p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Retirer cette liste">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    eventType:   Object,
    allLists:    Array,
    assignedIds: Array,
});

const availableLists = computed(() =>
    props.allLists.filter(l => !props.assignedIds.includes(l.id))
);

const totalRecipients = computed(() =>
    props.eventType.distribution_lists.reduce((sum, l) => sum + l.recipients_count, 0)
);

const attachForm = useForm({
    distribution_list_id: '',
});

function attachList() {
    attachForm.post(route('admin.event-types.distribution-lists.attach', props.eventType.id), {
        onSuccess: () => { attachForm.distribution_list_id = ''; },
    });
}

function detachList(list) {
    if (confirm(`Retirer « ${list.name} » de ce type d'événement ?`)) {
        router.delete(route('admin.event-types.distribution-lists.detach', {
            eventType:        props.eventType.id,
            distributionList: list.id,
        }));
    }
}
</script>
