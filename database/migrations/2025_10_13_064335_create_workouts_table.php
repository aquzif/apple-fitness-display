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
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_import_id')->constrained()->cascadeOnDelete();
            $table->string('activity_key')->nullable();
            $table->string('label')->nullable();
            $table->string('icon')->nullable();
            $table->string('icon_symbol')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('icon_background')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('duration_text')->nullable();
            $table->decimal('distance_value', 10, 2)->nullable();
            $table->string('distance_unit')->nullable();
            $table->decimal('energy_value', 10, 2)->nullable();
            $table->string('energy_unit')->nullable();
            $table->decimal('swim_value', 10, 2)->nullable();
            $table->string('swim_unit')->nullable();
            $table->json('metadata')->nullable();
            $table->json('statistics')->nullable();
            $table->json('events')->nullable();
            $table->string('source_name')->nullable();
            $table->decimal('total_flights_climbed', 10, 2)->nullable();
            $table->decimal('total_elevation_gain', 10, 2)->nullable();
            $table->string('total_elevation_gain_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
