<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsDateRange
{
    public function analyticsDateRange(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = isset($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : today()->subMonth()->startOfDay();
        $dateTo = isset($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : today()->endOfDay();

        if ($dateFrom->diffInDays($dateTo) > 365) {
            $dateFrom = $dateTo->copy()->subDays(365)->startOfDay();
        }

        return [$dateFrom, $dateTo];
    }

}
