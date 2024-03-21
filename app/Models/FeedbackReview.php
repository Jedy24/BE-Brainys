<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FeedbackReview extends Model
{
    protected $fillable = [
        'user_id', 'rating', 'product', 'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}