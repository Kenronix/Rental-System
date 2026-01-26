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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:255',
            'occupation' => 'required|string|max:255',
            'monthly_income' => 'required|integer|min:0',
            'address' => 'required|string',
            'number_of_people' => 'required|integer|min:1',
            'mother_name' => 'required|string|max:255',
            'mother_address' => 'required|string',
            'mother_phone' => 'required|string|max:255',
            'mother_email' => 'required|email|max:255',
            'father_name' => 'required|string|max:255',
            'father_address' => 'required|string',
            'father_phone' => 'required|string|max:255',
            'father_email' => 'required|email|max:255',
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

        // Create application
        $application = TenantApplication::create([
            'unit_id' => $validate['unit_id'],
            'id_picture' => $idPicturePath,
            'name' => $validate['name'],
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'whatsapp' => $validate['whatsapp'],
            'occupation' => $validate['occupation'],
            'monthly_income' => $validate['monthly_income'],
            'address' => $validate['address'],
            'number_of_people' => $validate['number_of_people'],
            'mother_name' => $validate['mother_name'],
            'mother_address' => $validate['mother_address'],
            'mother_phone' => $validate['mother_phone'],
            'mother_email' => $validate['mother_email'],
            'father_name' => $validate['father_name'],
            'father_address' => $validate['father_address'],
            'father_phone' => $validate['father_phone'],
            'father_email' => $validate['father_email'],
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
        
        // Check if unit is already occupied
        if ($unit->is_occupied && $unit->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'This unit is already occupied.',
            ], 422);
        }

        // Check if tenant already exists with this email
        $existingTenant = Tenant::where('email', $application->email)->first();
        
        if ($existingTenant) {
            // Use existing tenant
            $tenant = $existingTenant;
        } else {
            // Create new tenant account
            // Generate a random password (tenant will need to reset it)
            $randomPassword = Str::random(12);
            
            $tenant = Tenant::create([
                'name' => $application->name,
                'email' => $application->email,
                'password' => Hash::make($randomPassword),
                'phone' => $application->phone,
                'address' => $application->address,
            ]);
        }

        // Assign tenant to unit
        $unit->tenant_id = $tenant->id;
        $unit->is_occupied = true;
        $unit->status = 'active';
        // Set lease dates (you can customize these)
        $unit->lease_start = now();
        $unit->lease_end = now()->addYear(); // 1 year lease
        $unit->lease_duration = 12; // 12 months
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
