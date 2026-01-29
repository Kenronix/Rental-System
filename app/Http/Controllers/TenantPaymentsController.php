<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantPaymentsController extends Controller
{
    /**
     * Get all payments for the authenticated tenant
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

        // Get all payments for this tenant, ordered by created_at descending (most recent first)
        $payments = Payment::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                // Calculate total amount for utility payments
                $totalAmount = $payment->amount;
                if ($payment->payment_type === 'utility') {
                    $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
                }

                // Build description based on payment type
                $description = '';
                if ($payment->payment_type === 'rent') {
                    $description = 'Rent Payment';
                    if ($payment->due_date) {
                        $month = $payment->due_date->format('F');
                        $description .= ' - ' . $month;
                    }
                } elseif ($payment->payment_type === 'utility') {
                    $description = 'Utilities Payment';
                    $breakdown = [];
                    if ($payment->water && $payment->water > 0) {
                        $breakdown[] = 'Water';
                    }
                    if ($payment->electricity && $payment->electricity > 0) {
                        $breakdown[] = 'Electricity';
                    }
                    if ($payment->internet && $payment->internet > 0) {
                        $breakdown[] = 'Internet';
                    }
                    if (!empty($breakdown)) {
                        $description .= ' (' . implode(', ', $breakdown) . ')';
                    }
                } else {
                    $description = ucfirst($payment->payment_type) . ' Payment';
                }

                // Determine status
                $status = 'pending';
                if ($payment->review_status === 'approved' && $payment->status === 'paid') {
                    $status = 'paid';
                } elseif ($payment->review_status === 'pending_review') {
                    $status = 'pending';
                } elseif ($payment->status === 'paid') {
                    $status = 'paid';
                }

                return [
                    'id' => $payment->id,
                    'date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                    'description' => $description,
                    'amount' => $totalAmount,
                    'status' => $status,
                    'payment_method' => $payment->payment_method,
                    'reference_number' => $payment->reference_number,
                    'payment_proof' => $payment->payment_proof ? asset('storage/' . $payment->payment_proof) : null,
                    'payment_type' => $payment->payment_type,
                ];
            })
            ->values();

        // Calculate current balance (sum of unpaid/pending payments)
        $unpaidPayments = Payment::where('tenant_id', $tenant->id)
            ->where(function ($query) {
                $query->where('status', '!=', 'paid')
                    ->orWhere('review_status', '!=', 'approved');
            })
            ->get();

        $currentBalance = $unpaidPayments->sum(function ($payment) {
            if ($payment->payment_type === 'utility') {
                return (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
            }
            return (float) $payment->amount;
        });

        // Get the earliest due date from unpaid/pending payments
        $nextDueDate = $unpaidPayments
            ->where('due_date', '!=', null)
            ->sortBy('due_date')
            ->first();

        return response()->json([
            'success' => true,
            'current_balance' => $currentBalance,
            'next_due_date' => $nextDueDate ? $nextDueDate->due_date->format('Y-m-d') : null,
            'payment_history' => $payments,
        ]);
    }
}
