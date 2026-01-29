<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TenantUtilitiesController extends Controller
{
    /**
     * Get utilities for the authenticated tenant
     */
    public function index(Request $request)
    {
        $tenant = Auth::guard('tenant')->user();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get all utility payments for this tenant
        $utilityPayments = Payment::where('tenant_id', $tenant->id)
            ->where('payment_type', 'utility')
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform payments into grouped utility items
        $utilities = [];
        $paymentHistory = [];
        
        foreach ($utilityPayments as $payment) {
            // Calculate total amount
            $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
            
            if ($totalAmount > 0) {
                // Build breakdown
                $breakdown = [];
                if ($payment->water && $payment->water > 0) {
                    $breakdown[] = [
                        'name' => 'Water',
                        'amount' => (float) $payment->water,
                    ];
                }
                if ($payment->electricity && $payment->electricity > 0) {
                    $breakdown[] = [
                        'name' => 'Electricity',
                        'amount' => (float) $payment->electricity,
                    ];
                }
                if ($payment->internet && $payment->internet > 0) {
                    $breakdown[] = [
                        'name' => 'Internet',
                        'amount' => (float) $payment->internet,
                    ];
                }

                $utilityItem = [
                    'id' => $payment->id,
                    'payment_id' => $payment->id,
                    'name' => 'Utilities',
                    'amount' => $totalAmount,
                    'breakdown' => $breakdown,
                    'dueDate' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                    'status' => $payment->status === 'paid' ? 'paid' : 'unpaid',
                    'review_status' => $payment->review_status,
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                ];

                // Add to utilities list if unpaid or pending review
                if ($payment->status !== 'paid' || $payment->review_status === 'pending_review') {
                    $utilities[] = $utilityItem;
                }

                // Add to payment history
                $paymentHistory[] = $utilityItem;
            }
        }

        return response()->json([
            'success' => true,
            'utilities' => $utilities,
            'payment_history' => $paymentHistory,
        ]);
    }

    /**
     * Mark a utility payment as paid
     */
    public function pay(Request $request, $id)
    {
        $tenant = Auth::guard('tenant')->user();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Handle both old format (payment_id_type) and new format (just payment_id)
        $paymentId = $id;
        if (strpos($id, '_') !== false) {
            $parts = explode('_', $id);
            $paymentId = $parts[0];
        }
        
        // Find the payment
        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify this is a utility payment
        if ($payment->payment_type !== 'utility') {
            return response()->json([
                'success' => false,
                'message' => 'This is not a utility payment',
            ], 400);
        }

        // Validate request data
        $request->validate([
            'payment_method' => 'required|string|in:online,bank_transfer,cash,check,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        // Handle payment proof photo upload
        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = 'payment_proof_' . $payment->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $paymentProofPath = $file->storeAs('payment_proofs', $filename, 'public');
        }

        // Update payment with submitted information
        $payment->payment_method = $request->payment_method;
        $payment->reference_number = $request->reference_number;
        $payment->notes = $request->notes;
        $payment->payment_date = now();
        
        // When payment proof is uploaded, set review_status to pending_review
        if ($paymentProofPath) {
            $payment->payment_proof = $paymentProofPath;
            $payment->review_status = 'pending_review';
            // Keep status as pending until landlord reviews
            $payment->status = 'pending';
        } else {
            // For payments without proof, also mark as pending_review
            $payment->review_status = 'pending_review';
            $payment->status = 'pending';
        }

        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment submitted successfully. It will be reviewed by your landlord.',
        ]);
    }
}
