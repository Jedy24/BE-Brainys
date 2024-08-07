<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $roles
 * @property bool $is_active
 * @property string $password
 * @property string|null $otp
 * @property \Illuminate\Support\Carbon|null $otp_expiry
 * @property \Illuminate\Support\Carbon|null $otp_verified_at
 * @property bool $profile_completed
 * @property int $limit_generate
 * @property string|null $school_name
 * @property string|null $school_level
 * @property string|null $profession
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Provider[] $providers
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MaterialHistories[] $materialHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SyllabusHistories[] $syllabusHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ExerciseHistories[] $exerciseHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ExerciseV2Histories[] $exerciseV2History
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\HintHistories[] $hintHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BahanAjarHistories[] $bahanAjarHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\GamificationHistories[] $gamificationHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AlurTujuanPembelajaranHistories[] $alurTujuanPembelajaranHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ModulAjarHistories[] $modulAjarHistory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FeedbackReview[] $feedbackReviews
 */
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

    /**
     * Get the providers for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers()
    {
        return $this->hasMany(Provider::class, 'user_id', 'id');
    }

    /**
     * Get the material history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function materialHistory()
    {
        return $this->hasMany(MaterialHistories::class, 'user_id', 'id');
    }

    /**
     * Get the syllabus history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function syllabusHistory()
    {
        return $this->hasMany(SyllabusHistories::class, 'user_id', 'id');
    }

    /**
     * Get the exercise history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exerciseHistory()
    {
        return $this->hasMany(ExerciseHistories::class, 'user_id', 'id');
    }

    /**
     * Get the exercise V2 history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exerciseV2History()
    {
        return $this->hasMany(ExerciseV2Histories::class, 'user_id', 'id');
    }

    /**
     * Get the hint history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hintHistory()
    {
        return $this->hasMany(HintHistories::class, 'user_id', 'id');
    }

    /**
     * Get the bahan ajar history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bahanAjarHistory()
    {
        return $this->hasMany(BahanAjarHistories::class, 'user_id', 'id');
    }

    /**
     * Get the gamification history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gamificationHistory()
    {
        return $this->hasMany(GamificationHistories::class, 'user_id', 'id');
    }

    /**
     * Get the alur tujuan pembelajaran history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function alurTujuanPembelajaranHistory()
    {
        return $this->hasMany(AlurTujuanPembelajaranHistories::class, 'user_id', 'id');
    }

    /**
     * Get the modul ajar history for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function modulAjarHistory()
    {
        return $this->hasMany(ModulAjarHistories::class, 'user_id', 'id');
    }

    /**
     * Get the feedback reviews for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feedbackReviews()
    {
        return $this->hasMany(FeedbackReview::class, 'user_id', 'id');
    }

    /**
     * Calculate the sum of all related histories.
     *
     * @return int
     */
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

    /**
     * Get the user packages for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userPackages()
    {
        return $this->hasMany(UserPackage::class, 'id_user', 'id');
    }
}
