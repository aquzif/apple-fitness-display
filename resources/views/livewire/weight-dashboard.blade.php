<div class="min-h-screen bg-black text-slate-100">
    <div class="mx-auto flex max-w-5xl flex-col gap-10 px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-3">
            <div class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">Body</div>
            <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('Waga') }}</h1>
            <p class="max-w-3xl text-sm text-slate-400">
                {{ __('Monitoruj zmiany masy ciała na podstawie danych z aplikacji Zdrowie Apple.') }}
            </p>
        </header>

        <section class="grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur lg:col-span-3">
                <header class="flex flex-wrap items-baseline justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ __('Trend wagi') }}</h2>
                        <p class="text-sm text-slate-400">{{ __('Ostatnie pomiary wagi z eksportu Apple Health.') }}</p>
                    </div>
                    @if (! is_null($chart['latest']))
                        <div class="text-right">
                            <div class="text-3xl font-semibold text-white">{{ number_format($chart['latest'], 1, ',', ' ') }} {{ __('kg') }}</div>
                            @if (! is_null($chart['difference']))
                                <div class="text-xs text-slate-400">
                                    {{ __('Zmiana od pierwszego pomiaru:') }}
                                    <span class="font-semibold {{ $chart['difference'] >= 0 ? 'text-rose-300' : 'text-emerald-300' }}">
                                        {{ $chart['difference'] > 0 ? '+' : '' }}{{ number_format($chart['difference'], 1, ',', ' ') }} {{ __('kg') }}
                                    </span>
                                </div>
                            @endif
                            @if ($chart['latest_at'])
                                <div class="text-xs text-slate-500">{{ __('Ostatni pomiar:') }} {{ $chart['latest_at'] }}</div>
                            @endif
                        </div>
                    @endif
                </header>

                @if ($minAvailableDate && $maxAvailableDate)
                    <div class="rounded-2xl border border-white/10 bg-black/40 p-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-6">
                                <label class="flex flex-col text-xs uppercase tracking-[0.25em] text-slate-400">
                                    <span class="mb-1">{{ __('Od') }}</span>
                                    <input
                                        type="date"
                                        wire:model.live="startDate"
                                        min="{{ $minAvailableDate }}"
                                        max="{{ $maxAvailableDate }}"
                                        class="rounded-xl border border-white/10 bg-black/60 px-3 py-2 text-sm text-white focus:border-lime-400/60 focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                    >
                                </label>
                                <label class="flex flex-col text-xs uppercase tracking-[0.25em] text-slate-400">
                                    <span class="mb-1">{{ __('Do') }}</span>
                                    <input
                                        type="date"
                                        wire:model.live="endDate"
                                        min="{{ $minAvailableDate }}"
                                        max="{{ $maxAvailableDate }}"
                                        class="rounded-xl border border-white/10 bg-black/60 px-3 py-2 text-sm text-white focus:border-lime-400/60 focus:outline-none focus:ring-2 focus:ring-lime-400/30"
                                    >
                                </label>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ __('Zakres dat wpływa na wykres oraz listę pomiarów.') }}
                            </p>
                        </div>
                        @if ($dateRangeError)
                            <div class="mt-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-100">
                                {{ $dateRangeError }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="h-64 w-full overflow-hidden rounded-2xl border border-white/10 bg-black/40">
                    @if (! empty($chart['labels']))
                        <div wire:ignore class="h-full w-full">
                            <canvas id="weightChart" class="h-full w-full"></canvas>
                        </div>
                    @else
                        <div class="flex h-full items-center justify-center text-sm text-slate-500">
                            {{ __('Brak danych pomiarowych. Wczytaj eksport Apple Health, aby zobaczyć wykres.') }}
                        </div>
                    @endif
                </div>

                <footer class="flex flex-wrap gap-4 text-sm text-slate-400">
                    <span>{{ __('Minimum:') }} <span class="font-semibold text-white">{{ ! is_null($chart['min']) ? number_format($chart['min'], 1, ',', ' ') . ' kg' : __('brak') }}</span></span>
                    <span>{{ __('Maksimum:') }} <span class="font-semibold text-white">{{ ! is_null($chart['max']) ? number_format($chart['max'], 1, ',', ' ') . ' kg' : __('brak') }}</span></span>
                    <span>{{ __('Liczba pomiarów:') }} <span class="font-semibold text-white">{{ count($weights) }}</span></span>
                </footer>
            </div>

            <div class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur lg:col-span-2">
                <h2 class="text-xl font-semibold text-white">{{ __('Ostatnie pomiary') }}</h2>
                <ul class="space-y-3 max-h-64 overflow-y-auto pr-2">
                    @forelse ($weights as $weight)
                        <li class="flex items-start justify-between rounded-2xl border border-white/5 bg-black/40 p-4">
                            <div>
                                <div class="text-lg font-semibold text-white">{{ number_format($weight['value'], 1, ',', ' ') }} {{ $weight['unit'] }}</div>
                                <div class="text-xs text-slate-500">{{ $weight['recorded_for_humans'] }}</div>
                            </div>
                            <div class="text-xs text-right text-slate-500">
                                @if ($weight['source_name'])
                                    <div>{{ $weight['source_name'] }}</div>
                                @endif
                                {{--@if ($weight['device'])
                                    <div>{{ $weight['device'] }}</div>
                                @endif--}}
                            </div>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-white/10 bg-black/40 p-6 text-center text-sm text-slate-500">
                            {{ __('Brak pomiarów wagi. Wczytaj dane z pliku XML.') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
    document.addEventListener('livewire:init', () => {
        if (window.__weightChartInitialized) {
            return;
        }

        window.__weightChartInitialized = true;

        let weightChartInstance = null;

        const destroyChart = () => {
            if (weightChartInstance) {
                weightChartInstance.destroy();
                weightChartInstance = null;
            }
        };

        const renderWeightChart = (payload) => {
            const chart = payload?.chart ?? payload;
            const canvas = document.getElementById('weightChart');

            if (!canvas || !chart || !Array.isArray(chart.labels) || chart.labels.length === 0) {
                destroyChart();

                return;
            }

            const datasets = Array.isArray(chart.datasets) ? chart.datasets : [];

            if (datasets.length === 0) {
                destroyChart();

                return;
            }

            const context = canvas.getContext('2d');

            if (!context) {
                destroyChart();

                return;
            }

            destroyChart();

            const gradient = context.createLinearGradient(0, 0, 0, canvas.clientHeight || canvas.height || 0);
            gradient.addColorStop(0, 'rgba(132, 204, 22, 0.45)');
            gradient.addColorStop(1, 'rgba(132, 204, 22, 0.05)');

            const chartDatasets = datasets.map((dataset) => ({
                ...dataset,
                data: Array.isArray(dataset.data)
                    ? dataset.data.map((value) => (typeof value === 'number' ? value : Number(value)))
                    : [],
                backgroundColor: gradient,
                borderColor: dataset.borderColor ?? 'rgb(190, 242, 100)',
                pointBackgroundColor: dataset.pointBackgroundColor ?? 'rgb(190, 242, 100)',
                pointBorderColor: dataset.pointBorderColor ?? 'rgb(15, 23, 42)',
                pointHoverBackgroundColor: dataset.pointHoverBackgroundColor ?? '#ffffff',
                pointHoverBorderColor: dataset.pointHoverBorderColor ?? 'rgba(15, 23, 42, 0.6)',
                fill: true,
            }));

            weightChartInstance = new Chart(context, {
                type: 'line',
                data: {
                    labels: chart.labels,
                    datasets: chartDatasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    layout: {
                        padding: 16,
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Figtree, sans-serif',
                                    size: 11,
                                    weight: '500',
                                },
                            },
                        },
                        y: {
                            grid: {
                                color: 'rgba(148, 163, 184, 0.15)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    family: 'Figtree, sans-serif',
                                    size: 11,
                                    weight: '500',
                                },
                                callback(value) {
                                    return `${value} kg`;
                                },
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            borderColor: 'rgba(148, 163, 184, 0.25)',
                            borderWidth: 1,
                            padding: 12,
                            titleColor: '#e2e8f0',
                            bodyColor: '#f8fafc',
                            displayColors: false,
                            callbacks: {
                                label(context) {
                                    const value = context.parsed.y ?? 0;

                                    return `${value.toLocaleString('pl-PL', {
                                        minimumFractionDigits: 1,
                                        maximumFractionDigits: 1,
                                    })} kg`;
                                },
                            },
                        },
                    },
                    elements: {
                        line: {
                            tension: 0.35,
                            borderWidth: 2,
                            borderCapStyle: 'round',
                        },
                        point: {
                            radius: 4,
                            hoverRadius: 6,
                            hitRadius: 12,
                        },
                    },
                },
            });
        };

        Livewire.on('weight-chart-update', (chart) => {
            renderWeightChart(chart);
        });

        renderWeightChart(@json($chart));
    });
</script>
