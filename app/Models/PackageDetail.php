<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PackageDetail
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $id_package
 * @property string $name
 */
class PackageDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_package',
        'name',
    ];

    /**
     * Get the package that owns the detail.
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'id_package');
    }
}
