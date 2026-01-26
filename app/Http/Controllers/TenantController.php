<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Get all tenants for the authenticated landlord
     */
    public function index(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get all units owned by this landlord
        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $landlordUnits = Unit::whereIn('property_id', $properties)->pluck('id');
        
        // Get actual tenants (assigned to units) - refresh from database
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with(['tenant', 'property'])
            ->get();

        // Transform data to include tenant info with unit and property details
        $tenants = $units->map(function ($unit) {
            if (!$unit->tenant) {
                return null;
            }

            return [
                'id' => $unit->tenant->id,
                'application_id' => null,
                'name' => $unit->tenant->name,
                'email' => $unit->tenant->email,
                'phone' => $unit->tenant->phone,
                'address' => $unit->tenant->address,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'unit_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'rent' => $unit->monthly_rent,
                'lease_start' => $unit->lease_start,
                'lease_end' => $unit->lease_end,
                'status' => $unit->is_occupied ? 'active' : 'inactive',
                'type' => 'tenant', // Mark as actual tenant
            ];
        })->filter()->values();

        // Get tenant applications
        $applications = TenantApplication::whereIn('unit_id', $landlordUnits)
            ->with(['unit.property'])
            ->get();

        // Transform applications to match tenant format
        $applicationTenants = $applications->map(function ($application) {
            $unit = $application->unit;
            if (!$unit) {
                return null;
            }

            return [
                'id' => null,
                'application_id' => $application->id,
                'name' => $application->name,
                'email' => $application->email,
                'phone' => $application->phone,
                'address' => $application->address,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'unit_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'rent' => $unit->monthly_rent,
                'lease_start' => null,
                'lease_end' => null,
                'status' => $application->status, // pending, approved, rejected
                'type' => 'application', // Mark as application
                'monthly_income' => $application->monthly_income,
                'occupation' => $application->occupation,
                'number_of_people' => $application->number_of_people,
            ];
        })->filter()->values();

        // Combine tenants and applications
        $allTenants = $tenants->concat($applicationTenants)->sortByDesc(function ($item) {
            // Sort by status: active first, then pending, then others
            $order = ['active' => 1, 'pending' => 2, 'approved' => 3, 'rejected' => 4, 'inactive' => 5];
            return $order[$item['status']] ?? 99;
        })->values();

        // Calculate statistics - query fresh from database
        // Count unique tenants assigned to this landlord's units (only occupied units)
        $uniqueTenantIds = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->distinct()
            ->pluck('tenant_id');
        $totalTenants = $uniqueTenantIds->count();
        
        // Count active leases (units with active tenants and is_occupied = true)
        $activeLeases = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->count();
        
        // Count pending applications
        $pendingInvites = $applications->where('status', 'pending')->count();
        
        // Payment issues (placeholder - you can implement payment tracking later)
        $paymentIssues = 0;

        return response()->json([
            'success' => true,
            'tenants' => $allTenants,
            'statistics' => [
                'total_tenants' => $totalTenants,
                'active_leases' => $activeLeases,
                'pending_invites' => $pendingInvites,
                'payment_issues' => $paymentIssues,
            ],
        ]);
    }

    /**
     * Get a specific tenant with their units
     */
    public function show($id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get tenant with their units
        $tenant = Tenant::with(['units.property'])->find($id);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Verify tenant belongs to landlord's properties
        $landlordProperties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $tenantUnits = $tenant->units->filter(function ($unit) use ($landlordProperties) {
            return $landlordProperties->contains($unit->property_id);
        });

        if ($tenantUnits->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found in your properties.',
            ], 404);
        }

        // Format units data
        $unitsData = $tenantUnits->map(function ($unit) {
            return [
                'id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'monthly_rent' => $unit->monthly_rent,
                'lease_start' => $unit->lease_start,
                'lease_end' => $unit->lease_end,
                'status' => $unit->is_occupied ? 'active' : 'inactive',
            ];
        });

        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'units' => $unitsData,
            ],
        ]);
    }
}
