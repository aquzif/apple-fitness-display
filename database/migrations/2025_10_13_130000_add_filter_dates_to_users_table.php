<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->date('workout_filter_start_date')->nullable()->after('remember_token');
            $table->date('workout_filter_end_date')->nullable()->after('workout_filter_start_date');
            $table->date('weight_filter_start_date')->nullable()->after('workout_filter_end_date');
            $table->date('weight_filter_end_date')->nullable()->after('weight_filter_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'workout_filter_start_date',
                'workout_filter_end_date',
                'weight_filter_start_date',
                'weight_filter_end_date',
            ]);
        });
    }
};
