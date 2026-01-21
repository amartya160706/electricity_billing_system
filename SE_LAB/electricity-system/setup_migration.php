<?php
/**
 * Database Migration Script
 * Run this file to add username and first_login columns to users table
 */

require_once __DIR__ . '/db.php';

echo "Starting database migration...\n\n";

$errors = [];

// Check and add username column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
if ($result->num_rows === 0) {
    echo "Adding 'username' column...\n";
    if (!$conn->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE")) {
        $errors[] = "Failed to add username: " . $conn->error;
    } else {
        echo "✓ Added username column\n";
    }
} else {
    echo "✓ username column already exists\n";
}

// Check and add first_login column
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'first_login'");
if ($result->num_rows === 0) {
    echo "Adding 'first_login' column...\n";
    if (!$conn->query("ALTER TABLE users ADD COLUMN first_login BOOLEAN DEFAULT 1")) {
        $errors[] = "Failed to add first_login: " . $conn->error;
    } else {
        echo "✓ Added first_login column\n";
    }
} else {
    echo "✓ first_login column already exists\n";
}

// Update existing users without username
echo "\nUpdating existing users with usernames...\n";

$conn->query("UPDATE users SET username = service_number WHERE username IS NULL AND role = 'consumer'");
echo "✓ Updated consumers with service_number as username\n";

$conn->query("UPDATE users SET username = LOWER(REPLACE(name, ' ', '_')) WHERE username IS NULL");
echo "✓ Updated others with name-based username\n";

// Add sample admin user if not exists
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result->num_rows === 0) {
    echo "\nAdding sample admin user...\n";
    $hashed = password_hash('user123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, username, mobile, role, password, first_login) VALUES ('Admin', 'admin', '9999999999', 'admin', '$hashed', 0)");
    echo "✓ Added admin user (username: admin, password: user123)\n";
} else {
    echo "✓ Admin user already exists\n";
}

// Add sample employee user if not exists
$result = $conn->query("SELECT * FROM users WHERE username = 'employee'");
if ($result->num_rows === 0) {
    echo "\nAdding sample employee user...\n";
    $hashed = password_hash('user123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, username, mobile, role, password, first_login) VALUES ('Employee', 'employee', '8888888888', 'employee', '$hashed', 0)");
    echo "✓ Added employee user (username: employee, password: user123)\n";
} else {
    echo "✓ Employee user already exists\n";
}

echo "\n";
if (empty($errors)) {
    echo "=======================================\n";
    echo "Migration completed successfully!\n";
    echo "=======================================\n\n";
    echo "Login credentials:\n";
    echo "- Admin: username=admin, password=user123\n";
    echo "- Employee: username=employee, password=user123\n";
} else {
    echo "Errors occurred:\n";
    foreach ($errors as $e) {
        echo "- $e\n";
    }
}

