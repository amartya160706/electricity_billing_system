<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../db.php';
check_overdue_bills_and_notify($conn);

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $category = $_POST['service_category'];
    if (in_array($category, ['Household', 'Commercial', 'Industrial'])) {
        $stmt = $conn->prepare("INSERT INTO service_requests (service_category, status, created_by_employee, created_at) VALUES (?, 'Pending', ?, NOW())");
        $stmt->bind_param('si', $category, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_GET['approve'])) {
    $request_id = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE service_requests SET status = 'Approved' WHERE request_id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $stmt->close();
}

if (isset($_GET['reject'])) {
    $request_id = (int)$_GET['reject'];
    $stmt = $conn->prepare("UPDATE service_requests SET status = 'Rejected' WHERE request_id = ? AND status = 'Pending'");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $stmt->close();
}

function generate_service_number($conn, $category) {
    $prefix = $category === 'Household' ? '6' : ($category === 'Commercial' ? '7' : '8');
    $stmt = $conn->prepare("SELECT service_number FROM users WHERE role = 'consumer' AND service_category = ? AND service_number LIKE CONCAT(?, '%') ORDER BY service_number DESC LIMIT 1");
    $stmt->bind_param('ss', $category, $prefix);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $last = (int)$row['service_number'];
        $next = $last + 1;
    } else {
        $next = (int)($prefix . '000001');
    }
    $stmt->close();
    return (string)$next;
}

function generate_meter_number($conn) {
    $sql = "SELECT meter_number FROM users WHERE meter_number IS NOT NULL AND meter_number != '' ORDER BY meter_number DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($row = $res->fetch_assoc()) {
        $last = $row['meter_number'];
        $num = (int)substr($last, 1);
        $next = $num + 1;
    } else {
        $next = 1000001;
    }
    return 'M' . $next;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_request'])) {
    $request_id = (int)$_POST['request_id'];
    $name = to_title_case($_POST['name']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $sanctioned_load = (float)$_POST['sanctioned_load'];

    $stmt = $conn->prepare("SELECT service_category, status FROM service_requests WHERE request_id = ? LIMIT 1");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($req = $res->fetch_assoc()) {
        if ($req['status'] === 'Approved') {
            $category = $req['service_category'];
            $service_number = generate_service_number($conn, $category);
            $meter_number = generate_meter_number($conn);
            $start_date = date('Y-m-d');

            $ins = $conn->prepare("INSERT INTO users (name, mobile, address, pincode, service_number, meter_number, service_category, service_start_date, sanctioned_load, role, password, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'consumer', 'user123', 1)");
            $ins->bind_param('ssssssssd', $name, $mobile, $address, $pincode, $service_number, $meter_number, $category, $start_date, $sanctioned_load);
            $ins->execute();
            $ins->close();

            $upd = $conn->prepare("UPDATE service_requests SET status = 'Completed' WHERE request_id = ?");
            $upd->bind_param('i', $request_id);
            $upd->execute();
            $upd->close();
        }
    }
    $stmt->close();
}

$all_requests = $conn->query("SELECT sr.*, u.name AS employee_name FROM service_requests sr LEFT JOIN users u ON sr.created_by_employee = u.id ORDER BY sr.created_at DESC");

$notif_count = get_notification_count_for_user($conn, 'admin');

$consumers = $conn->query("SELECT * FROM users WHERE role = 'consumer' ORDER BY service_number ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Service Management</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Admin Panel</h1>
            <p>Service Management</p>
        </div>
        <nav>
            <a href="bills.php">Bills</a>
            <a href="employees.php">Employees</a>
            <a href="../notifications.php" class="notif-link">Notifications <?php if ($notif_count>0): ?><span class="badge"><?php echo $notif_count; ?></span><?php endif; ?></a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <section class="section-card">
        <h2>Create Service Request</h2>
        <form method="post">
            <label for="service_category">Service Category</label>
            <select name="service_category" id="service_category" required>
                <option value="Household">Household</option>
                <option value="Commercial">Commercial</option>
                <option value="Industrial">Industrial</option>
            </select>
            <button type="submit" name="create_request">Create Request</button>
        </form>
    </section>

    <section class="section-card">
        <h2>Service Requests</h2>
        <table>
            <tr>
                <th>Request ID</th>
                <th>Category</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $all_requests->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['request_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['service_category']); ?></td>
                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = $row['status'];
                        if ($status_text === 'Pending') {
                            $status_class = 'status-pending';
                        } elseif ($status_text === 'Approved') {
                            $status_class = 'status-approved';
                        } elseif ($status_text === 'Rejected') {
                            $status_class = 'status-rejected';
                        } elseif ($status_text === 'Completed') {
                            $status_class = 'status-approved';
                        }
                        ?>
                        <span class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <a href="services.php?approve=<?php echo $row['request_id']; ?>" class="btn-approve">Approve</a>
                            <a href="services.php?reject=<?php echo $row['request_id']; ?>" class="btn-reject" onclick="return confirm('Are you sure you want to reject this request?');">Reject</a>
                        <?php elseif ($row['status'] === 'Approved'): ?>
                            <form method="post" class="inline-form" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                <input type="text" name="name" placeholder="Consumer Name" required style="width: 120px;">
                                <input type="text" name="mobile" placeholder="Mobile" required style="width: 100px;">
                                <input type="text" name="address" placeholder="Address" required style="width: 150px;">
                                <input type="text" name="pincode" placeholder="Pin Code" required style="width: 80px;">
                                <input type="number" step="0.1" name="sanctioned_load" placeholder="Load (kW)" required style="width: 80px;">
                                <button type="submit" name="complete_request">Register</button>
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
        <h2>Registered Consumers</h2>
        <table>
            <tr>
                <th>Service Number</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Category</th>
                <th>Meter Number</th>
                <th>Service Start Date</th>
            </tr>
            <?php while ($c = $consumers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['service_number']); ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo htmlspecialchars($c['mobile']); ?></td>
                    <td><?php echo htmlspecialchars($c['service_category']); ?></td>
                    <td><?php echo htmlspecialchars($c['meter_number']); ?></td>
                    <td><?php echo htmlspecialchars($c['service_start_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </section>
</div>
</body>
</html>

