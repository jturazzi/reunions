<template>
    <AppLayout title="Surveillance de la queue">
        <template #breadcrumb>
            <nav class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="font-medium text-gray-600">Administration</span>
                <span>/</span>
                <span>Queue</span>
            </nav>
        </template>

        <div class="space-y-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Surveillance de la queue</h1>
                    <p class="text-sm text-gray-400 mt-0.5">
                        Actualisation automatique toutes les {{ interval / 1000 }}s
                        <span class="ml-2 text-gray-300">— dernière mise à jour : {{ stats.refreshed_at }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Indicateur live -->
                    <span class="flex items-center gap-1.5 text-xs text-gray-500">
                        <span :class="['inline-block w-2 h-2 rounded-full', polling ? 'bg-green-400 animate-pulse' : 'bg-gray-300']"></span>
                        {{ polling ? 'Live' : 'Pausé' }}
                    </span>
                    <button @click="togglePolling"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ polling ? 'Pause' : 'Reprendre' }}
                    </button>
                    <button @click="refresh"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                        ↺ Actualiser
                    </button>
                </div>
            </div>

            <!-- Compteurs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">En attente</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 tabular-nums">{{ stats.pending.toLocaleString('fr-FR') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">En cours</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600 tabular-nums">{{ stats.processing.toLocaleString('fr-FR') }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Échoués</p>
                    <p :class="['mt-2 text-3xl font-bold tabular-nums', stats.failed > 0 ? 'text-red-600' : 'text-gray-900']">
                        {{ stats.failed.toLocaleString('fr-FR') }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Traités/s</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600 tabular-nums">{{ throughput }}</p>
                </div>
            </div>

            <!-- Barre de progression globale -->
            <div v-if="peakTotal > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700">Progression globale</p>
                    <p class="text-sm text-gray-500 tabular-nums">
                        {{ processedCount.toLocaleString('fr-FR') }} / {{ peakTotal.toLocaleString('fr-FR') }}
                        <span class="ml-1 font-semibold text-gray-700">{{ progressPercent }}%</span>
                    </p>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500"
                         :class="stats.failed > 0 ? 'bg-gradient-to-r from-emerald-500 to-emerald-400' : 'bg-emerald-500'"
                         :style="{ width: progressPercent + '%' }">
                    </div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>{{ stats.total.toLocaleString('fr-FR') }} restant{{ stats.total > 1 ? 's' : '' }}</span>
                    <span v-if="eta">ETA : {{ eta }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Répartition par queue -->
                <div v-if="stats.queues.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-700">Répartition par queue</p>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="q in stats.queues" :key="q.name" class="px-5 py-3 flex items-center justify-between gap-4">
                            <span class="text-sm font-mono text-gray-700 truncate">{{ q.name }}</span>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span v-if="q.processing > 0"
                                      class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                    {{ q.processing }} actif{{ q.processing > 1 ? 's' : '' }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900 tabular-nums">
                                    {{ q.pending.toLocaleString('fr-FR') }} en attente
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Batches actifs -->
                <div v-if="stats.batches.length > 0" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-700">Batches en cours</p>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="b in stats.batches" :key="b.id" class="px-5 py-3.5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 truncate">{{ b.name }}</span>
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-2">{{ b.created_at }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-indigo-500 transition-all duration-500"
                                     :style="{ width: b.progress + '%' }">
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-400">
                                <span>{{ b.total_jobs - b.pending_jobs }} / {{ b.total_jobs }}</span>
                                <span :class="b.failed_jobs > 0 ? 'text-red-500' : ''">
                                    {{ b.failed_jobs }} échec{{ b.failed_jobs > 1 ? 's' : '' }}
                                </span>
                                <span class="font-semibold text-gray-600">{{ b.progress }}%</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Jobs récents en queue -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700">Jobs en attente / en cours (50 derniers)</p>
                    <span class="text-xs text-gray-400">{{ stats.total }} au total</span>
                </div>
                <div v-if="stats.recent_jobs.length === 0" class="px-5 py-10 text-center text-sm text-gray-400">
                    Aucun job dans la queue.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-16">ID</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Événement</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Destinataire</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-24">Action</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Type de job</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-16 text-center">Essais</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-20">Ajouté</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-28">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="job in stats.recent_jobs" :key="job.id" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-400">{{ job.id }}</td>
                                <td class="px-4 py-2.5">
                                    <span v-if="job.event_title" class="text-sm text-gray-800 font-medium">{{ job.event_title }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <template v-if="job.recipient_email">
                                        <p class="text-sm text-gray-700">{{ job.recipient_name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ job.recipient_email }}</p>
                                    </template>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span v-if="job.action" :class="actionClass(job.action)"
                                          class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold">
                                        {{ actionLabel(job.action) }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-400 hidden lg:table-cell">{{ job.job }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 tabular-nums text-center">{{ job.attempts }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-400 font-mono">{{ job.created_at }}</td>
                                <td class="px-4 py-2.5">
                                    <span v-if="job.processing"
                                          class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                                        Traitement
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                                        En attente
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Jobs échoués récents -->
            <div v-if="stats.recent_failed.length > 0" class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-red-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-red-700">Derniers échecs</p>
                    <span class="text-xs text-red-400">{{ stats.failed }} au total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-red-50">
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-16">ID</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Événement</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Destinataire</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-24">Action</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Exception</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider w-20">Échoué à</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50">
                            <tr v-for="job in stats.recent_failed" :key="job.id" class="hover:bg-red-50/40 transition-colors">
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-400">{{ job.id }}</td>
                                <td class="px-4 py-2.5">
                                    <span v-if="job.event_title" class="text-sm text-gray-800 font-medium">{{ job.event_title }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <template v-if="job.recipient_email">
                                        <p class="text-sm text-gray-700">{{ job.recipient_name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ job.recipient_email }}</p>
                                    </template>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span v-if="job.action" :class="actionClass(job.action)"
                                          class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold">
                                        {{ actionLabel(job.action) }}
                                    </span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-red-600 font-mono truncate max-w-xs">{{ job.exception }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-400 font-mono">{{ job.failed_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    initialStats: Object,
});

const interval = 2000; // ms entre chaque refresh

const stats   = ref({ ...props.initialStats });
const polling = ref(true);
const peakTotal = ref(props.initialStats.total); // pic max observé
const history = ref([]); // [{ ts, total }] pour calculer le débit

let timer = null;

// ── Calculs dérivés ────────────────────────────────────────────────────────

const processedCount = computed(() => Math.max(0, peakTotal.value - stats.value.total));

const progressPercent = computed(() => {
    if (peakTotal.value === 0) return 0;
    return Math.min(100, Math.round((processedCount.value / peakTotal.value) * 100));
});

// Débit calculé sur les 5 dernières mesures
const throughput = computed(() => {
    if (history.value.length < 2) return '—';
    const first = history.value[0];
    const last  = history.value[history.value.length - 1];
    const deltaJobs = first.total - last.total;
    const deltaSecs = (last.ts - first.ts) / 1000;
    if (deltaSecs <= 0 || deltaJobs <= 0) return '0';
    return (deltaJobs / deltaSecs).toFixed(1);
});

// ETA estimé
const eta = computed(() => {
    if (history.value.length < 2 || stats.value.total === 0) return null;
    const first = history.value[0];
    const last  = history.value[history.value.length - 1];
    const deltaJobs = first.total - last.total;
    const deltaSecs = (last.ts - first.ts) / 1000;
    if (deltaSecs <= 0 || deltaJobs <= 0) return null;
    const rate = deltaJobs / deltaSecs;
    const remainingSecs = Math.round(stats.value.total / rate);
    if (remainingSecs > 3600) {
        const h = Math.floor(remainingSecs / 3600);
        const m = Math.floor((remainingSecs % 3600) / 60);
        return `${h}h ${m}min`;
    }
    if (remainingSecs > 60) {
        const m = Math.floor(remainingSecs / 60);
        const s = remainingSecs % 60;
        return `${m}min ${s}s`;
    }
    return `${remainingSecs}s`;
});

// ── Polling ────────────────────────────────────────────────────────────────

async function refresh() {
    try {
        const response = await fetch(route('admin.queue.stats'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        if (!response.ok) return;
        const data = await response.json();
        stats.value = data;

        // Mise à jour du pic
        if (data.total > peakTotal.value) {
            peakTotal.value = data.total;
        }

        // Historique pour débit (max 10 points)
        history.value.push({ ts: Date.now(), total: data.total });
        if (history.value.length > 10) history.value.shift();
    } catch {
        // silencieux
    }
}

function startPolling() {
    if (timer) return;
    timer = setInterval(refresh, interval);
}

function stopPolling() {
    clearInterval(timer);
    timer = null;
}

function togglePolling() {
    polling.value = !polling.value;
    polling.value ? startPolling() : stopPolling();
}

// ── Helpers affichage ──────────────────────────────────────────────────────

function actionLabel(action) {
    return { create: 'Création', update: 'Mise à jour', delete: 'Suppression' }[action] ?? action;
}

function actionClass(action) {
    return {
        create: 'bg-emerald-50 text-emerald-700',
        update: 'bg-amber-50 text-amber-700',
        delete: 'bg-red-50 text-red-600',
    }[action] ?? 'bg-gray-100 text-gray-500';
}

onMounted(() => {
    // Enregistrer le point initial dans l'historique
    history.value.push({ ts: Date.now(), total: stats.value.total });
    startPolling();
});

onUnmounted(() => stopPolling());
</script>
