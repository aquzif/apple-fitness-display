<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'total_count',
        'total_duration_seconds',
        'total_energy',
        'total_energy_unit',
        'total_distance',
        'total_distance_unit',
        'total_burnt_energy',
        'total_burnt_energy_unit',
    ];

    protected $casts = [
        'total_count' => 'integer',
        'total_duration_seconds' => 'integer',
        'total_energy' => 'float',
        'total_distance' => 'float',
        'total_burnt_energy' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
}
