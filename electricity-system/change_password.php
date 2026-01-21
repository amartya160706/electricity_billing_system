<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'consumer' && $_SESSION['role'] !== 'employee')) {
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['first_login']) || $_SESSION['first_login'] != 1) {
    if ($_SESSION['role'] === 'consumer') {
        header('Location: consumer/dashboard.php');
    } else {
        header('Location: employee/requests.php');
    }
    exit;
}

require_once __DIR__ . '/db.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password)) {
        $error = 'Password cannot be empty.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, first_login = 0 WHERE id = ?");
        $stmt->bind_param('si', $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['first_login'] = 0;
            $success = 'Password changed successfully. Redirecting to dashboard...';
            
            $redirect_url = ($_SESSION['role'] === 'consumer') ? 'consumer/dashboard.php' : 'employee/requests.php';
            header("Refresh: 2; URL=$redirect_url");
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password - First Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
        }
        .password-card {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
        }
        .password-card h2 {
            margin-top: 0;
            color: #333;
        }
        .password-card p {
            color: #666;
            margin-bottom: 20px;
        }
        .error {
            color: #c62828;
            background: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            color: #2e7d32;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="password-card">
        <h2>🔐 Change Password</h2>
        <p>This is your first login. Please change your password to continue.</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php else: ?>
            <form method="post">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required minlength="6">
                
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                
                <button type="submit" name="change_password">Change Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
