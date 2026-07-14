<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Ellipsis } from '@lucide/vue';
import type { Building } from '@/components/buildings/building';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    destroy as buildingsDestroy,
    edit as buildingsEdit,
    nodes as buildingsNodes,
} from '@/routes/buildings';

interface Props {
    building: Building;
}

const props = defineProps<Props>();

// Remove the building after confirmation; cascades to its groups and destinations.
function destroy(): void {
    if (
        !confirm(
            `¿Eliminar el edificio "${props.building.name}"? Se borrarán también sus pisos, tipos de espacio y destinos.`,
        )
    ) {
        return;
    }

    router.delete(buildingsDestroy(props.building.id).url);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                class="flex h-8 w-8 p-0 data-[state=open]:bg-muted"
            >
                <Ellipsis class="h-4 w-4" />
                <span class="sr-only">Abrir menú</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuLabel>Acciones</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link :href="buildingsEdit(building.id)">Gestionar</Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="buildingsNodes(building.id)">Editor de nodos</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                class="text-destructive focus:text-destructive"
                @select="destroy"
            >
                Eliminar
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
