<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlurTujuanPembelajaranHistories extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phase',
        'subject',
        'element',
        'weeks',
        'notes',
        'output_data',
    ];

    protected $casts = [
        'output_data' => 'array',
    ];
}
