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

                <div class="h-64 w-full overflow-hidden rounded-2xl border border-white/10 bg-black/40">
                    @if ($chart['path'])
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                            <defs>
                                <linearGradient id="weightArea" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="rgb(132 204 22)" stop-opacity="0.45" />
                                    <stop offset="100%" stop-color="rgb(132 204 22)" stop-opacity="0.05" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $chart['path'] }}" fill="url(#weightArea)" stroke="none" />
                            <path d="{{ $chart['line_path'] }}" fill="none" stroke="rgb(190 242 100)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            @foreach ($chart['points'] as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.5" fill="rgb(190 242 100)" />
                            @endforeach
                        </svg>
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
                                @if ($weight['device'])
                                    <div>{{ $weight['device'] }}</div>
                                @endif
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
