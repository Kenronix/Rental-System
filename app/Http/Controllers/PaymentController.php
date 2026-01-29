<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\TenantApplication;
use App\Models\Notification;
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

        // Get all properties and units
        $properties = Property::where('landlord_id', $landlord->id)->pluck('id');
        $unitIds = Unit::whereIn('property_id', $properties)->pluck('id');
        
        // Get all payments
        $payments = Payment::whereIn('unit_id', $unitIds)
            ->with(['tenant', 'unit.property'])
            ->orderBy('created_at', 'desc')
            ->orderBy('payment_date', 'desc')
            ->get();

        // payments data
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

            // Payment proof
            $paymentProofUrl = null;
            if ($payment->payment_proof) {
                $paymentProofUrl = '/storage/' . $payment->payment_proof;
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
                'review_status' => $payment->review_status,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'payment_proof' => $payment->payment_proof,
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
     * New payment
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
            'unit_id' => 'required|exists:units,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'payment_type' => 'required|in:rent,utility',
            'amount' => 'required|numeric|min:0',
            'water' => 'nullable|numeric|min:0',
            'electricity' => 'nullable|numeric|min:0',
            'internet' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'due_date' => 'required|date',
            'status' => 'sometimes|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        // Default status to 'pending' (not paid) when creating a new payment
        if (!isset($validated['status'])) {
            $validated['status'] = 'pending';
        }
        
        // Validate due_date is after or equal to payment_date only if payment_date exists
        if (isset($validated['payment_date']) && $validated['payment_date']) {
            $request->validate([
                'due_date' => 'after_or_equal:payment_date',
            ]);
        }

        // Verify the unit
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

        // If tenant_id is not provided, get it from the unit
        if (!isset($validated['tenant_id']) || !$validated['tenant_id']) {
            if (!$unit->tenant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit has no tenant assigned',
                ], 400);
            }
            $validated['tenant_id'] = $unit->tenant_id;
        } else {
            // Verify tenant if na assigned siya sa unit
            if ($unit->tenant_id && $unit->tenant_id !== (int) $validated['tenant_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant is not assigned to this unit',
                ], 400);
            }
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

        // Verify the payment
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
            'payment_date' => 'nullable|date',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:paid,pending,overdue',
            'payment_method' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        // Validate due_date is after or equal to payment_date only if payment_date exists
        if (isset($validated['payment_date']) && $validated['payment_date'] && isset($validated['due_date'])) {
            $request->validate([
                'due_date' => 'after_or_equal:payment_date',
            ]);
        }

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

    /**
     * Approve a payment review
     */
    public function approve(Request $request, $id)
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

        // Update payment status
        $payment->review_status = 'approved';
        $payment->status = 'paid';
        $payment->save();

        // Create notification for tenant
        $paymentType = $payment->payment_type === 'rent' ? 'Rent' : 'Utilities';
        
        // Calculate total amount for utility payments
        $totalAmount = $payment->amount;
        if ($payment->payment_type === 'utility') {
            $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
        }
        
        $message = "Your {$paymentType} payment of Php " . number_format($totalAmount, 2) . " has been approved.";
        if ($payment->reference_number) {
            $message .= " Reference Number: {$payment->reference_number}.";
        }
        
        Notification::create([
            'tenant_id' => $payment->tenant_id,
            'type' => 'payment_approved',
            'title' => 'Payment Approved',
            'message' => $message,
            'payment_id' => $payment->id,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Reject a payment review
     */
    public function reject(Request $request, $id)
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

        // Update payment status
        $payment->review_status = 'rejected';
        $payment->status = 'pending';
        $payment->save();

        // Create notification for tenant
        $paymentType = $payment->payment_type === 'rent' ? 'Rent' : 'Utilities';
        
        // Calculate total amount for utility payments
        $totalAmount = $payment->amount;
        if ($payment->payment_type === 'utility') {
            $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
        }
        
        $message = "Your {$paymentType} payment of Php " . number_format($totalAmount, 2) . " has been rejected. Please contact your landlord for more information.";
        if ($payment->reference_number) {
            $message .= " Reference Number: {$payment->reference_number}.";
        }
        
        Notification::create([
            'tenant_id' => $payment->tenant_id,
            'type' => 'payment_rejected',
            'title' => 'Payment Rejected',
            'message' => $message,
            'payment_id' => $payment->id,
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected successfully',
            'payment' => $payment,
        ]);
    }
}
