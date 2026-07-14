<script setup lang="ts" generic="TData">
import { ArrowDownUp } from '@lucide/vue';
import type { SortDirection, Table } from '@tanstack/vue-table';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * Lista de ordenamiento simple, adaptada de tablecn (sadmann7) a Vue.
 *
 * A diferencia del original (multi-orden + drag & drop), esta versión maneja
 * UN solo criterio (columna + dirección), que es lo que el backend soporta
 * vía sort/direction. El estado vive en el `sorting` de TanStack.
 */
interface Props {
    table: Table<TData>;
}

const props = defineProps<Props>();

/** Columnas que se pueden ordenar, con su etiqueta legible. */
const sortableColumns = computed(() =>
    props.table
        .getAllColumns()
        .filter((column) => column.getCanSort())
        .map((column) => {
            const meta = column.columnDef.meta as
                | { label?: string }
                | undefined;

            return { id: column.id, label: meta?.label ?? column.id };
        }),
);

/** El orden activo (o undefined si no hay). */
const activeSort = computed(() => props.table.getState().sorting[0]);

const hasSort = computed(() => Boolean(activeSort.value));

/** Columna actualmente seleccionada. */
const selectedColumn = computed({
    get: () => activeSort.value?.id ?? '',
    set: (id: string) => {
        if (!id) {
            props.table.setSorting([]);

            return;
        }

        props.table.setSorting([{ id, desc: activeSort.value?.desc ?? false }]);
    },
});

/** Dirección actual ('asc' | 'desc'). */
const selectedDirection = computed({
    get: (): SortDirection => (activeSort.value?.desc ? 'desc' : 'asc'),
    set: (value: SortDirection) => {
        if (!activeSort.value) {
            return;
        }

        props.table.setSorting([
            { id: activeSort.value.id, desc: value === 'desc' },
        ]);
    },
});

function resetSort(): void {
    props.table.setSorting([]);
}

const directions: { value: SortDirection; label: string }[] = [
    { value: 'asc', label: 'Ascendente' },
    { value: 'desc', label: 'Descendente' },
];
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8">
                <ArrowDownUp class="mr-2 h-4 w-4 text-muted-foreground" />
                Ordenar
                <Badge
                    v-if="hasSort"
                    variant="secondary"
                    class="ml-2 rounded-sm px-1 font-normal"
                >
                    1
                </Badge>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="flex w-80 flex-col gap-3.5 p-4" align="end">
            <div class="flex flex-col gap-1">
                <h4 class="leading-none font-medium">
                    {{ hasSort ? 'Ordenar por' : 'Sin ordenamiento' }}
                </h4>
                <p class="text-sm text-muted-foreground">
                    Elegí una columna y la dirección para ordenar las filas.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Columna -->
                <Select v-model="selectedColumn">
                    <SelectTrigger class="h-8 flex-1">
                        <SelectValue placeholder="Columna" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem
                                v-for="column in sortableColumns"
                                :key="column.id"
                                :value="column.id"
                            >
                                {{ column.label }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <!-- Dirección -->
                <Select v-model="selectedDirection" :disabled="!hasSort">
                    <SelectTrigger class="h-8 w-36">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem
                                v-for="dir in directions"
                                :key="dir.value"
                                :value="dir.value"
                            >
                                {{ dir.label }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <Button
                v-if="hasSort"
                variant="outline"
                size="sm"
                class="w-full self-start"
                @click="resetSort"
            >
                Quitar orden
            </Button>
        </PopoverContent>
    </Popover>
</template>
