<?php

namespace Tests\Feature;

use App\Jobs\ProcessWorkoutImport;
use App\Livewire\WorkoutImportForm;
use App\Models\User;
use App\Models\WorkoutImport;
use App\Models\WorkoutImportJob;
use App\Services\AppleHealth\AppleHealthImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WorkoutImportFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_dispatches_workout_import_job(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WorkoutImportForm::class)
            ->set('upload', UploadedFile::fake()->create('export.xml', 10))
            ->call('handleUpload')
            ->assertHasNoErrors()
            ->assertSet('job.status', WorkoutImportJob::STATUS_QUEUED);

        $jobRecord = WorkoutImportJob::first();
        $this->assertNotNull($jobRecord);
        $this->assertSame(WorkoutImportJob::STATUS_QUEUED, $jobRecord->status);
        $this->assertNotNull($jobRecord->file_path);
        $this->assertSame($user->id, $jobRecord->user_id);

        Queue::assertPushed(ProcessWorkoutImport::class, function (ProcessWorkoutImport $job) use ($jobRecord) {
            return $job->jobId === $jobRecord->id;
        });
    }

    public function test_cannot_start_new_import_when_previous_is_running(): void
    {
        Storage::fake('local');
        Queue::fake();

        $user = User::factory()->create();
        $user->importJobs()->create([
            'status' => WorkoutImportJob::STATUS_PROCESSING,
            'file_path' => 'apple-health/imports/existing.xml',
        ]);

        Livewire::actingAs($user)
            ->test(WorkoutImportForm::class)
            ->set('upload', UploadedFile::fake()->create('export.xml', 10))
            ->call('handleUpload')
            ->assertHasErrors(['upload'])
            ->assertSet('statusMessage', __('Poprzedni import jest nadal przetwarzany.'));

        Queue::assertNotPushed(ProcessWorkoutImport::class);
    }

    public function test_job_updates_status_after_processing(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $import = $user->workoutImports()->create([
            'original_filename' => 'export.xml',
            'total_count' => 1,
            'total_duration_seconds' => 100,
            'total_energy' => 123.45,
            'total_energy_unit' => 'kcal',
            'total_burnt_energy' => 100.0,
            'total_burnt_energy_unit' => 'kcal',
            'total_distance' => 1.5,
            'total_distance_unit' => 'km',
            'weights_imported' => 2,
        ]);

        $jobRecord = $user->importJobs()->create([
            'status' => WorkoutImportJob::STATUS_QUEUED,
            'file_path' => 'apple-health/imports/pending.xml',
            'original_filename' => 'pending.xml',
            'client_extension' => 'xml',
            'mime_type' => 'text/xml',
        ]);

        $service = \Mockery::mock(AppleHealthImportService::class);
        $service->shouldReceive('import')
            ->once()
            ->withArgs(function ($userArgument, $upload) use ($user, $jobRecord) {
                return $userArgument->is($user)
                    && $upload->path() === $jobRecord->file_path;
            })
            ->andReturn(['import' => $import, 'summary' => [], 'weights_imported' => 2]);

        $this->app->instance(AppleHealthImportService::class, $service);

        $job = new ProcessWorkoutImport($jobRecord->id);
        $job->handle(app(AppleHealthImportService::class));

        $jobRecord->refresh();

        $this->assertSame(WorkoutImportJob::STATUS_FINISHED, $jobRecord->status);
        $this->assertSame($import->id, $jobRecord->import_id);
        $this->assertNull($jobRecord->file_path);
        $this->assertNotNull($jobRecord->started_at);
        $this->assertNotNull($jobRecord->finished_at);
    }
}
