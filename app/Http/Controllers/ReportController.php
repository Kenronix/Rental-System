<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\Payment;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get tenant payment report
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

        // Get date filter (default to current month)
        $filterDate = $request->input('date', now()->format('Y-m'));
        $carbonDate = Carbon::parse($filterDate . '-01');
        $startDate = $carbonDate->copy()->startOfMonth();
        $endDate = $carbonDate->copy()->endOfMonth();

        // Get all units owned by this landlord
        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        
        // Get actual tenants (assigned to units)
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with('property')
            ->get();

        // Get all unique tenant IDs
        $tenantIds = $units->pluck('tenant_id')->unique()->filter()->values()->toArray();
        
        // Fetch all tenants at once
        $tenantsMap = Tenant::whereIn('id', $tenantIds)
            ->get()
            ->keyBy('id');

        // Get all unit IDs
        $unitIds = $units->pluck('id')->toArray();
        
        // Fetch all approved tenant applications for these units
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id');

        // Get payments for the filtered date range
        $payments = Payment::whereIn('unit_id', $unitIds)
            ->whereBetween('due_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('tenant_id');

        // Transform data to include tenant info with payment status
        $reportData = $units->map(function ($unit) use ($tenantsMap, $applicationsMap, $payments, $startDate, $endDate) {
            $tenant = $tenantsMap->get($unit->tenant_id);
            
            if (!$tenant) {
                return null;
            }

            // Get the approved application for this unit
            $application = $applicationsMap->get($unit->id);
            
            // Use name from application
            $displayName = $tenant->name;
            if ($application) {
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (!empty($fullName)) {
                    $displayName = $fullName;
                } else {
                    $displayName = $application->name ?? $tenant->name;
                }
            }

            // Check if tenant has paid for this period
            $tenantPayments = $payments->get($tenant->id, collect());
            $hasPaid = $tenantPayments->where('unit_id', $unit->id)
                ->where('status', 'paid')
                ->isNotEmpty();
            
            $paymentRecord = $tenantPayments->where('unit_id', $unit->id)->first();
            $paymentStatus = 'Not Paid';
            $paymentAmount = null;
            $paymentDate = null;
            
            if ($paymentRecord) {
                $paymentStatus = ucfirst($paymentRecord->status);
                $paymentAmount = (float) $paymentRecord->amount;
                $paymentDate = $paymentRecord->payment_date ? $paymentRecord->payment_date->format('Y-m-d') : null;
            }

            return [
                'tenant_id' => $tenant->id,
                'tenant_name' => $displayName,
                'tenant_email' => $tenant->email,
                'tenant_phone' => $tenant->phone,
                'unit_id' => $unit->id,
                'unit_number' => $unit->unit_number,
                'property_id' => $unit->property_id,
                'property_name' => $unit->property->name ?? 'N/A',
                'monthly_rent' => (float) $unit->monthly_rent,
                'has_paid' => $hasPaid,
                'payment_status' => $paymentStatus,
                'payment_amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'due_date' => $paymentRecord ? ($paymentRecord->due_date ? $paymentRecord->due_date->format('Y-m-d') : null) : null,
            ];
        })->filter()->values();

        // Calculate statistics
        $totalTenants = $reportData->count();
        $paidTenants = $reportData->where('has_paid', true)->count();
        $unpaidTenants = $totalTenants - $paidTenants;
        $totalRent = $reportData->sum('monthly_rent');
        $totalPaid = $reportData->where('has_paid', true)->sum('payment_amount') ?? 0;
        $totalUnpaid = $totalRent - $totalPaid;

        return response()->json([
            'success' => true,
            'report' => $reportData,
            'statistics' => [
                'total_tenants' => $totalTenants,
                'paid_tenants' => $paidTenants,
                'unpaid_tenants' => $unpaidTenants,
                'total_rent' => $totalRent,
                'total_paid' => $totalPaid,
                'total_unpaid' => $totalUnpaid,
            ],
            'filter_date' => $filterDate,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Download report as CSV
     */
    public function download(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get date filter
        $filterDate = $request->input('date', now()->format('Y-m'));
        $carbonDate = Carbon::parse($filterDate . '-01');
        $startDate = $carbonDate->copy()->startOfMonth();
        $endDate = $carbonDate->copy()->endOfMonth();

        // Get all units owned by this landlord
        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        
        // Get actual tenants
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with('property')
            ->get();

        $tenantIds = $units->pluck('tenant_id')->unique()->filter()->values()->toArray();
        $tenantsMap = Tenant::whereIn('id', $tenantIds)->get()->keyBy('id');
        $unitIds = $units->pluck('id')->toArray();
        
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id');

        $payments = Payment::whereIn('unit_id', $unitIds)
            ->whereBetween('due_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('tenant_id');

        // Build CSV content
        $csvData = [];
        $csvData[] = ['Tenant Name', 'Email', 'Phone', 'Property', 'Unit', 'Monthly Rent', 'Payment Status', 'Payment Amount', 'Payment Date', 'Due Date'];

        foreach ($units as $unit) {
            $tenant = $tenantsMap->get($unit->tenant_id);
            if (!$tenant) continue;

            $application = $applicationsMap->get($unit->id);
            $displayName = $tenant->name;
            if ($application) {
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (!empty($fullName)) {
                    $displayName = $fullName;
                }
            }

            $tenantPayments = $payments->get($tenant->id, collect());
            $paymentRecord = $tenantPayments->where('unit_id', $unit->id)->first();
            
            $paymentStatus = 'Not Paid';
            $paymentAmount = '';
            $paymentDate = '';
            $dueDate = '';
            
            if ($paymentRecord) {
                $paymentStatus = ucfirst($paymentRecord->status);
                $paymentAmount = number_format((float) $paymentRecord->amount, 2);
                $paymentDate = $paymentRecord->payment_date ? $paymentRecord->payment_date->format('Y-m-d') : '';
                $dueDate = $paymentRecord->due_date ? $paymentRecord->due_date->format('Y-m-d') : '';
            }

            $csvData[] = [
                $displayName,
                $tenant->email,
                $tenant->phone ?? '',
                $unit->property->name ?? 'N/A',
                $unit->unit_number,
                number_format((float) $unit->monthly_rent, 2),
                $paymentStatus,
                $paymentAmount,
                $paymentDate,
                $dueDate,
            ];
        }

        // Generate CSV
        $filename = 'tenant_payment_report_' . $filterDate . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
