<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseHistory extends Model
{
    protected $table = 'exercise_histories';

    protected $fillable = [
        'name', 'subject', 'grade', 'number_of_question', 'type', 'notes', 'output_data', 'user_id'
    ];

    // Tambahkan relasi ke model User jika diperlukan
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
