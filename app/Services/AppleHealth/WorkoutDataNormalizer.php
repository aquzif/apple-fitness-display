<?php

namespace App\Services\AppleHealth;

use Carbon\CarbonImmutable;

class WorkoutDataNormalizer
{
    public function __construct(
        protected array $workoutTypes,
        protected array $defaultType
    ) {
        $this->defaultType['iconSymbol'] = $this->defaultType['iconSymbol'] ?? $this->defaultType['icon'] ?? null;
    }

    /**
     * @param array<int, array<string, mixed>> $workoutNodes
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $workoutNodes): array
    {
        $normalized = [];

        foreach ($workoutNodes as $index => $node) {
            $workout = $this->normalizeWorkout($node, $index);
            if ($workout !== null) {
                $normalized[] = $workout;
            }
        }

        usort($normalized, function (array $a, array $b) {
            $dateA = isset($a['startDate']) ? strtotime((string) $a['startDate']) ?: 0 : 0;
            $dateB = isset($b['startDate']) ? strtotime((string) $b['startDate']) ?: 0 : 0;

            return $dateB <=> $dateA;
        });

        return $normalized;
    }

    /**
     * @param array<string, mixed> $node
     */
    protected function normalizeWorkout(array $node, int $index): ?array
    {
        if ($node === [] || ! is_array($node)) {
            return null;
        }

        $metadata = $this->normalizeMetadata($node['MetadataEntry'] ?? null);
        $statistics = $this->normalizeStatistics($node['WorkoutStatistics'] ?? null);
        $events = $this->normalizeEvents($node['WorkoutEvent'] ?? null, $metadata);

        $start = $this->parseDate($node['startDate'] ?? null, $metadata);
        $end = $this->parseDate($node['endDate'] ?? null, $metadata);

        $durationSeconds = $this->normalizeDuration($node['duration'] ?? null, $node['durationUnit'] ?? null);
        $durationText = $this->formatDuration($durationSeconds);

        $distance = $this->parseQuantity(
            $node['totalDistance'] ?? null,
            $node['totalDistanceUnit'] ?? ($metadata['HKQuantityTypeIdentifierDistanceWalkingRunningUnit'] ?? null)
        );
        $energy = $this->parseQuantity($node['totalEnergyBurned'] ?? null, $node['totalEnergyBurnedUnit'] ?? null);
        $swim = $this->parseQuantity($node['totalSwimmingStrokeCount'] ?? null, $node['totalSwimmingStrokeCountUnit'] ?? null);

        $activityKey = $node['workoutActivityType'] ?? null;
        $typeConfig = $this->resolveWorkoutType($activityKey);

        return [
            'id' => sprintf('%s-%d', $activityKey ?? 'workout', $index),
            'activityKey' => $activityKey,
            'label' => $typeConfig['label'] ?? 'Workout',
            'icon' => $typeConfig['icon'] ?? null,
            'iconSymbol' => $typeConfig['iconSymbol'] ?? null,
            'accentColor' => $typeConfig['accentColor'] ?? null,
            'iconBackground' => $typeConfig['iconBackground'] ?? null,
            'startDate' => $start?->toIso8601String(),
            'endDate' => $end?->toIso8601String(),
            'durationSeconds' => $durationSeconds,
            'durationText' => $durationText,
            'distance' => $distance,
            'energy' => $energy,
            'swim' => $swim,
            'metadata' => $metadata,
            'statistics' => $statistics,
            'events' => $events,
            'sourceName' => $node['sourceName'] ?? null,
            'totalFlightsClimbed' => $this->parseNumber($node['totalFlightsClimbed'] ?? null),
            'totalElevationGain' => $this->parseNumber($node['totalElevationGain'] ?? null),
            'totalElevationGainUnit' => $node['totalElevationGainUnit'] ?? null,
        ];
    }

    /**
     * @param array<int|string, mixed>|mixed $metadata
     * @return array<string, mixed>
     */
    protected function normalizeMetadata(mixed $metadata): array
    {
        $entries = $this->toArray($metadata);
        $normalized = [];

        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['key'])) {
                $normalized[$entry['key']] = $entry['value']
                    ?? $entry['doubleValue']
                    ?? $entry['intValue']
                    ?? $entry['stringValue']
                    ?? null;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int|string, mixed>|mixed $statistics
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeStatistics(mixed $statistics): array
    {
        $entries = $this->toArray($statistics);
        $normalized = [];

        foreach ($entries as $stat) {
            if (! is_array($stat)) {
                continue;
            }

            $normalized[] = [
                'type' => $stat['type'] ?? null,
                'unit' => $stat['unit'] ?? null,
                'sum' => $this->parseNumber($stat['sum'] ?? null),
                'minimum' => $this->parseNumber($stat['minimum'] ?? null),
                'maximum' => $this->parseNumber($stat['maximum'] ?? null),
                'average' => $this->parseNumber($stat['average'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int|string, mixed>|mixed $events
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeEvents(mixed $events, array $metadata): array
    {
        $entries = $this->toArray($events);
        $normalized = [];

        foreach ($entries as $event) {
            if (! is_array($event)) {
                continue;
            }

            $normalized[] = [
                'type' => $event['type'] ?? null,
                'startDate' => isset($event['date']) ? $this->parseDate($event['date'], $metadata)?->toIso8601String() : null,
                'duration' => $this->normalizeDuration($event['duration'] ?? null, $event['durationUnit'] ?? null),
            ];
        }

        return $normalized;
    }

    protected function normalizeDuration(mixed $value, mixed $unit): int
    {
        if ($value === null) {
            return 0;
        }

        $numeric = $this->parseNumber($value) ?? 0.0;

        if (! is_string($unit) || $unit === '') {
            return (int) round($numeric * 60);
        }

        $normalized = strtolower($unit);

        return match (true) {
            str_starts_with($normalized, 'sec') => (int) round($numeric),
            str_starts_with($normalized, 'hr'), str_starts_with($normalized, 'hour') => (int) round($numeric * 3600),
            default => (int) round($numeric * 60),
        };
    }

    /**
     * @return array{value: float, unit: ?string}|null
     */
    protected function parseQuantity(mixed $value, mixed $unit): ?array
    {
        $numeric = $this->parseNumber($value);

        if ($numeric === null) {
            return null;
        }

        return [
            'value' => $numeric,
            'unit' => is_string($unit) && $unit !== '' ? $unit : null,
        ];
    }

    protected function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    protected function parseDate(mixed $value, array $metadata): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $timezone = $metadata['HKTimeZone'] ?? $metadata['HKMetadataKeyTimeZone'] ?? null;

        try {
            return $timezone
                ? CarbonImmutable::parse($value, $timezone)
                : CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function formatDuration(int $totalSeconds): string
    {
        $seconds = max(0, $totalSeconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    protected function resolveWorkoutType(?string $activityKey): array
    {
        return $this->workoutTypes[$activityKey] ?? $this->defaultType;
    }

    /**
     * @return array<int, mixed>
     */
    protected function toArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return array_is_list($value) ? $value : [$value];
        }

        return [$value];
    }
}
