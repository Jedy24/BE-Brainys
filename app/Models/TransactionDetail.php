<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TransactionDetail
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $id_transaction
 * @property string $item_type
 * @property int $item_id
 * @property float $item_price
 * @property int $item_qty
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Transaction $transaction
 */
class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_transaction',
        'item_type',
        'item_id',
        'item_price',
        'item_qty',
    ];

    /**
     * Get the transaction that owns the detail.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'id_transaction');
    }
}
