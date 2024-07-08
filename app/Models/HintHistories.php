<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HintHistories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pokok_materi',
        'grade',
        'subject',
        'elemen_capaian',
        'jumlah_soal',
        'notes',
        'generate_output',
        'user_id'
    ];

    protected $casts = [
        'generate_output' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
