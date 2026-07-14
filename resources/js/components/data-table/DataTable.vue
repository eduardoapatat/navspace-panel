<script setup lang="ts" generic="TData">
import { router } from '@inertiajs/vue3';
import type {
    ColumnDef,
    ColumnFiltersState,
    PaginationState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { nextTick, ref } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { valueUpdater } from '@/components/ui/table/utils';
import type { PaginatedResponse } from '@/types';

type QueryValue = string | number | string[] | undefined;

interface QueryBuilderContext {
    sorting: SortingState;
    columnFilters: ColumnFiltersState;
    pagination: PaginationState;
}

interface DataTableProps {
    columns: ColumnDef<TData, any>[];
    rows: PaginatedResponse<TData>;
    endpoint: string;
    queryBuilder?: (context: QueryBuilderContext) => Record<string, QueryValue>;
}
const props = defineProps<DataTableProps>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const pagination = ref<PaginationState>({
    pageIndex: props.rows.current_page - 1,
    pageSize: props.rows.per_page,
});

function buildDefaultParams(): Record<string, QueryValue> {
    const page = pagination.value.pageIndex + 1;
    const perPage = pagination.value.pageSize;
    const sort = sorting.value[0]?.id;
    const direction = sorting.value[0]
        ? sorting.value[0].desc
            ? 'desc'
            : 'asc'
        : 'asc';
    const dynamicFilters = columnFilters.value.reduce<
        Record<string, QueryValue>
    >((params, filter) => {
        if (filter.value == null) {
            return params;
        }

        if (Array.isArray(filter.value)) {
            params[filter.id] =
                filter.value.length > 0 ? filter.value : undefined;

            return params;
        }

        const normalizedValue = String(filter.value).trim();
        params[filter.id] =
            normalizedValue.length > 0 ? normalizedValue : undefined;

        return params;
    }, {});

    return {
        page,
        per_page: perPage,
        ...(sort && { sort }),
        direction,
        ...dynamicFilters,
    };
}

function buildTableParams(): Record<string, QueryValue> {
    if (props.queryBuilder) {
        return props.queryBuilder({
            sorting: sorting.value,
            columnFilters: columnFilters.value,
            pagination: pagination.value,
        });
    }

    return buildDefaultParams();
}

function fetchWithParams(): void {
    router.get(props.endpoint, buildTableParams(), {
        preserveState: true,
        preserveScroll: false,
        replace: true,
    });
}

function resetFilters(): void {
    columnFilters.value = [];
    pagination.value.pageIndex = 0;
    fetchWithParams();
}

const table = useVueTable({
    get data() {
        return props.rows.data;
    },
    get columns() {
        return props.columns;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get pagination() {
            return {
                pageIndex: props.rows.current_page - 1,
                pageSize: props.rows.per_page,
            };
        },
    },
    enableRowSelection: true,
    manualSorting: true,
    manualPagination: true,
    rowCount: props.rows.total,
    onSortingChange: (updaterOrValue) => {
        valueUpdater(updaterOrValue, sorting);
        fetchWithParams();
    },
    onColumnFiltersChange: (updaterOrValue) => {
        valueUpdater(updaterOrValue, columnFilters);
        pagination.value.pageIndex = 0;
        nextTick(() => fetchWithParams());
    },
    onColumnVisibilityChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, rowSelection),
    onPaginationChange: (updaterOrValue) => {
        valueUpdater(updaterOrValue, pagination);
        fetchWithParams();
    },
    getCoreRowModel: getCoreRowModel(),
});
</script>

<template>
    <div class="space-y-4">
        <slot name="toolbar" :table="table" :reset-filters="resetFilters" />

        <Table>
            <TableHeader>
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                    >
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="table.getRowModel().rows?.length">
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :data-state="row.getIsSelected() && 'selected'"
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                </template>

                <TableRow v-else>
                    <TableCell
                        :colspan="columns.length"
                        class="h-24 text-center"
                    >
                        No results.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
        <slot name="pagination" :table="table" :rows="rows" />
    </div>
</template>
