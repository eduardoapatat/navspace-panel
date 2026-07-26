<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Boxes, Building2, CalendarDays, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import type { Building } from '@/components/buildings/building';
import BuildingRowActions from '@/components/buildings/BuildingRowActions.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import {
    edit as buildingsEdit,
    nodes as buildingsNodes,
} from '@/routes/buildings';

interface Props {
    building: Building;
}

const props = defineProps<Props>();

// Localized creation date (e.g. "8 jul 2026"), or empty when not provided.
const createdAt = computed(() => {
    if (!props.building.created_at) {
        return '';
    }

    return new Date(props.building.created_at).toLocaleDateString('es-CL', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
});
</script>

<template>
    <Card class="flex flex-col overflow-hidden pt-0">
        <!-- Photo (or a placeholder when the building has none). -->
        <Link
            :href="buildingsEdit(building.id)"
            class="relative block aspect-video overflow-hidden bg-muted"
        >
            <img
                v-if="building.image_url"
                :src="building.image_url"
                :alt="building.name"
                class="h-full w-full object-cover transition-transform hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-muted-foreground"
            >
                <Building2 class="h-10 w-10" />
            </div>
            <Badge
                v-if="building.model_url"
                class="absolute top-2 right-2 gap-1"
                variant="secondary"
            >
                <Boxes class="h-3 w-3" />
                3D
            </Badge>
        </Link>

        <CardHeader class="flex flex-row items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <Link
                    :href="buildingsEdit(building.id)"
                    :title="building.name"
                    class="block truncate font-semibold hover:underline"
                >
                    {{ building.name }}
                </Link>
                <p
                    :title="building.address"
                    class="mt-1 flex items-center gap-1 truncate text-sm text-muted-foreground"
                >
                    <MapPin class="h-3.5 w-3.5 shrink-0" />
                    {{ building.address }}
                </p>
            </div>
            <BuildingRowActions :building="building" />
        </CardHeader>

        <CardContent class="mt-auto">
            <p
                v-if="createdAt"
                class="flex items-center gap-1 text-xs text-muted-foreground"
            >
                <CalendarDays class="h-3.5 w-3.5" />
                Creado el {{ createdAt }}
            </p>
        </CardContent>

        <CardFooter class="gap-2">
            <Button variant="outline" size="sm" class="flex-1" as-child>
                <Link :href="buildingsEdit(building.id)">Gestionar</Link>
            </Button>
            <Button variant="default" size="sm" class="flex-1" as-child>
                <Link :href="buildingsNodes(building.id)">Editor 3D</Link>
            </Button>
        </CardFooter>
    </Card>
</template>
