<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Package
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $description
 * @property int $credit_add_monthly
 * @property int $price
 * @property \Illuminate\Database\Eloquent\Collection|PackageDetail[] $details
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'credit_add_monthly',
        'price',
    ];

    /**
     * Get the package details for the package.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function details()
    {
        return $this->hasMany(PackageDetail::class, 'id_package', 'id');
    }
}
