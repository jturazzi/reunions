<template>
    <div class="min-h-screen bg-slate-50">
        <Head :title="title ? `${appName} — ${title}` : appName" />

        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center gap-4">

                    <!-- Logo -->
                    <Link :href="route('dashboard')" class="flex items-center gap-2 flex-shrink-0">
                        <!-- Microsoft logo (4 squares) -->
                        <svg class="w-7 h-7" viewBox="0 0 23 23" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1" y="1" width="10" height="10" fill="#F25022"/>
                            <rect x="12" y="1" width="10" height="10" fill="#7FBA00"/>
                            <rect x="1" y="12" width="10" height="10" fill="#00A4EF"/>
                            <rect x="12" y="12" width="10" height="10" fill="#FFB900"/>
                        </svg>
                        <span class="font-semibold text-gray-900 text-sm">{{ appName }}</span>
                    </Link>

                    <!-- Admin nav -->
                    <template v-if="auth.user?.is_admin">
                        <div class="w-px h-4 bg-gray-200 flex-shrink-0"></div>
                        <div class="hidden sm:flex items-center gap-0.5">
                            <Link :href="route('admin.events.index')"
                                  :class="['rounded-md px-3 py-1.5 text-sm transition-colors', isActive('admin.events') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']">
                                Événements
                            </Link>
                            <Link :href="route('admin.event-types.index')"
                                  :class="['rounded-md px-3 py-1.5 text-sm transition-colors', isActive('admin.event-types') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']">
                                Types
                            </Link>
                            <Link :href="route('admin.distribution-lists.index')"
                                  :class="['rounded-md px-3 py-1.5 text-sm transition-colors', isActive('admin.distribution-lists') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']">
                                Destinataires
                            </Link>
                            <Link :href="route('admin.logs.index')"
                                  :class="['rounded-md px-3 py-1.5 text-sm transition-colors', isActive('admin.logs') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']">
                                Logs
                            </Link>
                            <Link :href="route('admin.system.index')"
                                  :class="['rounded-md px-3 py-1.5 text-sm transition-colors', isActive('admin.system') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100']">
                                Système
                            </Link>
                        </div>
                    </template>

                    <div class="flex-1"></div>

                    <!-- Utilisateur -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-semibold text-indigo-700 flex-shrink-0 select-none">
                                {{ initials }}
                            </div>
                            <span class="text-sm text-gray-700 hidden sm:block">{{ auth.user?.name }}</span>
                        </div>
                        <div class="w-px h-4 bg-gray-200 flex-shrink-0"></div>
                        <Link :href="route('logout')" method="post" as="button"
                              class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                            Déconnexion
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Fil d'Ariane -->
        <div v-if="$slots.breadcrumb" class="bg-white border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2.5">
                <slot name="breadcrumb" />
            </div>
        </div>

        <!-- Contenu principal -->
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">

            <!-- Messages flash -->
            <div v-if="flash?.success" class="mb-6 rounded-xl bg-green-50 border border-green-100 px-4 py-3 flex items-center gap-3">
                <svg class="h-4 w-4 text-green-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm text-green-700">{{ flash.success }}</p>
            </div>

            <div v-if="flash?.error" class="mb-6 rounded-xl bg-red-50 border border-red-100 px-4 py-3 flex items-center gap-3">
                <svg class="h-4 w-4 text-red-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm text-red-700">{{ flash.error }}</p>
            </div>

            <slot />
        </main>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: String,
});

const page = usePage();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const appName = 'Réunions';

const initials = computed(() => {
    const name = auth.value?.user?.name ?? '';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() || '?';
});

// Lien actif : vérifie si le nom de route courant commence par le préfixe
function isActive(prefix) {
    return (page.props.ziggy?.location ?? '').includes('/' + prefix.replace(/\./g, '/').replace('admin/', 'admin/'));
}
</script>
