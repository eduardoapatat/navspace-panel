<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import type { Table } from '@tanstack/vue-table';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface DataTablePaginationProps {
    table: Table<any>;
    total?: number;
    perPage?: number;
    currentPage?: number;
    // When true, the footer shows the total item count instead of the
    // "rows selected" summary (used by non-selectable views like card grids).
    showTotal?: boolean;
}

const props = withDefaults(defineProps<DataTablePaginationProps>(), {
    total: undefined,
    perPage: undefined,
    currentPage: undefined,
    showTotal: false,
});

const pageCount = computed(() => {
    if (props.total != null && props.perPage != null && props.perPage > 0) {
        return Math.ceil(props.total / props.perPage) || 1;
    }

    return props.table.getPageCount();
});

const canPreviousPage = computed(() => {
    if (props.currentPage != null) {
        return props.currentPage > 1;
    }

    return props.table.getCanPreviousPage();
});

const canNextPage = computed(() => {
    if (
        props.currentPage != null &&
        props.total != null &&
        props.perPage != null &&
        props.perPage > 0
    ) {
        return props.currentPage < Math.ceil(props.total / props.perPage);
    }

    return props.table.getCanNextPage();
});
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="flex-1 text-sm text-muted-foreground">
            <template v-if="showTotal">
                {{ total ?? 0 }}
                {{ (total ?? 0) === 1 ? 'resultado' : 'resultados' }}
            </template>
            <template v-else>
                {{ table.getFilteredSelectedRowModel().rows.length }} de
                {{ table.getFilteredRowModel().rows.length }} filas
                seleccionadas
            </template>
        </div>
        <div class="flex items-center gap-4 sm:flex-row sm:gap-6 lg:gap-8">
            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium">Filas por página</p>
                <Select
                    :model-value="`${table.getState().pagination.pageSize}`"
                    @update:model-value="table.setPageSize as any"
                >
                    <SelectTrigger class="h-8 w-17.5">
                        <SelectValue
                            :placeholder="`${table.getState().pagination.pageSize}`"
                        />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem
                            v-for="pageSize in [10, 20, 30, 40, 50]"
                            :key="pageSize"
                            :value="`${pageSize}`"
                        >
                            {{ pageSize }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center justify-center text-sm font-medium">
                Página
                {{ currentPage ?? table.getState().pagination.pageIndex + 1 }}
                de {{ pageCount }}
            </div>
            <div class="flex items-center space-x-2">
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="!canPreviousPage"
                    @click="table.setPageIndex(0)"
                >
                    <span class="sr-only">Ir a la primera página</span>
                    <ChevronsLeft class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!canPreviousPage"
                    @click="table.previousPage()"
                >
                    <span class="sr-only">Ir a la página anterior</span>
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!canNextPage"
                    @click="table.nextPage()"
                >
                    <span class="sr-only">Ir a la página siguiente</span>
                    <ChevronRight class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="!canNextPage"
                    @click="table.setPageIndex(pageCount - 1)"
                >
                    <span class="sr-only">Ir a la última página</span>
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
