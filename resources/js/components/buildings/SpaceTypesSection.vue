<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import SpaceTypeController from '@/actions/App/Http/Controllers/SpaceTypeController';
import type { Building, SpaceType } from '@/components/buildings/building';
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
// The space type currently being edited; null means we are creating a new one.
const editingSpaceType = ref<SpaceType | null>(null);

function openCreate(): void {
    editingSpaceType.value = null;
    dialogOpen.value = true;
}

function openEdit(spaceType: SpaceType): void {
    editingSpaceType.value = spaceType;
    dialogOpen.value = true;
}

function destroy(spaceType: SpaceType): void {
    if (!confirm(`¿Eliminar el tipo de espacio "${spaceType.name}"?`)) {
        return;
    }

    router.delete(SpaceTypeController.destroy(spaceType.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Tipos de espacio</CardTitle>
                <CardDescription>
                    Categorías compartidas por todo el edificio (Salas,
                    Oficinas, etc.).
                </CardDescription>
            </div>
            <Button size="sm" @click="openCreate">
                <Plus class="mr-1 h-4 w-4" />
                Agregar tipo
            </Button>
        </CardHeader>
        <CardContent>
            <div
                v-if="building.space_types && building.space_types.length"
                class="max-h-96 divide-y overflow-y-auto rounded-md border"
            >
                <div
                    v-for="spaceType in building.space_types"
                    :key="spaceType.id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <span class="font-medium">{{ spaceType.name }}</span>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            @click="openEdit(spaceType)"
                        >
                            <Pencil class="h-4 w-4" />
                            <span class="sr-only">Editar tipo</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-destructive"
                            @click="destroy(spaceType)"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Eliminar tipo</span>
                        </Button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Este edificio aún no tiene tipos de espacio.
            </p>
        </CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ editingSpaceType ? 'Editar tipo' : 'Agregar tipo' }}
                </DialogTitle>
                <DialogDescription>
                    Define el nombre del tipo de espacio.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    editingSpaceType
                        ? SpaceTypeController.update.form(editingSpaceType.id)
                        : SpaceTypeController.store.form(props.building.id)
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
                        placeholder="Ej. Salas"
                        :default-value="editingSpaceType?.name ?? ''"
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
                        {{ editingSpaceType ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
