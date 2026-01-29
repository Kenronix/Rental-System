<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            color: #000;
            line-height: 1.5;
            font-size: 12px;
            background: #fff;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
            background: #fff;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 16px;
            color: #000;
        }
        
        .receipt-meta {
            margin-bottom: 24px;
        }
        
        .receipt-meta p {
            font-size: 12px;
            margin-bottom: 4px;
            color: #000;
        }
        
        .receipt-meta .label {
            font-weight: 400;
        }
        
        .receipt-meta .value {
            font-weight: 600;
        }
        
        .bill-to {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #000;
        }
        
        .bill-to-content p {
            font-size: 12px;
            margin-bottom: 4px;
            color: #000;
        }
        
        .bill-to-content .label {
            font-weight: 400;
        }
        
        .bill-to-content .value {
            font-weight: 600;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #f2f2f2;
            color: #000;
            font-size: 12px;
            font-weight: bold;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
.items-table th:nth-child(2),
.items-table th:nth-child(3) {
    text-align: right;
}
        
        .items-table td {
            padding: 10px 12px;
            font-size: 12px;
            color: #000;
            border-bottom: 1px solid #ddd;
        }
        
.items-table td:nth-child(2),
.items-table td:nth-child(3) {
    text-align: right;
}
        
        .totals-wrap {
            text-align: right;
            margin-bottom: 24px;
        }
        
        .totals-row {
            font-size: 12px;
            margin-bottom: 4px;
            color: #000;
        }
        
        .totals-row .label {
            font-weight: 400;
            display: inline-block;
            min-width: 80px;
        }
        
        .totals-row .value {
            font-weight: 600;
            display: inline-block;
            min-width: 100px;
            text-align: right;
        }
        
        .totals-row.total-row .value {
            font-weight: bold;
            font-size: 14px;
        }
        
        .receipt-footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #000;
        }
        
        .receipt-footer .label {
            font-weight: 400;
        }
        
        .receipt-footer .value {
            font-weight: 600;
        }
        
        @media print {
            .receipt-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <h1 class="receipt-title">Receipt</h1>
        
        <div class="receipt-meta">
            <p><span class="label">Receipt Number:</span> <span class="value">{{ $receiptNumber }}</span></p>
            <p><span class="label">Date:</span> <span class="value">{{ $paymentDate }}</span></p>
            @if($payment->due_date)
            <p><span class="label">Due date:</span> <span class="value">{{ $payment->due_date->format('F d, Y') }}</span></p>
            @endif
        </div>
        
        <div class="bill-to">Bill to:</div>
        <div class="bill-to-content">
            <p><span class="label">Customer Name:</span> <span class="value">{{ $tenant->name }}</span></p>
            <p><span class="label">Address:</span> <span class="value">{{ $property->street_address }}, {{ $property->city }}, {{ $property->state }} {{ $property->zip_code }}</span></p>
            <p><span class="label">Property:</span> <span class="value">{{ $property->name }} – Unit {{ $unit->unit_number }}</span></p>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if($payment->payment_type === 'rent')
                    <tr>
                        <td>Rent Payment</td>
                        <td>Php {{ number_format($payment->amount, 2) }}</td>
                        <td>Php {{ number_format($payment->amount, 2) }}</td>
                    </tr>
                @elseif($payment->payment_type === 'utility')
                    @if($payment->water && $payment->water > 0)
                    <tr>
                        <td>Water</td>
                        <td>Php {{ number_format($payment->water, 2) }}</td>
                        <td>Php {{ number_format($payment->water, 2) }}</td>
                    </tr>
                    @endif
                    @if($payment->electricity && $payment->electricity > 0)
                    <tr>
                        <td>Electricity</td>
                        <td>Php {{ number_format($payment->electricity, 2) }}</td>
                        <td>Php {{ number_format($payment->electricity, 2) }}</td>
                    </tr>
                    @endif
                    @if($payment->internet && $payment->internet > 0)
                    <tr>
                        <td>Internet</td>
                        <td>Php {{ number_format($payment->internet, 2) }}</td>
                        <td>Php {{ number_format($payment->internet, 2) }}</td>
                    </tr>
                    @endif
                @endif
            </tbody>
        </table>
        
        <div class="totals-wrap">
            <div class="totals-row">
                <span class="label">Subtotal:</span>
                <span class="value">Php {{ number_format($totalAmount, 2) }}</span>
            </div>
            <div class="totals-row total-row">
                <span class="label">Total:</span>
                <span class="value">Php {{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p><span class="label">Status:</span> <span class="value">Paid</span></p>
            @if($payment->payment_method)
            <p><span class="label">Payment Method:</span> <span class="value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span></p>
            @endif
            @if($payment->reference_number)
            <p><span class="label">Reference Number:</span> <span class="value">{{ $payment->reference_number }}</span></p>
            @endif
            <p style="margin-top: 12px; font-size: 11px; font-weight: 400;">This is an official receipt. Please keep for your records.</p>
        </div>
    </div>
</body>
</html>
