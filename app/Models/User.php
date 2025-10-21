<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'workout_filter_start_date',
        'workout_filter_end_date',
        'weight_filter_start_date',
        'weight_filter_end_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'workout_filter_start_date' => 'immutable_date',
            'workout_filter_end_date' => 'immutable_date',
            'weight_filter_start_date' => 'immutable_date',
            'weight_filter_end_date' => 'immutable_date',
        ];
    }

    public function workoutImports(): HasMany
    {
        return $this->hasMany(WorkoutImport::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(Weight::class);
    }

    public function importJobs(): HasMany
    {
        return $this->hasMany(WorkoutImportJob::class);
    }
}
