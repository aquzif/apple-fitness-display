<?php

namespace App\Livewire;

use App\Jobs\ProcessWorkoutImport;
use App\Models\User;
use App\Models\WorkoutImport;
use App\Models\WorkoutImportJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout as LivewireLayout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[LivewireLayout('layouts.app')]
class WorkoutImportForm extends Component
{
    use WithFileUploads;

    public ?WorkoutImport $import = null;

    public ?WorkoutImportJob $job = null;

    public $upload = null;

    /**
     * @var array<string, mixed>
     */
    public array $summary = [];

    public string $statusMessage = '';

    public int $weightsImported = 0;

    public ?int $displayedImportId = null;

    public function render(): View
    {
        return view('livewire.workout-import-form');
    }

    public function mount(): void
    {
        $this->refreshState();
    }

    public function handleUpload(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'upload' => ['required', 'file', 'mimes:xml,zip'],
        ], [
            'upload.required' => __('Wybierz plik exportu Apple Health.'),
            'upload.mimes' => __('Obsługiwane są pliki XML lub ZIP zawierające plik export.xml.'),
            'upload.max' => __('Plik jest zbyt duży (limit 10 GB).'),
        ]);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $activeJobExists = $user->importJobs()
            ->whereIn('status', [WorkoutImportJob::STATUS_QUEUED, WorkoutImportJob::STATUS_PROCESSING])
            ->exists();

        if ($activeJobExists) {
            $this->statusMessage = __('Poprzedni import jest nadal przetwarzany.');
            $this->addError('upload', __('Poprzedni import jest nadal przetwarzany.'));
            return;
        }

        /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
        $file = $this->upload;

        $storedPath = $file->store('apple-health/imports');

        $jobRecord = $user->importJobs()->create([
            'status' => WorkoutImportJob::STATUS_QUEUED,
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'client_extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
        ]);

        ProcessWorkoutImport::dispatch($jobRecord->id);

        $this->job = $jobRecord;
        $this->statusMessage = __('Rozpoczęto przetwarzanie importu Apple Health…');
        $this->upload = null;
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->job = $user->importJobs()
            ->whereIn('status', [WorkoutImportJob::STATUS_QUEUED, WorkoutImportJob::STATUS_PROCESSING])
            ->latest()
            ->first();

        if ($this->job) {
            $this->statusMessage = __('Trwa przetwarzanie importu Apple Health…');
        } else {
            $latestJob = $user->importJobs()->latest()->first();
            if ($latestJob && $latestJob->status === WorkoutImportJob::STATUS_FAILED) {
                $this->statusMessage = $latestJob->error_message
                    ?? __('Nie udało się przetworzyć pliku XML. Upewnij się, że to plik exportu Apple Health.');
            }
        }

        $this->syncLatestImport($user);
    }

    private function syncLatestImport(User $user): void
    {
        $latestImport = $user->workoutImports()->latest()->first();

        if (! $latestImport) {
            $this->import = null;
            $this->summary = [];
            $this->weightsImported = 0;
            $this->displayedImportId = null;
            return;
        }

        if ($this->displayedImportId === $latestImport->id) {
            $this->import = $latestImport;
            return;
        }

        $this->import = $latestImport->load('workouts.heartrates');
        $this->summary = [
            'totalCount' => $this->import->total_count,
            'totalDurationSeconds' => $this->import->total_duration_seconds,
            'totalEnergy' => $this->import->total_energy,
            'totalEnergyUnit' => $this->import->total_energy_unit,
            'totalBurntEnergy' => $this->import->total_burnt_energy,
            'totalBurntEnergyUnit' => $this->import->total_burnt_energy_unit,
            'totalDistance' => $this->import->total_distance,
            'totalDistanceUnit' => $this->import->total_distance_unit,
        ];
        $this->weightsImported = (int) $this->import->weights_imported;
        $this->displayedImportId = $this->import->id;

        if (! $this->job) {
            $this->statusMessage = $this->buildSuccessMessage();
        }
    }

    private function buildSuccessMessage(): string
    {
        $count = (int) ($this->summary['totalCount'] ?? 0);
        $workoutMessage = trans_choice('Załadowano :count trening.|Załadowano :count treningi.|Załadowano :count treningów.', $count, ['count' => $count]);

        $weightMessage = $this->weightsImported > 0
            ? trans_choice('Dodano :count pomiar wagi.|Dodano :count pomiary wagi.|Dodano :count pomiarów wagi.', $this->weightsImported, ['count' => $this->weightsImported])
            : __('Brak nowych pomiarów wagi w pliku.');

        return Str::finish($workoutMessage, ' ') . $weightMessage;
    }
}
