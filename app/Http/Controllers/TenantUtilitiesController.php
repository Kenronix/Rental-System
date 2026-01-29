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
            ->orderBy('due_date', 'desc')
            ->get();

        // Transform payments into utility items
        $utilities = [];
        
        foreach ($utilityPayments as $payment) {
            // Create separate utility items for each utility type that has a value
            if ($payment->water && $payment->water > 0) {
                $utilities[] = [
                    'id' => $payment->id . '_water',
                    'payment_id' => $payment->id,
                    'name' => 'Water',
                    'type' => 'water',
                    'amount' => (float) $payment->water,
                    'dueDate' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                    'status' => $payment->status === 'paid' ? 'paid' : 'unpaid',
                    'icon' => 'water',
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                ];
            }

            if ($payment->electricity && $payment->electricity > 0) {
                $utilities[] = [
                    'id' => $payment->id . '_electricity',
                    'payment_id' => $payment->id,
                    'name' => 'Electricity',
                    'type' => 'electricity',
                    'amount' => (float) $payment->electricity,
                    'dueDate' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                    'status' => $payment->status === 'paid' ? 'paid' : 'unpaid',
                    'icon' => 'electricity',
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                ];
            }

            if ($payment->internet && $payment->internet > 0) {
                $utilities[] = [
                    'id' => $payment->id . '_internet',
                    'payment_id' => $payment->id,
                    'name' => 'Internet',
                    'type' => 'internet',
                    'amount' => (float) $payment->internet,
                    'dueDate' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                    'status' => $payment->status === 'paid' ? 'paid' : 'unpaid',
                    'icon' => 'internet',
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'utilities' => $utilities,
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

        // Extract payment_id and utility type from the id (format: payment_id_type)
        $parts = explode('_', $id);
        if (count($parts) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid utility ID',
            ], 400);
        }

        $paymentId = $parts[0];
        
        // Find the payment - check both utility type payments and payments that might have utilities
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
        
        if ($paymentProofPath) {
            $payment->payment_proof = $paymentProofPath;
        }

        // Set review status to pending_review for online payments or when proof is uploaded
        if ($request->payment_method === 'online' || $paymentProofPath) {
            $payment->review_status = 'pending_review';
            // Keep status as pending until landlord reviews
            $payment->status = 'pending';
        } else {
            // For cash payments without proof, mark as pending_review as well
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
