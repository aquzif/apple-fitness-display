<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workout_imports', function (Blueprint $table) {
            $table->unsignedInteger('weights_imported')->default(0)->after('total_burnt_energy_unit');
        });
    }

    public function down(): void
    {
        Schema::table('workout_imports', function (Blueprint $table) {
            $table->dropColumn('weights_imported');
        });
    }
};
