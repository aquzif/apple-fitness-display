<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutHeartrate extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'timestamp',
        'bpm',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'bpm' => 'integer',
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
