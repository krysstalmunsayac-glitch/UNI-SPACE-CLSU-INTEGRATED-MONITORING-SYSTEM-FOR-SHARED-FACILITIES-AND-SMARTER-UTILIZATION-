<?php

use App\Support\CalendarColor;

test('it generates distinct background and border colors for a value', function () {
    $colors = CalendarColor::forValue('Conference Hall');

    expect($colors)
        ->toHaveKeys(['backgroundColor', 'borderColor'])
        ->and($colors['backgroundColor'])->toMatch('/^#[0-9a-fA-F]{6}$/')
        ->and($colors['borderColor'])->toMatch('/^#[0-9a-fA-F]{6}$/');
});
