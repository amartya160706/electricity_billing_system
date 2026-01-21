<?php
session_start();
if (!isset($_SESSION['role'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';
check_overdue_bills_and_notify($conn);

$role = $_SESSION['role'];

if ($role === 'consumer') {
    $service_number = $_SESSION['service_number'];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE service_number = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $service_number);
    $stmt->execute();
    $res = $stmt->get_result();

    $upd = $conn->prepare("UPDATE notifications SET is_seen = 1 WHERE service_number = ?");
    $upd->bind_param('s', $service_number);
    $upd->execute();
    $upd->close();
} elseif ($role === 'admin') {

    $res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
} else { 
    
    $res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Notifications & Alerts</h1>
        </div>
        <nav>
            <?php if ($role === 'admin'): ?>
                <a href="admin/services.php">Admin Services</a>
            <?php elseif ($role === 'employee'): ?>
                <a href="employee/requests.php">Employee Dashboard</a>
            <?php else: ?>
                <a href="consumer/dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <section class="section-card">
        <h2>All Notifications</h2>
        <ul class="notif-list">
            <?php while ($n = $res->fetch_assoc()): ?>
                <li class="<?php echo $n['is_seen'] ? 'seen' : 'unseen'; ?>">
                    [<?php echo htmlspecialchars($n['type']); ?>]
                    (Service: <?php echo htmlspecialchars($n['service_number']); ?>)
                    <?php echo htmlspecialchars($n['message']); ?>
                    <span class="time"><?php echo htmlspecialchars($n['created_at']); ?></span>
                </li>
            <?php endwhile; ?>
        </ul>
    </section>
</div>
</body>
</html>
