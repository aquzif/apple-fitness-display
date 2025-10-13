<?php

namespace App\Livewire;

use App\Models\Workout;
use App\Models\WorkoutImport;
use App\Services\AppleHealth\WorkoutDataNormalizer;
use App\Services\AppleHealth\WorkoutSummaryBuilder;
use App\Services\AppleHealth\WorkoutXmlParser;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout as LivewireLayout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use NumberFormatter;

#[LivewireLayout('layouts.app')]
class WorkoutDashboard extends Component
{
    use WithFileUploads;

    public ?WorkoutImport $import = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $workouts = [];

    /**
     * @var array<string, mixed>
     */
    public array $summary = [];

    public string $statusMessage = '';

    public string $filter = 'all';

    public $upload = null;

    /**
     * @var array<string, mixed>
     */
    protected array $defaultType = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $workoutTypes = [];

    /**
     * @var array<string, string>
     */
    protected array $iconAssets = [];

    protected ?NumberFormatter $numberFormatter = null;

    public function mount(): void
    {
        $config = config('workout_types');
        $this->defaultType = $config['default'] ?? [];
        $this->workoutTypes = $config['types'] ?? [];
        $this->iconAssets = config('icon_assets');

        $this->loadLatestImport();
    }

    public function updatedFilter(string $value): void
    {
        $this->filter = $value;
    }

    public function handleUpload(): void
    {
        $this->validate([
            'upload' => [
                'required',
            ],
        ], [
            'upload.required' => __('Wybierz plik exportu Apple Health.'),
            'upload.mimetypes' => __('Obsługiwany jest wyłącznie plik XML.'),
            'upload.mimes' => __('Obsługiwany jest wyłącznie plik XML.'),
            'upload.max' => __('Plik jest zbyt duży (limit 10 GB).'),
        ]);

        $user = Auth::user();

        if (! $user) {
            return;
        }

        /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
        $file = $this->upload;

        $path = $file->store('apple-health');
        $absolutePath = Storage::path($path);

        $parser = app(WorkoutXmlParser::class);
        $normalizer = new WorkoutDataNormalizer($this->workoutTypes, $this->defaultType);
        $summaryBuilder = app(WorkoutSummaryBuilder::class);

        try {
            $rawWorkouts = $parser->parseWorkoutsFromFile($absolutePath);
            $normalized = $normalizer->normalize($rawWorkouts);
            $summary = $summaryBuilder->build($normalized);

        } catch (\Throwable $exception) {
            report($exception);
            $this->statusMessage = __('Nie udało się przetworzyć pliku XML. Upewnij się, że to plik exportu Apple Health.');
            $this->addError('upload', __('Nie udało się przetworzyć pliku XML.'));
            Storage::delete($path);
            return;
        }

        Storage::delete($path);

        if ($normalized === []) {
            $this->resetDashboard();
            $this->statusMessage = __('Nie znaleziono treningów w tym eksporcie.');
            $this->upload = null;
            return;
        }

        DB::transaction(function () use ($user, $summary, $normalized, $file) {
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


        $records = array_map(fn (array $workout) => $this->mapWorkoutForDatabase($workout), $normalized);
            $import->workouts()->createMany($records);

            $this->import = $import->load('workouts');
        });

        $this->upload = null;

        $this->loadLatestImport();

        $count = $this->summary['totalCount'] ?? 0;
        $this->statusMessage = trans_choice('Załadowano :count trening.|Załadowano :count treningi.|Załadowano :count treningów.', $count, ['count' => $count]);
    }

    public function render(): View
    {

        return view('livewire.workout-dashboard', [
            'groupedWorkouts' => $this->groupedWorkouts(),
            'summaryView' => [
                'count' => $this->summary['totalCount'] ?? 0,
                'duration' => $this->formatSummaryDuration($this->summary['totalDurationSeconds'] ?? 0),
                'energy' => sprintf('%s %s', $this->formatNumber($this->summary['totalEnergy'] ?? 0), $this->summary['totalEnergyUnit'] ?? 'kcal'),
                'burntEnergy' => sprintf('%s %s', $this->formatNumber($this->summary['totalBurntEnergy'] ?? 0), $this->summary['totalBurntEnergyUnit'] ?? 'kcal'),
                'distance' => sprintf('%s %s', $this->formatNumber($this->summary['totalDistance'] ?? 0), $this->summary['totalDistanceUnit'] ?? 'km'),
            ],
        ]);
    }

