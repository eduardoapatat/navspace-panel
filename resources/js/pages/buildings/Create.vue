<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import BuildingController from '@/actions/App/Http/Controllers/BuildingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as buildingsCreate,
    index as buildingsIndex,
} from '@/routes/buildings';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Edificios',
                href: buildingsIndex(),
            },
            {
                title: 'Crear',
                href: buildingsCreate(),
            },
        ],
    },
});

// Local object URL used only to preview the selected building photo.
const imagePreviewUrl = ref<string | null>(null);

function clearImagePreview(): void {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
        imagePreviewUrl.value = null;
    }
}

function handleImageChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    clearImagePreview();

    if (file) {
        imagePreviewUrl.value = URL.createObjectURL(file);
    }
}

onBeforeUnmount(clearImagePreview);
</script>

<template>
    <Head title="Crear edificio" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Crear edificio
            </h1>
            <p class="text-muted-foreground">
                Registra un nuevo espacio navegable. El modelo 3D (.glb) es
                opcional y puedes cargarlo más tarde.
            </p>
        </div>

        <Form
            v-bind="BuildingController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input id="name" name="name" required placeholder="Ej. DICI" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Dirección</Label>
                <Input
                    id="address"
                    name="address"
                    required
                    placeholder="Ej. Av. General Velásquez 1775, Arica"
                />
                <InputError :message="errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="image">Foto del edificio</Label>
                <Input id="image" name="image" type="file" accept="image/*" @change="handleImageChange" />
                <div v-if="imagePreviewUrl" class="space-y-2">
                    <p class="text-xs font-medium text-muted-foreground">
                        Vista previa
                    </p>
                    <img
                        :src="imagePreviewUrl"
                        alt="Vista previa de la foto seleccionada"
                        class="h-32 w-48 rounded-md border object-cover"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    Opcional. Imagen que representa el edificio (se muestra en
                    el panel y en el selector de Unity), máximo 5 MB.
                </p>
                <InputError :message="errors.image" />
            </div>

            <div class="grid gap-2">
                <Label for="model">Modelo 3D (.glb)</Label>
                <Input id="model" name="model" type="file" accept=".glb" />
                <p class="text-xs text-muted-foreground">
                    Opcional. Archivo .glb del edificio, máximo 50 MB.
                </p>
                <InputError :message="errors.model" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">
                    Guardar edificio
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="buildingsIndex()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
