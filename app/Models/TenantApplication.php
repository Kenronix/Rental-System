<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'id_picture',
        'name',
        'email',
        'phone',
        'whatsapp',
        'occupation',
        'monthly_income',
        'address',
        'number_of_people',
        'mother_name',
        'mother_address',
        'mother_phone',
        'mother_email',
        'father_name',
        'father_address',
        'father_phone',
        'father_email',
        'status',
        'notes'
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
