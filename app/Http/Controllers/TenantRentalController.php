<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TenantRentalController extends Controller
{
    /**
     * Get rental information for the authenticated tenant
     */
    public function getRental(Request $request)
    {
        $tenant = Auth::guard('tenant')->user();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get the unit assigned to this tenant
        $unit = Unit::where('tenant_id', $tenant->id)
            ->where('is_occupied', true)
            ->with(['property.landlord'])
            ->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'No rental unit found for this tenant.',
            ], 404);
        }

        $property = $unit->property;
        $landlord = $property->landlord ?? null;

        // Get photos - combine property photos and unit photos
        $photos = [];
        if ($unit->photos && is_array($unit->photos) && count($unit->photos) > 0) {
            $photos = $unit->photos;
        } elseif ($property->photos && is_array($property->photos) && count($property->photos) > 0) {
            $photos = $property->photos;
        } elseif ($property->main_photo) {
            $photos = [$property->main_photo];
        }

        // Convert photo paths to URLs
        $photos = array_map(function ($photo) {
            if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
                return $photo;
            }
            if (!str_starts_with($photo, '/storage/')) {
                return '/storage/' . ltrim($photo, '/');
            }
            return $photo;
        }, $photos);

        // Build property address
        $address = trim(implode(', ', array_filter([
            $property->street_address,
            $property->city,
            $property->state,
            $property->zip_code
        ])));

        // Determine status
        $status = 'Active Lease';
        if ($unit->lease_end) {
            $leaseEnd = is_string($unit->lease_end) ? Carbon::parse($unit->lease_end) : $unit->lease_end;
            if ($leaseEnd && $leaseEnd->isPast()) {
                $status = 'Lease Expired';
            } elseif ($leaseEnd && $leaseEnd->diffInDays(now()) < 30) {
                $status = 'Lease Ending Soon';
            }
        }

        // Get landlord info
        $landlordInfo = null;
        if ($landlord) {
            $landlordName = $landlord->name;
            $landlordInitials = strtoupper(substr($landlordName, 0, 2));
            if (strlen($landlordName) > 2) {
                $words = explode(' ', $landlordName);
                if (count($words) > 1) {
                    $landlordInitials = strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
                }
            }

            $landlordInfo = [
                'id' => $landlord->id,
                'name' => $landlordName,
                'role' => 'Property Manager',
                'avatar' => $landlordInitials,
            ];
        }

        return response()->json([
            'success' => true,
            'rental' => [
                'id' => $unit->id,
                'property_name' => $property->name,
                'address' => $address,
                'unit_number' => $unit->unit_number,
                'status' => $status,
                'bedrooms' => $unit->bedrooms ?? 0,
                'bathrooms' => $unit->bathrooms ?? 0,
                'monthly_rent' => (float) $unit->monthly_rent,
                'security_deposit' => (float) ($unit->security_deposit ?? $unit->lease_deposit ?? 0),
                'lease_start' => $unit->lease_start ? (is_string($unit->lease_start) ? Carbon::parse($unit->lease_start)->format('Y-m-d') : $unit->lease_start->format('Y-m-d')) : null,
                'lease_end' => $unit->lease_end ? (is_string($unit->lease_end) ? Carbon::parse($unit->lease_end)->format('Y-m-d') : $unit->lease_end->format('Y-m-d')) : null,
                'photos' => $photos,
            ],
            'landlord' => $landlordInfo,
        ]);
    }
}
