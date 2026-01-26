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
        'profile_picture',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'whatsapp',
        'occupation',
        'monthly_income',
        'address',
        'number_of_people',
        'lease_duration_months',
        'lease_start_date',
        'reference1_name',
        'reference1_address',
        'reference1_phone',
        'reference1_email',
        'reference1_relationship',
        'reference2_name',
        'reference2_address',
        'reference2_phone',
        'reference2_email',
        'reference2_relationship',
        'status',
        'notes'
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
