<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DestinationController from '@/actions/App/Http/Controllers/DestinationController';
import type {
    Building,
    Destination,
    ScheduleTypeOption,
} from '@/components/buildings/building';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    building: Building;
    scheduleTypeOptions: ScheduleTypeOption[];
}

const props = defineProps<Props>();

// Sentinel for "no group" in the group filter select (empty string isn't a
// valid select value in reka-ui).
const NO_GROUP = 'none';

// Group chosen only to narrow the node list below. It's a local UI filter, not
// part of the saved destination (the group lives on the node, not the destination).
const selectedGroupId = ref<string>(NO_GROUP);

// Assignable nodes: never the initial (calibration) node, and narrowed to the
// group picked above. The two-step flow keeps the node list short when there are
// many nodes; the destination still stores just the chosen node.
const nodeOptions = computed(() =>
    (props.building.nodes ?? [])
        .filter((node) => !node.is_initial)
        .filter((node) => {
            if (selectedGroupId.value === NO_GROUP) {
                return node.node_group_id === null;
            }

            return node.node_group_id === Number(selectedGroupId.value);
        })
        .map((node) => {
            const coords = `(${node.position_x.toFixed(1)}, ${node.position_y.toFixed(1)}, ${node.position_z.toFixed(1)})`;
            const name = node.label ? `${node.label} · ${coords}` : coords;

            return {
                id: node.id,
                label: name,
            };
        }),
);

// The group each node belongs to, so editing a destination can preselect the
// filter to the group of its currently assigned node.
function groupOfNode(nodeId: number | null): string {
    if (nodeId === null) {
        return NO_GROUP;
    }

    const node = (props.building.nodes ?? []).find((n) => n.id === nodeId);

    return node?.node_group_id != null ? String(node.node_group_id) : NO_GROUP;
}

const dialogOpen = ref(false);
// The destination being edited; null means we are creating a new one.
const editingDestination = ref<Destination | null>(null);

// The academic schedule image the user just picked (null = keep the existing one).
const scheduleFile = ref<File | null>(null);

// Preview URL (object URL) of the schedule image just picked, or null to fall
// back to the existing stored image when editing.
const schedulePreview = ref<string | null>(null);

// Preview URL of the responsible photo just picked (object URL), or null to fall
// back to the existing stored photo when editing.
const photoPreview = ref<string | null>(null);

const form = useForm({
    space_type_id: null as number | null,
    display_name: '',
    description: '',
    node_id: null as number | null,
    type: 'attention' as 'attention' | 'academic',
    schedule_file: null as File | null,
    schedule_text: '',
    responsible_name: '',
    responsible_phone: '',
    responsible_email: '',
    responsible_photo: null as File | null,
    // When true, the server deletes the existing file and clears the column.
    remove_responsible_photo: false,
    remove_schedule_file: false,
});

// Public URL of the currently stored responsible photo (relative path served
// from the storage symlink), or null when the destination has none.
const currentPhotoUrl = computed<string | null>(() =>
    editingDestination.value?.responsible_photo
        ? `/storage/${editingDestination.value.responsible_photo}`
        : null,
);

// Public URL of the currently stored schedule image, or null when there is none.
const currentScheduleUrl = computed<string | null>(() =>
    editingDestination.value?.schedule_file
        ? `/storage/${editingDestination.value.schedule_file}`
        : null,
);

// Clears the form and its extra refs (files, previews, group filter) back to a
// blank state. Used when opening "create" and after a successful submit, so the
// dialog never keeps values from a previously created/edited destination.
function resetForm(): void {
    form.reset();
    form.clearErrors();
    scheduleFile.value = null;
    schedulePreview.value = null;
    photoPreview.value = null;
    selectedGroupId.value = NO_GROUP;
}

function openCreate(): void {
    editingDestination.value = null;
    resetForm();
    dialogOpen.value = true;
}

function openEdit(destination: Destination): void {
    editingDestination.value = destination;
    form.clearErrors();
    scheduleFile.value = null;
    schedulePreview.value = null;
    photoPreview.value = null;
    form.space_type_id = destination.space_type_id;
    form.display_name = destination.display_name;
    form.description = destination.description ?? '';
    form.node_id = destination.node_id;
    form.type = destination.type;
    form.schedule_file = null;
    form.schedule_text = destination.schedule_text ?? '';
    form.responsible_name = destination.responsible_name ?? '';
    form.responsible_phone = destination.responsible_phone ?? '';
    form.responsible_email = destination.responsible_email ?? '';
    form.responsible_photo = null;
    form.remove_responsible_photo = false;
    form.remove_schedule_file = false;
    // Preselect the group filter to the group of the assigned node, so the node
    // is already visible in the (narrowed) node list when the dialog opens.
    selectedGroupId.value = groupOfNode(destination.node_id);
    dialogOpen.value = true;
}

// Keeps the picked schedule image in the form and builds a local preview URL.
// Picking a file cancels a pending removal.
function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    scheduleFile.value = file;
    form.schedule_file = file;
    schedulePreview.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        form.remove_schedule_file = false;
    }
}

