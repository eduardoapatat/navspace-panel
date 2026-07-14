<script setup lang="ts" generic="TData">
import {
    CalendarDate,
    
    getLocalTimeZone
} from '@internationalized/date';
import type {DateValue} from '@internationalized/date';
import { CalendarIcon, XCircle } from '@lucide/vue';
import type { Column } from '@tanstack/vue-table';
import type { DateRange } from 'reka-ui';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { Separator } from '@/components/ui/separator';

/**
 * Filtro de rango de fechas, adaptado de tablecn (sadmann7) a Vue.
 *
 * El valor del filtro de columna se guarda como [fromISO, toISO] (strings YYYY-MM-DD),
 * que es lo que el backend espera en {columna}_from / {columna}_to.
 */
interface Props {
    column?: Column<TData, unknown>;
    title?: string;
}

const props = defineProps<Props>();

const dateFormatter = new Intl.DateTimeFormat('es-CL', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

/** Convierte 'YYYY-MM-DD' en un DateValue de @internationalized/date. */
function toDateValue(value: string): DateValue {
    const [year, month, day] = value.split('-').map(Number);

    return new CalendarDate(year, month, day);
}

/** Convierte un DateValue en 'YYYY-MM-DD'. */
function toIso(value: DateValue): string {
    return value.toString();
}

/** Rango actual leído del filtro de la columna. */
const range = computed<DateRange>(() => {
    const value = props.column?.getFilterValue() as
        | [string, string]
        | undefined;

    return {
        start: value?.[0] ? toDateValue(value[0]) : undefined,
        end: value?.[1] ? toDateValue(value[1]) : undefined,
    };
});

const hasFilter = computed(() => {
    const value = props.column?.getFilterValue() as
        | [string, string]
        | undefined;

    return Boolean(value?.[0] || value?.[1]);
});

const label = computed(() => {
    const value = props.column?.getFilterValue() as
        | [string, string]
        | undefined;

    if (!value?.[0] && !value?.[1]) {
        return null;
    }

    const tz = getLocalTimeZone();
    const from = value?.[0]
        ? dateFormatter.format(toDateValue(value[0]).toDate(tz))
        : '...';
    const to = value?.[1]
        ? dateFormatter.format(toDateValue(value[1]).toDate(tz))
        : '...';

    return `${from} - ${to}`;
});

function onUpdate(value: DateRange): void {
    if (!value.start && !value.end) {
        props.column?.setFilterValue(undefined);

        return;
    }

    props.column?.setFilterValue([
        value.start ? toIso(value.start) : undefined,
        value.end ? toIso(value.end) : undefined,
    ]);
}

function onReset(event: Event): void {
    event.stopPropagation();
    props.column?.setFilterValue(undefined);
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <XCircle
                    v-if="hasFilter"
                    class="mr-2 h-4 w-4"
                    @click="onReset"
                />
                <CalendarIcon v-else class="mr-2 h-4 w-4" />
                {{ title }}
                <template v-if="label">
                    <Separator orientation="vertical" class="mx-2 h-4" />
                    <Badge
                        variant="secondary"
                        class="rounded-sm px-1 font-normal"
                    >
                        {{ label }}
                    </Badge>
                </template>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <RangeCalendar
                :model-value="range"
                @update:model-value="onUpdate"
            />
        </PopoverContent>
    </Popover>
</template>
