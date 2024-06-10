<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoInviteEmail extends Model
{
    use HasFactory;

    protected $table = 'auto_invite_emails';

    protected $fillable = [
        'email_domain',
        'is_active',
    ];
}
