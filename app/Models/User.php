<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'roles',
        'is_active',
        'password',
        'otp',
        'otp_expiry',
        'otp_verified_at',
        'profile_completed',
        'limit_generate',
        'school_name',
        'school_level',
        'profession',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function providers()
    {
        return $this->hasMany(Provider::class, 'user_id', 'id');
    }

    public function materialHistory()
    {
        return $this->hasMany(MaterialHistories::class, 'user_id', 'id');
    }

    public function syllabusHistory()
    {
        return $this->hasMany(SyllabusHistories::class, 'user_id', 'id');
    }

    public function exerciseHistory()
    {
        return $this->hasMany(ExerciseHistories::class, 'user_id', 'id');
    }

    public function exerciseV2History()
    {
        return $this->hasMany(ExerciseV2Histories::class, 'user_id', 'id');
    }

    public function hintHistory()
    {
        return $this->hasMany(HintHistories::class, 'user_id', 'id');
    }

    public function bahanAjarHistory()
    {
        return $this->hasMany(BahanAjarHistories::class, 'user_id', 'id');
    }

    public function gamificationHistory()
    {
        return $this->hasMany(GamificationHistories::class, 'user_id', 'id');
    }

    public function alurTujuanPembelajaranHistory()
    {
        return $this->hasMany(AlurTujuanPembelajaranHistories::class, 'user_id', 'id');
    }

    public function modulAjarHistory()
    {
        return $this->hasMany(ModulAjarHistories::class, 'user_id', 'id');
    }

    public function generateAllSum()
    {
        return (
            $this->materialHistory()->count()
            + $this->syllabusHistory()->count()
            + $this->exerciseHistory()->count()
            + $this->exerciseV2History()->count()
            + $this->bahanAjarHistory()->count()
            + $this->gamificationHistory()->count()
            + $this->hintHistory()->count()
            + $this->alurTujuanPembelajaranHistory()->count()
            + $this->modulAjarHistory()->count()
        );
    }

    public function feedbackReviews()
    {
        return $this->hasMany(FeedbackReview::class, 'user_id', 'id');
    }
}
