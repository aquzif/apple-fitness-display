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

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->weights = [];
            $this->chart = $this->emptyChart();
            return;
        }

        $weights = $user->weights()->orderByDesc('recorded_at')->get();

        $this->weights = $weights->map(fn (Weight $weight) => [
            'value' => $weight->value,
            'unit' => $weight->unit,
            'recorded_at' => $weight->recorded_at?->toIso8601String(),
            'recorded_for_humans' => $weight->recorded_at?->locale(app()->getLocale())->isoFormat('LLL'),
            'source_name' => $weight->source_name,
            'device' => $weight->device,
        ])->all();

        $this->chart = $this->buildChart($weights->sortBy('recorded_at')->values());
    }

    public function render(): View
    {
        return view('livewire.weight-dashboard');
    }

    protected function buildChart(Collection $weights): array
    {
        if ($weights->isEmpty()) {
            return $this->emptyChart();
        }

        $values = $weights->pluck('value');
        $min = $values->min();
        $max = $values->max();
        $range = max($max - $min, 0.1);
        $count = max($weights->count(), 1);

        $points = [];
        foreach ($weights->values() as $index => $weight) {
            $x = $count === 1 ? 0.0 : ($index / ($count - 1)) * 100;
            $normalized = ($weight->value - $min) / $range;
            $y = 100 - (($normalized * 80) + 10);
            $points[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        }

        $path = '';
        $linePath = '';
        if ($points !== []) {
            $segments = ['M ' . $this->formatPoint($points[0])];
            foreach (array_slice($points, 1) as $point) {
                $segments[] = 'L ' . $this->formatPoint($point);
            }

            $segments[] = 'L 100 100';
            $segments[] = 'L 0 100';
            $segments[] = 'Z';
            $path = implode(' ', $segments);

            $lineSegments = ['M ' . $this->formatPoint($points[0])];
            foreach (array_slice($points, 1) as $point) {
                $lineSegments[] = 'L ' . $this->formatPoint($point);
            }
            $linePath = implode(' ', $lineSegments);
        }

        $latest = $weights->last();
        $first = $weights->first();
        $difference = $latest && $first ? round($latest->value - $first->value, 1) : null;

        return [
            'path' => $path,
            'line_path' => $linePath,
            'min' => round($min, 1),
            'max' => round($max, 1),
            'latest' => $latest ? round($latest->value, 1) : null,
            'latest_at' => $latest && $latest->recorded_at instanceof CarbonImmutable
                ? $latest->recorded_at->locale(app()->getLocale())->isoFormat('LL')
                : null,
            'difference' => $difference,
            'points' => $points,
        ];
    }

    protected function emptyChart(): array
    {
        return [
            'path' => '',
            'line_path' => '',
            'min' => null,
            'max' => null,
            'latest' => null,
            'latest_at' => null,
            'difference' => null,
            'points' => [],
        ];
    }

    protected function formatPoint(array $point): string
    {
        return sprintf('%.2f %.2f', $point['x'], $point['y']);
    }
}
