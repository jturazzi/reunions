<template>
    <AppLayout title="Logs d'activité">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Logs d'activité</span>
            </nav>
        </template>

        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Logs d'activité</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ total }} entrée{{ total > 1 ? 's' : '' }} — du plus récent au plus ancien</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div v-if="entries.length === 0" class="p-12 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <p class="text-gray-500 text-sm">Aucune activité enregistrée pour l'instant.</p>
                </div>

                <template v-else>
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-36">Date</th>
                                <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                                <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-5 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Détails</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(entry, i) in entries" :key="i" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap font-mono">{{ entry.date }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span :class="['inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold', levelClass(entry.level)]">
                                            {{ entry.level.toUpperCase() }}
                                        </span>
                                        <span class="text-sm text-gray-800">{{ entry.message }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <template v-if="entry.context.user">
                                        <p class="text-sm font-medium text-gray-700">{{ entry.context.user }}</p>
                                        <p v-if="entry.context.email" class="text-xs text-gray-400">{{ entry.context.email }}</p>
                                    </template>
                                    <template v-else><span class="text-gray-300">—</span></template>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-400 font-mono hidden lg:table-cell">
                                    {{ detailsJson(entry.context) }}
                                    <span v-if="entry.context.ip" class="ml-1">· {{ entry.context.ip }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="total > perPage" class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-400">Page {{ page }} / {{ Math.ceil(total / perPage) }}</span>
                        <div class="flex gap-2">
                            <Link v-if="page > 1" :href="route('admin.logs.index', { page: page - 1 })"
                                  class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                                ← Précédent
                            </Link>
                            <Link v-if="page < Math.ceil(total / perPage)" :href="route('admin.logs.index', { page: page + 1 })"
                                  class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                                Suivant →
                            </Link>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    entries: Array,
    total: Number,
    page: Number,
    perPage: Number,
});

function levelClass(level) {
    const l = level.toLowerCase();
    if (['error', 'critical', 'alert', 'emergency'].includes(l)) return 'bg-red-100 text-red-700';
    if (l === 'warning') return 'bg-amber-100 text-amber-700';
    return 'bg-green-100 text-green-700';
}

function detailsJson(context) {
    const details = { ...context };
    delete details.user;
    delete details.email;
    delete details.ip;
    return Object.keys(details).length ? JSON.stringify(details) : '';
}
</script>
