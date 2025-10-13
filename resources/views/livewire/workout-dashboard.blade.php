<div class="min-h-screen bg-black text-slate-100">
    <div class="mx-auto flex max-w-6xl flex-col gap-10 px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-6">
            <div class="flex flex-col gap-3">
                <div class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">Sessions</div>
                <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('Treningi') }}</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'all' => __('Wszystkie'),
                    'workout' => __('Treningi'),
                    'mind' => __('Mindfulness'),
                    'cycling' => __('Kolarstwo'),
                ] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('filter', '{{ $value }}')"
                        @class([
                            'rounded-full border px-4 py-1.5 text-sm transition',
                            'border-lime-400/50 bg-lime-400/20 text-white shadow-lg shadow-lime-400/30' => $filter === $value,
                            'border-white/10 text-slate-300 hover:border-white/30 hover:text-white' => $filter !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($statusMessage)
                <div class="rounded-2xl border border-white/10 bg-black/40 px-4 py-3 text-sm text-slate-300" role="status">
                    {{ $statusMessage }}
                </div>
            @endif
        </header>

        <section class="grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur sm:grid-cols-2 lg:grid-cols-4" aria-live="polite">
            <div class="flex flex-col gap-1">
                <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Łączna liczba') }}</span>
                <span class="text-3xl font-semibold text-white">{{ $summaryView['count'] }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Czas') }}</span>
                <span class="text-3xl font-semibold text-white">{{ $summaryView['duration'] }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Spalone kalorie') }}</span>
                <span class="text-3xl font-semibold text-white">{{ $summaryView['burntEnergy'] }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Wszystkie kalorie') }}</span>
                <span class="text-3xl font-semibold text-white">{{ $summaryView['energy'] }}</span>
            </div>
        </section>

        <section class="flex flex-col gap-8" aria-live="polite">
            @if (empty($groupedWorkouts))
                <div class="rounded-3xl border border-white/10 bg-white/5 p-12 text-center backdrop-blur">
                    <h2 class="text-2xl font-semibold text-white">{{ __('Brak danych treningowych') }}</h2>
                    <p class="mt-2 text-sm text-slate-400">{{ __('Załaduj plik exportu, aby zobaczyć listę treningów.') }}</p>
                </div>
            @else
                @foreach ($groupedWorkouts as $monthKey => $month)
                    <article class="space-y-6">
                        <div class="flex flex-row gap-2 items-center" >
                            <h2 class="text-xl font-semibold text-white">{{ $month['label'] }}</h2>
                            @php
                                $monthEnergy = 0;
                                $monthBurntEnergy = 0;
                                $monthDuration = 0;



                                foreach ($month['days'] as $day) {
                                    foreach ($day['items'] as $workout) {
                                        $monthEnergy += \App\Utils\WorkoutUtils::getAllCalories($workout);
                                        $monthBurntEnergy += \App\Utils\WorkoutUtils::getBurntCalories($workout);
                                        $monthDuration += $workout['durationSeconds'] ?? 0;
                                    }
                                }

                            @endphp

                            <span class="rounded-full border border-gray-300/40 bg-gray-400/20 px-3 py-1 text-xs font-semibold text-gray-100">{{ $this->formatSummaryDuration($monthDuration) }}</span>
                            <span class="rounded-full border border-orange-300/40 bg-orange-400/20 px-3 py-1 text-xs font-semibold text-orange-100">{{ $this->formatNumber($monthBurntEnergy) }} spalonych kcal</span>
                            <span class="rounded-full border border-red-300/40 bg-red-400/20 px-3 py-1 text-xs font-semibold text-red-100">{{ $this->formatNumber($monthEnergy) }} kcal</span>
                        </div>
                        <div class="space-y-6">
                            @foreach ($month['days'] as $day)

                                @php
                                    $dayEnergy = 0;
                                    $dayBurntEnergy = 0;
                                    $dayDuration = 0;



                                    foreach ($day['items'] as $workout) {
                                        $dayEnergy += \App\Utils\WorkoutUtils::getAllCalories($workout);
                                        $dayBurntEnergy += \App\Utils\WorkoutUtils::getBurntCalories($workout);
                                        $dayDuration += $workout['durationSeconds'] ?? 0;
                                    }


                                @endphp

                                <div class="space-y-4">
                                    <header class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/10 pb-2 text-sm uppercase tracking-wide text-slate-400">

                                        <div>
                                            <span>{{ $day['label']['weekday'] }}</span>
                                            <span class="rounded-full lowercase border border-gray-300/40 bg-gray-400/20 px-3 py-1 text-xs font-semibold text-gray-100">{{ $this->formatSummaryDuration($dayDuration) }}</span>
                                            <span class="rounded-full lowercase border border-orange-300/40 bg-orange-400/20 px-3 py-1 text-xs font-semibold text-orange-100">{{ $this->formatNumber($dayBurntEnergy) }} spalonych kcal</span>
                                            <span class="rounded-full lowercase border border-red-300/40 bg-red-400/20 px-3 py-1 text-xs font-semibold text-red-100">{{ $this->formatNumber($dayEnergy) }} kcal</span>
                                        </div>
                                        <span class="text-slate-500">{{ $day['label']['date'] }}</span>

                                    </header>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        @foreach ($day['items'] as $workout)
                                            <article class="flex gap-4 rounded-3xl border border-white/5 bg-white/5 p-4 transition hover:border-white/20">
                                                <div class="flex h-20 w-20 flex-none items-center justify-center rounded-2xl" style="background: {{ $workout['iconBackground'] }};">
                                                    @if ($workout['iconUri'])
                                                        <img src="{{ $workout['iconUri'] }}" alt="" class="h-12 w-12" loading="lazy" decoding="async" />
                                                    @else
                                                        <div class="text-3xl">{{ $workout['icon'] ?? '💪' }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex flex-1 flex-col justify-between gap-3">
                                                    <div class="space-y-2">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <h3 class="text-lg font-semibold text-white">{{ $workout['label'] ?? $workout['type']['label'] ?? $this->formatActivityKey($workout['activityKey']) }}</h3>
                                                            @if (!empty($workout['durationText']))
                                                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white">{{ $workout['durationText'] }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-sm text-slate-400">
                                                            {{ collect([
                                                                $workout['startDate'] ? \Carbon\CarbonImmutable::parse($workout['startDate'])->locale(app()->getLocale())->isoFormat('LT') : null,
                                                                $this->formatGoal($workout['metadata']['HKWorkoutGoalType'] ?? null) ?: null,
                                                                $workout['sourceName'] ?? null,
                                                            ])->filter()->implode(' • ') }}
                                                        </div>
                                                        @php
                                                            $details = collect([
                                                                !empty($workout['metadata']['HKOutdoorWorkoutRoute']) ? __('Outdoor') : null,
                                                                !empty($workout['metadata']['HKIndoorWorkout']) ? __('Indoor') : null,
                                                                $workout['totalFlightsClimbed'] ? $workout['totalFlightsClimbed'] . ' ' . __('pięter') : null,
                                                                ($workout['totalElevationGain'] && $workout['totalElevationGainUnit']) ? __('Wzniesienie :value:unit', ['value' => $workout['totalElevationGain'], 'unit' => $workout['totalElevationGainUnit']]) : null,
//                                                                $workout['metadata']['HKWeatherCondition'] ? __('Pogoda: :weather', ['weather' => $workout['metadata']['HKWeatherCondition']]) : null,
                                                            ])->filter();
                                                        @endphp

                                                    </div>
                                                    <div class="flex flex-wrap gap-2">

                                                        @if (\App\Utils\WorkoutUtils::getBurntCalories($workout) > 0)
                                                            <span class="rounded-full border border-orange-300/40 bg-orange-400/20 px-3 py-1 text-xs font-semibold text-orange-100">
                                                                {{ $this->formatNumber(\App\Utils\WorkoutUtils::getBurntCalories($workout)) }} spalonych kcal
                                                            </span>
                                                        @endif
                                                        @if (\App\Utils\WorkoutUtils::getAllCalories($workout) > 0)
                                                            <span class="rounded-full border border-red-300/40 bg-red-400/20 px-3 py-1 text-xs font-semibold text-red-100">
                                                                {{ $this->formatNumber(\App\Utils\WorkoutUtils::getAllCalories($workout)) }}  kcal
                                                            </span>
                                                        @endif
                                                        @if (!empty($workout['distance']['value']))
                                                            <span class="rounded-full border border-sky-300/40 bg-sky-400/20 px-3 py-1 text-xs font-semibold text-sky-100">{{ $this->formatNumber($workout['distance']['value']) }} {{ $workout['distance']['unit'] ?? 'km' }}</span>
                                                        @endif

                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            @endif
        </section>
    </div>
</div>
