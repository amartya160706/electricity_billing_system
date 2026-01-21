<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit;
}
require_once '../db.php';
check_overdue_bills_and_notify($conn);

$services = $conn->query("SELECT * FROM users WHERE role = 'consumer' ORDER BY service_number ASC");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_bill'])) {
    $service_number = $_POST['service_number'];
    $billing_from = $_POST['billing_from'];
    $billing_to = $_POST['billing_to'];
    $prev_reading = (int)$_POST['previous_reading'];
    $curr_reading = (int)$_POST['current_reading'];
    $due_date = $_POST['due_date'];

    if ($curr_reading < $prev_reading) {
        $message = 'Current reading cannot be less than previous reading.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE service_number = ? AND role = 'consumer' LIMIT 1");
        $stmt->bind_param('s', $service_number);
        $stmt->execute();
        $consumer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($consumer) {
            $category = $consumer['service_category'];
            $units = $curr_reading - $prev_reading;

            if ($category === 'Household') {
                if ($units <= 50) {
                    $energy_charges = $units * 1.5;
                } elseif ($units <= 100) {
                    $energy_charges = 50 * 1.5 + ($units - 50) * 2;
                } else {
                    $energy_charges = 50 * 1.5 + 50 * 2 + ($units - 100) * 2.5;
                }
            } elseif ($category === 'Commercial') {
                if ($units <= 100) {
                    $energy_charges = $units * 6;
                } elseif ($units <= 200) {
                    $energy_charges = 100 * 6 + ($units - 100) * 8;
                } else {
                    $energy_charges = 100 * 6 + 100 * 8 + ($units - 200) * 10;
                }
            } else {
                $energy_charges = $units * 12;
            }

            if ($category === 'Household') {
                $fixed_charges = 50;
            } elseif ($category === 'Commercial') {
                $fixed_charges = 100;
            } else {
                $fixed_charges = 150;
            }

            $fsa = $units * 1;

            $duty = $energy_charges * 0.05;
            $gst = ($energy_charges + $duty) * 0.18;
            $tax = $gst + $duty;

            $prevStmt = $conn->prepare("SELECT * FROM bills WHERE service_number = ? ORDER BY billing_to DESC LIMIT 1");
            $prevStmt->bind_param('s', $service_number);
            $prevStmt->execute();
            $prevBill = $prevStmt->get_result()->fetch_assoc();
            $prevStmt->close();

            $arrears = 0;
            $penalty = 0;
            if ($prevBill && $prevBill['status'] !== 'Paid') {
                $arrears = $prevBill['total_amount'];
                $penalty = 100;
            }

            $total = $energy_charges + $fixed_charges + $fsa + $duty + $gst + $arrears + $penalty;

            $ins = $conn->prepare("INSERT INTO bills (service_number, billing_from, billing_to, previous_reading, current_reading, units_consumed, energy_charges, fixed_charges, fsa, duty, gst, tax, arrears, penalty, total_amount, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid', NOW())");
            $types = 'sssiiiddddddddds';
            $ins->bind_param($types, $service_number, $billing_from, $billing_to, $prev_reading, $curr_reading, $units, $energy_charges, $fixed_charges, $fsa, $duty, $gst, $tax, $arrears, $penalty, $total, $due_date);
            $ins->execute();
            $bill_id = $ins->insert_id;
            $ins->close();

            $msg = 'Your electricity bill for this month has been generated. Please check and pay before the due date.';
            $n = $conn->prepare("INSERT INTO notifications (service_number, bill_id, message, type, is_seen, created_at) VALUES (?, ?, ?, 'bill_generated', 0, NOW())");
            $n->bind_param('sis', $service_number, $bill_id, $msg);
            $n->execute();
            $n->close();

            header('Location: bills.php?success=1');
            exit;
        } else {
            $message = 'Invalid service number selected.';
        }
    }
}

if (isset($_GET['success'])) {
    $message = 'Bill generated successfully.';
}

$bills = $conn->query("SELECT * FROM bills ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee - Bills</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Employee Panel</h1>
            <p>Bill Generation</p>
        </div>
        <nav>
            <a href="../notifications.php">Notifications</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <section class="section-card">
        <h2>Generate Bill</h2>
        <?php if ($message): ?><div class="info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form method="post" id="bill-form">
            <label for="service_number_select">Service Number</label>
            <select name="service_number" id="service_number_select" size="5" required>
                <?php while ($s = $services->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($s['service_number']); ?>"
                            data-name="<?php echo htmlspecialchars($s['name']); ?>"
                            data-address="<?php echo htmlspecialchars($s['address']); ?>"
                            data-meter="<?php echo htmlspecialchars($s['meter_number']); ?>"
                            data-category="<?php echo htmlspecialchars($s['service_category']); ?>"
                            data-load="<?php echo htmlspecialchars($s['sanctioned_load']); ?>"
                            data-start="<?php echo htmlspecialchars($s['service_start_date']); ?>">
                        <?php echo htmlspecialchars($s['service_number']); ?> - <?php echo htmlspecialchars($s['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <div class="grid-2">
                <div>
                    <label>Consumer Name</label>
                    <input type="text" id="c_name" readonly>
                    <label>Address</label>
                    <input type="text" id="c_address" readonly>
                    <label>Meter Number</label>
                    <input type="text" id="c_meter" readonly>
                </div>
                <div>
                    <label>Service Category</label>
                    <input type="text" id="c_category" readonly>
                    <label>Sanctioned Load (kW)</label>
                    <input type="text" id="c_load" readonly>
                    <label>Service Start Date</label>
                    <input type="text" id="c_start" readonly>
                </div>
            </div>

            <label>Billing Period</label>
            <div class="grid-2">
                <input type="date" name="billing_from" required>
                <input type="date" name="billing_to" required>
            </div>

            <label>Meter Readings</label>
            <div class="grid-2">
                <input type="number" name="previous_reading" placeholder="Previous Reading" required>
                <input type="number" name="current_reading" placeholder="Current Reading" required>
            </div>

            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" required>

            <button type="submit" name="generate_bill">Generate Bill</button>
        </form>
    </section>

    <section class="section-card">
        <h2>All Bills (Read-only)</h2>
        <table>
            <tr>
                <th>Bill #</th>
                <th>Service Number</th>
                <th>Billing Period</th>
                <th>Units</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
            <?php while ($b = $bills->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $b['bill_id']; ?></td>
                    <td><?php echo htmlspecialchars($b['service_number']); ?></td>
                    <td><?php echo htmlspecialchars($b['billing_from']); ?> to <?php echo htmlspecialchars($b['billing_to']); ?></td>
                    <td><?php echo htmlspecialchars($b['units_consumed']); ?></td>
                    <td><span class="status status-<?php echo strtolower($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($b['total_amount']); ?></td>
                    <td><a href="bill_view.php?bill_id=<?php echo $b['bill_id']; ?>" class="btn-view" target="_blank">View Bill</a></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </section>
</div>
<script src="../script.js"></script>
</body>
</html>

