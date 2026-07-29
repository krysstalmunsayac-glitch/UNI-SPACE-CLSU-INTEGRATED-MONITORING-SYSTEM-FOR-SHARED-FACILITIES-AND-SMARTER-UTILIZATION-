<?php

use App\Models\User;

it('accepts only supported Philippine mobile number formats', function (string $number, bool $valid) {
    expect((bool) preg_match(User::PH_CONTACT_REGEX, $number))->toBe($valid);
})->with([
    'local format' => ['09123456789', true],
    'international format' => ['+639123456789', true],
    'too short' => ['0912345678', false],
    'too long' => ['091234567890', false],
    'invalid prefix' => ['08123456789', false],
    'contains letters' => ['09123ABC789', false],
]);
