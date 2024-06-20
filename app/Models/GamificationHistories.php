<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamificationHistories extends Model
{
    use HasFactory;

    protected $table = 'gamification_histories';

    protected $fillable = [
        'name',
        'subject',
        'grade',
        'notes',
        'output_data',
        'user_id',
    ];

    protected $casts = [
        'output_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
