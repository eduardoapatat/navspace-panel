<script setup lang="ts">
import type {
    ColumnDef,
    ColumnFiltersState,
    PaginationState,
    SortingState,
} from '@tanstack/vue-table';
import type { Building } from '@/components/buildings/building';
import BuildingTableToolbar from '@/components/buildings/BuildingTableToolbar.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import DataTablePagination from '@/components/data-table/DataTablePagination.vue';
import { index as buildingsIndex } from '@/routes/buildings';
import type { PaginatedResponse } from '@/types';

interface DataTableProps {
    columns: ColumnDef<Building, unknown>[];
    buildings: PaginatedResponse<Building>;
}

defineProps<DataTableProps>();

type QueryValue = string | number | string[] | undefined;

/**
 * Translate the table state (sorting, filters, page) into the params the
 * backend expects (PaginatedRequest): page, per_page, sort, direction, search.
 */
function buildBuildingParams(context: {
    sorting: SortingState;
    columnFilters: ColumnFiltersState;
    pagination: PaginationState;
}): Record<string, QueryValue> {
    const page = context.pagination.pageIndex + 1;
    const perPage = context.pagination.pageSize;
    // sort and direction only make sense together: if no column is sorted,
    // we send neither (avoids a lone direction=asc in the URL).
    const sort = context.sorting[0]?.id;
    const direction = sort
        ? context.sorting[0].desc
            ? 'desc'
            : 'asc'
        : undefined;

    const search = context.columnFilters.find((f) => f.id === 'name')?.value;

    return {
        page,
        per_page: perPage,
        ...(sort && { sort, direction }),
        ...(search != null &&
            String(search).trim() && { search: String(search).trim() }),
    };
}
</script>

<template>
    <DataTable
        :columns="columns"
        :rows="buildings"
        :endpoint="buildingsIndex().url"
        :query-builder="buildBuildingParams"
    >
        <template #toolbar="{ table, resetFilters }">
            <BuildingTableToolbar :table="table" @reset="resetFilters" />
        </template>

        <template #pagination="{ table, rows }">
            <DataTablePagination
                :table="table"
                :total="rows.total"
                :per-page="rows.per_page"
                :current-page="rows.current_page"
            />
        </template>
    </DataTable>
</template>
