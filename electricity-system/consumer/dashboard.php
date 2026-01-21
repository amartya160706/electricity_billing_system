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
check_overdue_bills_and_notify($conn);

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'consumer' LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$consumer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$consumer) {
    header('Location: ../logout.php');
    exit;
}

$service_number = $consumer['service_number'];

$pay_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill'])) {
    $bill_id = (int)$_POST['bill_id'];
    $upd = $conn->prepare("UPDATE bills SET status = 'Paid' WHERE bill_id = ? AND service_number = ?");
    $upd->bind_param('is', $bill_id, $service_number);
    $upd->execute();
    if ($upd->affected_rows > 0) {
        $n = $conn->prepare("UPDATE notifications SET is_seen = 1 WHERE bill_id = ? AND service_number = ?");
        $n->bind_param('is', $bill_id, $service_number);
        $n->execute();
        $n->close();
        $pay_message = 'Bill paid successfully.';
    }
    $upd->close();
}

$billsStmt = $conn->prepare("SELECT * FROM bills WHERE service_number = ? ORDER BY billing_to DESC");
$billsStmt->bind_param('s', $service_number);
$billsStmt->execute();
$billsRes = $billsStmt->get_result();

$notifStmt = $conn->prepare("SELECT * FROM notifications WHERE service_number = ? ORDER BY created_at DESC");
$notifStmt->bind_param('s', $service_number);
$notifStmt->execute();
$notifRes = $notifStmt->get_result();

$notif_count = get_notification_count_for_user($conn, 'consumer', $service_number);

$updSeen = $conn->prepare("UPDATE notifications SET is_seen = 1 WHERE service_number = ?");
$updSeen->bind_param('s', $service_number);
$updSeen->execute();
$updSeen->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Consumer Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Consumer Dashboard</h1>
            <p>Service Number: <?php echo htmlspecialchars($consumer['service_number']); ?></p>
        </div>
        <nav>
            <a href="../notifications.php" class="notif-link">Notifications <?php if ($notif_count>0): ?><span class="badge"><?php echo $notif_count; ?></span><?php endif; ?></a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <?php if ($pay_message): ?><div class="info"><?php echo htmlspecialchars($pay_message); ?></div><?php endif; ?>

        <section class="section-card">
            <h2>Consumer Profile</h2>
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
            <h2>Your Bills</h2>
            <table>
                <tr>
                    <th>Bill #</th>
                    <th>Billing Period</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
                <?php while ($b = $billsRes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $b['bill_id']; ?></td>
                        <td><?php echo htmlspecialchars($b['billing_from']); ?> to <?php echo htmlspecialchars($b['billing_to']); ?></td>
                        <td><?php echo htmlspecialchars($b['due_date']); ?></td>
                        <td><span class="status status-<?php echo strtolower($b['status']); ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($b['total_amount']); ?></td>
                        <td>
                            <a href="bill_view.php?bill_id=<?php echo $b['bill_id']; ?>" class="btn-view">View Bill</a>
                            <?php if ($b['status'] === 'Unpaid' || $b['status'] === 'Overdue'): ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="bill_id" value="<?php echo $b['bill_id']; ?>">
                                    <button type="submit" name="pay_bill">Pay Now</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </section>

        <section class="section-card">
            <h2>Notifications</h2>
            <ul class="notif-list">
                <?php while ($n = $notifRes->fetch_assoc()): ?>
                    <li class="<?php echo $n['is_seen'] ? 'seen' : 'unseen'; ?>">
                    [<?php echo htmlspecialchars($n['type']); ?>]
                        <?php echo htmlspecialchars($n['message']); ?>
                        <span class="time"><?php echo htmlspecialchars($n['created_at']); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </section>
</div>
</body>
</html>

