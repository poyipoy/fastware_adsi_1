<!DOCTYPE html>
<html>
<head>
    <title>Invoice History CLaim COmplain</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-details h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .invoice-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
            <div class="header">
                <h2>Invoice</h2>
                <p>Invoice Number: {{ $record->no_wo }}</p>
            </div>
            <div class="invoice-details">
                <h2>Customer Details</h2>
                <p>Customer Code: {{ $record->customer_code }}</p>
                <p>Customer Name: {{ $record->name_customer }}</p>
                <p>Area: {{ $record->area }}</p>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Material Type</th>
                        <th>Thickness</th>
                        <th>Weight</th>
                        <th>Outer Diameter</th>
                        <th>Inner Diameter</th>
                        <th>Length</th>
                        <th>QTY(/Kg)</th>
                        <th>PCS</th>
                        <th>Category(NG)</th>
                        <th>Process Type</th>
                        <th>Type 1</th>
                        <th>Schedule Visit</th>
                        <th>Due Date</th>
                        <th>Pic</th>
                        <!-- Tambahkan kolom lain sesuai kebutuhan -->
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>{{ $record->type_name }}</td>
                        <td>{{ $record->thickness }}</td>
                        <td>{{ $record->weight }}</td>
                        <td>{{ $record->outer_diameter }}</td>
                        <td>{{ $record->inner_diameter }}</td>
                        <td>{{ $record->length }}</td>
                        <td>{{ $record->qty }}</td>
                        <td>{{ $record->pcs }}</td>
                        <td>{{ $record->category }}</td>
                        <td>{{ $record->process_type }}</td>
                        <td>{{ $record->type_1 }}</td>
                        <td>{{ $record->schedule }}</td>
                        <td>{{ $record->due_date }}</td>
                        <td>{{ $record->pic }}</td>
                    </tr>
                </tbody>
            </table>
            <!-- Tambahkan informasi lain atau detail sesuai kebutuhan -->
            <div class="footer">
                <p>Generated at: {{ $record->created_at }}</p>
            </div>
    </div>
    
    
</body>
</html>
