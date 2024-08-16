<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionPayment
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $id_transaction
 * @property string $pay_id
 * @property string $unique_code
 * @property string $service
 * @property string $service_name
 * @property float $amount
 * @property float|null $balance
 * @property float|null $fee
 * @property string|null $type_fee
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expired
 * @property string|null $qrcode_url
 * @property string|null $virtual_account
 * @property string|null $checkout_url
 * @property string|null $checkout_url_v2
 * @property string|null $checkout_url_v3
 * @property string|null $checkout_url_beta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TransactionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_transaction',
        'pay_id',
        'unique_code',
        'service',
        'service_name',
        'amount',
        'balance',
        'fee',
        'type_fee',
        'status',
        'expired',
        'qrcode_url',
        'virtual_account',
        'checkout_url',
        'checkout_url_v2',
        'checkout_url_v3',
        'checkout_url_beta',
    ];

    /**
     * Get the transaction associated with the payment.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction');
    }
}
