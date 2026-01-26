<?php

namespace App\Http\Controllers;

use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantApplicationController extends Controller
{
    /**
     * Store a new tenant application
     */
    public function store(Request $request)
    {
        $validate = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'id_picture' => 'required|string',
            'profile_picture' => 'nullable|string',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name' => 'nullable|string|max:255', // Keep for backward compatibility
            'email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if email already exists in tenants table
                    $existingTenant = Tenant::where('email', $value)->first();
                    if ($existingTenant) {
                        $fail('This email address is already registered. Please use a different email address.');
                    }
                },
            ],
            'phone' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:255',
            'occupation' => 'required|string|max:255',
            'monthly_income' => 'required|integer|min:0',
            'address' => 'required|string',
            'number_of_people' => 'required|integer|min:1',
            'lease_duration_months' => 'required|integer|in:1,3,6,12,24',
            'lease_start_date' => 'required|date|after_or_equal:today',
            'reference1_name' => 'required|string|max:255',
            'reference1_address' => 'required|string',
            'reference1_phone' => 'required|string|max:255',
            'reference1_email' => 'required|email|max:255',
            'reference1_relationship' => 'required|string|max:255',
            'reference2_name' => 'required|string|max:255',
            'reference2_address' => 'required|string',
            'reference2_phone' => 'required|string|max:255',
            'reference2_email' => 'required|email|max:255',
            'reference2_relationship' => 'required|string|max:255',
        ]);

        // Verify unit exists
        $unit = Unit::find($validate['unit_id']);
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        // Helper function to save base64 image
        $saveBase64Image = function($base64String, $prefix = 'id_picture') {
            if (!$base64String || !is_string($base64String)) {
                return null;
            }

            if (!str_starts_with($base64String, 'data:image')) {
                return null;
            }

            try {
                preg_match('/data:image\/(\w+);base64,/', $base64String, $matches);
                $extension = $matches[1] ?? 'jpg';
                
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64String));
                
                if (!$imageData) {
                    return null;
                }
                
                $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
                $path = 'tenant-applications/id-pictures/' . $filename;
                
                $directory = storage_path('app/public/tenant-applications/id-pictures');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                Storage::disk('public')->put($path, $imageData);
                
                return $path;
            } catch (\Exception $e) {
                Log::error('Error saving base64 image: ' . $e->getMessage());
                return null;
            }
        };

        // Save ID picture
        $idPicturePath = $saveBase64Image($validate['id_picture'], 'id_picture');

        if (!$idPicturePath) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save ID picture. Please try again.',
            ], 422);
        }

        // Save profile picture if provided
        $profilePicturePath = null;
        if (!empty($validate['profile_picture'])) {
            $profilePicturePath = $saveBase64Image($validate['profile_picture'], 'profile_picture');
        }

        // Create application
        $application = TenantApplication::create([
            'unit_id' => $validate['unit_id'],
            'id_picture' => $idPicturePath,
            'profile_picture' => $profilePicturePath,
            'first_name' => $validate['first_name'],
            'middle_name' => $validate['middle_name'] ?? null,
            'last_name' => $validate['last_name'],
            'name' => trim(($validate['first_name'] ?? '') . ' ' . ($validate['middle_name'] ?? '') . ' ' . ($validate['last_name'] ?? '')), // Combine for backward compatibility
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'whatsapp' => $validate['whatsapp'],
            'occupation' => $validate['occupation'],
            'monthly_income' => $validate['monthly_income'],
            'address' => $validate['address'],
            'number_of_people' => $validate['number_of_people'],
            'lease_duration_months' => $validate['lease_duration_months'],
            'lease_start_date' => $validate['lease_start_date'],
            'reference1_name' => $validate['reference1_name'],
            'reference1_address' => $validate['reference1_address'],
            'reference1_phone' => $validate['reference1_phone'],
            'reference1_email' => $validate['reference1_email'],
            'reference1_relationship' => $validate['reference1_relationship'],
            'reference2_name' => $validate['reference2_name'],
            'reference2_address' => $validate['reference2_address'],
            'reference2_phone' => $validate['reference2_phone'],
            'reference2_email' => $validate['reference2_email'],
            'reference2_relationship' => $validate['reference2_relationship'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'application' => $application,
        ], 201);
    }

    /**
     * Get a unit by ID (public endpoint for application form)
     */
    public function getUnit($unitId)
    {
        $unit = Unit::with('property')->find($unitId);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        // Convert storage paths to URLs for unit photos
        if ($unit->photos && is_array($unit->photos)) {
            $unit->photos = array_map(function ($photo) {
                if (str_starts_with($photo, 'http')) {
                    return $photo;
                } else {
                    if (!str_starts_with($photo, 'storage/') && !str_starts_with($photo, '/storage/')) {
                        return '/storage/' . $photo;
                    } elseif (!str_starts_with($photo, '/')) {
                        return '/' . $photo;
                    }
                    return $photo;
                }
            }, $unit->photos);
        }

        // Convert property photo paths to URLs if property exists
        if ($unit->property) {
            if ($unit->property->main_photo) {
                if (!str_starts_with($unit->property->main_photo, 'http')) {
                    if (!str_starts_with($unit->property->main_photo, 'storage/') && !str_starts_with($unit->property->main_photo, '/storage/')) {
                        $unit->property->main_photo = '/storage/' . $unit->property->main_photo;
                    } elseif (!str_starts_with($unit->property->main_photo, '/')) {
                        $unit->property->main_photo = '/' . $unit->property->main_photo;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'unit' => $unit,
            'property' => $unit->property,
        ]);
    }

    /**
     * Get a specific tenant application by ID (for landlords)
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

        // Get application with unit and property
        $application = TenantApplication::with(['unit.property'])->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], 404);
        }

        // Verify the application belongs to a property owned by this landlord
        $property = $application->unit->property ?? null;
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this application.',
            ], 403);
        }

        // Convert ID picture path to URL
        if ($application->id_picture) {
            // If it's already a full URL, keep it
            if (str_starts_with($application->id_picture, 'http://') || str_starts_with($application->id_picture, 'https://')) {
                // Already a URL, keep as is
            } 
            // If it already starts with /storage/, keep it
            elseif (str_starts_with($application->id_picture, '/storage/')) {
                // Already correct format
            }
            // If it starts with storage/ (without leading slash), add leading slash
            elseif (str_starts_with($application->id_picture, 'storage/')) {
                $application->id_picture = '/' . $application->id_picture;
            }
            // Otherwise, prepend /storage/
            else {
                $application->id_picture = '/storage/' . $application->id_picture;
            }
        }

        // Convert profile picture path to URL
        if ($application->profile_picture) {
            if (str_starts_with($application->profile_picture, 'http://') || str_starts_with($application->profile_picture, 'https://')) {
                // Already a URL
            } elseif (str_starts_with($application->profile_picture, '/storage/')) {
                // Already correct format
            } elseif (str_starts_with($application->profile_picture, 'storage/')) {
                $application->profile_picture = '/' . $application->profile_picture;
            } else {
                $application->profile_picture = '/storage/' . $application->profile_picture;
            }
        }

        return response()->json([
            'success' => true,
            'application' => $application,
        ]);
    }

    /**
     * Approve a tenant application
     */
    public function approve($id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $application = TenantApplication::with(['unit.property'])->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], 404);
        }

        // Verify the application belongs to a property owned by this landlord
        $property = $application->unit->property ?? null;
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to approve this application.',
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Application has already been processed.',
            ], 422);
        }

        $unit = $application->unit;
        
        // Check if this unit already has a tenant assigned
        if ($unit->is_occupied && $unit->tenant_id) {
            // Check if the existing tenant matches the application email
            $existingTenant = Tenant::find($unit->tenant_id);
            if ($existingTenant && $existingTenant->email === $application->email) {
                // Same tenant applying for the same unit
                // IMPORTANT: Do NOT update the tenant's name - preserve the original name
                // Only update phone and address if provided
                if ($application->phone) {
                    $existingTenant->phone = $application->phone;
                }
                if ($application->address) {
                    $existingTenant->address = $application->address;
                }
                $existingTenant->save();
                $tenant = $existingTenant;
            } else {
                // Different tenant already assigned - this shouldn't happen if validation is correct
                return response()->json([
                    'success' => false,
                    'message' => 'This unit is already occupied by a different tenant.',
                ], 422);
            }
        } else {
            // Unit is not occupied, check if tenant already exists with this email
            $existingTenant = Tenant::where('email', $application->email)->first();
            
            if ($existingTenant) {
                // Tenant exists but not assigned to this unit
                // IMPORTANT: Do NOT update the tenant's name - preserve the original name
                // Only update phone and address if provided
                // The name in the application might be different from the tenant's actual name
                if ($application->phone) {
                    $existingTenant->phone = $application->phone;
                }
                if ($application->address) {
                    $existingTenant->address = $application->address;
                }
                $existingTenant->save();
                $tenant = $existingTenant;
            } else {
                // Create new tenant account with the name from the application
                // Combine first_name, middle_name, and last_name for tenant name
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (empty($fullName)) {
                    $fullName = $application->name; // Fallback to name field if separate fields are empty
                }
                
                // Generate password: lastname + last 4 digits of phone number
                $lastName = $application->last_name ?? '';
                if (empty($lastName)) {
                    // Parse last name from full name if last_name is not available
                    $nameParts = explode(' ', trim($application->name));
                    if (count($nameParts) > 0) {
                        $lastName = end($nameParts);
                    }
                }
                
                // Get last 4 digits of phone number
                $phone = $application->phone ?? '';
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
                        'message' => 'Cannot create account. Missing last name or phone number.',
                    ], 422);
                }
                
                $password = strtolower($lastName) . $last4Digits;
                
                $tenant = Tenant::create([
                    'name' => $fullName,
                    'email' => $application->email,
                    'password' => Hash::make($password),
                    'phone' => $application->phone,
                    'address' => $application->address,
                ]);
            }
        }

        // Assign tenant to unit
        $unit->tenant_id = $tenant->id;
        $unit->is_occupied = true;
        $unit->status = 'active';
        // Set lease dates based on application's lease duration
        // Refresh application to ensure we have the latest lease_duration_months value
        $application->refresh();
        
        // Get lease duration from application - ensure it's properly retrieved
        $leaseDurationMonths = $application->lease_duration_months;
        
        // Validate and set default if not set
        if (!$leaseDurationMonths || !in_array($leaseDurationMonths, [1, 3, 6, 12, 24])) {
            $leaseDurationMonths = 12; // Default to 12 months if not specified or invalid
        }
        
        // Ensure lease_duration_months is an integer
        $leaseDurationMonths = (int) $leaseDurationMonths;
        
        // Log for debugging
        Log::info('Setting lease duration', [
            'application_id' => $application->id,
            'lease_duration_months' => $leaseDurationMonths,
            'application_lease_duration' => $application->lease_duration_months,
            'lease_start_date' => $application->lease_start_date
        ]);
        
        // Use lease start date from application if provided, otherwise use current date
        $leaseStart = $application->lease_start_date 
            ? Carbon::parse($application->lease_start_date)->startOfDay()
            : now();
        
        $unit->lease_start = $leaseStart;
        $unit->lease_end = $leaseStart->copy()->addMonths($leaseDurationMonths);
        $unit->lease_duration = $leaseDurationMonths;
        $unit->lease_amount = $unit->monthly_rent;
        $unit->lease_deposit = $unit->security_deposit;
        $unit->save();

        // Update property tenant count
        if ($property) {
            $property->increment('tenants');
        }

        // Update application status
        $application->status = 'approved';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Application approved successfully. Tenant has been assigned to the unit.',
            'application' => $application,
        ]);
    }

    /**
     * Reject a tenant application
     */
    public function reject($id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $application = TenantApplication::with(['unit.property'])->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], 404);
        }

        // Verify the application belongs to a property owned by this landlord
        $property = $application->unit->property ?? null;
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reject this application.',
            ], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Application has already been processed.',
            ], 422);
        }

        // Update application status
        $application->status = 'rejected';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Application rejected.',
            'application' => $application,
        ]);
    }
}
