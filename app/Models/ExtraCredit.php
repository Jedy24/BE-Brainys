<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ExtraCredit
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property int $credit_amount
 * @property int $price
 */
class ExtraCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'credit_amount',
        'price',
    ];
}
