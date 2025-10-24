<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DunningEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payment_request_id',
        'attempt_no',
        'type',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'attempt_no' => 'integer',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}
