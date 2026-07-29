<?php

namespace App\Support;

class CalendarColor
{
    /**
     * Generate a stable palette color pair for a given value.
     */
    public static function forValue(string $value): array
    {
        $colors = [
            ['#3b82f6', '#2563eb'],
            ['#009639', '#007a2f'],
            ['#f59e0b', '#d97706'],
            ['#ef4444', '#dc2626'],
            ['#8b5cf6', '#7c3aed'],
            ['#14b8a6', '#0f766e'],
            ['#ec4899', '#db2777'],
            ['#6366f1', '#4f46e5'],
        ];

        $hash = 0;

        foreach (mb_str_split($value) as $char) {
            $hash = ((int) $hash * 31 + ord($char)) % 1000003;
        }

        $index = $hash % count($colors);

        $pair = $colors[$index];

        return [
            'backgroundColor' => $pair[0],
            'borderColor' => $pair[1],
        ];
    }
}
