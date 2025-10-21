<div class="min-h-screen bg-black text-slate-100">
    <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 pb-16 pt-8 sm:px-6 lg:px-8">
        <nav>
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300 transition hover:border-white/30 hover:text-white"
            >
                <span aria-hidden="true">&larr;</span>
                <span>{{ __('Wróć do listy treningów') }}</span>
            </a>
        </nav>

        @php
            $config = config('workout_types');
            $defaultType = $config['default'] ?? [];
            $type = $config['types'][$workoutSummary['activity_key'] ?? ''] ?? $defaultType;
            $iconAssets = config('icon_assets');
            $symbol = $workoutSummary['icon_symbol']
                ?? $workoutSummary['icon']
                ?? $type['iconSymbol']
                ?? $type['icon']
                ?? $defaultType['iconSymbol']
                ?? $defaultType['icon']
                ?? null;
            $iconUri = $symbol && isset($iconAssets[$symbol]) ? $iconAssets[$symbol] : ($symbol ? '/icons/' . $symbol : null);
            $iconBackground = $type['iconBackground'] ?? $workoutSummary['icon_background'] ?? ($defaultType['iconBackground'] ?? 'rgba(48, 209, 88, 0.18)');
            $accentColor = $type['accentColor'] ?? $workoutSummary['accent_color'] ?? ($defaultType['accentColor'] ?? '#30d158');
            $activityLabel = $workoutSummary['label']
                ?? $type['label']
                ?? ($workoutSummary['activity_key'] ? (string) \Illuminate\Support\Str::of($workoutSummary['activity_key'])->replace('_', ' ')->headline() : __('Trening'));
            $start = $workoutSummary['start'] instanceof \Carbon\CarbonImmutable ? $workoutSummary['start'] : null;
            $end = $workoutSummary['end'] instanceof \Carbon\CarbonImmutable ? $workoutSummary['end'] : null;
            $distanceValue = $workoutSummary['distance'];
            $distanceUnit = $workoutSummary['distance_unit'] ?? 'km';
            $energyValue = $workoutSummary['energy'];
            $energyUnit = $workoutSummary['energy_unit'] ?? 'kcal';
            $burntEnergyValue = $workoutSummary['burnt_energy'];
            $totalEnergyValue = $workoutSummary['total_energy'];
            $burntEnergyUnit = $workoutSummary['burnt_energy_unit'] ?? 'kcal';
            $totalEnergyUnit = $workoutSummary['total_energy_unit'] ?? 'kcal';
            $flights = $workoutSummary['total_flights_climbed'];
            $elevation = $workoutSummary['total_elevation_gain'];
            $elevationUnit = $workoutSummary['total_elevation_gain_unit'] ?? 'm';
        @endphp

        <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-20 w-20 flex-none items-center justify-center rounded-2xl"
                        style="background: {{ $iconBackground }};"
                        aria-hidden="true"
                    >
                        @if ($iconUri)
                            <img src="{{ $iconUri }}" alt="" class="h-12 w-12" loading="lazy" decoding="async" />
                        @else
                            <span class="text-3xl">{{ $symbol ?? '💪' }}</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-500">{{ __('Trening') }}</p>
                        <h1 class="text-3xl font-semibold text-white sm:text-4xl">{{ $activityLabel }}</h1>
                        <div class="text-sm text-slate-400">
                            {{ collect([
                                $start?->locale(app()->getLocale())->isoFormat('LLLL'),
                                $end?->locale(app()->getLocale())->isoFormat('LLLL'),
                                $workoutSummary['duration_text'] ?? null,
                                $workoutSummary['source_name'] ?? null,
                            ])->filter()->implode(' • ') }}
                        </div>
                    </div>
                </div>
                @if ($heartrateSummary['count'] > 0)
                    <div class="rounded-2xl border border-white/10 bg-black/30 px-4 py-3 text-right">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Tętno (wszystkie próbki)') }}</div>
                        <div class="mt-2 flex items-end justify-end gap-3 text-white">
                            <div class="text-sm text-slate-400">{{ __('min') }} <span class="text-lg font-semibold text-white">{{ $heartrateSummary['min'] }}</span></div>
                            <div class="text-sm text-slate-400">{{ __('avg') }} <span class="text-lg font-semibold text-white">{{ number_format($heartrateSummary['avg'], 1) }}</span></div>
                            <div class="text-sm text-slate-400">{{ __('max') }} <span class="text-lg font-semibold text-white">{{ $heartrateSummary['max'] }}</span></div>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">{{ trans_choice('1 próbka|:count próbki|:count próbek', $heartrateSummary['count'], ['count' => $heartrateSummary['count']]) }}</div>
                    </div>
                @endif
            </header>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @if (! is_null($distanceValue))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Dystans') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $distanceValue, 2, ',', ' ') }} {{ $distanceUnit }}</div>
                    </div>
                @endif

                @if (! is_null($energyValue))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Kalorie (całkowite)') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $energyValue, 0, ',', ' ') }} {{ $energyUnit }}</div>
                    </div>
                @endif

                @if (! is_null($totalEnergyValue))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Kalorie (sumaryczne)') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $totalEnergyValue, 0, ',', ' ') }} {{ $totalEnergyUnit }}</div>
                    </div>
                @endif

                @if (! is_null($burntEnergyValue))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Kalorie (aktywne)') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $burntEnergyValue, 0, ',', ' ') }} {{ $burntEnergyUnit }}</div>
                    </div>
                @endif

                @if (! is_null($flights))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Piętra') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $flights, 0, ',', ' ') }}</div>
                    </div>
                @endif

                @if (! is_null($elevation))
                    <div class="rounded-2xl border border-white/10 bg-black/30 p-4">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-500">{{ __('Wzniesienie') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-white">{{ number_format((float) $elevation, 0, ',', ' ') }} {{ $elevationUnit }}</div>
                    </div>
                @endif
            </div>
        </section>

        <section class="space-y-4">
            <header class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold text-white">{{ __('Próbki tętna na minutę') }}</h2>
                <p class="text-sm text-slate-400">{{ __('W każdej minucie pokazujemy minimalne i maksymalne zarejestrowane tętno.') }}</p>
            </header>

            <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
                @if (empty($heartrateMinutes))
                    <div class="p-8 text-center text-sm text-slate-400">{{ __('Brak próbek tętna powiązanych z tym treningiem.') }}</div>
                @else
                    @php
                        $globalMin = collect($heartrateMinutes)
                            ->pluck('min')
                            ->filter(fn ($value) => $value !== null)
                            ->min();
                        $globalMax = collect($heartrateMinutes)
                            ->pluck('max')
                            ->filter(fn ($value) => $value !== null)
                            ->max();
                        $ticks = [];
                        $range = 1;
                        $baseline = 0;
                        if ($globalMin !== null && $globalMax !== null) {
                            $rawRange = max(0, $globalMax - $globalMin);
                            $baseline = $globalMin;
                            if ($rawRange === 0) {
                                $ticks = array_fill(0, 5, (int) $globalMax);
                            } else {
                                $range = max(1, $rawRange);
                                $steps = 4;
                                for ($i = 0; $i <= $steps; $i++) {
                                    $ticks[] = (int) round($globalMax - ($rawRange * $i) / $steps);
                                }
                            }
                        }
                    @endphp

                    @php
                        $baseline = $globalMin ?? $globalMax ?? $baseline;
                        $totalEntries = count($heartrateMinutes);
                    @endphp

                    <div class="flex flex-col gap-6 p-6">
                        <div class="relative h-64 w-full">
                            <div class="absolute inset-0 flex flex-col justify-between">
                                @foreach ($ticks as $index => $tick)
                                    <div class="flex items-center gap-3 text-[10px] uppercase tracking-[0.2em] text-slate-500">
                                        <span class="inline-flex w-10 justify-end text-xs font-medium text-slate-400">{{ $tick }}</span>
                                        <span class="h-px flex-1 bg-white/10"></span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="absolute inset-x-10 bottom-0 top-0 flex items-end gap-1 sm:gap-2">
                                @foreach ($heartrateMinutes as $index => $entry)
                                    @php
                                        $minute = $entry['minute'];
                                        $minValue = $entry['min'] ?? $baseline;
                                        $maxValue = $entry['max'] ?? $baseline;
                                        $minPct = max(0, min(100, $globalMax !== null ? (($minValue - $baseline) / $range) * 100 : 0));
                                        $maxPct = max(0, min(100, $globalMax !== null ? (($maxValue - $baseline) / $range) * 100 : 0));
                                        $showLabel = $index === 0 || $index === $totalEntries - 1 || $index % 5 === 0;
                                        $minuteLabel = $minute?->locale(app()->getLocale())->isoFormat('HH:mm');
                                        $tooltipText = implode(' • ',
                                            array_filter([
                                                $minuteLabel,
                                                ($entry['min'] !== null && $entry['max'] !== null)
                                                    ? __(':min–:max bpm', ['min' => $entry['min'], 'max' => $entry['max']])
                                                    : null,
                                                $entry['samples']
                                                    ? trans_choice('1 próbka|:count próbki|:count próbek', $entry['samples'], ['count' => $entry['samples']])
                                                    : null,
                                            ], fn ($value) => ! is_null($value) && $value !== '')
                                        );
                                    @endphp
                                    <div
                                        class="group relative flex flex-1 flex-col items-center focus:outline-none"
                                        tabindex="0"
                                        @if ($tooltipText !== '') aria-label="{{ $tooltipText }}" @endif
                                    >
                                        @if ($tooltipText !== '')
                                            <div
                                                class="pointer-events-none absolute -top-2 z-10 flex -translate-y-3 scale-95 transform flex-col items-center opacity-0 transition duration-150 ease-out group-focus-visible:-translate-y-full group-focus-visible:scale-100 group-focus-visible:opacity-100 group-hover:-translate-y-full group-hover:scale-100 group-hover:opacity-100"
                                            >
                                                <div class="rounded-lg bg-black/80 px-3 py-2 text-xs font-medium text-white shadow-xl">
                                                    {{ $tooltipText }}
                                                </div>
                                                <div class="h-2 w-px bg-black/60"></div>
                                            </div>
                                        @endif
                                        <div class="relative h-52 w-full">
                                            <span
                                                class="absolute left-1/2 w-2 -translate-x-1/2 rounded-full bg-emerald-400/80"
                                                style="bottom: {{ $minPct }}%; top: {{ 100 - $maxPct }}%;"
                                            ></span>
                                            <span
                                                class="absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-emerald-200"
                                                style="bottom: calc({{ $minPct }}% - 4px);"
                                                aria-hidden="true"
                                            ></span>
                                            <span
                                                class="absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-emerald-100"
                                                style="bottom: calc({{ $maxPct }}% - 4px);"
                                                aria-hidden="true"
                                            ></span>
                                        </div>
                                        <div class="mt-2 text-center text-[10px] uppercase tracking-[0.2em] text-slate-500">
                                            {{ $showLabel ? $minuteLabel : '·' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <ul class="sr-only">
                            @foreach ($heartrateMinutes as $entry)
                                <li>
                                    {{ __('Minuta :time — minimum :min bpm, maksimum :max bpm, :count próbek', [
                                        'time' => $entry['minute']?->locale(app()->getLocale())->isoFormat('HH:mm') ?? '—',
                                        'min' => $entry['min'] ?? '—',
                                        'max' => $entry['max'] ?? '—',
                                        'count' => $entry['samples'],
                                    ]) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
