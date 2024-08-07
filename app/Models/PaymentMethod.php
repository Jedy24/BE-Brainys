<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PaymentMethod
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $thumbnail
 * @property string $name
 * @property string $code
 * @property bool $status
 * @property string $provider
 * @property string $provider_code
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'thumbnail',
        'name',
        'code',
        'status',
        'provider',
        'provider_code',
        'description',
    ];
}
