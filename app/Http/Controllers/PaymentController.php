<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Get all payments for the authenticated landlord
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
        $unitIds = Unit::whereIn('property_id', $properties)->pluck('id');
        
        // Get all payments for these units
        $payments = Payment::whereIn('unit_id', $unitIds)
            ->with(['tenant', 'unit.property'])
            ->orderBy('payment_date', 'desc')
            ->get();

        // Transform payments data
        $paymentsData = $payments->map(function ($payment) {
            $tenant = $payment->tenant;
            $unit = $payment->unit;
            $property = $unit->property ?? null;
            
            // Get tenant name from application if available
            $displayName = $tenant->name;
            $application = TenantApplication::where('email', $tenant->email)
                ->where('unit_id', $unit->id)
                ->where('status', 'approved')
                ->latest()
                ->first();
            
            if ($application) {
                $fullName = trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''));
                if (!empty($fullName)) {
                    $displayName = $fullName;
                }
            }

            return [
                'id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'tenant_name' => $displayName,
                'tenant_email' => $tenant->email,
                'unit_id' => $payment->unit_id,
                'unit_number' => $unit->unit_number,
                'property_id' => $property ? $property->id : null,
                'property_name' => $property ? $property->name : 'N/A',
                'payment_type' => $payment->payment_type ?? 'rent',
                'amount' => (float) $payment->amount,
                'water' => $payment->water ? (float) $payment->water : null,
                'electricity' => $payment->electricity ? (float) $payment->electricity : null,
                'internet' => $payment->internet ? (float) $payment->internet : null,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        // Calculate statistics
        $totalPayments = $paymentsData->count();
        $totalAmount = $paymentsData->sum('amount');
        $paidAmount = $paymentsData->where('status', 'paid')->sum('amount');
        $pendingAmount = $paymentsData->where('status', 'pending')->sum('amount');
        $overdueAmount = $paymentsData->where('status', 'overdue')->sum('amount');
        $paidCount = $paymentsData->where('status', 'paid')->count();
        $pendingCount = $paymentsData->where('status', 'pending')->count();
        $overdueCount = $paymentsData->where('status', 'overdue')->count();

        return response()->json([
            'success' => true,
            'payments' => $paymentsData,
            'statistics' => [
                'total_payments' => $totalPayments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'overdue_amount' => $overdueAmount,
                'paid_count' => $paidCount,
                'pending_count' => $pendingCount,
                'overdue_count' => $overdueCount,
            ],
        ]);
    }

    /**
     * Store a new payment
     */
    public function store(Request $request)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'unit_id' => 'required|exists:units,id',
            'payment_type' => 'required|in:rent,utility',
            'amount' => 'required|numeric|min:0',
            'water' => 'nullable|numeric|min:0',
            'electricity' => 'nullable|numeric|min:0',
            'internet' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:payment_date',
            'status' => 'required|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Verify the unit belongs to this landlord
        $unit = Unit::find($validated['unit_id']);
        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found',
            ], 404);
        }

        $property = Property::find($unit->property_id);
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create payment for this unit',
            ], 403);
        }

        // Verify tenant is assigned to this unit
        if ($unit->tenant_id !== (int) $validated['tenant_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is not assigned to this unit',
            ], 400);
        }

        $payment = Payment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Update a payment
     */
    public function update(Request $request, $id)
    {
        $landlord = Auth::guard('landlord')->user();
        
        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $payment = Payment::with('unit.property')->find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify the payment belongs to this landlord
        $property = $payment->unit->property;
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'payment_type' => 'sometimes|in:rent,utility',
            'amount' => 'sometimes|numeric|min:0',
            'water' => 'nullable|numeric|min:0',
            'electricity' => 'nullable|numeric|min:0',
            'internet' => 'nullable|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:payment_date',
            'status' => 'sometimes|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Delete a payment
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

        $payment = Payment::with('unit.property')->find($id);
        
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify the payment belongs to this landlord
        $property = $payment->unit->property;
        if (!$property || $property->landlord_id !== $landlord->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ]);
    }
}
