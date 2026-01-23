<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Unit extends Model{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'unit_number',
        'unit_type',
        'bedrooms',
        'bathrooms',
        'square_footage',
        'monthly_rent',
        'security_deposit',
        'advance_deposit',
        'description',
        'photos',
        'status',
        'is_occupied',
        'tenant_id',
        'lease_start',
        'lease_end',
        'lease_duration',
        'lease_amount',
        'lease_deposit',
    ];

    public function landlord(): BelongsTo
    {
        return $this->belongTo(Landlord::class);
    }
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    public function getPhotosAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    public function setPhotosAttribute($value)
    {
        $this->attributes['photos'] = $value ? json_encode($value) : null;
    }
}