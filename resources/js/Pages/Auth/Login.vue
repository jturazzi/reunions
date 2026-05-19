<template>
    <Head title="Connexion" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-xs text-center">

            <!-- Logo Microsoft -->
            <svg viewBox="0 0 23 23" class="w-10 h-10 mx-auto mb-6" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="10" height="10" fill="#F25022"/>
                <rect x="12" y="1" width="10" height="10" fill="#7FBA00"/>
                <rect x="1" y="12" width="10" height="10" fill="#00A4EF"/>
                <rect x="12" y="12" width="10" height="10" fill="#FFB900"/>
            </svg>

            <h1 class="text-xl font-semibold text-gray-900 mb-1">Réunions</h1>
            <p class="text-sm text-gray-500 mb-8">Connectez-vous avec votre compte d'entreprise</p>

            <!-- Erreur -->
            <div v-if="flash.error" class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-left flex items-start gap-2">
                <svg class="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm text-red-700">{{ flash.error }}</p>
            </div>

            <!-- Bouton -->
            <a :href="route('auth.microsoft')"
               class="flex w-full items-center justify-center gap-3 rounded-lg bg-white border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 21 21" fill="none">
                    <rect x="1" y="1" width="9" height="9" fill="#F25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7FBA00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00A4EF"/>
                    <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
                </svg>
                Se connecter avec Microsoft 365
            </a>

        </div>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>
