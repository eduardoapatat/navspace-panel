<script setup lang="ts">
import { X } from '@lucide/vue';
import type { Table } from '@tanstack/vue-table';
import { computed } from 'vue';
import type { Building } from '@/components/buildings/building';
import DataTableSortList from '@/components/data-table/DataTableSortList.vue';
import DataTableViewOptions from '@/components/data-table/DataTableViewOptions.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface DataTableToolbarProps {
    table: Table<Building>;
}

const props = defineProps<DataTableToolbarProps>();

const emit = defineEmits<{
    reset: [];
}>();

const isFiltered = computed(
    () => props.table.getState().columnFilters.length > 0,
);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex flex-1 items-center space-x-2">
            <Input
                placeholder="Buscar edificios..."
                :model-value="
                    (table.getColumn('name')?.getFilterValue() as string) ?? ''
                "
                class="h-8 w-37.5 lg:w-62.5"
                @input="
                    table.getColumn('name')?.setFilterValue($event.target.value)
                "
            />

            <Button
                v-if="isFiltered"
                variant="ghost"
                class="h-8 px-2 lg:px-3"
                @click="
                    table.resetColumnFilters();
                    emit('reset');
                "
            >
                Limpiar
                <X class="ml-2 h-4 w-4" />
            </Button>
        </div>
        <div class="flex items-center space-x-2">
            <DataTableSortList :table="table" />
            <DataTableViewOptions :table="table" />
        </div>
    </div>
</template>
