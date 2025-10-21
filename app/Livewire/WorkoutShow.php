<?php

namespace App\Livewire;

use App\Models\Workout;
use App\Utils\WorkoutUtils;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout as LivewireLayout;
use Livewire\Component;

#[LivewireLayout('layouts.app')]
class WorkoutShow extends Component
{
    public Workout $workout;

    /**
     * @var array<int, array{minute: ?CarbonImmutable, min: ?int, max: ?int, samples: int}>
     */
    public array $heartrateMinutes = [];

    /**
     * @var array<string, float|int|null>
     */
    public array $heartrateSummary = [
        'min' => null,
        'max' => null,
        'avg' => null,
        'count' => 0,
    ];

    /**
     * @var array<string, mixed>
     */
    public array $workoutSummary = [];

    public function mount(Workout $workout): void
    {
        $workout->loadMissing([
            'heartrates' => fn ($query) => $query->orderBy('timestamp'),
            'import',
        ]);

        $userId = Auth::id();
        if (! $userId || $workout->import?->user_id !== $userId) {
            abort(404);
        }

        $this->workout = $workout;

        $this->heartrateMinutes = $this->buildHeartrateMinutes($workout);
        $this->heartrateSummary = $this->buildHeartrateSummary($workout);
        $this->workoutSummary = $this->buildWorkoutSummary($workout);
    }

    public function render(): View
    {
        return view('livewire.workout-show');
    }

    /**
     * @return array<int, array{minute: ?CarbonImmutable, min: ?int, max: ?int, samples: int}>
     */
    protected function buildHeartrateMinutes(Workout $workout): array
    {
        $collection = $workout->heartrates
            ->filter(fn ($entry) => $entry->timestamp && $entry->bpm !== null);

        if ($collection->isEmpty()) {
            return [];
        }

        return $collection
            ->groupBy(function ($entry) {
                return CarbonImmutable::instance($entry->timestamp)->startOfMinute()->format('c');
            })
            ->map(function (Collection $entries, string $minuteKey) {
                $minute = CarbonImmutable::parse($minuteKey);
                $values = $entries->pluck('bpm')->filter(fn ($value) => $value !== null)->map(fn ($value) => (int) $value);

                return [
                    'minute' => $minute,
                    'min' => $values->min(),
                    'max' => $values->max(),
                    'samples' => $values->count(),
                ];
            })
            ->sortBy(fn (array $row) => $row['minute'])
            ->values()
            ->all();
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function buildHeartrateSummary(Workout $workout): array
    {
        $values = $workout->heartrates
            ->pluck('bpm')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value);

        if ($values->isEmpty()) {
            return [
                'min' => null,
                'max' => null,
                'avg' => null,
                'count' => 0,
            ];
        }

        return [
            'min' => $values->min(),
            'max' => $values->max(),
            'avg' => round($values->avg(), 1),
            'count' => $values->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildWorkoutSummary(Workout $workout): array
    {
        $start = $workout->start_date instanceof CarbonImmutable
            ? $workout->start_date
            : ($workout->start_date ? CarbonImmutable::parse($workout->start_date) : null);
        $end = $workout->end_date instanceof CarbonImmutable
            ? $workout->end_date
            : ($workout->end_date ? CarbonImmutable::parse($workout->end_date) : null);

        $asArray = $workout->toArray();
        if (! isset($asArray['statistics']) || ! is_array($asArray['statistics'])) {
            $asArray['statistics'] = is_array($workout->statistics) ? $workout->statistics : [];
        }

        return [
            'label' => $workout->label,
            'activity_key' => $workout->activity_key,
            'icon' => $workout->icon,
            'icon_symbol' => $workout->icon_symbol,
            'icon_background' => $workout->icon_background,
            'accent_color' => $workout->accent_color,
            'start' => $start,
            'end' => $end,
            'duration_text' => $workout->duration_text,
            'distance' => $workout->distance_value,
            'distance_unit' => $workout->distance_unit,
            'energy' => $workout->energy_value,
            'energy_unit' => $workout->energy_unit,
            'burnt_energy' => WorkoutUtils::getBurntCalories($asArray),
            'total_energy' => WorkoutUtils::getAllCalories($asArray),
            'burnt_energy_unit' => $workout->energy_unit ?? 'kcal',
            'total_energy_unit' => $workout->energy_unit ?? 'kcal',
            'source_name' => $workout->source_name,
            'total_flights_climbed' => $workout->total_flights_climbed,
            'total_elevation_gain' => $workout->total_elevation_gain,
            'total_elevation_gain_unit' => $workout->total_elevation_gain_unit,
        ];
    }
}
