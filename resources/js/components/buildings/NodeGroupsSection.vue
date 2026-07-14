<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Layers, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import NodeGroupController from '@/actions/App/Http/Controllers/NodeGroupController';
import type { Building, NodeGroup } from '@/components/buildings/building';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Grupos</CardTitle>
                <CardDescription>
                    Etiquetas opcionales para agrupar nodos y destinos (ej.
                    "Piso 1", "Sótano").
                </CardDescription>
            </div>
            <Button size="sm" @click="openCreate">
                <Plus class="mr-1 h-4 w-4" />
                Agregar grupo
            </Button>
        </CardHeader>
        <CardContent>
            <div
                v-if="building.node_groups && building.node_groups.length"
                class="divide-y rounded-md border"
            >
                <div
                    v-for="group in building.node_groups"
                    :key="group.id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-muted"
                        >
                            <Layers class="h-4 w-4" />
                        </span>
                        <span class="font-medium">{{ group.name }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            @click="openEdit(group)"
                        >
                            <Pencil class="h-4 w-4" />
                            <span class="sr-only">Editar grupo</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-destructive"
                            @click="destroy(group)"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Eliminar grupo</span>
                        </Button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Agrupar es opcional. Crea un grupo solo si quieres organizar los
                nodos y destinos por niveles o zonas.
            </p>
        </CardContent>
    </Card>

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
