<?php

namespace App\Services\AppleHealth;

use Carbon\CarbonImmutable;

class WorkoutRecordMapper
{
    /**
     * @param array<string, mixed> $workout
     * @return array<string, mixed>
     */
    public function mapForDatabase(array $workout): array
    {
        $distance = is_array($workout['distance'] ?? null) ? $workout['distance'] : null;
        $energy = is_array($workout['energy'] ?? null) ? $workout['energy'] : null;
        $swim = is_array($workout['swim'] ?? null) ? $workout['swim'] : null;

        return [
            'activity_key' => $workout['activityKey'] ?? null,
            'label' => $workout['label'] ?? null,
            'icon' => $workout['icon'] ?? null,
            'icon_symbol' => $workout['iconSymbol'] ?? null,
            'accent_color' => $workout['accentColor'] ?? null,
            'icon_background' => $workout['iconBackground'] ?? null,
            'start_date' => isset($workout['startDate']) ? $this->parseCarbon($workout['startDate']) : null,
            'end_date' => isset($workout['endDate']) ? $this->parseCarbon($workout['endDate']) : null,
            'duration_seconds' => $workout['durationSeconds'] ?? 0,
            'duration_text' => $workout['durationText'] ?? null,
            'distance_value' => $distance['value'] ?? null,
            'distance_unit' => $distance['unit'] ?? null,
            'energy_value' => $energy['value'] ?? null,
            'energy_unit' => $energy['unit'] ?? null,
            'swim_value' => $swim['value'] ?? null,
            'swim_unit' => $swim['unit'] ?? null,
            'metadata' => $workout['metadata'] ?? null,
            'statistics' => $workout['statistics'] ?? null,
            'events' => $workout['events'] ?? null,
            'source_name' => $workout['sourceName'] ?? null,
            'total_flights_climbed' => $workout['totalFlightsClimbed'] ?? null,
            'total_elevation_gain' => $workout['totalElevationGain'] ?? null,
            'total_elevation_gain_unit' => $workout['totalElevationGainUnit'] ?? null,
        ];
    }

    private function parseCarbon($value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
