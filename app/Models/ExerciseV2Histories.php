<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseV2Histories extends Model
{
    use HasFactory;

    protected $table = 'exercise_v2_histories';

    protected $fillable = [
        'name',
        'phase',
        'subject',
        'element',
        'number_of_question',
        'type',
        'notes',
        'output_data',
        'user_id'
    ];

    protected $casts = [
        'output_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
