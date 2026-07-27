<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowDownAZ, ArrowUpAZ, Search, X } from '@lucide/vue';
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type { PaginationState, SortingState } from '@tanstack/vue-table';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, ref } from 'vue';
import type { Building } from '@/components/buildings/building';
import BuildingCard from '@/components/buildings/BuildingCard.vue';
import DataTablePagination from '@/components/data-table/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as buildingsIndex } from '@/routes/buildings';
import type { PaginatedResponse } from '@/types';

interface Props {
    buildings: PaginatedResponse<Building>;
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

type QueryValue = string | number | undefined;

// The columns whose fields the backend (Building::sortable) allows sorting by.
const sortOptions = [
    { value: 'name', label: 'Nombre' },
    { value: 'address', label: 'Dirección' },
    { value: 'created_at', label: 'Fecha de creación' },
] as const;

// Search box and sort state are seeded from the current server params so the
// controls reflect the URL after a reload.
const search = ref(props.filters.search ?? '');
const sortBy = ref<string>('created_at');
const sortDesc = ref(true);

// TanStack table drives pagination/row-count only; rows render as cards below.
const pagination = ref<PaginationState>({
    pageIndex: props.buildings.current_page - 1,
    pageSize: props.buildings.per_page,
});

/**
 * Send the current search, sort and page to the backend (PaginatedRequest):
 * page, per_page, sort, direction, search.
 */
function fetchWithParams(): void {
    const params: Record<string, QueryValue> = {
        page: pagination.value.pageIndex + 1,
        per_page: pagination.value.pageSize,
        sort: sortBy.value,
        direction: sortDesc.value ? 'desc' : 'asc',
    };

    const trimmed = search.value.trim();

    if (trimmed.length > 0) {
        params.search = trimmed;
    }

    router.get(buildingsIndex().url, params, {
        preserveState: true,
        preserveScroll: false,
        replace: true,
    });
}

// Searching resets to the first page so results are not hidden on a later page.
let searchTimer: ReturnType<typeof setTimeout> | undefined;
function onSearch(value: string): void {
    search.value = value;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        pagination.value.pageIndex = 0;
        fetchWithParams();
    }, 300);
}

function onSortChange(value: string): void {
    sortBy.value = value;
    fetchWithParams();
}

function toggleDirection(): void {
    sortDesc.value = !sortDesc.value;
    fetchWithParams();
}

function clearSearch(): void {
    search.value = '';
    pagination.value.pageIndex = 0;
    fetchWithParams();
}

const isFiltered = computed(() => search.value.trim().length > 0);

// A minimal column set so useVueTable can drive manual pagination correctly.
const columns: ColumnDef<Building>[] = [{ accessorKey: 'name' }];

const table = useVueTable({
    get data() {
        return props.buildings.data;
    },
    columns,
    manualPagination: true,
    rowCount: props.buildings.total,
    state: {
        get sorting(): SortingState {
            return [{ id: sortBy.value, desc: sortDesc.value }];
        },
        get pagination() {
            return {
                pageIndex: props.buildings.current_page - 1,
                pageSize: props.buildings.per_page,
            };
        },
    },
    onPaginationChange: (updaterOrValue) => {
        const next =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(pagination.value)
                : updaterOrValue;
        pagination.value = next;
        fetchWithParams();
    },
    getCoreRowModel: getCoreRowModel(),
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-1 items-center gap-2">
                <div class="relative w-full max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        :model-value="search"
                        placeholder="Buscar edificios..."
                        class="h-9 pl-8"
                        @input="onSearch($event.target.value)"
                    />
                </div>
                <Button
                    v-if="isFiltered"
                    variant="ghost"
                    class="h-9 px-2 lg:px-3"
                    @click="clearSearch"
                >
                    Limpiar
                    <X class="ml-2 h-4 w-4" />
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Ordenar por</span>
                <Select
                    :model-value="sortBy"
                    @update:model-value="onSortChange"
                >
                    <SelectTrigger class="h-9 w-44">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    variant="outline"
                    size="icon"
                    class="h-9 w-9"
                    :title="sortDesc ? 'Descendente' : 'Ascendente'"
                    @click="toggleDirection"
                >
                    <ArrowDownAZ v-if="sortDesc" class="h-4 w-4" />
                    <ArrowUpAZ v-else class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div
            v-if="buildings.data.length"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <BuildingCard
                v-for="building in buildings.data"
                :key="building.id"
                :building="building"
            />
        </div>
        <div
            v-else
            class="flex h-40 items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground"
        >
            No se encontraron edificios.
        </div>

        <DataTablePagination
            :table="table"
            :total="buildings.total"
            :per-page="buildings.per_page"
            :current-page="buildings.current_page"
            show-total
        />
    </div>
</template>
