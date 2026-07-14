<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Layers, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import NodeGroupController from '@/actions/App/Http/Controllers/NodeGroupController';
import type { Building, NodeGroup } from '@/components/buildings/building';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    building: Building;
}

const props = defineProps<Props>();

const dialogOpen = ref(false);
// The group currently being edited; null means we are creating a new one.
const editingGroup = ref<NodeGroup | null>(null);

function openCreate(): void {
    editingGroup.value = null;
    dialogOpen.value = true;
}

function openEdit(group: NodeGroup): void {
    editingGroup.value = group;
    dialogOpen.value = true;
}

function destroy(group: NodeGroup): void {
    if (
        !confirm(
            `¿Eliminar el grupo "${group.name}"? Sus nodos y destinos quedarán sin agrupar.`,
        )
    ) {
        return;
    }

    router.delete(NodeGroupController.destroy(group.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <!-- Compact groups panel, styled to match the A* simulation block. -->
    <div class="rounded-md border p-2">
        <div class="flex items-center justify-between">
            <span class="flex items-center gap-1.5 text-xs font-medium">
                <Layers class="h-4 w-4 text-muted-foreground" />
                Grupos
            </span>
            <button
                type="button"
                class="flex items-center gap-1 rounded px-1.5 py-0.5 text-[11px] text-muted-foreground hover:bg-accent hover:text-foreground"
                @click="openCreate"
            >
                <Plus class="h-3.5 w-3.5" />
                Agregar
            </button>
        </div>

        <div
            v-if="building.node_groups && building.node_groups.length"
            class="mt-2 space-y-1"
        >
            <div
                v-for="group in building.node_groups"
                :key="group.id"
                class="group flex items-center justify-between rounded px-2 py-1 text-xs hover:bg-accent/50"
            >
                <span class="truncate font-medium">{{ group.name }}</span>
                <div
                    class="flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        title="Editar grupo"
                        @click="openEdit(group)"
                    >
                        <Pencil class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        class="text-destructive hover:opacity-70"
                        title="Eliminar grupo"
                        @click="destroy(group)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
        <p v-else class="mt-2 text-[11px] leading-relaxed text-muted-foreground">
            Agrupar es opcional. Crea un grupo para organizar nodos y destinos
            por niveles o zonas.
        </p>
    </div>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ editingGroup ? 'Editar grupo' : 'Agregar grupo' }}
                </DialogTitle>
                <DialogDescription>
                    Ponle un nombre al grupo (ej. "Piso 1", "Sótano", "Zona A").
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    editingGroup
                        ? NodeGroupController.update.form(editingGroup.id)
                        : NodeGroupController.store.form(props.building.id)
                "
                :options="{ preserveScroll: true }"
                @success="dialogOpen = false"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        placeholder="Ej. Primer piso"
                        :default-value="editingGroup?.name ?? ''"
                    />
                    <InputError :message="errors.name" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="dialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ editingGroup ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
