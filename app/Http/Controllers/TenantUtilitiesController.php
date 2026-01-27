<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        // Find the payment
        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->where('payment_type', 'utility')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Update payment status to paid
        $payment->status = 'paid';
        $payment->payment_date = now();
        $payment->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
        ]);
    }
}
