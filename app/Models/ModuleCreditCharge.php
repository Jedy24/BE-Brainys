<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ModuleCreditCharge
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $credit_charged_generate
 * @property int $credit_charged_docx
 * @property int $credit_charged_pptx
 * @property int $credit_charged_xlsx
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ModuleCreditCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'credit_charged_generate',
        'credit_charged_docx',
        'credit_charged_pptx',
        'credit_charged_xlsx',
    ];
}
