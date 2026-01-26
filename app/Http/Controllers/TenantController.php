<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        // Fetch units first
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with('property')
            ->get();

        // Get all unique tenant IDs
        $tenantIds = $units->pluck('tenant_id')->unique()->filter()->values()->toArray();
        
        // Fetch all tenants at once to avoid N+1 queries
        $tenantsMap = Tenant::whereIn('id', $tenantIds)
            ->get()
            ->keyBy('id');

        // Get all unit IDs
        $unitIds = $units->pluck('id')->toArray();
        
        // Fetch all approved tenant applications for these units
        // This will give us the original names from the applications
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id'); // Key by unit_id so we can quickly find the application for each unit

        // Transform data to include tenant info with unit and property details
        $tenants = $units->map(function ($unit) use ($tenantsMap, $applicationsMap) {
            // Get tenant from the map using the unit's tenant_id
            $tenant = $tenantsMap->get($unit->tenant_id);
            
            if (!$tenant) {
                return null;
            }

            // Get the approved application for this unit to get the original name
            $application = $applicationsMap->get($unit->id);
            
            // Use name from application - combine first_name, middle_name, and last_name if available
            $displayName = $tenant->name;
            if ($application) {
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (!empty($fullName)) {
                    $displayName = $fullName;
                } else {
                    $displayName = $application->name ?? $tenant->name; // Fallback to name field
                }
            }
            $displayEmail = $application ? $application->email : $tenant->email;
            $displayPhone = $application ? $application->phone : $tenant->phone;
            $displayAddress = $application ? $application->address : $tenant->address;
            
            // Get created_at from application if available, otherwise use unit's lease_start or updated_at
            $createdAt = $application ? $application->created_at : ($unit->lease_start ?? $unit->updated_at);

            // Get profile picture from application if available
            $profilePicture = null;
            if ($application && $application->profile_picture) {
                $profilePicture = $application->profile_picture;
                // Convert to URL format if needed
                if (!str_starts_with($profilePicture, 'http://') && !str_starts_with($profilePicture, 'https://')) {
                    if (!str_starts_with($profilePicture, '/storage/')) {
                        $profilePicture = '/storage/' . ltrim($profilePicture, '/');
                    }
                }
            }
            
            return [
                'id' => $tenant->id,
                'application_id' => $application ? $application->id : null,
                'name' => $displayName, // Get name from tenant_application, not tenant table
                'email' => $displayEmail,
                'phone' => $displayPhone,
                'address' => $displayAddress,
                'profile_picture' => $profilePicture, // Add profile picture from application
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'unit_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'rent' => $unit->monthly_rent,
                'lease_start' => $unit->lease_start,
                'lease_end' => $unit->lease_end,
                'status' => $unit->is_occupied ? 'active' : 'inactive',
                'type' => 'tenant', // Mark as actual tenant
                'created_at' => $createdAt, // Add created_at for sorting
            ];
        })->filter()->values();

        // Get tenant applications (exclude approved ones since they become actual tenants)
        $applications = TenantApplication::whereIn('unit_id', $landlordUnits)
            ->where('status', '!=', 'approved') // Exclude approved applications
            ->with(['unit.property'])
            ->get();

        // Transform applications to match tenant format
        $applicationTenants = $applications->map(function ($application) {
            $unit = $application->unit;
            if (!$unit) {
                return null;
            }

            // Combine first_name, middle_name, and last_name for display name
            $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $application->name; // Fallback to name field if separate fields are empty
            }
            
            // Get profile picture and convert to URL format if needed
            $profilePicture = null;
            if ($application->profile_picture) {
                $profilePicture = $application->profile_picture;
                if (!str_starts_with($profilePicture, 'http://') && !str_starts_with($profilePicture, 'https://')) {
                    if (!str_starts_with($profilePicture, '/storage/')) {
                        $profilePicture = '/storage/' . ltrim($profilePicture, '/');
                    }
                }
            }
            
            return [
                'id' => null,
                'application_id' => $application->id,
                'name' => $fullName,
                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'email' => $application->email,
                'profile_picture' => $profilePicture, // Add profile picture
                'phone' => $application->phone,
                'address' => $application->address,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'unit_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'rent' => $unit->monthly_rent,
                'lease_start' => null,
                'lease_end' => null,
                'status' => $application->status, // pending, rejected
                'type' => 'application', // Mark as application
                'monthly_income' => $application->monthly_income,
                'occupation' => $application->occupation,
                'number_of_people' => $application->number_of_people,
                'created_at' => $application->created_at, // Add created_at for sorting
            ];
        })->filter()->values();

        // Combine tenants and applications
        // Sort by created_at (newest first) - newly added tenants will appear first
        $allTenants = $tenants->concat($applicationTenants)->sortByDesc(function ($item) {
            // Get created_at timestamp - use application created_at if available, otherwise use lease_start or current time
            $createdAt = $item['created_at'] ?? now();
            if ($createdAt instanceof \Carbon\Carbon) {
                return $createdAt->timestamp;
            }
            return is_string($createdAt) ? strtotime($createdAt) : time();
        })->values();

        // Calculate statistics - query fresh from database
        // Count all tenant-unit assignments (matches what's shown in the table)
        // This counts all occupied units with tenants, not unique tenants
        $totalTenants = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->count();
        
        // Count active leases (units with active tenants and is_occupied = true)
        $activeLeases = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->count();
        
        // Count pending applications (query fresh from database, exclude approved)
        $pendingInvites = TenantApplication::whereIn('unit_id', $landlordUnits)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'tenants' => $allTenants,
            'statistics' => [
                'total_tenants' => $totalTenants,
                'active_leases' => $activeLeases,
                'pending_invites' => $pendingInvites,
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

        // Get the specific unit_id from query parameter if provided
        $requestedUnitId = request()->query('unit_id');

        // Get tenant directly from database to ensure we get fresh, accurate data
        $tenant = Tenant::find($id);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Reload tenant to ensure fresh data (bypass any potential caching)
        $tenant->refresh();
        
        // Get tenant's units separately to ensure accurate data
        $tenantUnits = Unit::where('tenant_id', $tenant->id)
            ->with('property')
            ->get();

        // Verify tenant belongs to landlord's properties
        $landlordProperties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $tenantUnits = $tenantUnits->filter(function ($unit) use ($landlordProperties) {
            return $landlordProperties->contains($unit->property_id);
        });

        if ($tenantUnits->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found in your properties.',
            ], 404);
        }

        // Get tenant application data (including references) for this tenant
        // Find the application for the specific unit being viewed
        // Each unit should have its own application, so we match by unit_id and tenant email
        // We also verify that the unit's tenant_id matches the tenant's id to ensure correct matching
        $tenantApplication = null;
        
        // Priority 1: If a specific unit_id is requested, find the application for that exact unit
        if ($requestedUnitId) {
            // Verify the requested unit belongs to this tenant and has this tenant assigned
            $requestedUnit = $tenantUnits->firstWhere('id', $requestedUnitId);
            if ($requestedUnit && $requestedUnit->tenant_id == $tenant->id) {
                // Find application for this specific unit and tenant email
                // Double-check: unit_id matches AND email matches AND unit's tenant_id matches
                $tenantApplication = TenantApplication::where('unit_id', $requestedUnitId)
                    ->where('email', $tenant->email)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }
        
        // Priority 2: If no specific unit requested or no application found, find for units assigned to this tenant
        if (!$tenantApplication) {
            foreach ($tenantUnits as $unit) {
                // Only check units that are actually assigned to this tenant
                if ($unit->tenant_id == $tenant->id) {
                    $application = TenantApplication::where('unit_id', $unit->id)
                        ->where('email', $tenant->email)
                        ->where('status', 'approved')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($application) {
                        $tenantApplication = $application;
                        break; // Use the first matching application
                    }
                }
            }
        }
        
        // Priority 3: Fallback - if still no application found, try matching by unit_id only (for the requested unit)
        // This handles edge cases where email might have changed
        if (!$tenantApplication && $requestedUnitId) {
            $requestedUnit = $tenantUnits->firstWhere('id', $requestedUnitId);
            if ($requestedUnit && $requestedUnit->tenant_id == $tenant->id) {
                $tenantApplication = TenantApplication::where('unit_id', $requestedUnitId)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        // Format units data with complete information
        $unitsData = $tenantUnits->map(function ($unit) {
            return [
                'id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'unit_type' => $unit->unit_type,
                'bedrooms' => $unit->bedrooms,
                'bathrooms' => $unit->bathrooms,
                'square_footage' => $unit->square_footage,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'property_address' => $unit->property->address ?? 'N/A',
                'monthly_rent' => $unit->monthly_rent,
                'security_deposit' => $unit->security_deposit,
                'advance_deposit' => $unit->advance_deposit,
                'lease_start' => $unit->lease_start,
                'lease_end' => $unit->lease_end,
                'lease_duration' => $unit->lease_duration,
                'lease_amount' => $unit->lease_amount,
                'lease_deposit' => $unit->lease_deposit,
                'status' => $unit->is_occupied ? 'active' : 'inactive',
                'description' => $unit->description,
                'amenities' => $unit->amenities ?? [],
            ];
        });

        // Format application data if available
        $applicationData = null;
        if ($tenantApplication) {
            // Convert image paths to URLs
            $idPictureUrl = $tenantApplication->id_picture;
            if ($idPictureUrl && !str_starts_with($idPictureUrl, 'http://') && !str_starts_with($idPictureUrl, 'https://')) {
                if (!str_starts_with($idPictureUrl, '/storage/')) {
                    $idPictureUrl = '/storage/' . ltrim($idPictureUrl, '/');
                }
            }

            $profilePictureUrl = $tenantApplication->profile_picture;
            if ($profilePictureUrl && !str_starts_with($profilePictureUrl, 'http://') && !str_starts_with($profilePictureUrl, 'https://')) {
                if (!str_starts_with($profilePictureUrl, '/storage/')) {
                    $profilePictureUrl = '/storage/' . ltrim($profilePictureUrl, '/');
                }
            }

            $applicationData = [
                'whatsapp' => $tenantApplication->whatsapp,
                'occupation' => $tenantApplication->occupation,
                'monthly_income' => $tenantApplication->monthly_income,
                'number_of_people' => $tenantApplication->number_of_people,
                'lease_duration_months' => $tenantApplication->lease_duration_months,
                'id_picture' => $idPictureUrl,
                'profile_picture' => $profilePictureUrl,
                'reference1' => [
                    'name' => $tenantApplication->reference1_name,
                    'address' => $tenantApplication->reference1_address,
                    'phone' => $tenantApplication->reference1_phone,
                    'email' => $tenantApplication->reference1_email,
                    'relationship' => $tenantApplication->reference1_relationship,
                ],
                'reference2' => [
                    'name' => $tenantApplication->reference2_name,
                    'address' => $tenantApplication->reference2_address,
                    'phone' => $tenantApplication->reference2_phone,
                    'email' => $tenantApplication->reference2_email,
                    'relationship' => $tenantApplication->reference2_relationship,
                ],
            ];
        }

        // Get fresh tenant data directly from database to ensure accuracy
        $freshTenant = Tenant::find($tenant->id);
        
        // Use name from tenant_application if available, otherwise fall back to tenant name
        $displayName = $tenantApplication ? $tenantApplication->name : $freshTenant->name;
        $displayEmail = $tenantApplication ? $tenantApplication->email : $freshTenant->email;
        $displayPhone = $tenantApplication ? $tenantApplication->phone : $freshTenant->phone;
        $displayAddress = $tenantApplication ? $tenantApplication->address : $freshTenant->address;
        
        return response()->json([
            'success' => true,
            'tenant' => [
                'id' => $freshTenant->id,
                'name' => $displayName, // Get name from tenant_application, not tenant table
                'email' => $displayEmail,
                'phone' => $displayPhone,
                'address' => $displayAddress,
                'units' => $unitsData,
                'application' => $applicationData,
            ],
        ]);
    }

    /**
     * Remove tenant from unit (delete tenant assignment)
     */
    public function destroy($id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get tenant
        $tenant = Tenant::find($id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Get landlord's properties
        $landlordProperties = Property::where('landlord_id', $landlord->id)->pluck('id');
        
        // Find units assigned to this tenant that belong to the landlord
        $units = Unit::whereIn('property_id', $landlordProperties)
            ->where('tenant_id', $tenant->id)
            ->get();

        if ($units->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found in your properties.',
            ], 404);
        }

        // Remove tenant from all units
        foreach ($units as $unit) {
            $unit->tenant_id = null;
            $unit->is_occupied = false;
            $unit->status = 'available';
            $unit->lease_start = null;
            $unit->lease_end = null;
            $unit->save();

            // Decrement property tenant count
            $property = Property::find($unit->property_id);
            if ($property && $property->tenants > 0) {
                $property->decrement('tenants');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tenant removed successfully.',
        ]);
    }

    /**
     * Generate account credentials for a tenant
     */
    public function generateAccount($id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get tenant
        $tenant = Tenant::find($id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Get landlord's properties
        $landlordProperties = Property::where('landlord_id', $landlord->id)->pluck('id');
        
        // Find units assigned to this tenant that belong to the landlord
        $units = Unit::whereIn('property_id', $landlordProperties)
            ->where('tenant_id', $tenant->id)
            ->get();

        if ($units->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found in your properties.',
            ], 404);
        }

        // Get the tenant application to extract last_name
        $application = TenantApplication::where('email', $tenant->email)
            ->where('status', 'approved')
            ->whereIn('unit_id', $units->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Extract last name from application or parse from tenant name
        $lastName = '';
        if ($application && $application->last_name) {
            $lastName = $application->last_name;
        } else {
            // Parse last name from full name (assume last word is last name)
            $nameParts = explode(' ', trim($tenant->name));
            if (count($nameParts) > 0) {
                $lastName = end($nameParts);
            }
        }
        
        // Get last 4 digits of phone number
        $phone = $tenant->phone ?? '';
        $last4Digits = '';
        if (!empty($phone)) {
            // Remove all non-digit characters and get last 4 digits
            $digitsOnly = preg_replace('/\D/', '', $phone);
            $last4Digits = substr($digitsOnly, -4);
        }
        
        // Generate password: lastname + last 4 digits of phone
        if (empty($lastName) || empty($last4Digits)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot generate password. Missing last name or phone number.',
            ], 422);
        }
        
        $password = strtolower($lastName) . $last4Digits;
        
        // Update tenant password
        $tenant->password = Hash::make($password);
        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Account credentials generated successfully.',
            'credentials' => [
                'email' => $tenant->email,
                'password' => $password, // Return plain password so landlord can share it
            ],
        ]);
    }
}
