<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapaianPembelajaran extends Model
{
    use HasFactory;

    protected $table = 'capaian_pembelajaran';

    protected $fillable = [
        'mata_pelajaran',
        'fase',
        'element',
        'subelemen',
        'capaian_pembelajaran',
        'capaian_pembelajaran_redaksi',
    ];
}
