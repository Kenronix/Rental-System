<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Property;
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
}
