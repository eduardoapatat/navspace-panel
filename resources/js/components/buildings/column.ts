import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import type { Building } from '@/components/buildings/building';
import BuildingRowActions from '@/components/buildings/BuildingRowActions.vue';
import DataTableColumnHeader from '@/components/data-table/DataTableColumnHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

const dateFormatter = new Intl.DateTimeFormat('es-CL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

export const columns: ColumnDef<Building>[] = [
    {
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                modelValue:
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value) =>
                    table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
                class: 'translate-y-0.5',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
                class: 'translate-y-0.5',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'name',
        meta: { label: 'Nombre' },
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Nombre' }),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'max-w-[500px] truncate font-medium' },
                row.getValue('name'),
            ),
    },
    {
        accessorKey: 'address',
        meta: { label: 'Dirección' },
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Dirección' }),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-muted-foreground' },
                row.getValue('address'),
            ),
    },
    {
        accessorKey: 'model_url',
        meta: { label: 'Modelo 3D' },
        enableSorting: false,
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Modelo 3D' }),
        cell: ({ row }) => {
            const hasModel = !!row.getValue('model_url');

            return h(
                Badge,
                { variant: hasModel ? 'default' : 'secondary' },
                () => (hasModel ? 'Cargado' : 'Sin modelo'),
            );
        },
    },
    {
        accessorKey: 'created_at',
        meta: { label: 'Creado' },
        header: ({ column }) =>
            h(DataTableColumnHeader, { column, title: 'Creado' }),
        cell: ({ row }) => {
            const raw = row.getValue('created_at') as string;

            return h(
                'div',
                { class: 'text-muted-foreground' },
                dateFormatter.format(new Date(raw)),
            );
        },
    },
    {
        id: 'actions',
        enableSorting: false,
        enableHiding: false,
        // No header text: just the three-dots actions menu per row.
        cell: ({ row }) =>
            h(
                'div',
                { class: 'flex justify-end' },
                h(BuildingRowActions, { building: row.original }),
            ),
    },
];
