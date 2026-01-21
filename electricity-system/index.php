<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/services.php");
    } elseif ($_SESSION['role'] === 'employee') {
        header("Location: employee/requests.php");
    } else {
        header("Location: consumer/dashboard.php");
    }
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Electricity Billing Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1>ELECTRICITY BILLING</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="auth.php">
                <label>Select Role</label>
                <div class="role-selection">
                    <label class="role-option">
                        <input type="radio" name="role" value="admin" checked>
                        <span>Admin</span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="employee">
                        <span>Employee</span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="consumer">
                        <span>Consumer</span>
                    </label>
                </div>

                <label for="username">ID / Service Number / Mobile</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="login-btn">Login</button>
            </form>

            <div class="login-footer">
                <p>Electricity Service & Billing System</p>
            </div>
        </div>
    </div>
</body>
</html>

