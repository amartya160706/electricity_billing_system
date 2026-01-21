<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../db.php';
check_overdue_bills_and_notify($conn);

$notif_count = get_notification_count_for_user($conn, 'admin');

$service_number = isset($_GET['service_number']) ? trim($_GET['service_number']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bill_id = (int)$_POST['bill_id'];
    $new_status = $_POST['status'];
    if (in_array($new_status, ['Paid', 'Unpaid', 'Overdue'])) {
        $stmt = $conn->prepare("UPDATE bills SET status = ? WHERE bill_id = ?");
        $stmt->bind_param('si', $new_status, $bill_id);
        $stmt->execute();
        $stmt->close();
    }
}

if ($service_number !== '') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE service_number = ? AND role = 'consumer' LIMIT 1");
    $stmt->bind_param('s', $service_number);
    $stmt->execute();
    $consumer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $billsStmt = $conn->prepare("SELECT * FROM bills WHERE service_number = ? ORDER BY billing_to DESC");
    $billsStmt->bind_param('s', $service_number);
    $billsStmt->execute();
    $billsRes = $billsStmt->get_result();
} else {
    $sql = "SELECT b.* FROM bills b INNER JOIN (SELECT service_number, MAX(billing_to) AS max_to FROM bills GROUP BY service_number) x ON b.service_number = x.service_number AND b.billing_to = x.max_to ORDER BY b.service_number";
    $latest = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Bills</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Admin Panel</h1>
            <p>Bills Overview</p>
        </div>
        <nav>
            <a href="services.php">Admin Services</a>
            <a href="bills.php">Bills</a>
            <a href="employees.php">Employees</a>
            <a href="../notifications.php" class="notif-link">Notifications <?php if ($notif_count>0): ?><span class="badge"><?php echo $notif_count; ?></span><?php endif; ?></a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <?php if ($service_number !== '' && $consumer): ?>
        <section class="section-card">
            <h2>Consumer Profile - <?php echo htmlspecialchars($consumer['service_number']); ?></h2>
            <div class="profile-grid">
                <div>
                    <h3>Personal Details</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($consumer['name']); ?></p>
                    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($consumer['mobile']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($consumer['address']); ?></p>
                    <p><strong>Pin Code:</strong> <?php echo htmlspecialchars($consumer['pincode']); ?></p>
                </div>
                <div>
                    <h3>Service Details</h3>
                    <p><strong>Service Number:</strong> <?php echo htmlspecialchars($consumer['service_number']); ?></p>
                    <p><strong>Meter Number:</strong> <?php echo htmlspecialchars($consumer['meter_number']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($consumer['service_category']); ?></p>
                    <p><strong>Service Start Date:</strong> <?php echo htmlspecialchars($consumer['service_start_date']); ?></p>
                    <p><strong>Sanctioned Load (kW):</strong> <?php echo htmlspecialchars($consumer['sanctioned_load']); ?></p>
                    <p><strong>Service Status:</strong> Active</p>
                </div>
            </div>
        </section>

        <section class="section-card">
            <h2>Bill History</h2>
            <table>
                <tr>
                    <th>Bill #</th>
                    <th>Billing Period</th>
                    <th>Due Date</th>
                    <th>Units</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
                <?php while ($b = $billsRes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $b['bill_id']; ?></td>
                        <td><?php echo htmlspecialchars($b['billing_from']); ?> to <?php echo htmlspecialchars($b['billing_to']); ?></td>
                        <td><?php echo htmlspecialchars($b['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($b['units_consumed']); ?></td>
                        <td><?php echo htmlspecialchars($b['total_amount']); ?></td>
                        <td><span class="status status-<?php echo strtolower($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="bill_id" value="<?php echo $b['bill_id']; ?>">
                                <select name="status" style="width: 100px;">
                                    <option value="Paid" <?php echo $b['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Unpaid" <?php echo $b['status'] === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                    <option value="Overdue" <?php echo $b['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                                <button type="submit" name="update_status">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
    <?php else: ?>
        <section class="section-card">
            <h2>Latest Bills per Service</h2>
            <table>
                <tr>
                    <th>Service Number</th>
                    <th>Bill #</th>
                    <th>Billing Period</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
                <?php while ($b = $latest->fetch_assoc()): ?>
                    <tr>
                        <td><a href="bills.php?service_number=<?php echo urlencode($b['service_number']); ?>"><?php echo htmlspecialchars($b['service_number']); ?></a></td>
                        <td><?php echo $b['bill_id']; ?></td>
                        <td><?php echo htmlspecialchars($b['billing_from']); ?> to <?php echo htmlspecialchars($b['billing_to']); ?></td>
                        <td><?php echo htmlspecialchars($b['due_date']); ?></td>
                        <td><span class="status status-<?php echo strtolower($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($b['total_amount']); ?></td>
                        <td><a href="../employee/bill_view.php?bill_id=<?php echo $b['bill_id']; ?>" class="btn-view" target="_blank">View Bill</a></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>
    <?php endif; ?>
</div>
</body>
</html>

