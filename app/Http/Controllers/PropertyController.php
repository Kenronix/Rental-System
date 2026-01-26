<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PropertyController extends Controller
{
    /**
     * Store a newly created property
     */
    public function store(Request $request)
    {
        // Ensure user is authenticated as landlord
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only landlords can create properties.',
            ], 401);
        }

        // Validate basic fields first
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:residential,commercial',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
        ]);

        // Photos are optional and can be base64 strings or arrays
        $photos = $request->input('photos', []);
        $mainPhoto = $request->input('main_photo');

        // Handle photo uploads (base64 strings)
        $photoPaths = [];
        $mainPhotoPath = null;

        // Helper function to save base64 image
        $saveBase64Image = function($base64String, $prefix = 'photo') {
            if (!$base64String || !is_string($base64String)) {
                return null;
            }

            // Check if it's a base64 data URL
            if (!str_starts_with($base64String, 'data:image')) {
                return null;
            }

            try {
                // Extract image data and extension
                preg_match('/data:image\/(\w+);base64,/', $base64String, $matches);
                $extension = $matches[1] ?? 'jpg';
                
                // Decode base64
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64String));
                
                if (!$imageData) {
                    return null;
                }
                
                // Generate unique filename
                $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
                $path = 'properties/photos/' . $filename;
                
                // Ensure directory exists
                $directory = storage_path('app/public/properties/photos');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                // Save to storage
                Storage::disk('public')->put($path, $imageData);
                
                // Return the path that will be stored in database (relative to storage/app/public)
                // We'll convert it to URL when retrieving
                return $path;
            } catch (\Exception $e) {
                \Log::error('Error saving base64 image: ' . $e->getMessage());
                return null;
            }
        };

        // Upload main photo if provided (base64 string)
        if ($mainPhoto) {
            $mainPhotoPath = $saveBase64Image($mainPhoto, 'main');
        }

        // Upload additional photos if provided (array of base64 strings)
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

        // Create the property
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'street_address' => $validated['street_address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code'],
            'units' => 0, // Can be updated later
            'tenants' => 0, // Can be updated later
            'main_photo' => $mainPhotoPath,
            'photos' => $photoPaths,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Property created successfully',
            'property' => $property,
        ], 201);
    }

    /**
     * Get all properties for the authenticated landlord
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

        $properties = Property::where('landlord_id', $landlord->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($property) {
                // Convert storage paths to accessible URLs
                if ($property->main_photo) {
                    // If it's already a full URL, keep it
                    if (str_starts_with($property->main_photo, 'http')) {
                        // Already a full URL, keep as is
                    } else {
                        // It's a storage path, convert to URL
                        // Ensure it starts with /storage/ for proper routing
                        if (!str_starts_with($property->main_photo, 'storage/') && !str_starts_with($property->main_photo, '/storage/')) {
                            $property->main_photo = '/storage/' . $property->main_photo;
                        } elseif (!str_starts_with($property->main_photo, '/')) {
                            $property->main_photo = '/' . $property->main_photo;
                        }
                        // Convert to full URL
                        $property->main_photo = url($property->main_photo);
                    }
                }
                if ($property->photos && is_array($property->photos)) {
                    $property->photos = array_map(function ($photo) {
                        if (str_starts_with($photo, 'http')) {
                            // Already a full URL, keep as is
                            return $photo;
                        } else {
                            // Ensure it starts with /storage/
                            if (!str_starts_with($photo, 'storage/') && !str_starts_with($photo, '/storage/')) {
                                $photo = '/storage/' . $photo;
                            } elseif (!str_starts_with($photo, '/')) {
                                $photo = '/' . $photo;
                            }
                            // Convert to full URL
                            return url($photo);
                        }
                    }, $property->photos);
                }
                return $property;
            });

        return response()->json([
            'success' => true,
            'properties' => $properties,
        ]);
    }

    /**
     * Get a specific property by ID
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

        $property = Property::where('id', $id)
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        // Convert storage paths to accessible URLs
        if ($property->main_photo) {
            if (!str_starts_with($property->main_photo, 'http')) {
                if (!str_starts_with($property->main_photo, 'storage/') && !str_starts_with($property->main_photo, '/storage/')) {
                    $property->main_photo = '/storage/' . $property->main_photo;
                } elseif (!str_starts_with($property->main_photo, '/')) {
                    $property->main_photo = '/' . $property->main_photo;
                }
            }
        }
        if ($property->photos && is_array($property->photos)) {
            $property->photos = array_map(function ($photo) {
                if (str_starts_with($photo, 'http')) {
                    $parsed = parse_url($photo);
                    return $parsed['path'] ?? '/storage/' . $photo;
                } else {
                    if (!str_starts_with($photo, 'storage/') && !str_starts_with($photo, '/storage/')) {
                        return '/storage/' . $photo;
                    } elseif (!str_starts_with($photo, '/')) {
                        return '/' . $photo;
                    }
                    return $photo;
                }
            }, $property->photos);
        }

        return response()->json([
            'success' => true,
            'property' => $property,
        ]);
    }

    /**
     * Update a property
     */
    public function update(Request $request, $id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only landlords can update properties.',
            ], 401);
        }

        $property = Property::where('id', $id)
            ->where('landlord_id', $landlord->id)
            ->first();

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:residential,commercial',
            'street_address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'zip_code' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:active,inactive,vacant',
        ]);

        // Handle photo uploads (base64 strings)
        $photoPaths = [];
        $mainPhotoPath = null;

        // Helper function to save base64 image
        $saveBase64Image = function($base64String, $prefix = 'photo') {
            if (!$base64String || !is_string($base64String)) {
                return null;
            }

            // Check if it's a base64 data URL
            if (!str_starts_with($base64String, 'data:image')) {
                // If it's already a URL/path, extract just the path part (existing photo)
                // Remove domain and /storage/ prefix if present
                $path = preg_replace('/^https?:\/\/[^\/]+/', '', $base64String);
                $path = preg_replace('/^\/storage\//', '', $path);
                return $path ?: null;
            }

            try {
                // Extract image data and extension
                preg_match('/data:image\/(\w+);base64,/', $base64String, $matches);
                $extension = $matches[1] ?? 'jpg';
                
                // Decode base64
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64String));
                
                if (!$imageData) {
                    return null;
                }
                
                // Generate unique filename
                $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $extension;
                $path = 'properties/photos/' . $filename;
                
                // Ensure directory exists
                $directory = storage_path('app/public/properties/photos');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                // Save to storage
                Storage::disk('public')->put($path, $imageData);
                
                return $path;
            } catch (\Exception $e) {
                \Log::error('Error saving base64 image: ' . $e->getMessage());
                return null;
            }
        };

        // Upload main photo if provided
        if ($request->has('main_photo') && $request->input('main_photo')) {
            $mainPhotoPath = $saveBase64Image($request->input('main_photo'), 'main');
            if ($mainPhotoPath) {
                $validated['main_photo'] = $mainPhotoPath;
            }
        }

        // Upload additional photos if provided
        if ($request->has('photos') && is_array($request->input('photos'))) {
            foreach ($request->input('photos') as $index => $photo) {
                if ($photo) {
                    $path = $saveBase64Image($photo, 'photo_' . $index);
                    if ($path) {
                        $photoPaths[] = $path;
                    }
                }
            }
            if (count($photoPaths) > 0) {
                $validated['photos'] = $photoPaths;
            }
        }

        $property->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Property updated successfully',
            'property' => $property,
        ]);
    }

    /**
     * Get all properties (for admin - shows all properties from all landlords)
     */
    public function getAllProperties(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can view all properties.',
            ], 401);
        }

        // Get all properties with landlord information
        $properties = Property::with('landlord:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($property) {
                // Convert storage paths to accessible URLs
                if ($property->main_photo) {
                    if (str_starts_with($property->main_photo, 'http')) {
                        // Already a full URL, keep as is
                    } else {
                        // Convert to full URL using asset() or Storage::url()
                        if (str_starts_with($property->main_photo, 'storage/') || str_starts_with($property->main_photo, '/storage/')) {
                            // Already has storage prefix, just ensure it starts with /
                            $property->main_photo = str_starts_with($property->main_photo, '/') 
                                ? $property->main_photo 
                                : '/' . $property->main_photo;
                        } else {
                            // Add storage prefix
                            $property->main_photo = '/storage/' . $property->main_photo;
                        }
                        // Convert to full URL
                        $property->main_photo = url($property->main_photo);
                    }
                }
                if ($property->photos && is_array($property->photos)) {
                    $property->photos = array_map(function ($photo) {
                        if (str_starts_with($photo, 'http')) {
                            // Already a full URL, keep as is
                            return $photo;
                        } else {
                            // Ensure it has /storage/ prefix
                            if (!str_starts_with($photo, 'storage/') && !str_starts_with($photo, '/storage/')) {
                                $photo = '/storage/' . $photo;
                            } elseif (!str_starts_with($photo, '/')) {
                                $photo = '/' . $photo;
                            }
                            // Convert to full URL
                            return url($photo);
                        }
                    }, $property->photos);
                }
                return $property;
            });

        return response()->json([
            'success' => true,
            'properties' => $properties,
        ]);
    }
}
