<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_id')->nullable()->constrained('workout_imports')->nullOnDelete();
            $table->string('status', 32)->default('queued');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('client_extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_import_jobs');
    }
};
