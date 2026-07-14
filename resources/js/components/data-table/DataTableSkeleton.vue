<script setup lang="ts">
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

/**
 * Esqueleto de carga para la tabla, adaptado de tablecn (sadmann7) a Vue.
 * Se muestra mientras llegan los datos para evitar un salto en blanco.
 */
interface Props {
    /** Cantidad de columnas a simular. */
    columnCount: number;
    /** Filas del cuerpo. */
    rowCount?: number;
    /** Botones de filtro en la barra superior. */
    filterCount?: number;
    /** Anchos por columna (se repiten cíclicamente). */
    cellWidths?: string[];
    /** Mostrar el bloque de opciones de vista (derecha de la barra). */
    withViewOptions?: boolean;
    /** Mostrar el bloque de paginación inferior. */
    withPagination?: boolean;
    /** Aplicar minWidth a las celdas (en vez de 'auto'). */
    shrinkZero?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rowCount: 10,
    filterCount: 0,
    cellWidths: () => ['auto'],
    withViewOptions: true,
    withPagination: true,
    shrinkZero: false,
    class: undefined,
});

const columns = computed(() => Array.from({ length: props.columnCount }));
const rows = computed(() => Array.from({ length: props.rowCount }));
const filters = computed(() => Array.from({ length: props.filterCount }));

const cellWidths = computed(() =>
    Array.from(
        { length: props.columnCount },
        (_, index) =>
            props.cellWidths[index % props.cellWidths.length] ?? 'auto',
    ),
);

function cellStyle(index: number): Record<string, string> {
    return {
        width: cellWidths.value[index] ?? 'auto',
        minWidth: props.shrinkZero
            ? (cellWidths.value[index] ?? 'auto')
            : 'auto',
    };
}
</script>

<template>
    <div :class="cn('flex w-full flex-col gap-2.5 overflow-auto', props.class)">
        <!-- Barra superior: filtros (izquierda) + opciones de vista (derecha) -->
        <div
            class="flex w-full items-center justify-between gap-2 overflow-auto p-1"
        >
            <div class="flex flex-1 items-center gap-2">
                <Skeleton
                    v-for="(_, i) in filters"
                    :key="`filter-${i}`"
                    class="h-7 w-18 border-dashed"
                />
            </div>
            <Skeleton
                v-if="withViewOptions"
                class="ml-auto hidden h-7 w-18 lg:flex"
            />
        </div>

        <!-- Tabla -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead
                            v-for="(_, j) in columns"
                            :key="`head-${j}`"
                            :style="cellStyle(j)"
                        >
                            <Skeleton class="h-6 w-full" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="(_, i) in rows"
                        :key="`row-${i}`"
                        class="hover:bg-transparent"
                    >
                        <TableCell
                            v-for="(_, j) in columns"
                            :key="`cell-${i}-${j}`"
                            :style="cellStyle(j)"
                        >
                            <Skeleton class="h-6 w-full" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Paginación -->
        <div
            v-if="withPagination"
            class="flex w-full items-center justify-between gap-4 overflow-auto p-1 sm:gap-8"
        >
            <Skeleton class="h-7 w-40 shrink-0" />
            <div class="flex items-center gap-4 sm:gap-6 lg:gap-8">
                <div class="flex items-center gap-2">
                    <Skeleton class="h-7 w-24" />
                    <Skeleton class="h-7 w-18" />
                </div>
                <div
                    class="flex items-center justify-center text-sm font-medium"
                >
                    <Skeleton class="h-7 w-20" />
                </div>
                <div class="flex items-center gap-2">
                    <Skeleton class="hidden size-7 lg:block" />
                    <Skeleton class="size-7" />
                    <Skeleton class="size-7" />
                    <Skeleton class="hidden size-7 lg:block" />
                </div>
            </div>
        </div>
    </div>
</template>
