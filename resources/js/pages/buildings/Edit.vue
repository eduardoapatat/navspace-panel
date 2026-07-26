<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Boxes, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import BuildingController from '@/actions/App/Http/Controllers/BuildingController';
import type {
    Building,
    ScheduleTypeOption,
} from '@/components/buildings/building';
import DestinationsSection from '@/components/buildings/DestinationsSection.vue';
import SpaceTypesSection from '@/components/buildings/SpaceTypesSection.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    edit as buildingsEdit,
    index as buildingsIndex,
    nodes as buildingsNodes,
} from '@/routes/buildings';

interface Props {
    building: Building;
    scheduleTypeOptions: ScheduleTypeOption[];
}

const props = defineProps<Props>();

// Marked-for-removal flags submitted as hidden inputs with the properties form.
// A "Quitar" button sets these; "Guardar cambios" applies the deletion. Choosing
// a new file instead just replaces it (the backend prioritizes the upload).
const removeImage = ref(false);
const removeModel = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edificios',
                href: buildingsIndex(),
            },
            {
                title: 'Gestión',
                href: buildingsEdit(0),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Gestión · ${building.name}`" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between">
            <div class="flex flex-col gap-1">
                <h1
                    class="text-2xl font-semibold text-gray-900 dark:text-white"
                >
                    {{ building.name }}
                </h1>
                <p class="text-muted-foreground">{{ building.address }}</p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="buildingsNodes(props.building.id)">
                    <Boxes class="mr-1 h-4 w-4" />
                    Editor de nodos
                </Link>
            </Button>
        </div>

        <!-- Building properties (name, address, photo, .glb model). -->
        <Card>
            <CardHeader>
                <CardTitle>Propiedades</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="BuildingController.update.form(building.id)"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="flex w-full flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autocomplete="organization"
                                    :default-value="building.name"
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="address">Dirección</Label>
                                <Input
                                    id="address"
                                    name="address"
                                    required
                                    autocomplete="street-address"
                                    :default-value="building.address"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Incluye referencias útiles para facilitar la
                                    ubicación del acceso principal.
                                </p>
                                <InputError :message="errors.address" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="image">Foto del edificio</Label>
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="building.image_url && !removeImage"
                                        :src="building.image_url"
                                        alt="Foto del edificio"
                                        class="h-16 w-16 rounded-md border object-cover"
                                    />
                                    <Badge
                                        :variant="
                                            building.image_url && !removeImage
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            building.image_url && !removeImage
                                                ? 'Foto cargada'
                                                : 'Sin foto'
                                        }}
                                    </Badge>
                                    <Button
                                        v-if="building.image_url && !removeImage"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-destructive"
                                        @click="removeImage = true"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        <span class="sr-only">Quitar foto</span>
                                    </Button>
                                </div>
                                <Input
                                    id="image"
                                    name="image"
                                    type="file"
                                    accept="image/*"
                                    @change="removeImage = false"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Opcional. Sube una imagen solo si quieres
                                    reemplazar la actual.
                                </p>
                                <p
                                    v-if="removeImage"
                                    class="text-xs text-muted-foreground"
                                >
                                    La foto se quitará al guardar.
                                    <button
                                        type="button"
                                        class="underline"
                                        @click="removeImage = false"
                                    >
                                        Deshacer
                                    </button>
                                </p>
                                <input
                                    type="hidden"
                                    name="remove_image"
                                    :value="removeImage ? '1' : '0'"
                                />
                                <InputError :message="errors.image" />
                            </div>
                        </div>

                        <div class="flex w-full flex-col gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="model">Modelo 3D (.glb)</Label>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :variant="
                                            building.model_url && !removeModel
                                                ? 'default'
                                                : 'secondary'
                                        "
                                    >
                                        {{
                                            building.model_url && !removeModel
                                                ? 'Modelo cargado'
                                                : 'Sin modelo'
                                        }}
                                    </Badge>
                                    <Button
                                        v-if="building.model_url && !removeModel"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-destructive"
                                        @click="removeModel = true"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        <span class="sr-only">Quitar modelo</span>
                                    </Button>
                                </div>
                                <Input
                                    id="model"
                                    name="model"
                                    type="file"
                                    accept=".glb"
                                    @change="removeModel = false"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Opcional. Sube un .glb solo si quieres
                                    reemplazar el actual.
                                </p>
                                <p
                                    v-if="removeModel"
                                    class="text-xs text-muted-foreground"
                                >
                                    El modelo se quitará al guardar.
                                    <button
                                        type="button"
                                        class="underline"
                                        @click="removeModel = false"
                                    >
                                        Deshacer
                                    </button>
                                </p>
                                <input
                                    type="hidden"
                                    name="remove_model"
                                    :value="removeModel ? '1' : '0'"
                                />
                                <InputError :message="errors.model" />

                                <!-- Yaw correction. Auto-set from the exporter on
                                     upload (Blender glb import 180° rotated in
                                     Unity); override only if a model comes out
                                     turned. -->
                                <div
                                    v-if="building.model_url && !removeModel"
                                    class="mt-1 flex flex-col gap-1"
                                >
                                    <Label for="model_yaw" class="text-xs">
                                        Corrección de orientación (Unity)
                                    </Label>
                                    <select
                                        id="model_yaw"
                                        name="model_yaw"
                                        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                                    >
                                        <option
                                            v-for="deg in [0, 90, 180, 270]"
                                            :key="deg"
                                            :value="deg"
                                            :selected="building.model_yaw === deg"
                                        >
                                            {{ deg }}°
                                        </option>
                                    </select>
                                    <p class="text-xs text-muted-foreground">
                                        Se detecta sola al subir el modelo
                                        (Blender = 180°). Cámbiala solo si el
                                        edificio sale girado en la app.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="processing">
                            Guardar cambios
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <!-- Destinations on the left, space types on the right. They stack on
             narrow screens. Items start-aligned so different heights don't stretch. -->
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
            <DestinationsSection
                :building="building"
                :schedule-type-options="scheduleTypeOptions"
            />

            <SpaceTypesSection :building="building" />
        </div>
    </div>
</template>
