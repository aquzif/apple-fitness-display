<?php

namespace App\Livewire;

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
}
