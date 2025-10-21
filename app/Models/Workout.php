<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_import_id',
        'activity_key',
        'label',
        'icon',
        'icon_symbol',
        'accent_color',
        'icon_background',
        'start_date',
        'end_date',
        'duration_seconds',
        'duration_text',
        'distance_value',
        'distance_unit',
        'energy_value',
        'energy_unit',
        'swim_value',
        'swim_unit',
        'metadata',
        'statistics',
        'events',
        'source_name',
        'total_flights_climbed',
        'total_elevation_gain',
        'total_elevation_gain_unit',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'duration_seconds' => 'integer',
        'distance_value' => 'float',
        'energy_value' => 'float',
        'swim_value' => 'float',
        'metadata' => 'array',
        'statistics' => 'array',
        'events' => 'array',
        'total_flights_climbed' => 'float',
        'total_elevation_gain' => 'float',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(WorkoutImport::class, 'workout_import_id');
    }

    public function heartrates(): HasMany
    {
        return $this->hasMany(WorkoutHeartrate::class);
    }
}
