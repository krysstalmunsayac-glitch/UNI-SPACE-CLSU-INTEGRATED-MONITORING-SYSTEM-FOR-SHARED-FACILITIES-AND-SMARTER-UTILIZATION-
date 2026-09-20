<?php

use App\Http\Controllers\FacilityRequestController;
use Illuminate\Validation\ValidationException;

function invokeBookingDurationValidator(string $start, string $end): void
{
    $controller = (new ReflectionClass(FacilityRequestController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'validateBookingDuration');

    $method->invoke($controller, $start, $end);
}

it('accepts a booking lasting at least one hour', function () {
    invokeBookingDurationValidator('09:00', '10:00');

    expect(true)->toBeTrue();
});

it('rejects invalid booking durations', function (string $start, string $end) {
    invokeBookingDurationValidator($start, $end);
})->with([
    'end before start' => ['10:00', '09:00'],
    'end equals start' => ['10:00', '10:00'],
    'duration below one hour' => ['10:00', '10:59'],
])->throws(ValidationException::class, 'A booking must be at least 1 hour.');
