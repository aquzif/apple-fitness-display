<?php

namespace App\Livewire;

use App\Models\WorkoutImport;
use App\Services\AppleHealth\AppleHealthImportService;
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

    public $upload = null;

    /**
     * @var array<string, mixed>
     */
    public array $summary = [];

    public string $statusMessage = '';

    public int $weightsImported = 0;

    public function render(): View
    {
        return view('livewire.workout-import-form');
    }

    public function handleUpload(AppleHealthImportService $importService): void
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

        /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file */
        $file = $this->upload;

        try {
            $result = $importService->import($user, $file);
        } catch (\RuntimeException $exception) {
            $this->statusMessage = $exception->getMessage();
            $this->addError('upload', $exception->getMessage());
            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->statusMessage = __('Nie udało się przetworzyć pliku XML. Upewnij się, że to plik exportu Apple Health.');
            $this->addError('upload', __('Nie udało się przetworzyć pliku XML.'));
            return;
        }

        $this->import = $result['import'];
        $this->summary = $result['summary'];
        $this->weightsImported = $result['weights_imported'];
        $this->upload = null;

        $count = (int) ($this->summary['totalCount'] ?? 0);
        $workoutMessage = trans_choice('Załadowano :count trening.|Załadowano :count treningi.|Załadowano :count treningów.', $count, ['count' => $count]);

        $weightMessage = $this->weightsImported > 0
            ? trans_choice('Dodano :count pomiar wagi.|Dodano :count pomiary wagi.|Dodano :count pomiarów wagi.', $this->weightsImported, ['count' => $this->weightsImported])
            : __('Brak nowych pomiarów wagi w pliku.');

        $this->statusMessage = Str::finish($workoutMessage, ' ') . $weightMessage;
    }
}