// Keeps the picked photo in the form and builds a local preview URL for it.
// Picking a photo cancels a pending removal.
function onPhotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.responsible_photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        form.remove_responsible_photo = false;
    }
}

// Marks the stored responsible photo to be deleted on save (clears any pick).
function removePhoto(): void {
    form.responsible_photo = null;
    photoPreview.value = null;
    form.remove_responsible_photo = true;
}

// When the group filter changes, clear the node if it no longer belongs to the
// chosen group (avoids keeping a node hidden from the narrowed list).
function onGroupChange(value: string): void {
    selectedGroupId.value = value;

    if (form.node_id !== null && groupOfNode(form.node_id) !== value) {
        form.node_id = null;
    }
}

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true, // send multipart so the PDF uploads
        onSuccess: () => {
            dialogOpen.value = false;
            // Clear everything so the next "Agregar destino" starts blank and
            // doesn't inherit the values just submitted.
            editingDestination.value = null;
            resetForm();
        },
    };

    if (editingDestination.value) {
        // Inertia can't PUT multipart directly; POST with method spoofing so the
        // file upload works on an update.
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            DestinationController.update(editingDestination.value.id).url,
            options,
        );
    } else {
        // Reset the transform: Inertia keeps the one set by a previous edit, so
        // without this a create after an edit would send _method=put to the
        // POST-only store route (405 Method Not Allowed).
        form.transform((data) => data).post(
            DestinationController.store(props.building.id).url,
            options,
        );
    }
}

