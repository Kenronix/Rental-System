<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    /**
     * Generate and download receipt for a payment
     */
    public function generate(Request $request, $id)
    {
        $tenant = Auth::guard('tenant')->user();
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $payment = Payment::with(['tenant', 'unit.property.landlord'])
            ->where('id', $id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Only generate receipt for approved payments
        if ($payment->review_status !== 'approved' || $payment->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Receipt can only be generated for approved payments',
            ], 400);
        }

        // Calculate total amount
        $totalAmount = $payment->amount;
        if ($payment->payment_type === 'utility') {
            $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
        }

        // Prepare receipt data
        $receiptData = [
            'payment' => $payment,
            'tenant' => $payment->tenant,
            'unit' => $payment->unit,
            'property' => $payment->unit->property,
            'landlord' => $payment->unit->property->landlord,
            'totalAmount' => $totalAmount,
            'receiptNumber' => 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'paymentDate' => $payment->payment_date ? $payment->payment_date->format('F d, Y') : $payment->created_at->format('F d, Y'),
            'paymentTime' => $payment->created_at->format('h:i A'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('receipt', $receiptData);
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'Receipt-' . $receiptData['receiptNumber'] . '-' . date('Y-m-d') . '.pdf';
        
        // Return PDF download
        return $pdf->download($filename);
    }

    /**
     * Generate and download receipt for a payment (landlord)
     */
    public function landlordGenerate(Request $request, $id)
    {
        $landlord = Auth::guard('landlord')->user();

        if (!$landlord) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $payment = Payment::with(['tenant', 'unit.property.landlord'])
            ->where('id', $id)
            ->whereHas('unit.property', fn ($q) => $q->where('landlord_id', $landlord->id))
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Only generate receipt for approved/paid payments
        if ($payment->review_status !== 'approved' || $payment->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Receipt can only be generated for approved payments',
            ], 400);
        }

        // Calculate total amount
        $totalAmount = $payment->amount;
        if ($payment->payment_type === 'utility') {
            $totalAmount = (float) ($payment->water ?? 0) + (float) ($payment->electricity ?? 0) + (float) ($payment->internet ?? 0);
        }

        $receiptData = [
            'payment' => $payment,
            'tenant' => $payment->tenant,
            'unit' => $payment->unit,
            'property' => $payment->unit->property,
            'landlord' => $payment->unit->property->landlord,
            'totalAmount' => $totalAmount,
            'receiptNumber' => 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'paymentDate' => $payment->payment_date ? $payment->payment_date->format('F d, Y') : $payment->created_at->format('F d, Y'),
            'paymentTime' => $payment->created_at->format('h:i A'),
        ];

        $pdf = Pdf::loadView('receipt', $receiptData);
        $pdf->setPaper('A4', 'portrait');
        $filename = 'Receipt-' . $receiptData['receiptNumber'] . '-' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
