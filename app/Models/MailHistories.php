<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailHistories extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_surat',
        'jenis_surat',
        'tujuan_surat',
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
