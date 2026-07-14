<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Building } from '@/components/buildings/building';
import BuildingCardGrid from '@/components/buildings/BuildingCardGrid.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    index as buildingsIndex,
    create as buildingsCreate,
} from '@/routes/buildings';
import type { PaginatedResponse } from '@/types';

interface Props {
    buildings: PaginatedResponse<Building>;
    filters: {
        search?: string;
    };
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edificios',
                href: buildingsIndex(),
            },
        ],
    },
});

defineProps<Props>();
</script>

<template>
    <Head title="Edificios" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex flex-col gap-1">
            <div class="flex justify-between">
                <h1
                    class="text-2xl font-semibold text-gray-900 dark:text-white"
                >
                    Edificios
                </h1>
                <Button as-child>
                    <Link :href="buildingsCreate()"> Subir Edificio </Link>
                </Button>
            </div>
            <p class="text-muted-foreground">
                Administra los espacios navegables del sistema.
            </p>
        </div>

        <BuildingCardGrid :buildings="buildings" :filters="filters" />
    </div>
</template>
