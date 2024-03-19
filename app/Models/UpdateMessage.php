<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'message',
    ];
}
