<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DestinationController from '@/actions/App/Http/Controllers/DestinationController';
import SpaceTypeController from '@/actions/App/Http/Controllers/SpaceTypeController';
import type {
    Building,
    Destination,
    ScheduleTypeOption,
    SpaceType,
} from '@/components/buildings/building';
import InputError from '@/components/InputError.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
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

// Destinations grouped by their space type, so each accordion section lists only
// the destinations that belong to that type.
const groups = computed(() =>
    (props.building.space_types ?? []).map((spaceType) => ({
        spaceType,
        destinations: (props.building.destinations ?? []).filter(
            (destination) => destination.space_type_id === spaceType.id,
        ),
    })),
);

/* ---------------------------------------------------------------------------
 * Space types
 * ------------------------------------------------------------------------- */

const spaceTypeDialogOpen = ref(false);
const editingSpaceType = ref<SpaceType | null>(null);
const spaceTypeForm = useForm({ name: '' });

function openCreateSpaceType(): void {
    editingSpaceType.value = null;
    spaceTypeForm.reset();
    spaceTypeForm.clearErrors();
    spaceTypeDialogOpen.value = true;
}

function openEditSpaceType(spaceType: SpaceType): void {
    editingSpaceType.value = spaceType;
    spaceTypeForm.clearErrors();
    spaceTypeForm.name = spaceType.name;
    spaceTypeDialogOpen.value = true;
}

function submitSpaceType(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            spaceTypeDialogOpen.value = false;
            editingSpaceType.value = null;
            spaceTypeForm.reset();
        },
    };

    if (editingSpaceType.value) {
        spaceTypeForm.put(
            SpaceTypeController.update(editingSpaceType.value.id).url,
            options,
        );
    } else {
        spaceTypeForm.post(
            SpaceTypeController.store(props.building.id).url,
            options,
        );
    }
}

function destroySpaceType(spaceType: SpaceType): void {
    if (
        !confirm(
            `¿Eliminar el tipo de espacio "${spaceType.name}"? Se eliminarán también sus destinos.`,
        )
    ) {
        return;
    }

    router.delete(SpaceTypeController.destroy(spaceType.id).url, {
        preserveScroll: true,
    });
}

/* ---------------------------------------------------------------------------
 * Destinations
 * ------------------------------------------------------------------------- */

// Sentinel for "no group" in the group filter select (empty string isn't a
// valid select value in reka-ui).
const NO_GROUP = 'none';

// Group chosen only to narrow the node list. It's a local UI filter, not part of
// the saved destination (the group lives on the node, not the destination).
const selectedGroupId = ref<string>(NO_GROUP);

// Assignable nodes: never the initial (calibration) node, and narrowed to the
// group picked above.
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

            return { id: node.id, label: name };
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

const destinationDialogOpen = ref(false);
const editingDestination = ref<Destination | null>(null);

const scheduleFile = ref<File | null>(null);
const schedulePreview = ref<string | null>(null);
const photoPreview = ref<string | null>(null);

const destinationForm = useForm({
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
    remove_responsible_photo: false,
    remove_schedule_file: false,
});

const currentPhotoUrl = computed<string | null>(() =>
    editingDestination.value?.responsible_photo
        ? `/storage/${editingDestination.value.responsible_photo}`
        : null,
);

const currentScheduleUrl = computed<string | null>(() =>
    editingDestination.value?.schedule_file
        ? `/storage/${editingDestination.value.schedule_file}`
        : null,
);

function resetDestinationForm(): void {
    destinationForm.reset();
    destinationForm.clearErrors();
    scheduleFile.value = null;
    schedulePreview.value = null;
    photoPreview.value = null;
    selectedGroupId.value = NO_GROUP;
}

// Opens the destination dialog with the space type preselected, so a destination
// is always created under a type (a destination cannot exist without one).
function openCreateDestination(spaceTypeId: number): void {
    editingDestination.value = null;
    resetDestinationForm();
    destinationForm.space_type_id = spaceTypeId;
    destinationDialogOpen.value = true;
}

function openEditDestination(destination: Destination): void {
    editingDestination.value = destination;
    destinationForm.clearErrors();
    scheduleFile.value = null;
    schedulePreview.value = null;
    photoPreview.value = null;
    destinationForm.space_type_id = destination.space_type_id;
    destinationForm.display_name = destination.display_name;
    destinationForm.description = destination.description ?? '';
    destinationForm.node_id = destination.node_id;
    destinationForm.type = destination.type;
    destinationForm.schedule_file = null;
    destinationForm.schedule_text = destination.schedule_text ?? '';
    destinationForm.responsible_name = destination.responsible_name ?? '';
    destinationForm.responsible_phone = destination.responsible_phone ?? '';
    destinationForm.responsible_email = destination.responsible_email ?? '';
    destinationForm.responsible_photo = null;
    destinationForm.remove_responsible_photo = false;
    destinationForm.remove_schedule_file = false;
    selectedGroupId.value = groupOfNode(destination.node_id);
    destinationDialogOpen.value = true;
}

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    scheduleFile.value = file;
    destinationForm.schedule_file = file;
    schedulePreview.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        destinationForm.remove_schedule_file = false;
    }
}

function onPhotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    destinationForm.responsible_photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : null;

    if (file) {
        destinationForm.remove_responsible_photo = false;
    }
}

function removePhoto(): void {
    destinationForm.responsible_photo = null;
    photoPreview.value = null;
    destinationForm.remove_responsible_photo = true;
}

function onGroupChange(value: string): void {
    selectedGroupId.value = value;

    if (
        destinationForm.node_id !== null &&
        groupOfNode(destinationForm.node_id) !== value
    ) {
        destinationForm.node_id = null;
    }
}

function submitDestination(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            destinationDialogOpen.value = false;
            editingDestination.value = null;
            resetDestinationForm();
        },
    };

    if (editingDestination.value) {
        destinationForm
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(
                DestinationController.update(editingDestination.value.id).url,
                options,
            );
    } else {
        destinationForm
            .transform((data) => data)
            .post(DestinationController.store(props.building.id).url, options);
    }
}

function destroyDestination(destination: Destination): void {
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
        <CardHeader class="flex flex-row items-start justify-between">
            <div>
                <CardTitle>Espacios y destinos</CardTitle>
                <CardDescription>
                    Los destinos se agrupan por tipo de espacio. Cada destino
                    pertenece siempre a un tipo.
                </CardDescription>
            </div>
            <Button size="sm" @click="openCreateSpaceType">
                <Plus class="mr-1 h-4 w-4" />
                Agregar tipo
            </Button>
        </CardHeader>
        <CardContent>
            <p
                v-if="!building.space_types?.length"
                class="text-sm text-muted-foreground"
            >
                Este edificio aún no tiene tipos de espacio. Agrega uno para
                poder crear destinos.
            </p>

            <!-- Grouped list. The whole area scrolls once it grows past the
                 max height, keeping the page compact. -->
            <div v-else class="max-h-[32rem] overflow-y-auto rounded-md border">
                <Accordion
                    type="multiple"
                    class="divide-y"
                    :default-value="
                        groups.map((g) => g.spaceType.id.toString())
                    "
                >
                    <AccordionItem
                        v-for="group in groups"
                        :key="group.spaceType.id"
                        :value="group.spaceType.id.toString()"
                        class="border-b-0 px-4"
                    >
                        <div class="flex items-center">
                            <AccordionTrigger class="flex-1">
                                <span class="flex items-center gap-2">
                                    {{ group.spaceType.name }}
                                    <Badge variant="secondary">
                                        {{ group.destinations.length }}
                                    </Badge>
                                </span>
                            </AccordionTrigger>
                            <div class="flex items-center gap-1 pl-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    @click="openEditSpaceType(group.spaceType)"
                                >
                                    <Pencil class="h-4 w-4" />
                                    <span class="sr-only">Editar tipo</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-destructive"
                                    @click="destroySpaceType(group.spaceType)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span class="sr-only">Eliminar tipo</span>
                                </Button>
                            </div>
                        </div>

                        <AccordionContent class="pb-3">
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="destination in group.destinations"
                                    :key="destination.id"
                                    class="flex items-center justify-between rounded-md border px-3 py-2"
                                >
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">
                                            {{ destination.display_name }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                destination.type === 'academic'
                                                    ? 'Académico'
                                                    : 'Atención'
                                            }}
                                            <template
                                                v-if="destination.node_id"
                                            >
                                                ·
                                                <span class="text-green-600">
                                                    con nodo
                                                </span>
                                            </template>
                                            <template v-else>
                                                ·
                                                <span class="text-amber-600">
                                                    sin nodo
                                                </span>
                                            </template>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8"
                                            @click="
                                                openEditDestination(destination)
                                            "
                                        >
                                            <Pencil class="h-4 w-4" />
                                            <span class="sr-only">
                                                Editar destino
                                            </span>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive"
                                            @click="
                                                destroyDestination(destination)
                                            "
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            <span class="sr-only">
                                                Eliminar destino
                                            </span>
                                        </Button>
                                    </div>
                                </div>

                                <p
                                    v-if="!group.destinations.length"
                                    class="px-1 text-xs text-muted-foreground"
                                >
                                    Sin destinos en este tipo.
                                </p>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="mt-1 self-start"
                                    @click="
                                        openCreateDestination(
                                            group.spaceType.id,
                                        )
                                    "
                                >
                                    <Plus class="mr-1 h-4 w-4" />
                                    Agregar destino
                                </Button>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>
        </CardContent>
    </Card>

    <!-- Space type dialog -->
    <Dialog v-model:open="spaceTypeDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ editingSpaceType ? 'Editar tipo' : 'Agregar tipo' }}
                </DialogTitle>
                <DialogDescription>
                    Define el nombre del tipo de espacio.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitSpaceType">
                <div class="grid gap-2">
                    <Label>Nombre</Label>
                    <Input
                        v-model="spaceTypeForm.name"
                        required
                        placeholder="Ej. Salas"
                    />
                    <InputError :message="spaceTypeForm.errors.name" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="spaceTypeDialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="spaceTypeForm.processing">
                        {{ editingSpaceType ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Destination dialog -->
    <Dialog v-model:open="destinationDialogOpen">
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

            <form class="space-y-4" @submit.prevent="submitDestination">
                <div class="grid gap-2">
                    <Label>Nombre</Label>
                    <Input
                        v-model="destinationForm.display_name"
                        required
                        placeholder="Ej. Licancabur"
                    />
                    <InputError
                        :message="destinationForm.errors.display_name"
                    />
                </div>

                <div class="grid gap-2">
                    <Label>Tipo de espacio</Label>
                    <Select
                        :model-value="destinationForm.space_type_id?.toString()"
                        @update:model-value="
                            destinationForm.space_type_id = Number($event)
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
                    <InputError
                        :message="destinationForm.errors.space_type_id"
                    />
                </div>

                <div class="grid gap-2">
                    <Label>Descripción</Label>
                    <Textarea v-model="destinationForm.description" rows="2" />
                    <InputError :message="destinationForm.errors.description" />
                </div>

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
                            :model-value="
                                destinationForm.node_id?.toString() ?? 'none'
                            "
                            @update:model-value="
                                destinationForm.node_id =
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
                        <InputError :message="destinationForm.errors.node_id" />
                    </div>
                </div>
                <p
                    v-if="!nodeOptions.length"
                    class="text-xs text-muted-foreground"
                >
                    No hay nodos en este grupo. Elige otro grupo, o coloca nodos
                    en el editor 3D para anclar este destino a una posición.
                </p>

                <div class="grid gap-4 rounded-md border p-3">
                    <div class="grid gap-2">
                        <Label>Tipo de horario</Label>
                        <Select
                            :model-value="destinationForm.type"
                            @update:model-value="
                                destinationForm.type = $event as
                                    'attention' | 'academic'
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

                    <div
                        v-if="destinationForm.type === 'academic'"
                        class="grid gap-2"
                    >
                        <Label>Horario (imagen)</Label>
                        <input
                            type="file"
                            accept="image/*"
                            class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                            @change="onFileChange"
                        />
                        <div
                            v-if="
                                (schedulePreview || currentScheduleUrl) &&
                                !destinationForm.remove_schedule_file
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
                                !destinationForm.remove_schedule_file
                            "
                            class="text-xs text-muted-foreground"
                        >
                            Sube una imagen nueva para reemplazarla.
                            <button
                                type="button"
                                class="text-destructive underline"
                                @click="
                                    destinationForm.remove_schedule_file = true
                                "
                            >
                                Quitar
                            </button>
                        </p>
                        <p
                            v-if="destinationForm.remove_schedule_file"
                            class="text-xs text-muted-foreground"
                        >
                            La imagen se quitará al guardar.
                            <button
                                type="button"
                                class="underline"
                                @click="
                                    destinationForm.remove_schedule_file = false
                                "
                            >
                                Deshacer
                            </button>
                        </p>
                        <InputError
                            :message="destinationForm.errors.schedule_file"
                        />
                    </div>

                    <template v-else>
                        <div class="grid gap-2">
                            <Label>Horario (texto)</Label>
                            <Input
                                v-model="destinationForm.schedule_text"
                                placeholder="Ej. Lunes a Viernes 10:00–19:00"
                            />
                            <InputError
                                :message="destinationForm.errors.schedule_text"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label>Responsable</Label>
                            <Input
                                v-model="destinationForm.responsible_name"
                                placeholder="Nombre"
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <Input
                                    v-model="destinationForm.responsible_phone"
                                    placeholder="Teléfono"
                                />
                                <Input
                                    v-model="destinationForm.responsible_email"
                                    placeholder="Email"
                                />
                            </div>
                            <InputError
                                :message="
                                    destinationForm.errors.responsible_email
                                "
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label>
                                Foto del responsable (tipo carnet, 3:4)
                            </Label>
                            <div class="flex items-start gap-3">
                                <div
                                    class="aspect-[3/4] w-24 shrink-0 overflow-hidden rounded-md border bg-muted"
                                >
                                    <img
                                        v-if="
                                            (photoPreview || currentPhotoUrl) &&
                                            !destinationForm.remove_responsible_photo
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
                                            !destinationForm.remove_responsible_photo
                                        "
                                        type="button"
                                        class="justify-self-start text-xs text-destructive underline"
                                        @click="removePhoto"
                                    >
                                        Quitar foto actual
                                    </button>
                                    <p
                                        v-if="
                                            destinationForm.remove_responsible_photo
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        Se quitará al guardar.
                                        <button
                                            type="button"
                                            class="underline"
                                            @click="
                                                destinationForm.remove_responsible_photo = false
                                            "
                                        >
                                            Deshacer
                                        </button>
                                    </p>
                                </div>
                            </div>
                            <InputError
                                :message="
                                    destinationForm.errors.responsible_photo
                                "
                            />
                        </div>
                    </template>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="destinationDialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <!-- Disabled without a space type: a destination must always
                         belong to one, matching the backend validation. -->
                    <Button
                        type="submit"
                        :disabled="
                            destinationForm.processing ||
                            !destinationForm.space_type_id
                        "
                    >
                        {{ editingDestination ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
