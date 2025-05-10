<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubrikNilaiHistories extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phase',
        'subject',
        'element',
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
