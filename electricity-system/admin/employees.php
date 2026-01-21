<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../db.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $mobile = trim($_POST['mobile'] ?? '');

    // Validation
    if (empty($mobile)) {
        $message = 'Mobile number is required.';
        $message_type = 'error';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = 'Mobile number must be exactly 10 digits.';
        $message_type = 'error';
    } else {
        // Check for duplicate mobile
        $stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? LIMIT 1");
        $stmt->bind_param('s', $mobile);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = 'Mobile number already exists. Please use a different mobile number.';
            $message_type = 'error';
        } else {
            $stmt->close();

            // Count existing employees with sequential naming to generate next username
            $count_result = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'employee' AND name LIKE 'employee%'");
            $count_data = $count_result->fetch_assoc();
            $next_num = (int)$count_data['cnt'] + 1;
            $employee_name = 'employee' . $next_num;
            $default_password = 'emp123';

            // Insert new employee with minimal required fields
            $address = '';
            $pincode = '';
            $meter_number = null;
            $service_category = null;
            $service_start_date = null;
            $sanctioned_load = 0.0;
            
            $ins = $conn->prepare("INSERT INTO users (name, mobile, address, pincode, meter_number, service_category, service_start_date, sanctioned_load, role, password, first_login, service_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'employee', ?, 1, NULL)");
            $ins->bind_param('sssssssds', $employee_name, $mobile, $address, $pincode, $meter_number, $service_category, $service_start_date, $sanctioned_load, $default_password);
            
            if ($ins->execute()) {
                $message = "Employee added successfully! Username: <strong>$employee_name</strong>, Password: <strong>emp123</strong>. Employee will be prompted to change password on first login.";
                $message_type = 'success';
            } else {
                $message = 'Failed to add employee. Please try again.';
                $message_type = 'error';
            }
            $ins->close();
        }
    }
}

// Get all employees ordered by their employee number (employee1, employee2, etc.)
$employees = $conn->query("SELECT id, name, mobile FROM users WHERE role = 'employee' ORDER BY CAST(SUBSTRING(name FROM 9) AS UNSIGNED) ASC");

$notif_count = get_notification_count_for_user($conn, 'admin');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Employee</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header class="top-bar">
        <div>
            <h1>Admin Panel</h1>
            <p>Add Employee</p>
        </div>
        <nav>
            <a href="services.php">Services</a>
            <a href="bills.php">Bills</a>
            <a href="employees.php" style="font-weight: bold;">Employees</a>
            <a href="../notifications.php" class="notif-link">Notifications <?php if ($notif_count>0): ?><span class="badge"><?php echo $notif_count; ?></span><?php endif; ?></a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <?php if ($message): ?>
        <div class="<?php echo $message_type === 'success' ? 'info' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <section class="section-card">
        <h2>Add New Employee</h2>
        <form method="post">
            <label for="mobile">Mobile Number (unique)</label>
            <input type="text" id="mobile" name="mobile" placeholder="Enter 10-digit mobile number" required maxlength="10" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">

            <p style="margin-top: 10px; color: #666; font-size: 0.9em;">Username will be auto-generated (employee1, employee2, etc.)</p>
            <p style="color: #666; font-size: 0.9em;">Default password: <strong>emp123</strong></p>

            <button type="submit" name="add_employee">Add Employee</button>
        </form>
    </section>

    <section class="section-card">
        <h2>Registered Employees</h2>
        <?php if ($employees && $employees->num_rows > 0): ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Mobile</th>
                </tr>
                <?php $counter = 1; while ($emp = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter; ?></td>
                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td><?php echo htmlspecialchars($emp['mobile']); ?></td>
                    </tr>
                <?php $counter++; endwhile; ?>
            </table>
        <?php else: ?>
            <p>No employees registered yet.</p>
        <?php endif; ?>
    </section>
</div>
</body>
</html>

