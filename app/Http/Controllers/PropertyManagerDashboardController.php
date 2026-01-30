<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Property;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class PropertyManagerDashboardController extends Controller
{
    public function index()
    {
        $totalProperties = Property::count();
        $activeTenants = Tenant::count(); // Or filter by active lease

        // Calculate monthly revenue (sum of rent from occupied units)
        $monthlyRevenue = Unit::where('is_occupied', true)->sum('monthly_rent');

        // Recent Payments
        $recentPayments = Payment::with(['tenant', 'unit.property'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'tenant_name' => $payment->tenant->name,
                    'tenant_avatar' => $payment->tenant->avatar ?? null, // Assuming avatar column or relationship
                    'property_name' => $payment->unit->property->name ?? 'Unknown',
                    'unit_number' => $payment->unit->unit_number ?? '',
                    'amount' => $payment->amount,
                    'date' => $payment->created_at->diffForHumans(),
                    'status' => $payment->status,
                ];
            });

        // Expiring Leases (units with lease_end in next 30 days)
        $expiringLeases = Unit::with('tenant', 'property')
            ->where('is_occupied', true)
            ->whereNotNull('lease_end')
            ->orderBy('lease_end', 'asc')
            ->take(5)
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'tenant_name' => $unit->tenant->name ?? 'Unknown',
                    'property_name' => $unit->property->name ?? 'Unknown',
                    'unit_number' => $unit->unit_number,
                    'lease_end' => $unit->lease_end,
                    'days_remaining' => \Carbon\Carbon::parse($unit->lease_end)->diffInDays(now()),
                ];
            });

        // Property Performance (Mock or Real)
        // For now, let's list top 5 properties by revenue (or just list them)
        $propertyPerformance = Property::withCount([
            'units as total_units',
            'units as occupied_units' => function ($query) {
                $query->where('is_occupied', true);
            }
        ])->get()->map(function ($property) {
            $revenue = $property->units()->where('is_occupied', true)->sum('monthly_rent');
            $occupancy = $property->total_units > 0 ? round(($property->occupied_units / $property->total_units) * 100) : 0;
            return [
                'id' => $property->id,
                'name' => $property->name,
                'location' => $property->city, // Using city as location
                'units' => $property->total_units,
                'occupancy' => $occupancy,
                'revenue' => $revenue,
                'status' => $occupancy >= 90 ? 'Excellent' : ($occupancy >= 70 ? 'Good' : 'Average'),
            ];
        });

        return response()->json([
            'stats' => [
                'total_properties' => $totalProperties,
                'active_tenants' => $activeTenants,
                'monthly_revenue' => $monthlyRevenue,
            ],
            'recent_payments' => $recentPayments,
            'expiring_leases' => $expiringLeases,
            'property_performance' => $propertyPerformance,
        ]);
    }
    public function getProperties()
    {
        $properties = Property::withCount([
            'units as total_units',
            'units as occupied_units' => function ($query) {
                $query->where('is_occupied', true);
            }
        ])->get()->map(function ($property) {
            // Calculate tenants (occupied units)
            $property->tenants_count = $property->occupied_units;

            // Handle Photo URL
            if ($property->main_photo) {
                if (!str_starts_with($property->main_photo, 'http')) {
                    if (!str_starts_with($property->main_photo, 'storage/') && !str_starts_with($property->main_photo, '/storage/')) {
                         $property->main_photo = '/storage/' . $property->main_photo;
                    } elseif (!str_starts_with($property->main_photo, '/')) {
                        $property->main_photo = '/' . $property->main_photo;
                    }
                }
            } else {
                // Return a placeholder or null
                $property->main_photo = null; 
            }

            return $property;
        });

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }

    public function show($id)
    {
        $property = Property::with(['landlord', 'units'])->withCount([
            'units as total_units',
            'units as occupied_units' => function ($query) {
                $query->where('is_occupied', true);
            }
        ])->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found'
            ], 404);
        }

        // Handle Property Photo URL
        if ($property->main_photo) {
             if (!str_starts_with($property->main_photo, 'http')) {
                if (!str_starts_with($property->main_photo, 'storage/') && !str_starts_with($property->main_photo, '/storage/')) {
                     $property->main_photo = '/storage/' . $property->main_photo;
                } elseif (!str_starts_with($property->main_photo, '/')) {
                    $property->main_photo = '/' . $property->main_photo;
                }
            }
        }

        // Process units photos if needed, though they might be stored as JSON
        $property->units->transform(function ($unit) {
            // Ensure photos are accessible
            if ($unit->photos) {
                // if it's a string, decode it? Model should handle casting to array if configured
                // The Unit model calls json_decode in accessor, so $unit->photos is an array
                $photos = $unit->photos;
                if (is_array($photos)) {
                    $unit->photos = array_map(function($photo) {
                         if (!str_starts_with($photo, 'http')) {
                            if (!str_starts_with($photo, 'storage/') && !str_starts_with($photo, '/storage/')) {
                                return '/storage/' . $photo;
                            } elseif (!str_starts_with($photo, '/')) {
                                return '/' . $photo;
                            }
                        }
                        return $photo;
                    }, $photos);
                }
            }
            return $unit;
        });

        return response()->json([
            'success' => true,
            'property' => $property
        ]);
    }

    public function getLandlords()
    {
        $landlords = Landlord::with(['properties.units' => function($q) {
            $q->where('is_occupied', true);
        }])->withCount('properties')->get()->map(function($landlord) {
             $monthlyRevenue = 0;
             foreach($landlord->properties as $property) {
                 $monthlyRevenue += $property->units->sum('monthly_rent');
             }
             
             return [
                 'id' => $landlord->id,
                 'name' => $landlord->name,
                 'email' => $landlord->email,
                 'phone' => $landlord->phone,
                 'avatar' => null, 
                 'properties_count' => $landlord->properties_count,
                 'monthly_revenue' => $monthlyRevenue,
                 'status' => 'Active', // Mocked
             ];
        });

        $totalLandlords = Landlord::count();
        $totalProperties = Property::count();
        $totalRevenue = Unit::where('is_occupied', true)->sum('monthly_rent');

        return response()->json([
            'success' => true,
            'landlords' => $landlords,
            'stats' => [
                'total_landlords' => $totalLandlords,
                'total_properties' => $totalProperties,
                'monthly_revenue' => $totalRevenue
            ]
        ]);
    }

    public function getPayments()
    {
        $payments = Payment::with(['tenant', 'unit.property'])
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'tenant_name' => $payment->tenant->name,
                    'tenant_email' => $payment->tenant->email,
                    'property_name' => $payment->unit->property->name ?? 'Unknown',
                    'unit_number' => $payment->unit->unit_number ?? '',
                    'type' => $payment->payment_type ?? 'Rent', // Default to Rent if null
                    'amount' => $payment->amount,
                    'payment_date' => $payment->created_at->format('M d, Y'), // Or payment_date column
                    'due_date' => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('M d, Y') : 'N/A',
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method ?? 'Unknown',
                    'reference' => $payment->reference_number ?? 'N/A',
                ];
            });

        $totalPayments = Payment::count();
        $paidAmount = Payment::where('status', 'paid')->sum('amount');
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        // Assuming overdue is a status or calculate based on date?
        // Let's assume there's an 'overdue' status or just check pending + date
        $overdueAmount = Payment::where('status', 'overdue')
            ->orWhere(function($q) {
                $q->where('status', 'pending')->where('due_date', '<', now());
            })->sum('amount');


        return response()->json([
            'success' => true,
            'payments' => $payments,
            'stats' => [
                'total_payments' => $totalPayments,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'overdue_amount' => $overdueAmount
            ]
        ]);
    }

    public function getReports()
    {
        // 1. Stats
        $propertiesManaged = Property::count();
        $totalUnits = Unit::count();
        $totalTenants = Tenant::count(); // Or Unit::where('is_occupied', true)->count();
        
        // Income (This Month) - Actual collected payments
        $incomeThisMonth = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // 2. Report Data (Transactions) mechanism
        // For simplicity, we return all payments initially, similar to the payments page, 
        // effectively making this a "Transaction Report". 
        // In a real app, this might accept date filters via Request.
        $transactions = Payment::with(['tenant', 'unit.property'])
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'tenant_name' => $payment->tenant->name,
                    'tenant_email' => $payment->tenant->email,
                    'property_name' => $payment->unit->property->name ?? 'Unknown',
                    'unit_number' => $payment->unit->unit_number ?? '',
                    'type' => $payment->payment_type ?? 'Rent',
                    'amount' => $payment->amount,
                    'payment_date' => $payment->created_at->format('M d, Y'),
                    'due_date' => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('M d, Y') : 'N/A',
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method ?? 'Unknown',
                    'reference' => $payment->reference_number ?? 'N/A',
                ];
            });
        
        // Also fetch properties list for the filter dropdown
        $propertiesList = Property::select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'properties_managed' => $propertiesManaged,
                'total_units' => $totalUnits,
                'total_tenants' => $totalTenants,
                'income_this_month' => $incomeThisMonth,
            ],
            'transactions' => $transactions,
            'properties_list' => $propertiesList
        ]);
    }
}
