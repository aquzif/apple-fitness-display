<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Weight;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout as LivewireLayout;
use Livewire\Component;

#[LivewireLayout('layouts.app')]
class WeightDashboard extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $weights = [];
    public array $minWeightsByWeek = [];

    /**
     * @var array<string, mixed>
     */
    public array $chart = [];

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $minAvailableDate = null;

    public ?string $maxAvailableDate = null;

    public ?string $dateRangeError = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->weights = [];
            $this->chart = $this->emptyChart();
            return;
        }

        $minDate = $user->weights()->min('recorded_at');
        $maxDate = $user->weights()->max('recorded_at');

        $this->minAvailableDate = $minDate ? CarbonImmutable::parse($minDate)->toDateString() : null;
        $this->maxAvailableDate = $maxDate ? CarbonImmutable::parse($maxDate)->toDateString() : null;

        $this->applySavedWeightFilters($user);

        if ($this->startDate === null && $this->minAvailableDate) {
            $this->startDate = $this->minAvailableDate;
        }

        if ($this->endDate === null && $this->maxAvailableDate) {
            $this->endDate = $this->maxAvailableDate;
        }

        $this->reloadWeights();
    }

    public function render(): View
    {
        return view('livewire.weight-dashboard');
    }

    public function updatedStartDate(?string $value): void
    {
        $this->startDate = $value ?: null;
        $this->reloadWeights();
    }

    public function updatedEndDate(?string $value): void
    {
        $this->endDate = $value ?: null;
        $this->reloadWeights();
    }

    protected function reloadWeights(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->weights = [];
            $this->chart = $this->emptyChart();
            return;
        }

        [$start, $end, $invalid] = $this->normalizedDateRange();

        if ($invalid) {
            $this->weights = [];
            $this->chart = $this->emptyChart();
            $this->dispatch('weight-chart-update', chart: [
                'labels' => [],
                'datasets' => [],
            ]);

            return;
        }

        $this->persistWeightDateFilters($start, $end);

        $query = $user->weights()->orderByDesc('recorded_at');

        if ($start) {
            $query->where('recorded_at', '>=', $start);
        }

        if ($end) {
            $query->where('recorded_at', '<=', $end);
        }

        $weights = $query->get();

        $this->weights = $weights->map(fn (Weight $weight) => [
            'value' => $weight->value,
            'unit' => $weight->unit,
            'recorded_at' => $weight->recorded_at?->toIso8601String(),
            'recorded_for_humans' => $weight->recorded_at?->locale(app()->getLocale())->isoFormat('LLL'),
            'source_name' => $weight->source_name,
            'device' => $weight->device,
        ])->all();

        $this->chart = $this->buildChart($weights->sortBy('recorded_at')->values());

        $this->dispatch('weight-chart-update', chart: [
            'labels' => $this->chart['labels'],
            'datasets' => $this->chart['datasets'],
        ]);

        // Build minimum weights by week
        // find lowest weight for each week in the selected date range
        // array item will be array as follows:
        // [
        //   'week_start' => '2024-01-01',
        //   'week_end' => '2024-01-07',
        //   'min_weight' => 70.5,
        //   'change_from_previous_week' => -0.5,
        // ]

        //sort weights by recorded_at ascending
        $weights = $weights->sortBy('recorded_at')->values();

        $this->minWeightsByWeek = [];
        $weeks = [];

        foreach ($weights as $weight) {
            $weekStart = $weight->recorded_at?->startOfWeek()->toDateString();
            $weekEnd = $weight->recorded_at?->endOfWeek()->toDateString();

            if (! isset($weeks[$weekStart])) {
                $weeks[$weekStart] = [
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                    'min_weight' => $weight->value,
                ];
            } else {
                if ($weight->value < $weeks[$weekStart]['min_weight']) {
                    $weeks[$weekStart]['min_weight'] = $weight->value;
                }
            }
        }

        // Calculate change from previous week
        $previousMin = null;
        foreach ($weeks as $week) {
            $change = null;
            if ($previousMin !== null) {
                $change = round($week['min_weight'] - $previousMin, 1);
            }
            $week['change_from_previous_week'] = $change;
            $this->minWeightsByWeek[] = $week;
            $previousMin = $week['min_weight'];
        }

        //change order in minWeidhtsByWeek to descending by week_start
        $this->minWeightsByWeek = array_reverse($this->minWeightsByWeek);



    }

    protected function buildChart(Collection $weights): array
    {
        if ($weights->isEmpty()) {
            return $this->emptyChart();
        }

        $values = $weights->pluck('value');
        $min = $values->min();
        $max = $values->max();
        $labels = $weights
            ->map(fn (Weight $weight) => $weight->recorded_at?->locale(app()->getLocale())->isoFormat('DD.MM.YYYY'))
            ->all();

        $dataset = [
            'label' => __('Masa ciała (kg)'),
            'data' => $values->map(fn (float $value) => round($value, 1))->all(),
            'borderColor' => 'rgb(190, 242, 100)',
            'pointBackgroundColor' => 'rgb(190, 242, 100)',
            'pointBorderColor' => 'rgb(15, 23, 42)',
            'pointHoverRadius' => 6,
            'pointRadius' => 4,
            'fill' => true,
            'tension' => 0.35,
        ];

        $latest = $weights->last();
        $first = $weights->first();
        $difference = $latest && $first ? round($latest->value - $first->value, 1) : null;

        return [
            'labels' => $labels,
            'datasets' => [$dataset],
            'min' => round($min, 1),
            'max' => round($max, 1),
            'latest' => $latest ? round($latest->value, 1) : null,
            'latest_at' => $latest && $latest->recorded_at instanceof CarbonImmutable
                ? $latest->recorded_at->locale(app()->getLocale())->isoFormat('LL')
                : null,
            'difference' => $difference,
        ];
    }

    protected function emptyChart(): array
    {
        return [
            'labels' => [],
            'datasets' => [],
            'min' => null,
            'max' => null,
            'latest' => null,
            'latest_at' => null,
            'difference' => null,
        ];
    }

    protected function normalizedDateRange(): array
    {
        $start = null;
        $end = null;

        try {
            $start = $this->startDate ? CarbonImmutable::parse($this->startDate)->startOfDay() : null;
        } catch (\Throwable) {
            $this->dateRangeError = __('Nieprawidłowa data początkowa.');

            return [null, null, true];
        }

        try {
            $end = $this->endDate ? CarbonImmutable::parse($this->endDate)->endOfDay() : null;
        } catch (\Throwable) {
            $this->dateRangeError = __('Nieprawidłowa data końcowa.');

            return [null, null, true];
        }

        if ($start && $end && $start->gt($end)) {
            $this->dateRangeError = __('Data początkowa nie może być późniejsza niż końcowa.');

            return [null, null, true];
        }

        $this->dateRangeError = null;

        return [$start, $end, false];
    }

    protected function applySavedWeightFilters(User $user): void
    {
        $start = $this->clampDateWithinAvailableRange($user->weight_filter_start_date?->toDateString());
        $end = $this->clampDateWithinAvailableRange($user->weight_filter_end_date?->toDateString());

        if ($start && $end) {
            try {
                if (CarbonImmutable::parse($start)->gt(CarbonImmutable::parse($end))) {
                    $end = $start;
                }
            } catch (\Throwable) {
                $end = $start;
            }
        }

        if ($start !== null) {
            $this->startDate = $start;
        }

        if ($end !== null) {
            $this->endDate = $end;
        }
    }

    protected function clampDateWithinAvailableRange(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            $value = CarbonImmutable::parse($date);
        } catch (\Throwable) {
            return null;
        }

        return $value->toDateString();

        if ($this->minAvailableDate) {
            try {
                $min = CarbonImmutable::parse($this->minAvailableDate);
                if ($value->lt($min)) {
                    $value = $min;
                }
            } catch (\Throwable) {
                // Ignore invalid minimum boundary
            }
        }

        if ($this->maxAvailableDate) {
            try {
                $max = CarbonImmutable::parse($this->maxAvailableDate);
                if ($value->gt($max)) {
                    $value = $max;
                }
            } catch (\Throwable) {
                // Ignore invalid maximum boundary
            }
        }

        return $value->toDateString();
    }

    protected function persistWeightDateFilters(?CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $startDate = $start?->toDateString();
        $endDate = $end?->toDateString();

        $currentStart = $user->weight_filter_start_date?->toDateString();
        $currentEnd = $user->weight_filter_end_date?->toDateString();

        if ($currentStart === $startDate && $currentEnd === $endDate) {
            return;
        }

        $user->forceFill([
            'weight_filter_start_date' => $startDate,
            'weight_filter_end_date' => $endDate,
        ])->save();
    }
}
