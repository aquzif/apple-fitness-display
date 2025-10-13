<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedBigInteger('total_duration_seconds')->default(0);
            $table->decimal('total_energy', 10, 2)->default(0);
            $table->string('total_energy_unit')->default('kcal');
            $table->decimal('total_distance', 10, 2)->default(0);
            $table->string('total_distance_unit')->default('km');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_imports');
    }
};
