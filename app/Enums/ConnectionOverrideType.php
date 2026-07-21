<?php

namespace App\Enums;

/**
 * Manual exception applied to the A* graph Unity builds at runtime.
 *
 * The wall raycast is disabled, so connections are decided only by proximity
 * (max_distance) and a vertical limit (max_y_distance). That misses two cases
 * the admin must be able to fix by hand from the 3D editor:
 * 'blocked' — never link these two nodes (they sit across a wall);
 * 'forced'  — always link them (stairs, where the vertical limit would win).
 */
enum ConnectionOverrideType: string
{
    case Blocked = 'blocked';
    case Forced = 'forced';

    /**
     * Options for a select input: value + label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::Blocked->value, 'label' => 'Nunca unir'],
            ['value' => self::Forced->value, 'label' => 'Unir siempre'],
        ];
    }
}
