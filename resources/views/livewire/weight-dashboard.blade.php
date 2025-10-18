<div class="min-h-screen bg-black text-slate-100">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-3">
            <div class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">Body</div>
            <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('Waga') }}</h1>
            <p class="max-w-3xl text-sm text-slate-400">
                {{ __('Monitoruj zmiany masy ciała na podstawie danych z aplikacji Zdrowie Apple.') }}
            </p>
        </header>

        <section class="grid gap-6 lg:grid-cols-12">
            <div class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur xl:col-span-6 md:col-span-12">
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

            <div class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur xl:col-span-3  md:col-span-6">
                <h2 class="text-xl font-semibold text-white">{{ __('Ostatnie pomiary') }}</h2>
                <ul class="space-y-3 max-h-64 lg:max-h-[500px]  overflow-y-auto pr-2">
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
            <div class="space-y-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur xl:col-span-3  md:col-span-6">
                <h2 class="text-xl font-semibold text-white">{{ __('Zmiana wagi') }}</h2>
                <ul class="space-y-3 max-h-64 lg:max-h-[500px]  overflow-y-auto pr-2">
                    @forelse ($minWeightsByWeek as $weight)
{{--                        @dd($weight)--}}
                        <li class="flex items-start justify-between rounded-2xl border border-white/5 bg-black/40 p-4">
                            <div>
                                <div class="text-lg font-semibold text-white">
                                    {{ number_format($weight['min_weight'], 1, ',', ' ') }}
                                    <span
                                        class="
                                        text-sm
                                        {{ $weight['change_from_previous_week'] > 0 ? 'text-rose-300' : '' }}
                                        {{ $weight['change_from_previous_week'] < 0 ? 'text-emerald-300' : '' }}
                                        "
                                    >({{ number_format($weight['change_from_previous_week'], 1, ',', ' ') }})</span>
                                </div>
                                <div class="text-xs text-slate-500">{{ $weight['week_start'] }} - {{ $weight['week_end']  }}</div>
                            </div>
                        </li>
                        {{--<li class="flex items-start justify-between rounded-2xl border border-white/5 bg-black/40 p-4">
                            <div>
                                <div class="text-lg font-semibold text-white">{{ number_format($weight['value'], 1, ',', ' ') }} {{ $weight['unit'] }}</div>
                                <div class="text-xs text-slate-500">{{ $weight['recorded_for_humans'] }}</div>
                            </div>
                            <div class="text-xs text-right text-slate-500">
                                @if ($weight['source_name'])
                                    <div>{{ $weight['source_name'] }}</div>
                                @endif
                                --}}{{--@if ($weight['device'])
                                    <div>{{ $weight['device'] }}</div>
                                @endif--}}{{--
                            </div>
                        </li>--}}
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


