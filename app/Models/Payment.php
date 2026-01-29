<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'payment_type',
        'amount',
        'water',
        'electricity',
        'internet',
        'payment_date',
        'due_date',
        'status',
        'review_status',
        'payment_method',
        'reference_number',
        'payment_proof',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'water' => 'decimal:2',
            'electricity' => 'decimal:2',
            'internet' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
