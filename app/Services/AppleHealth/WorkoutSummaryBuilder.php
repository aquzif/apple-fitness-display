<?php

namespace App\Services\AppleHealth;

use App\Utils\WorkoutUtils;

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
                'totalBurntEnergy' => 0.0,
                'totalBurntEnergyUnit' => 'kcal',
                'totalEnergy' => 0.0,
                'totalEnergyUnit' => 'kcal',
                'totalDurationSeconds' => 0,
                'totalDistance' => 0.0,
                'totalDistanceUnit' => 'km',
            ];
        }

        return array_reduce($workouts, function (array $carry, array $workout) {
            $carry['totalCount'] += 1;

            $carry['totalEnergy'] += WorkoutUtils::getAllCalories($workout);
            $carry['totalBurntEnergy'] += WorkoutUtils::getBurntCalories($workout);

            if (isset($workout['distance']['value'])) {
                $carry['totalDistance'] += (float) $workout['distance']['value'];
                $carry['totalDistanceUnit'] = $workout['distance']['unit'] ?? $carry['totalDistanceUnit'];
            }

            $carry['totalDurationSeconds'] += (int) ($workout['durationSeconds'] ?? 0);

            return $carry;
        }, [
            'totalCount' => 0,
            'totalBurntEnergy' => 0.0,
            'totalBurntEnergyUnit' => 'kcal',
            'totalEnergy' => 0.0,
            'totalEnergyUnit' => 'kcal',
            'totalDurationSeconds' => 0,
            'totalDistance' => 0.0,
            'totalDistanceUnit' => 'km',
        ]);
    }
}