    protected function loadLatestImport(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->resetDashboard();
            return;
        }

        $import = $user->workoutImports()->latest()->with('workouts')->first();
        if (! $import) {
            $this->import = null;
            $this->resetDashboard();
            return;
        }

        $this->import = $import;
        $this->summary = [
            'totalCount' => $import->total_count,
            'totalDurationSeconds' => $import->total_duration_seconds,
            'totalEnergy' => (float) $import->total_energy,
            'totalEnergyUnit' => $import->total_energy_unit,
            'totalBurntEnergy' => (float) $import->total_burnt_energy,
            'totalBurntEnergyUnit' => $import->total_burnt_energy_unit,
            'totalDistance' => (float) $import->total_distance,
            'totalDistanceUnit' => $import->total_distance_unit,
        ];

        $this->workouts = $import->workouts
            ->sortByDesc('start_date')
            ->map(fn (Workout $workout) => $this->decorateWorkout($this->mapWorkoutForDisplay($workout->toArray())))
            ->values()
            ->all();

        $count = $this->summary['totalCount'];
        $this->statusMessage = trans_choice('Załadowano :count trening.|Załadowano :count treningi.|Załadowano :count treningów.', $count, ['count' => $count]);
    }

    protected function resetDashboard(): void
    {
        $this->summary = [
            'totalCount' => 0,
            'totalDurationSeconds' => 0,
            'totalEnergy' => 0.0,
            'totalEnergyUnit' => 'kcal',
            'totalDistance' => 0.0,
            'totalDistanceUnit' => 'km',
        ];
        $this->workouts = [];
        $this->statusMessage = __('Załaduj plik exportu, aby zobaczyć listę treningów.');
    }

    protected function groupedWorkouts(): array
    {
        $workouts = $this->filteredWorkouts();
        $groups = [];

        foreach ($workouts as $workout) {
            $date = isset($workout['startDate']) ? $this->parseCarbon($workout['startDate']) : null;
            $monthKey = $date ? $date->format('Y-m') : 'unknown';

            if (! isset($groups[$monthKey])) {
                $groups[$monthKey] = [
                    'label' => $date ? $this->capitalize($date->locale('pl')->translatedFormat('F Y')) : __('Bez daty'),
                    'days' => [],
                ];
            }

            $dayKey = $date ? $date->format('Y-m-d') : 'unknown';
            if (! isset($groups[$monthKey]['days'][$dayKey])) {
                $groups[$monthKey]['days'][$dayKey] = [
                    'label' => [
                        'weekday' => $date ? $this->capitalize($date->locale('pl')->translatedFormat('l')) : __('Bez daty'),
                        'date' => $date ? $date->locale('pl')->translatedFormat('d.m.Y') : __('Bez daty'),
                    ],
                    'items' => [],
                ];
            }

            $groups[$monthKey]['days'][$dayKey]['items'][] = $workout;
        }

        krsort($groups);



        return array_map(function (array $month) {
            $days = $month['days'];
            krsort($days);
            $month['days'] = array_values($days);
            return $month;
        }, $groups);
    }

    protected function filteredWorkouts(): array
    {
        return array_values(array_filter($this->workouts, function (array $workout): bool {
            $key = strtolower((string) ($workout['activityKey'] ?? ''));

            return match ($this->filter) {
                'mind' => str_contains($key, 'mind'),
                'cycling' => str_contains($key, 'cycle'),
                'all' => true,
                default => $key === '' || ! str_contains($key, 'mind'),
            };
        }));
    }

    protected function mapWorkoutForDatabase(array $workout): array
    {

        $distance = is_array($workout['distance'] ?? null) ? $workout['distance'] : null;
        $energy = is_array($workout['energy'] ?? null) ? $workout['energy'] : null;
        $swim = is_array($workout['swim'] ?? null) ? $workout['swim'] : null;


        $toReturn = [
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

        //dd($toReturn,$workout);
        return $toReturn;
    }

    /**
     * @param array<string, mixed> $workout
     * @return array<string, mixed>
     */
    protected function mapWorkoutForDisplay(array $workout): array
    {
        return [
            'id' => $workout['id'] ?? null,
            'activityKey' => $workout['activity_key'] ?? null,
            'label' => $workout['label'] ?? null,
            'icon' => $workout['icon'] ?? null,
            'iconSymbol' => $workout['icon_symbol'] ?? null,
            'accentColor' => $workout['accent_color'] ?? null,
            'iconBackground' => $workout['icon_background'] ?? null,
            'startDate' => isset($workout['start_date']) ? $this->parseCarbon($workout['start_date'])?->toIso8601String() : null,
            'endDate' => isset($workout['end_date']) ? $this->parseCarbon($workout['end_date'])?->toIso8601String() : null,
            'durationSeconds' => $workout['duration_seconds'] ?? 0,
            'durationText' => $workout['duration_text'] ?? null,
            'distance' => $this->measurementArray($workout['distance_value'] ?? null, $workout['distance_unit'] ?? null),
            'energy' => $this->measurementArray($workout['energy_value'] ?? null, $workout['energy_unit'] ?? null),
            'swim' => $this->measurementArray($workout['swim_value'] ?? null, $workout['swim_unit'] ?? null),
            'metadata' => $workout['metadata'] ?? [],
            'statistics' => $workout['statistics'] ?? [],
            'events' => $workout['events'] ?? [],
            'sourceName' => $workout['source_name'] ?? null,
            'totalFlightsClimbed' => $workout['total_flights_climbed'] ?? null,
            'totalElevationGain' => $workout['total_elevation_gain'] ?? null,
            'totalElevationGainUnit' => $workout['total_elevation_gain_unit'] ?? null,
        ];
    }

    protected function decorateWorkout(array $workout): array
    {
        $type = $this->workoutTypes[$workout['activityKey']] ?? $this->defaultType;

        $symbol = $this->resolveSymbol([
            $type['iconSymbol'] ?? null,
            $type['icon'] ?? null,
            $workout['iconSymbol'] ?? null,
            $workout['icon'] ?? null,
            $this->defaultType['iconSymbol'] ?? null,
            $this->defaultType['icon'] ?? null,
        ]);

        $workout['type'] = $type;
        $workout['symbol'] = $symbol;
        $workout['iconUri'] = $this->iconAssets[$symbol] ?? ($this->iconAssets[$this->defaultType['iconSymbol'] ?? 'bolt.circle.fill'] ?? '');
        $workout['iconBackground'] = $type['iconBackground'] ?? $workout['iconBackground'] ?? $this->defaultType['iconBackground'] ?? 'rgba(48, 209, 88, 0.18)';
        $workout['accentColor'] = $type['accentColor'] ?? $workout['accentColor'] ?? $this->defaultType['accentColor'] ?? '#30d158';

        return $workout;
    }

    protected function resolveSymbol(array $candidates): string
    {
        $pattern = '/^[a-z0-9.]+$/i';
        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            if (isset($this->iconAssets[$candidate])) {
                return $candidate;
            }

            if (preg_match($pattern, $candidate)) {
                return $candidate;
            }
        }

        return $this->defaultType['iconSymbol'] ?? 'bolt.circle.fill';
    }

    protected function measurementArray($value, $unit): ?array
    {
        if ($value === null) {
            return null;
        }

        return [
            'value' => (float) $value,
            'unit' => $unit ?: null,
        ];
    }

    public function formatNumber(float $value): string
    {
        $formatter = $this->numberFormatter ??= tap(new NumberFormatter('pl_PL', NumberFormatter::DECIMAL), function (NumberFormatter $formatter) use ($value) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
        });

        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $value >= 10 ? 1 : 2);

        $formatted = $formatter->format($value);

        return $formatted === false ? number_format($value, $value >= 10 ? 1 : 2, ',', ' ') : $formatted;
    }

    public function formatSummaryDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0
            ? sprintf('%dh %dm', $hours, $minutes)
            : sprintf('%dm', $minutes);
    }

    public function formatGoal(?string $goal): string
    {
        if (! $goal) {
            return '';
        }

        $normalized = Str::lower($goal);

        return match (true) {
            str_contains($normalized, 'open') => __('Cel: otwarty'),
            str_contains($normalized, 'time') => __('Cel: czas'),
            str_contains($normalized, 'distance') => __('Cel: dystans'),
            str_contains($normalized, 'calorie') => __('Cel: kalorie'),
            default => __('Cel: :goal', ['goal' => $goal]),
        };
    }

    public function formatActivityKey(?string $key): string
    {
        if (! $key) {
            return __('Trening');
        }

        $label = Str::of($key)
            ->replace('HKWorkoutActivityType', '')
            ->snake()
            ->replace('_', ' ')
            ->title();

        return (string) $label;
    }

    protected function parseCarbon($value): ?CarbonImmutable
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

    protected function capitalize(string $value): string
    {
        return Str::ucfirst($value);
    }
}
