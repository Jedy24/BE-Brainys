<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseHistories extends Model
{
    protected $table = 'exercise_histories';

    protected $fillable = [
        'name',
        'subject',
        'grade',
        'number_of_question',
        'type',
        'notes',
        'output_data',
        'user_id'
    ];

    protected $casts = [
        'output_data' => 'json',
    ];

    // Tambahkan relasi ke model User jika diperlukan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
