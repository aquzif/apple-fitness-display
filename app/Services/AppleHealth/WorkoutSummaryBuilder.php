<?php

namespace App\Services\AppleHealth;

class WorkoutSummaryBuilder
{
    /**
     * @param array<int, array<string, mixed>> $workouts
     * @return array<string, mixed>
     */
    public function build(array $workouts): array
    {
        if ($workouts === []) {
            return [
                'totalCount' => 0,
                'totalEnergy' => 0.0,
                'totalEnergyUnit' => 'kcal',
                'totalDurationSeconds' => 0,
                'totalDistance' => 0.0,
                'totalDistanceUnit' => 'km',
            ];
        }

        return array_reduce($workouts, function (array $carry, array $workout) {
            $carry['totalCount'] += 1;

            if (isset($workout['energy']['value'])) {
                $carry['totalEnergy'] += (float) $workout['energy']['value'];
                $carry['totalEnergyUnit'] = $workout['energy']['unit'] ?? $carry['totalEnergyUnit'];
            }

            if (isset($workout['distance']['value'])) {
                $carry['totalDistance'] += (float) $workout['distance']['value'];
                $carry['totalDistanceUnit'] = $workout['distance']['unit'] ?? $carry['totalDistanceUnit'];
            }

            $carry['totalDurationSeconds'] += (int) ($workout['durationSeconds'] ?? 0);

            return $carry;
        }, [
            'totalCount' => 0,
            'totalEnergy' => 0.0,
            'totalEnergyUnit' => 'kcal',
            'totalDurationSeconds' => 0,
            'totalDistance' => 0.0,
            'totalDistanceUnit' => 'km',
        ]);
    }
}
