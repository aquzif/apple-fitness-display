<div class="min-h-screen bg-black text-slate-100">
    <div class="mx-auto flex max-w-4xl flex-col gap-10 px-4 pb-16 pt-10 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-6">
            <div class="flex flex-col gap-3">
                <div class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">Apple Health</div>
                <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('Wprowadź dane') }}</h1>
                <p class="max-w-2xl text-sm text-slate-400">
                    {{ __('Wczytaj eksport XML z aplikacji Zdrowie, aby zaktualizować listę treningów i pomiarów wagi.') }}
                </p>
            </div>

            <div class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 bg-gradient-to-br from-white/10 to-white/5 p-6 backdrop-blur">
                <form wire:submit.prevent="handleUpload" class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <label class="flex flex-1 cursor-pointer items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-lime-300/50 hover:bg-white/10">
                        <input type="file" class="hidden" wire:model.live="upload" />
                        <div class="flex h-16 w-16 flex-none items-center justify-center rounded-2xl bg-white/5">
                            <svg class="h-10 w-10 text-lime-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 9l4.5-4.5m0 0L16.5 9m-4.5-4.5V15" />
                            </svg>
                        </div>
                        <div class="flex flex-col gap-1 text-left">
                            <h2 class="text-lg font-semibold text-white">{{ __('Przeciągnij i upuść lub wybierz plik XML') }}</h2>
                            <p class="text-sm text-slate-400">{{ __('Limit rozmiaru: 10 GB') }}</p>
                        </div>
                    </label>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-lime-400/90 px-5 py-2 text-sm font-semibold text-black shadow-lg shadow-lime-400/40 transition hover:bg-lime-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 9l4.5-4.5m0 0L16.5 9m-4.5-4.5V15" />
                            </svg>
                            {{ __('Prześlij') }}
                        </button>
                        @if ($upload)
                            <span class="text-xs text-slate-400">{{ $upload->getClientOriginalName() }}</span>
                        @endif
                    </div>
                </form>

                @error('upload')
                    <p class="text-sm text-rose-300">{{ $message }}</p>
                @enderror

                @if ($statusMessage !== '')
                    <div class="rounded-2xl border border-white/10 bg-black/40 p-4 text-sm text-slate-300" role="status">
                        {{ $statusMessage }}
                    </div>
                @endif
            </div>
        </header>

        @if (! empty($summary))
            @php
                $durationSeconds = (int) ($summary['totalDurationSeconds'] ?? 0);
                $durationHours = intdiv($durationSeconds, 3600);
                $durationMinutes = intdiv($durationSeconds % 3600, 60);
                $durationText = $durationHours > 0 ? sprintf('%dh %dm', $durationHours, $durationMinutes) : sprintf('%dm', $durationMinutes);
                $formatNumber = static fn ($value) => number_format((float) $value, (float) $value >= 10 ? 1 : 2, ',', ' ');
            @endphp
            <section class="grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur sm:grid-cols-2 lg:grid-cols-4" aria-live="polite">
                <div class="flex flex-col gap-1">
                    <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Łączna liczba') }}</span>
                    <span class="text-3xl font-semibold text-white">{{ $summary['totalCount'] ?? 0 }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Czas') }}</span>
                    <span class="text-3xl font-semibold text-white">{{ $durationText }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Spalone kalorie') }}</span>
                    <span class="text-3xl font-semibold text-white">{{ $formatNumber($summary['totalBurntEnergy'] ?? 0) }} {{ $summary['totalBurntEnergyUnit'] ?? 'kcal' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-xs uppercase tracking-wider text-slate-400">{{ __('Wszystkie kalorie') }}</span>
                    <span class="text-3xl font-semibold text-white">{{ $formatNumber($summary['totalEnergy'] ?? 0) }} {{ $summary['totalEnergyUnit'] ?? 'kcal' }}</span>
                </div>
            </section>
        @endif
    </div>
</div>
