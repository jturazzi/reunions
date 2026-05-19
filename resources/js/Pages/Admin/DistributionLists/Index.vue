<template>
    <AppLayout title="Destinataires">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Destinataires</span>
            </nav>
        </template>

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Destinataires</h1>
                    <p class="mt-0.5 text-sm text-gray-400">Gérez les groupes de destinataires à assigner aux types d'événements.</p>
                </div>
                <Link :href="route('admin.distribution-lists.create')"
                      class="rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                    + Nouveau groupe
                </Link>
            </div>

            <div v-if="$page.props.flash?.error"
                 class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $page.props.flash.error }}
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="lists.length === 0" class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 mb-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500">Aucun groupe configuré.</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <Link :href="route('admin.distribution-lists.create')" class="text-indigo-600 hover:underline">Créez le premier groupe</Link>
                        pour commencer à envoyer des invitations.
                    </p>
                </div>
                <table v-else class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nom</th>
                            <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Destinataires</th>
                            <th class="px-5 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="list in lists" :key="list.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-medium text-gray-900">{{ list.name }}</p>
                                <p v-if="list.description" class="text-xs text-gray-400 mt-0.5">{{ list.description }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                               list.recipients_count > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ list.recipients_count }} membre{{ list.recipients_count > 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-1.5">
                                    <Link :href="route('admin.distribution-lists.show', list.id)"
                                          class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                        Gérer
                                    </Link>
                                    <button @click="deleteList(list)"
                                            :disabled="list.event_types_count > 0"
                                            :title="list.event_types_count > 0 ? 'Groupe affecté à ' + list.event_types_count + ' type(s) — désaffectez-le d\'abord' : 'Supprimer'"
                                            :class="['rounded-lg p-1.5 transition-colors',
                                                     list.event_types_count > 0
                                                       ? 'text-gray-200 cursor-not-allowed'
                                                       : 'text-gray-400 hover:text-red-600 hover:bg-red-50']">
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
    lists: Array,
});

function deleteList(list) {
    if (list.event_types_count > 0) return;
    if (confirm(`Supprimer le groupe "${list.name}" ? Les membres associés seront également supprimés.`)) {
        router.delete(route('admin.distribution-lists.destroy', list.id));
    }
}
</script>