function destroy(destination: Destination): void {
    if (!confirm(`¿Eliminar el destino "${destination.display_name}"?`)) {
        return;
    }

    router.delete(DestinationController.destroy(destination.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Destinos</CardTitle>
                <CardDescription>
                    Lugares a los que la app puede navegar (salas, oficinas...).
                </CardDescription>
            </div>
            <Button
                size="sm"
                :disabled="!building.space_types?.length"
                @click="openCreate"
            >
                <Plus class="mr-1 h-4 w-4" />
                Agregar destino
            </Button>
        </CardHeader>
        <CardContent>
            <p
                v-if="!building.space_types?.length"
                class="text-sm text-muted-foreground"
            >
                Primero agrega al menos un tipo de espacio para poder crear
                destinos.
            </p>
            <div
                v-else-if="
                    building.destinations && building.destinations.length
                "
                class="max-h-96 divide-y overflow-y-auto rounded-md border"
            >
                <div
                    v-for="destination in building.destinations"
                    :key="destination.id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <div class="flex flex-col">
                        <span class="font-medium">
                            {{ destination.display_name }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ destination.space_type?.name }}
                            ·
                            {{
                                destination.type === 'academic'
                                    ? 'Académico'
                                    : 'Atención'
                            }}
                            <template
                                v-if="
                                    destination.type === 'academic' &&
                                    destination.schedule_file
                                "
                            >
                                ·
                                <span class="text-green-600">con horario</span>
                            </template>
                            <template v-if="destination.node_id">
                                · <span class="text-green-600">con nodo</span>
                            </template>
                            <template v-else>
                                · <span class="text-amber-600">sin nodo</span>
                            </template>
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8"
                            @click="openEdit(destination)"
                        >
                            <Pencil class="h-4 w-4" />
                            <span class="sr-only">Editar destino</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-destructive"
                            @click="destroy(destination)"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Eliminar destino</span>
                        </Button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Este edificio aún no tiene destinos.
            </p>
        </CardContent>
    </Card>

    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        editingDestination
                            ? 'Editar destino'
                            : 'Agregar destino'
                    }}
                </DialogTitle>
                <DialogDescription>
                    El nodo se asigna después con el editor 3D.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label>Nombre</Label>
                    <Input
                        v-model="form.display_name"
                        required
                        placeholder="Ej. Licancabur"
                    />
                    <InputError :message="form.errors.display_name" />
                </div>

                <div class="grid gap-2">
                    <Label>Tipo de espacio</Label>
                    <Select
                        :model-value="form.space_type_id?.toString()"
                        @update:model-value="
                            form.space_type_id = Number($event)
                        "
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Selecciona" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="spaceType in building.space_types"
                                :key="spaceType.id"
                                :value="spaceType.id.toString()"
                            >
                                {{ spaceType.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.space_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label>Descripción</Label>
                    <Textarea v-model="form.description" rows="2" />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Two-step node picker: first pick a group to narrow the list,
                     then pick the node. Only the node is saved; the group is just
                     a filter (the group belongs to the node, not the destination). -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label>Grupo del nodo</Label>
                        <Select
                            class="w-full"
                            :model-value="selectedGroupId"
                            @update:model-value="
                                onGroupChange($event as string)
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Sin grupo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NO_GROUP">
                                    Sin grupo
                                </SelectItem>
                                <SelectItem
                                    v-for="group in building.node_groups"
                                    :key="group.id"
                                    :value="group.id.toString()"
                                >
                                    {{ group.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label>Nodo (posición 3D)</Label>
                        <Select
                            class="w-full"
                            :model-value="form.node_id?.toString() ?? 'none'"
                            @update:model-value="
                                form.node_id =
                                    $event === 'none' ? null : Number($event)
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Sin nodo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Sin nodo</SelectItem>
                                <SelectItem
                                    v-for="option in nodeOptions"
                                    :key="option.id"
                                    :value="option.id.toString()"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.node_id" />
                    </div>
                </div>
                <p
                    v-if="!nodeOptions.length"
                    class="text-xs text-muted-foreground"
                >
                    No hay nodos en este grupo. Elige otro grupo, o coloca nodos
                    en el editor 3D para anclar este destino a una posición.
                </p>

                <!-- Schedule: the type decides which fields show. Academic = PDF;
                     attention = text schedule + responsible contact. -->
                <div class="grid gap-4 rounded-md border p-3">
                    <div class="grid gap-2">
                        <Label>Tipo de horario</Label>
                        <Select
                            :model-value="form.type"
                            @update:model-value="
                                form.type = $event as 'attention' | 'academic'
                            "
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in scheduleTypeOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Academic: upload a schedule image (horizontal). -->
                    <div v-if="form.type === 'academic'" class="grid gap-2">
                        <Label>Horario (imagen)</Label>
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                            @change="onFileChange"
                        />
                        <!-- Preview: the picked image, or the stored one when editing. -->
                        <div
                            v-if="
                                (schedulePreview || currentScheduleUrl) &&
                                !form.remove_schedule_file
                            "
                            class="aspect-[16/9] w-full overflow-hidden rounded-md border bg-muted"
                        >
                            <img
                                :src="
                                    schedulePreview ?? currentScheduleUrl ?? ''
                                "
                                alt="Horario"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <p
                            v-if="
                                editingDestination?.schedule_file &&
                                !scheduleFile &&
                                !form.remove_schedule_file
                            "
                            class="text-xs text-muted-foreground"
                        >
                            Sube una imagen nueva para reemplazarla.
                            <button
                                type="button"
                                class="text-destructive underline"
                                @click="form.remove_schedule_file = true"
                            >
                                Quitar
                            </button>
                        </p>
                        <p
                            v-if="form.remove_schedule_file"
                            class="text-xs text-muted-foreground"
                        >
                            La imagen se quitará al guardar.
                            <button
                                type="button"
                                class="underline"
                                @click="form.remove_schedule_file = false"
                            >
                                Deshacer
                            </button>
                        </p>
                        <InputError :message="form.errors.schedule_file" />
                    </div>

                    <!-- Attention: free-text schedule + responsible contact. -->
                    <template v-else>
                        <div class="grid gap-2">
                            <Label>Horario (texto)</Label>
                            <Input
                                v-model="form.schedule_text"
                                placeholder="Ej. Lunes a Viernes 10:00–19:00"
                            />
                            <InputError :message="form.errors.schedule_text" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Responsable</Label>
                            <Input
                                v-model="form.responsible_name"
                                placeholder="Nombre"
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <Input
                                    v-model="form.responsible_phone"
                                    placeholder="Teléfono"
                                />
                                <Input
                                    v-model="form.responsible_email"
                                    placeholder="Email"
                                />
                            </div>
                            <InputError
                                :message="form.errors.responsible_email"
                            />
                        </div>

                        <!-- Photo of the responsible person, passport style (3:4). -->
                        <div class="grid gap-2">
                            <Label
                                >Foto del responsable (tipo carnet, 3:4)</Label
                            >
                            <div class="flex items-start gap-3">
                                <div
                                    class="aspect-[3/4] w-24 shrink-0 overflow-hidden rounded-md border bg-muted"
                                >
                                    <img
                                        v-if="
                                            (photoPreview || currentPhotoUrl) &&
                                            !form.remove_responsible_photo
                                        "
                                        :src="
                                            photoPreview ??
                                            currentPhotoUrl ??
                                            ''
                                        "
                                        alt="Foto del responsable"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div class="grid gap-1">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                                        @change="onPhotoChange"
                                    />
                                    <button
                                        v-if="
                                            currentPhotoUrl &&
                                            !photoPreview &&
                                            !form.remove_responsible_photo
                                        "
                                        type="button"
                                        class="justify-self-start text-xs text-destructive underline"
                                        @click="removePhoto"
                                    >
                                        Quitar foto actual
                                    </button>
                                    <p
                                        v-if="form.remove_responsible_photo"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Se quitará al guardar.
                                        <button
                                            type="button"
                                            class="underline"
                                            @click="
                                                form.remove_responsible_photo = false
                                            "
                                        >
                                            Deshacer
                                        </button>
                                    </p>
                                </div>
                            </div>
                            <InputError
                                :message="form.errors.responsible_photo"
                            />
                        </div>
                    </template>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="dialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ editingDestination ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
