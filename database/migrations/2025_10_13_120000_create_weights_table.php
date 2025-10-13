<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->float('value');
            $table->string('unit', 16)->default('kg');
            $table->string('source_name')->nullable();
            $table->string('device')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weights');
    }
};
