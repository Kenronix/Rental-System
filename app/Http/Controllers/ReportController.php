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
        $reportData = $units->map(function ($unit) use ($tenantsMap, $applicationsMap, $payments, $startDate, $endDate, $carbonDate) {
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
            
            // Only get paid payments
            $paymentRecord = $tenantPayments->where('unit_id', $unit->id)
                ->where('status', 'paid')
                ->first();
            
            // Only include tenants who have paid payments
            if (!$paymentRecord) {
                return null;
            }
            
            $hasPaid = true; // Since we're only showing paid payments
            
            $paymentStatus = 'Not Paid';
            $paymentAmount = null;
            $paymentDate = null;
            
            // Calculate due_date based on lease_start date
            $defaultDueDate = $endDate->format('Y-m-d'); // Default to end of month
            
            if ($unit->lease_start) {
                $leaseStart = is_string($unit->lease_start) ? Carbon::parse($unit->lease_start) : $unit->lease_start;
                $leaseStartDay = $leaseStart->day;
                
                // Calculate due date for the selected month using the lease start day
                $calculatedDueDate = $carbonDate->copy()->day($leaseStartDay);
                
                // If the calculated day doesn't exist in the month (e.g., Feb 30), use the last day of the month
                if ($calculatedDueDate->month != $carbonDate->month) {
                    $calculatedDueDate = $carbonDate->copy()->endOfMonth();
                }
                
                $defaultDueDate = $calculatedDueDate->format('Y-m-d');
            }
            
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
                'due_date' => $paymentRecord && $paymentRecord->due_date ? $paymentRecord->due_date->format('Y-m-d') : $defaultDueDate,
                'payment_type' => $paymentRecord ? ($paymentRecord->payment_type ?? 'rent') : null,
                'water' => $paymentRecord && $paymentRecord->water ? (float) $paymentRecord->water : null,
                'electricity' => $paymentRecord && $paymentRecord->electricity ? (float) $paymentRecord->electricity : null,
                'internet' => $paymentRecord && $paymentRecord->internet ? (float) $paymentRecord->internet : null,
            ];
        })->filter()->values();

        // Calculate statistics (only paid payments are shown)
        $totalTenants = $reportData->count(); // All entries are paid
        $paidTenants = $totalTenants; // All shown tenants have paid
        $unpaidTenants = 0; // No unpaid tenants shown
        $totalRent = $reportData->sum('monthly_rent');
        $totalPaid = $reportData->sum('payment_amount') ?? 0; // All payments shown are paid
        $totalUnpaid = 0; // No unpaid amounts shown

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

        // Check if filtering by utility type
        $isUtilityFilter = $request->input('type') === 'utility';
        
        // Build CSV content
        $csvData = [];
        if ($isUtilityFilter) {
            $csvData[] = ['Tenant Name', 'Email', 'Phone', 'Property', 'Unit', 'Electricity', 'Water', 'Internet', 'Payment Status', 'Payment Amount', 'Payment Date', 'Due Date'];
        } else {
            $csvData[] = ['Tenant Name', 'Email', 'Phone', 'Property', 'Unit', 'Monthly Rent', 'Payment Status', 'Payment Amount', 'Payment Date', 'Due Date'];
        }

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
            
            // Only get paid payments
            $paymentRecord = $tenantPayments->where('unit_id', $unit->id)
                ->where('status', 'paid')
                ->first();
            
            // Skip if no paid payment
            if (!$paymentRecord) {
                continue;
            }
            
            $paymentStatus = ucfirst($paymentRecord->status);
            $paymentAmount = number_format((float) $paymentRecord->amount, 2);
            $paymentDate = $paymentRecord->payment_date ? $paymentRecord->payment_date->format('Y-m-d') : '';
            $dueDate = $paymentRecord->due_date ? $paymentRecord->due_date->format('Y-m-d') : '';

            if ($isUtilityFilter) {
                $csvData[] = [
                    $displayName,
                    $tenant->email,
                    $tenant->phone ?? '',
                    $unit->property->name ?? 'N/A',
                    $unit->unit_number,
                    $paymentRecord->electricity ? number_format((float) $paymentRecord->electricity, 2) : '',
                    $paymentRecord->water ? number_format((float) $paymentRecord->water, 2) : '',
                    $paymentRecord->internet ? number_format((float) $paymentRecord->internet, 2) : '',
                    $paymentStatus,
                    $paymentAmount,
                    $paymentDate,
                    $dueDate,
                ];
            } else {
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

    /**
     * Download report as PDF
     */
    public function downloadPdf(Request $request)
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

        // Build report data
        $reportData = [];
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
            
            // Only get paid payments
            $paymentRecord = $tenantPayments->where('unit_id', $unit->id)
                ->where('status', 'paid')
                ->first();
            
            // Skip if no paid payment
            if (!$paymentRecord) {
                continue;
            }
            
            $paymentStatus = ucfirst($paymentRecord->status);
            $paymentAmount = number_format((float) $paymentRecord->amount, 2, '.', ',');
            $paymentDate = $paymentRecord->payment_date ? $paymentRecord->payment_date->format('M d, Y') : '';
            $dueDate = $paymentRecord->due_date ? $paymentRecord->due_date->format('M d, Y') : '';
            $paymentType = $paymentRecord->payment_type ?? 'rent';

            $reportData[] = [
                'tenant_name' => $displayName,
                'email' => $tenant->email,
                'phone' => $tenant->phone ?? '',
                'property' => $unit->property->name ?? 'N/A',
                'unit' => $unit->unit_number,
                'monthly_rent' => number_format((float) $unit->monthly_rent, 2, '.', ','),
                'payment_status' => $paymentStatus,
                'payment_amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'due_date' => $dueDate,
                'payment_type' => $paymentType,
                'water' => $paymentRecord->water ? number_format((float) $paymentRecord->water, 2, '.', ',') : '',
                'electricity' => $paymentRecord->electricity ? number_format((float) $paymentRecord->electricity, 2, '.', ',') : '',
                'internet' => $paymentRecord->internet ? number_format((float) $paymentRecord->internet, 2, '.', ',') : '',
            ];
        }

        // Check if filtering by utility type
        $isUtilityFilter = $request->input('type') === 'utility';
        
        // Generate HTML for PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tenant Payment Report - ' . htmlspecialchars($carbonDate->format('F Y')) . '</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .date { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #e0e0e0; padding: 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        td { padding: 6px; border: 1px solid #000; }
        .no-data { text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <h1>Tenant Payment Report</h1>
    <div class="date">Period: ' . htmlspecialchars($carbonDate->format('F Y')) . '</div>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Property</th>
                <th>Unit</th>';
        
        if ($isUtilityFilter) {
            $html .= '<th>Electricity</th>
                <th>Water</th>
                <th>Internet</th>';
        } else {
            $html .= '<th>Monthly Rent</th>';
        }
        
        $html .= '<th>Payment Status</th>
                <th>Payment Amount</th>
                <th>Payment Date</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>';

        if (empty($reportData)) {
            $colspan = $isUtilityFilter ? 12 : 10;
            $html .= '<tr><td colspan="' . $colspan . '" class="no-data">No payment records found for this period.</td></tr>';
        } else {
            foreach ($reportData as $row) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($row['tenant_name']) . '</td>
                    <td>' . htmlspecialchars($row['email']) . '</td>
                    <td>' . htmlspecialchars($row['phone']) . '</td>
                    <td>' . htmlspecialchars($row['property']) . '</td>
                    <td>' . htmlspecialchars($row['unit']) . '</td>';
                
                if ($isUtilityFilter) {
                    $html .= '<td>' . ($row['electricity'] ? 'PHP ' . htmlspecialchars($row['electricity']) : '-') . '</td>
                        <td>' . ($row['water'] ? 'PHP ' . htmlspecialchars($row['water']) : '-') . '</td>
                        <td>' . ($row['internet'] ? 'PHP ' . htmlspecialchars($row['internet']) : '-') . '</td>';
                } else {
                    $html .= '<td>PHP ' . htmlspecialchars($row['monthly_rent']) . '</td>';
                }
                
                $html .= '<td>' . htmlspecialchars($row['payment_status']) . '</td>
                    <td>' . ($row['payment_amount'] ? 'PHP ' . htmlspecialchars($row['payment_amount']) : '-') . '</td>
                    <td>' . htmlspecialchars($row['payment_date'] ?: '-') . '</td>
                    <td>' . htmlspecialchars($row['due_date'] ?: '-') . '</td>
                </tr>';
            }
        }

        $html .= '</tbody></table></body></html>';

        $filename = 'tenant_payment_report_' . $filterDate . '.pdf';
        
        // Debug: Log the HTML to see what's being generated
        \Log::info('Payment Report - Records count: ' . count($reportData));
        \Log::info('Payment Report - HTML preview: ' . substr($html, 0, 500));
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('defaultFont', 'DejaVu Sans');
                $pdf->setOption('chroot', base_path());
                return $pdf->download($filename);
            } catch (\Exception $e) {
                \Log::error('DomPDF error (payment): ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                // Fallback to HTML
                return response($html, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
            }
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    }

    /**
     * Download properties as CSV
     */
    public function downloadPropertiesCsv(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = Property::where('landlord_id', $landlord->id);
        
        // Filter by type if provided
        $type = $request->input('type');
        if ($type && in_array($type, ['residential', 'commercial'])) {
            $query->where('type', $type);
        }
        
        $properties = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Property Name', 'Location', 'Type', 'Total Units', 'Available Units', 'Occupied Units'];

        foreach ($properties as $property) {
            $availableUnits = ($property->units ?? 0) - ($property->tenants ?? 0);
            $csvData[] = [
                $property->name,
                $property->street_address . ', ' . $property->city . ', ' . $property->state . ' ' . $property->zip_code,
                ucfirst($property->type),
                $property->units ?? 0,
                $availableUnits,
                $property->tenants ?? 0,
            ];
        }

        $filename = 'properties_report_' . now()->format('Y-m-d') . '.csv';
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

    /**
     * Download properties as PDF
     */
    public function downloadPropertiesPdf(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $query = Property::where('landlord_id', $landlord->id);
        
        // Filter by type if provided
        $type = $request->input('type');
        if ($type && in_array($type, ['residential', 'commercial'])) {
            $query->where('type', $type);
        }
        
        $properties = $query->orderBy('created_at', 'desc')->get();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Properties Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .date { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #e0e0e0; padding: 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        td { padding: 6px; border: 1px solid #000; }
        .no-data { text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <h1>Properties Report</h1>
    <div class="date">Generated: ' . now()->format('F d, Y') . '</div>
    <table>
        <thead>
            <tr>
                <th>Property Name</th>
                <th>Location</th>
                <th>Type</th>
                <th>Total Units</th>
                <th>Available Units</th>
                <th>Occupied Units</th>
            </tr>
        </thead>
        <tbody>';

        if ($properties->isEmpty()) {
            $html .= '<tr><td colspan="6" class="no-data">No properties found.</td></tr>';
        } else {
            foreach ($properties as $property) {
                $availableUnits = ($property->units ?? 0) - ($property->tenants ?? 0);
                $location = htmlspecialchars($property->street_address . ', ' . $property->city . ', ' . $property->state . ' ' . $property->zip_code);
                $html .= '<tr>
                    <td>' . htmlspecialchars($property->name) . '</td>
                    <td>' . $location . '</td>
                    <td>' . htmlspecialchars(ucfirst($property->type)) . '</td>
                    <td>' . ($property->units ?? 0) . '</td>
                    <td>' . $availableUnits . '</td>
                    <td>' . ($property->tenants ?? 0) . '</td>
                </tr>';
            }
        }

        $html .= '</tbody></table></body></html>';

        $filename = 'properties_report_' . now()->format('Y-m-d') . '.pdf';
        
        // Debug: Log the HTML to see what's being generated
        \Log::info('Properties Report - Properties count: ' . $properties->count());
        \Log::info('Properties Report - HTML preview: ' . substr($html, 0, 500));
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('defaultFont', 'DejaVu Sans');
                $pdf->setOption('chroot', base_path());
                return $pdf->download($filename);
            } catch (\Exception $e) {
                \Log::error('DomPDF error (properties): ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                // Fallback to HTML
                return response($html, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
            }
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    }

    /**
     * Download units as CSV
     */
    public function downloadUnitsCsv(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $propertiesQuery = Property::where('landlord_id', $landlord->id);
        
        // Filter by property type if provided
        $type = $request->input('type');
        if ($type && in_array($type, ['residential', 'commercial'])) {
            $propertiesQuery->where('type', $type);
        }
        
        $properties = $propertiesQuery->pluck('id');
        $unitsQuery = Unit::whereIn('property_id', $properties)
            ->with(['property', 'tenant']);
        
        // Filter by status if provided
        $status = $request->input('status');
        if ($status && in_array($status, ['occupied', 'available'])) {
            $unitsQuery->where('is_occupied', $status === 'occupied' ? 1 : 0);
        }
        
        $units = $unitsQuery->orderBy('property_id')
            ->orderBy('unit_number')
            ->get();

        $csvData = [];
        $csvData[] = ['Unit Number', 'Property', 'Unit Type', 'Monthly Rent', 'Status', 'Tenant Name', 'Tenant Email', 'Tenant Phone'];

        foreach ($units as $unit) {
            $csvData[] = [
                $unit->unit_number,
                $unit->property->name ?? 'N/A',
                $unit->unit_type,
                number_format((float) $unit->monthly_rent, 2),
                $unit->is_occupied ? 'Occupied' : 'Available',
                $unit->tenant ? $unit->tenant->name : '',
                $unit->tenant ? $unit->tenant->email : '',
                $unit->tenant ? ($unit->tenant->phone ?? '') : '',
            ];
        }

        $filename = 'units_report_' . now()->format('Y-m-d') . '.csv';
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

    /**
     * Download units as PDF
     */
    public function downloadUnitsPdf(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $propertiesQuery = Property::where('landlord_id', $landlord->id);
        
        // Filter by property type if provided
        $type = $request->input('type');
        if ($type && in_array($type, ['residential', 'commercial'])) {
            $propertiesQuery->where('type', $type);
        }
        
        $properties = $propertiesQuery->pluck('id');
        $unitsQuery = Unit::whereIn('property_id', $properties)
            ->with(['property', 'tenant']);
        
        // Filter by status if provided
        $status = $request->input('status');
        if ($status && in_array($status, ['occupied', 'available'])) {
            $unitsQuery->where('is_occupied', $status === 'occupied' ? 1 : 0);
        }
        
        $units = $unitsQuery->orderBy('property_id')
            ->orderBy('unit_number')
            ->get();

        $logoBase64 = $this->getLogoBase64();
        $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" alt="Logo" style="height: 30px; margin-bottom: 5px;" />' : '';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Units Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .date { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #e0e0e0; padding: 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        td { padding: 6px; border: 1px solid #000; }
        .no-data { text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <h1>Units Report</h1>
    <div class="date">Generated: ' . now()->format('F d, Y') . '</div>
    <table>
        <thead>
            <tr>
                <th>Unit Number</th>
                <th>Property</th>
                <th>Unit Type</th>
                <th>Monthly Rent</th>
                <th>Status</th>
                <th>Tenant Name</th>
                <th>Tenant Email</th>
                <th>Tenant Phone</th>
            </tr>
        </thead>
        <tbody>';

        if ($units->isEmpty()) {
            $html .= '<tr><td colspan="8" class="no-data">No units found.</td></tr>';
        } else {
            foreach ($units as $unit) {
                $propertyName = $unit->property ? htmlspecialchars($unit->property->name) : 'N/A';
                $unitNumber = htmlspecialchars($unit->unit_number ?? '');
                $unitType = htmlspecialchars(ucfirst($unit->unit_type ?? ''));
                $monthlyRent = number_format((float) ($unit->monthly_rent ?? 0), 2);
                $status = $unit->is_occupied ? 'Occupied' : 'Available';
                $tenantName = $unit->tenant ? htmlspecialchars($unit->tenant->name ?? '') : '';
                $tenantEmail = $unit->tenant ? htmlspecialchars($unit->tenant->email ?? '') : '';
                $tenantPhone = $unit->tenant ? htmlspecialchars($unit->tenant->phone ?? '') : '';
                
                $html .= '<tr>
                    <td>' . $unitNumber . '</td>
                    <td>' . $propertyName . '</td>
                    <td>' . $unitType . '</td>
                    <td>PHP ' . $monthlyRent . '</td>
                    <td>' . $status . '</td>
                    <td>' . $tenantName . '</td>
                    <td>' . $tenantEmail . '</td>
                    <td>' . $tenantPhone . '</td>
                </tr>';
            }
        }

        $html .= '</tbody></table></body></html>';

        $filename = 'units_report_' . now()->format('Y-m-d') . '.pdf';
        
        // Debug: Log the HTML to see what's being generated
        \Log::info('Units Report - Units count: ' . $units->count());
        \Log::info('Units Report - HTML preview: ' . substr($html, 0, 500));
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('defaultFont', 'DejaVu Sans');
                $pdf->setOption('chroot', base_path());
                return $pdf->download($filename);
            } catch (\Exception $e) {
                \Log::error('DomPDF error (units): ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                // Fallback to HTML
                return response($html, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
            }
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    }

    /**
     * Download tenants as CSV
     */
    public function downloadTenantsCsv(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with(['property', 'tenant'])
            ->get();

        $tenantIds = $units->pluck('tenant_id')->unique()->filter()->values()->toArray();
        $tenantsMap = Tenant::whereIn('id', $tenantIds)->get()->keyBy('id');
        $unitIds = $units->pluck('id')->toArray();
        
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id');

        $csvData = [];
        $csvData[] = ['Tenant Name', 'Email', 'Phone', 'Property', 'Unit', 'Monthly Rent', 'Lease Start', 'Lease End', 'Status'];

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

            $csvData[] = [
                $displayName,
                $tenant->email,
                $tenant->phone ?? '',
                $unit->property->name ?? 'N/A',
                $unit->unit_number,
                number_format((float) $unit->monthly_rent, 2),
                $unit->lease_start ? Carbon::parse($unit->lease_start)->format('Y-m-d') : '',
                $unit->lease_end ? Carbon::parse($unit->lease_end)->format('Y-m-d') : '',
                $unit->is_occupied ? 'Active' : 'Inactive',
            ];
        }

        $filename = 'tenants_report_' . now()->format('Y-m-d') . '.csv';
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

    /**
     * Download tenants as PDF
     */
    public function downloadTenantsPdf(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $units = Unit::whereIn('property_id', $properties)
            ->whereNotNull('tenant_id')
            ->where('is_occupied', true)
            ->with(['property', 'tenant'])
            ->get();

        $tenantIds = $units->pluck('tenant_id')->unique()->filter()->values()->toArray();
        $tenantsMap = Tenant::whereIn('id', $tenantIds)->get()->keyBy('id');
        $unitIds = $units->pluck('id')->toArray();
        
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id');

        $logoBase64 = $this->getLogoBase64();
        $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" alt="Logo" style="height: 30px; margin-bottom: 5px;" />' : '';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tenants Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .date { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #e0e0e0; padding: 8px; border: 1px solid #000; text-align: left; font-weight: bold; }
        td { padding: 6px; border: 1px solid #000; }
        .no-data { text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <h1>Tenants Report</h1>
    <div class="date">Generated: ' . now()->format('F d, Y') . '</div>
    <table>
        <thead>
            <tr>
                <th>Tenant Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Monthly Rent</th>
                <th>Lease Start</th>
                <th>Lease End</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

        $hasData = false;
        foreach ($units as $unit) {
            $tenant = $tenantsMap->get($unit->tenant_id);
            if (!$tenant) continue;

            $hasData = true;
            $application = $applicationsMap->get($unit->id);
            $displayName = $tenant->name ?? '';
            if ($application) {
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (!empty($fullName)) {
                    $displayName = $fullName;
                }
            }

            $propertyName = $unit->property ? htmlspecialchars($unit->property->name) : 'N/A';
            $unitNumber = htmlspecialchars($unit->unit_number ?? '');
            $monthlyRent = number_format((float) ($unit->monthly_rent ?? 0), 2);
            $leaseStart = $unit->lease_start ? Carbon::parse($unit->lease_start)->format('M d, Y') : '';
            $leaseEnd = $unit->lease_end ? Carbon::parse($unit->lease_end)->format('M d, Y') : '';
            $status = $unit->is_occupied ? 'Active' : 'Inactive';

            $html .= '<tr>
                <td>' . htmlspecialchars($displayName) . '</td>
                <td>' . htmlspecialchars($tenant->email ?? '') . '</td>
                <td>' . htmlspecialchars($tenant->phone ?? '') . '</td>
                <td>' . $propertyName . '</td>
                <td>' . $unitNumber . '</td>
                <td>PHP ' . $monthlyRent . '</td>
                <td>' . $leaseStart . '</td>
                <td>' . $leaseEnd . '</td>
                <td>' . $status . '</td>
            </tr>';
        }

        if (!$hasData) {
            $html .= '<tr><td colspan="9" class="no-data">No tenants found.</td></tr>';
        }

        $html .= '</tbody></table></body></html>';

        $filename = 'tenants_report_' . now()->format('Y-m-d') . '.pdf';
        
        // Debug: Log the HTML to see what's being generated
        \Log::info('Tenants Report - Units count: ' . $units->count());
        \Log::info('Tenants Report - Has data: ' . ($hasData ? 'yes' : 'no'));
        \Log::info('Tenants Report - HTML preview: ' . substr($html, 0, 500));
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOption('enable-local-file-access', true);
                $pdf->setOption('isRemoteEnabled', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('defaultFont', 'DejaVu Sans');
                $pdf->setOption('chroot', base_path());
                return $pdf->download($filename);
            } catch (\Exception $e) {
                \Log::error('DomPDF error (tenants): ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                // Fallback to HTML
                return response($html, 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
            }
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    }

    /**
     * Download individual tenant profile as PDF
     */
    public function downloadTenantProfilePdf(Request $request, $id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get tenant details (similar to TenantController::show)
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
        }

        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $units = Unit::where('tenant_id', $tenant->id)
            ->whereIn('property_id', $properties)
            ->with('property')
            ->get();

        if ($units->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tenant not found in your properties'], 404);
        }

        $unitIds = $units->pluck('id')->toArray();
        $applicationsMap = TenantApplication::whereIn('unit_id', $unitIds)
            ->where('status', 'approved')
            ->get()
            ->keyBy('unit_id');

        // Get application for first unit
        $application = $applicationsMap->get($units->first()->id);
        
        $displayName = $tenant->name;
        if ($application) {
            $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
            if (!empty($fullName)) {
                $displayName = $fullName;
            } else {
                $displayName = $application->name ?? $tenant->name;
            }
        }

        $logoBase64 = $this->getLogoBase64();
        $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" alt="Logo" style="height: 30px; margin-bottom: 5px;" />' : '';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Tenant Profile - ' . htmlspecialchars($displayName) . '</title>
    <style>
        @page { margin: 15mm; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            margin: 0;
            padding: 10px;
            color: #1a1a1a;
        }
        .report-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-header img {
            height: 30px;
            margin-bottom: 5px;
        }
        h1 { 
            color: #1a1a1a; 
            margin-bottom: 5px; 
            font-size: 18px;
            font-weight: 700;
            text-align: center;
        }
        .report-date { 
            color: #666; 
            margin-bottom: 15px; 
            font-size: 11px;
            text-align: center;
        }
        .detail-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .detail-value {
            color: #1a1a1a;
            font-size: 11px;
            margin: 0;
        }
        .unit-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            background: #f9fafb;
        }
        .unit-header {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        .unit-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 10px;
        }
        .unit-detail-label {
            font-weight: 600;
            color: #666;
        }
        .unit-detail-value {
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="report-header">
        ' . $logoHtml . '
        <h1>Tenant Profile</h1>
        <div class="report-date">Generated: ' . now()->format('F d, Y') . '</div>
    </div>

    <!-- Personal Information -->
    <div class="detail-section">
        <h3 class="section-title">Personal Information</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <label class="detail-label">Full Name</label>
                <p class="detail-value">' . htmlspecialchars($displayName) . '</p>
            </div>
            <div class="detail-item">
                <label class="detail-label">Email</label>
                <p class="detail-value">' . htmlspecialchars($tenant->email) . '</p>
            </div>
            <div class="detail-item">
                <label class="detail-label">Phone Number</label>
                <p class="detail-value">' . htmlspecialchars($tenant->phone ?? 'N/A') . '</p>
            </div>';

        if ($application && $application->whatsapp) {
            $html .= '<div class="detail-item">
                <label class="detail-label">WhatsApp</label>
                <p class="detail-value">' . htmlspecialchars($application->whatsapp) . '</p>
            </div>';
        }

        $html .= '<div class="detail-item full-width">
                <label class="detail-label">Address</label>
                <p class="detail-value">' . htmlspecialchars($tenant->address ?? 'N/A') . '</p>
            </div>';

        if ($application) {
            if ($application->occupation) {
                $html .= '<div class="detail-item">
                    <label class="detail-label">Occupation</label>
                    <p class="detail-value">' . htmlspecialchars($application->occupation) . '</p>
                </div>';
            }
            if ($application->monthly_income) {
                $html .= '<div class="detail-item">
                    <label class="detail-label">Monthly Income</label>
                    <p class="detail-value">' . number_format((float) $application->monthly_income, 2) . '</p>
                </div>';
            }
            if ($application->number_of_people) {
                $html .= '<div class="detail-item">
                    <label class="detail-label">Number of People</label>
                    <p class="detail-value">' . htmlspecialchars($application->number_of_people) . '</p>
                </div>';
            }
            if ($application->lease_duration_months) {
                $html .= '<div class="detail-item">
                    <label class="detail-label">Lease Duration</label>
                    <p class="detail-value">' . htmlspecialchars($application->lease_duration_months) . ' months</p>
                </div>';
            }
        }

        $html .= '</div>
    </div>';

        // Reference 1
        if ($application && $application->reference1_name) {
            $html .= '<div class="detail-section">
                <h3 class="section-title">Reference 1</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label class="detail-label">Relationship</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference1_relationship ?? 'N/A') . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Name</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference1_name) . '</p>
                    </div>
                    <div class="detail-item full-width">
                        <label class="detail-label">Address</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference1_address) . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Phone Number</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference1_phone) . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Email</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference1_email) . '</p>
                    </div>
                </div>
            </div>';
        }

        // Reference 2
        if ($application && $application->reference2_name) {
            $html .= '<div class="detail-section">
                <h3 class="section-title">Reference 2</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label class="detail-label">Relationship</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference2_relationship ?? 'N/A') . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Name</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference2_name) . '</p>
                    </div>
                    <div class="detail-item full-width">
                        <label class="detail-label">Address</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference2_address) . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Phone Number</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference2_phone) . '</p>
                    </div>
                    <div class="detail-item">
                        <label class="detail-label">Email</label>
                        <p class="detail-value">' . htmlspecialchars($application->reference2_email) . '</p>
                    </div>
                </div>
            </div>';
        }

        // Unit Information
        foreach ($units as $unit) {
            $html .= '<div class="detail-section">
                <h3 class="section-title">Unit Information - ' . htmlspecialchars($unit->property->name ?? 'N/A') . ' - Unit ' . htmlspecialchars($unit->unit_number) . '</h3>
                <div class="unit-item">
                    <div class="unit-details">
                        <div>
                            <span class="unit-detail-label">Unit Type:</span>
                            <span class="unit-detail-value">' . htmlspecialchars(ucfirst($unit->unit_type)) . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Bedrooms:</span>
                            <span class="unit-detail-value">' . ($unit->bedrooms ?? 'N/A') . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Bathrooms:</span>
                            <span class="unit-detail-value">' . ($unit->bathrooms ?? 'N/A') . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Square Footage:</span>
                            <span class="unit-detail-value">' . ($unit->square_footage ? $unit->square_footage . ' sq ft' : 'N/A') . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Monthly Rent:</span>
                            <span class="unit-detail-value">' . number_format((float) $unit->monthly_rent, 2) . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Security Deposit:</span>
                            <span class="unit-detail-value">' . ($unit->security_deposit ? number_format((float) $unit->security_deposit, 2) : 'N/A') . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Lease Start:</span>
                            <span class="unit-detail-value">' . ($unit->lease_start ? Carbon::parse($unit->lease_start)->format('M d, Y') : 'N/A') . '</span>
                        </div>
                        <div>
                            <span class="unit-detail-label">Lease End:</span>
                            <span class="unit-detail-value">' . ($unit->lease_end ? Carbon::parse($unit->lease_end)->format('M d, Y') : 'N/A') . '</span>
                        </div>
                    </div>
                </div>
            </div>';
        }

        $html .= '</body>
</html>';

        $filename = 'tenant_profile_' . str_replace(' ', '_', $displayName) . '_' . now()->format('Y-m-d') . '.pdf';
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                return $pdf->download($filename);
            } catch (\Exception $e) {}
        }
        
        return response($html, 200)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    }

    /**
     * Get logo as base64 encoded string
     */
    private function getLogoBase64()
    {
        try {
            // Try multiple possible paths
            $possiblePaths = [
                public_path('images/tahananLogo.png'),
                resource_path('js/images/tahananLogo.png'),
                public_path('build/images/tahananLogo.png'),
            ];
            
            foreach ($possiblePaths as $logoPath) {
                if (file_exists($logoPath)) {
                    $logoData = file_get_contents($logoPath);
                    if ($logoData !== false) {
                        $logoBase64 = base64_encode($logoData);
                        return 'data:image/png;base64,' . $logoBase64;
                    }
                }
            }
        } catch (\Exception $e) {
            // Return null if logo cannot be loaded
            \Log::error('Error loading logo for PDF: ' . $e->getMessage());
        }
        return null;
    }
}
