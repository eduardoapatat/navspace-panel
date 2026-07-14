<?php

namespace App\Enums;

/**
 * Fixed system type of a destination's schedule. Independent from SpaceType,
 * which is admin-created and free. The admin picks this from a fixed list, so
 * the app (and Unity) always knows how to show it: 'academic' opens the PDF,
 * 'attention' shows the text schedule and the responsible contact.
 */
enum ScheduleType: string
{
    case Attention = 'attention';
    case Academic = 'academic';

    /**
     * Options for a select input: value + label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::Attention->value, 'label' => 'Atención'],
            ['value' => self::Academic->value, 'label' => 'Académico'],
        ];
    }
}
