<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
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

    public function providers(){
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

    public function generateAllSum(){
        return ($this->materialHistory()->count() + $this->syllabusHistory()->count() + $this->exerciseHistory()->count());
    }

    public function feedbackReviews()
    {
        return $this->hasMany(FeedbackReview::class, 'user_id', 'id');
    }

    public static function getGenerateAllSumGroupedByDate($startDate, $endDate)
    {
        return DB::table('users')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM((SELECT COUNT(*) FROM material_histories WHERE material_histories.user_id = users.id) + (SELECT COUNT(*) FROM syllabus_histories WHERE syllabus_histories.user_id = users.id) + (SELECT COUNT(*) FROM exercise_histories WHERE exercise_histories.user_id = users.id)) as total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }
}
