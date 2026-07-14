<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { ref } from 'vue';
import type { Building } from '@/components/buildings/building';
import NodeEditor from '@/components/buildings/NodeEditor.vue';
import { Button } from '@/components/ui/button';
import {
    edit as buildingsEdit,
    index as buildingsIndex,
} from '@/routes/buildings';

interface Props {
    building: Building;
}

defineProps<Props>();

// Reference to the editor so the header's Save button can drive its save state.
const editor = ref<InstanceType<typeof NodeEditor> | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Edificios', href: buildingsIndex() },
            { title: 'Editor de nodos', href: buildingsIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="`Nodos · ${building.name}`" />

    <!--
      Height is pinned to the viewport minus the header (h-16 = 4rem) and the
      breadcrumb bar (h-12 = 3rem), plus 2px for their bottom borders, so the inner
      flex column can stretch the canvas and node list to full height without a tiny
      page scrollbar appearing. Width follows the layout's widened main.
    -->
    <div class="flex h-[calc(100vh-7rem-2px)] flex-col gap-4 p-4">
        <div class="flex shrink-0 items-center justify-between">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="buildingsEdit(building.id)">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-semibold">
                        Editor de nodos · {{ building.name }}
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Coloca los nodos de navegación y organízalos en grupos.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span
                    v-if="editor?.dirty"
                    class="rounded bg-amber-500/10 px-2 py-1 text-xs text-amber-600"
                >
                    Cambios sin guardar
                </span>
                <Button
                    size="sm"
                    :disabled="!editor?.dirty || editor?.saving"
                    @click="editor?.save()"
                >
                    <Save class="mr-1 h-4 w-4" />
                    {{
                        editor?.saving
                            ? 'Guardando…'
                            : editor?.dirty
                              ? 'Guardar'
                              : 'Guardado'
                    }}
                </Button>
            </div>
        </div>

        <!-- Fill the remaining height; the editor manages its own inner layout. -->
        <div class="min-h-0 flex-1 overflow-hidden">
            <NodeEditor
                ref="editor"
                :building="building"
                :model-url="building.model_url"
            />
        </div>
    </div>
</template>
