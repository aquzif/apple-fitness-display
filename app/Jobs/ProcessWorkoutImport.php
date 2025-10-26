<?php

namespace App\Jobs;

use App\Models\WorkoutImportJob;
use App\Services\AppleHealth\AppleHealthImportService;
use App\Services\AppleHealth\AppleHealthUploadedFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWorkoutImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobId)
    {
    }

    public function handle(AppleHealthImportService $importService): void
    {
        $jobRecord = WorkoutImportJob::query()->with('user')->find($this->jobId);

        if (! $jobRecord || ! $jobRecord->user) {
            return;
        }

        if ($jobRecord->status === WorkoutImportJob::STATUS_FINISHED) {
            return;
        }

        if (! $jobRecord->file_path) {
            $jobRecord->fill([
                'status' => WorkoutImportJob::STATUS_FAILED,
                'error_message' => __('Brak pliku importu Apple Health do przetworzenia.'),
                'finished_at' => now(),
            ])->save();

            return;
        }

        $jobRecord->fill([
            'status' => WorkoutImportJob::STATUS_PROCESSING,
            'started_at' => now(),
        ])->save();

        $upload = new AppleHealthUploadedFile(
            $jobRecord->file_path,
            $jobRecord->original_filename,
            $jobRecord->client_extension,
            $jobRecord->mime_type,
        );

        $result = $importService->import($jobRecord->user, $upload);

        $jobRecord->fill([
            'status' => WorkoutImportJob::STATUS_FINISHED,
            'import_id' => $result['import']->id,
            'finished_at' => now(),
            'error_message' => null,
            'file_path' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $jobRecord = WorkoutImportJob::find($this->jobId);

        if (! $jobRecord) {
            return;
        }

        $jobRecord->fill([
            'status' => WorkoutImportJob::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ])->save();

        Log::error('Workout import failed', [
            'job_id' => $this->jobId,
            'exception' => $exception,
        ]);
    }
}
