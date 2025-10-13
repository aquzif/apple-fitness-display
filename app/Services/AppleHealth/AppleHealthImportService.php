<?php

namespace App\Services\AppleHealth;

use App\Models\User;
use App\Models\Weight;
use App\Models\WorkoutImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppleHealthImportService
{
    public function __construct(
        private WorkoutXmlParser $parser,
        private WorkoutSummaryBuilder $summaryBuilder,
        private WorkoutRecordMapper $recordMapper,
    ) {
    }

    /**
     * @return array{import: WorkoutImport, summary: array<string, mixed>, weights_imported: int}
     */
    public function import(User $user, TemporaryUploadedFile $file): array
    {
        $config = config('workout_types');
        $defaultType = $config['default'] ?? [];
        $workoutTypes = $config['types'] ?? [];
        $normalizer = new WorkoutDataNormalizer($workoutTypes, $defaultType);

        $path = $file->store('apple-health');
        $absolutePath = Storage::path($path);

        try {
            $rawWorkouts = $this->parser->parseWorkoutsFromFile($absolutePath);
            $normalizedWorkouts = $normalizer->normalize($rawWorkouts);
            $summary = $this->summaryBuilder->build($normalizedWorkouts);
            $weightRecords = $this->parser->parseBodyMassRecordsFromFile($absolutePath);
        } finally {
            Storage::delete($path);
        }

        if ($normalizedWorkouts === []) {
            throw new \RuntimeException('Brak treningów w pliku eksportu.');
        }

        return DB::transaction(function () use ($user, $summary, $normalizedWorkouts, $file, $weightRecords) {
            $import = $user->workoutImports()->create([
                'original_filename' => $file->getClientOriginalName(),
                'total_count' => $summary['totalCount'],
                'total_duration_seconds' => $summary['totalDurationSeconds'],
                'total_energy' => $summary['totalEnergy'],
                'total_energy_unit' => $summary['totalEnergyUnit'],
                'total_burnt_energy' => $summary['totalBurntEnergy'],
                'total_burnt_energy_unit' => $summary['totalBurntEnergyUnit'],
                'total_distance' => $summary['totalDistance'],
                'total_distance_unit' => $summary['totalDistanceUnit'],
            ]);

            $records = array_map(fn (array $workout) => $this->recordMapper->mapForDatabase($workout), $normalizedWorkouts);
            $import->workouts()->createMany($records);

            $weightsImported = $this->storeWeights($user, $weightRecords);

            return [
                'import' => $import->load('workouts'),
                'summary' => $summary,
                'weights_imported' => $weightsImported,
            ];
        });
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    protected function storeWeights(User $user, array $records): int
    {
        if ($records === []) {
            return 0;
        }

        $rows = [];
        $now = now();

        foreach ($records as $record) {
            $value = isset($record['value']) ? (float) $record['value'] : null;
            $startDate = $record['startDate'] ?? $record['creationDate'] ?? $record['endDate'] ?? null;

            if ($value === null || $startDate === null) {
                continue;
            }

            $recordedAt = $this->parseDate($startDate);
            if (! $recordedAt) {
                continue;
            }

            $rows[] = [
                'user_id' => $user->id,
                'recorded_at' => $recordedAt,
                'value' => $value,
                'unit' => $record['unit'] ?? 'kg',
                'source_name' => $record['sourceName'] ?? null,
                'device' => $record['device'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        Weight::query()->upsert($rows, ['user_id', 'recorded_at'], ['value', 'unit', 'source_name', 'device', 'updated_at']);

        return count($rows);
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
