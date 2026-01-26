<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UnitController extends Controller
{
    public function store(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();

        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only landlords can create units.'
            ], 401);
        }

        $validate = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'status' => 'required|in:active,inactive,vacant',
            'unit_number' => 'required|string|max:255',
            'unit_type' => 'required|string|max:255',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'square_footage' => 'nullable|integer',
            'monthly_rent' => 'required|integer',
            'security_deposit' => 'required|integer',
            'advance_deposit' => 'required|integer',
            'description' => 'required|string',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'is_occupied' => 'required|boolean',
            'tenant_id' => 'nullable|exists:tenants,id',
            'lease_start' => 'nullable|date',
            'lease_end' => 'nullable|date',
            'lease_duration' => 'nullable|integer',
            'lease_amount' => 'nullable|integer',
            'lease_deposit' => 'nullable|integer',
        ]);

        // Ensure property belongs to landlord
        $property = \App\Models\Property::where('id', $validate['property_id'])
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found or you do not have permission to add units to it.',
            ], 404);
        }

        // Helper function to save base64 image
        $saveBase64Image = function($base64String, $prefix = 'photo') {
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
                $path = 'units/photos/' . $filename;
                
                $directory = storage_path('app/public/units/photos');
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

        $photos = $request->input('photos', []);
        $photoPaths = [];
        if ($photos && is_array($photos)) {
            foreach ($photos as $index => $photo) {
                if ($photo && is_string($photo)) {
                    $path = $saveBase64Image($photo, 'photo_' . $index);
                    if ($path) {
                        $photoPaths[] = $path;
                    }
                }
            }
        }

        $unit = Unit::create([
            'property_id' => $validate['property_id'],
            'unit_number' => $validate['unit_number'],
            'unit_type' => $validate['unit_type'],
            'bedrooms' => $validate['bedrooms'],
            'bathrooms' => $validate['bathrooms'],
            'square_footage' => $validate['square_footage'] ?? null,
            'monthly_rent' => $validate['monthly_rent'],
            'security_deposit' => $validate['security_deposit'],
            'advance_deposit' => $validate['advance_deposit'],
            'description' => $validate['description'],
            'photos' => $photoPaths,
            'amenities' => $validate['amenities'] ?? [],
            'status' => $validate['status'],
            'is_occupied' => $validate['is_occupied'],
            'tenant_id' => $validate['tenant_id'] ?? null,
            'lease_start' => $validate['lease_start'] ?? null,
            'lease_end' => $validate['lease_end'] ?? null,
            'lease_duration' => $validate['lease_duration'] ?? null,
            'lease_amount' => $validate['lease_amount'] ?? null,
            'lease_deposit' => $validate['lease_deposit'] ?? null,
        ]);

        // Update property units count
        $property = \App\Models\Property::find($validate['property_id']);
        if ($property) {
            $property->increment('units');
        }

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully',
            'unit' => $unit,
        ], 201);
    }

    /**
     * Get all units for a property
     */
    public function index($propertyId)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Verify property belongs to landlord
        $property = \App\Models\Property::where('id', $propertyId)
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        $units = Unit::where('property_id', $propertyId)
            ->with('tenant')
            ->orderBy('unit_number', 'asc')
            ->get()
            ->map(function ($unit) {
                // Convert storage paths to URLs
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
                return $unit;
            });

        return response()->json([
            'success' => true,
            'units' => $units,
        ]);
    }

    /**
     * Get a specific unit by ID
     */
    public function show($propertyId, $unitId)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Verify property belongs to landlord
        $property = \App\Models\Property::where('id', $propertyId)
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        $unit = Unit::where('id', $unitId)
            ->where('property_id', $propertyId)
            ->with('tenant')
            ->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found',
            ], 404);
        }

        // Convert storage paths to URLs
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

        return response()->json([
            'success' => true,
            'unit' => $unit,
        ]);
    }

    /**
     * Update a unit
     */
    public function update(Request $request, $propertyId, $unitId)
    {
        $landlord = Auth::guard('landlord')->user();

        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only landlords can update units.'
            ], 401);
        }

        // Verify property belongs to landlord
        $property = \App\Models\Property::where('id', $propertyId)
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found or you do not have permission.',
            ], 404);
        }

        // Verify unit belongs to property
        $unit = Unit::where('id', $unitId)
            ->where('property_id', $propertyId)
            ->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        $validate = $request->validate([
            'status' => 'required|in:active,inactive,vacant',
            'unit_number' => 'required|string|max:255',
            'unit_type' => 'required|string|max:255',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'square_footage' => 'nullable|integer',
            'monthly_rent' => 'required|integer',
            'security_deposit' => 'required|integer',
            'advance_deposit' => 'required|integer',
            'description' => 'required|string',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'is_occupied' => 'required|boolean',
            'tenant_id' => 'nullable|exists:tenants,id',
            'lease_start' => 'nullable|date',
            'lease_end' => 'nullable|date',
            'lease_duration' => 'nullable|integer',
            'lease_amount' => 'nullable|integer',
            'lease_deposit' => 'nullable|integer',
        ]);

        // Check for duplicate unit number (excluding current unit)
        $existingUnit = Unit::where('property_id', $propertyId)
            ->where('unit_number', $validate['unit_number'])
            ->where('id', '!=', $unitId)
            ->first();

        if ($existingUnit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit number already exists for this property.',
            ], 422);
        }

        // Helper function to save base64 image
        $saveBase64Image = function($base64String, $prefix = 'photo') {
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
                $path = 'units/photos/' . $filename;
                
                $directory = storage_path('app/public/units/photos');
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

        $photos = $request->input('photos', []);
        $photoPaths = [];
        if ($photos && is_array($photos)) {
            foreach ($photos as $index => $photo) {
                if ($photo && is_string($photo)) {
                    // Check if it's a new base64 image or existing URL
                    if (str_starts_with($photo, 'data:image')) {
                        $path = $saveBase64Image($photo, 'photo_' . $index);
                        if ($path) {
                            $photoPaths[] = $path;
                        }
                    } elseif (str_starts_with($photo, '/storage/') || str_starts_with($photo, 'storage/')) {
                        // Keep existing photos
                        $photoPaths[] = str_replace('/storage/', '', str_replace('storage/', '', $photo));
                    }
                }
            }
        }

        $unit->update([
            'unit_number' => $validate['unit_number'],
            'unit_type' => $validate['unit_type'],
            'bedrooms' => $validate['bedrooms'],
            'bathrooms' => $validate['bathrooms'],
            'square_footage' => $validate['square_footage'] ?? null,
            'monthly_rent' => $validate['monthly_rent'],
            'security_deposit' => $validate['security_deposit'],
            'advance_deposit' => $validate['advance_deposit'],
            'description' => $validate['description'],
            'photos' => $photoPaths,
            'amenities' => $validate['amenities'] ?? [],
            'status' => $validate['status'],
            'is_occupied' => $validate['is_occupied'],
            'tenant_id' => $validate['tenant_id'] ?? null,
            'lease_start' => $validate['lease_start'] ?? null,
            'lease_end' => $validate['lease_end'] ?? null,
            'lease_duration' => $validate['lease_duration'] ?? null,
            'lease_amount' => $validate['lease_amount'] ?? null,
            'lease_deposit' => $validate['lease_deposit'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully',
            'unit' => $unit,
        ]);
    }
}