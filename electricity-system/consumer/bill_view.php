<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'consumer') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['first_login']) || $_SESSION['first_login'] == 1) {
    header('Location: ../change_password.php');
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];
$service_number = $_SESSION['service_number'];

$bill_id = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;

if ($bill_id <= 0) {
    die("Invalid bill ID");
}

$stmt = $conn->prepare("SELECT * FROM bills WHERE bill_id = ? AND service_number = ?");
$stmt->bind_param('is', $bill_id, $service_number);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    die("Bill not found or access denied");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'consumer'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$consumer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$consumer) {
    die("Consumer not found");
}

$subtotal = $bill['energy_charges'] + $bill['fixed_charges'] + $bill['fsa'] + $bill['duty'] + $bill['gst'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Electricity Bill - <?php echo $bill['bill_id']; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .bill-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #333;
            padding: 20px;
        }
        .bill-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .bill-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .bill-section {
            margin-bottom: 20px;
        }
        .bill-section h3 {
            background: #f0f0f0;
            padding: 8px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .bill-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        .bill-row:last-child {
            border-bottom: none;
        }
        .bill-label {
            font-weight: bold;
        }
        .bill-value {
            text-align: right;
        }
        .bill-total {
            background: #f5f5f5;
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            margin-top: 10px;
            border: 2px solid #333;
        }
        .bill-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #333;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #0077cc;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .back-btn:hover {
            background: #005fa3;
        }
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .print-btn:hover {
            background: #218838;
        }
        .pay-btn {
            background: #2e7d32;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .pay-btn:hover {
            background: #1b5e20;
        }
        @media print {
            .back-btn, .print-btn, .pay-btn {
                display: none;
            }
            .bill-container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    <button class="print-btn" onclick="window.print()">Print Bill</button>
    
    <div class="bill-container">
        <div class="bill-header">
            <h1>ELECTRICITY SERVICE BILL</h1>
            <p>Electricity Service & Billing System</p>
        </div>

        <div class="bill-section">
            <h3>Bill Information</h3>
            <div class="bill-row">
                <span class="bill-label">Bill Number:</span>
                <span class="bill-value"><?php echo $bill['bill_id']; ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Service Number:</span>
                <span class="bill-value"><?php echo htmlspecialchars($bill['service_number']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Billing Period:</span>
                <span class="bill-value"><?php echo date('d/m/Y', strtotime($bill['billing_from'])); ?> to <?php echo date('d/m/Y', strtotime($bill['billing_to'])); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Due Date:</span>
                <span class="bill-value"><?php echo date('d/m/Y', strtotime($bill['due_date'])); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Bill Date:</span>
                <span class="bill-value"><?php echo date('d/m/Y', strtotime($bill['created_at'])); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Status:</span>
                <span class="bill-value"><span class="status status-<?php echo strtolower($bill['status']); ?>"><?php echo htmlspecialchars($bill['status']); ?></span></span>
            </div>
        </div>

        <div class="bill-section">
            <h3>Consumer Details</h3>
            <div class="bill-row">
                <span class="bill-label">Consumer Name:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['name']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Address:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['address']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Pin Code:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['pincode']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Mobile:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['mobile']); ?></span>
            </div>
        </div>

        <div class="bill-section">
            <h3>Service Details</h3>
            <div class="bill-row">
                <span class="bill-label">Meter Number:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['meter_number']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Category:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['service_category']); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Sanctioned Load:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['sanctioned_load']); ?> kW</span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Service Start Date:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['service_start_date']); ?></span>
            </div>
        </div>

        <div class="bill-section">
            <h3>Usage Details</h3>
            <div class="bill-row">
                <span class="bill-label">Previous Reading:</span>
                <span class="bill-value"><?php echo $bill['previous_reading']; ?> units</span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Current Reading:</span>
                <span class="bill-value"><?php echo $bill['current_reading']; ?> units</span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Units Consumed:</span>
                <span class="bill-value"><strong><?php echo $bill['units_consumed']; ?> units</strong></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Tariff Category:</span>
                <span class="bill-value"><?php echo htmlspecialchars($consumer['service_category']); ?></span>
            </div>
        </div>

        <div class="bill-section">
            <h3>Charges Breakdown</h3>
            <div class="bill-row">
                <span class="bill-label">Energy Charges:</span>
                <span class="bill-value">₹<?php echo number_format($bill['energy_charges'], 2); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Fixed Charges:</span>
                <span class="bill-value">₹<?php echo number_format($bill['fixed_charges'], 2); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">FSA (Fuel Surcharge):</span>
                <span class="bill-value">₹<?php echo number_format($bill['fsa'], 2); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Duty (5%):</span>
                <span class="bill-value">₹<?php echo number_format($bill['duty'], 2); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">GST (18% on base + duty):</span>
                <span class="bill-value">₹<?php echo number_format($bill['gst'], 2); ?></span>
            </div>
            <div class="bill-row">
                <span class="bill-label">Total Tax (Duty + GST):</span>
                <span class="bill-value">₹<?php echo number_format($bill['tax'], 2); ?></span>
            </div>
        </div>

        <div class="bill-section">
            <h3>Payment Summary</h3>
            <div class="bill-row">
                <span class="bill-label">Subtotal:</span>
                <span class="bill-value">₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($bill['arrears'] > 0): ?>
            <div class="bill-row">
                <span class="bill-label">Arrears (Previous Unpaid):</span>
                <span class="bill-value">₹<?php echo number_format($bill['arrears'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($bill['penalty'] > 0): ?>
            <div class="bill-row">
                <span class="bill-label">Penalty (Late Payment):</span>
                <span class="bill-value">₹<?php echo number_format($bill['penalty'], 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="bill-row bill-total">
                <span class="bill-label">Total Amount Due:</span>
                <span class="bill-value">₹<?php echo number_format($bill['total_amount'], 2); ?></span>
            </div>
        </div>

        <?php if ($bill['status'] === 'Unpaid' || $bill['status'] === 'Overdue'): ?>
        <div class="bill-section">
            <div style="text-align: center;">
                <form method="post">
                    <input type="hidden" name="bill_id" value="<?php echo $bill['bill_id']; ?>">
                    <button type="submit" name="pay_bill" class="pay-btn">Pay Now</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="bill-footer">
            <p>This is a computer-generated bill. Please pay by the due date to avoid penalty.</p>
            <p>For any queries, contact customer care.</p>
        </div>
    </div>
</body>
</html>

