<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Transaction
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $id_user
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $transaction_code
 * @property string $transaction_name
 * @property float $amount_sub
 * @property float $amount_fee
 * @property float $amount_total
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|TransactionDetail[] $details
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'transaction_date',
        'transaction_code',
        'transaction_name',
        'amount_sub',
        'amount_fee',
        'amount_total',
        'status',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Get the details for the transaction.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class, 'id_transaction');
    }
}
