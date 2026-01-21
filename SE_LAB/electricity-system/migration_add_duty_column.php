<?php
/**
 * Database Migration Script
 * Run this file to add duty column to bills table
 */

require_once __DIR__ . '/db.php';

echo "Starting database migration...\n\n";

// Check and add duty column
$result = $conn->query("SHOW COLUMNS FROM bills LIKE 'duty'");
if ($result->num_rows === 0) {
    echo "Adding 'duty' column to bills table...\n";
    if (!$conn->query("ALTER TABLE bills ADD COLUMN duty DECIMAL(10,2) DEFAULT 0.00")) {
        echo "Failed to add duty column: " . $conn->error . "\n";
    } else {
        echo "✓ Added duty column\n";
    }
} else {
    echo "✓ duty column already exists\n";
}

// Check and add gst column
$result = $conn->query("SHOW COLUMNS FROM bills LIKE 'gst'");
if ($result->num_rows === 0) {
    echo "Adding 'gst' column to bills table...\n";
    if (!$conn->query("ALTER TABLE bills ADD COLUMN gst DECIMAL(10,2) DEFAULT 0.00")) {
        echo "Failed to add gst column: " . $conn->error . "\n";
    } else {
        echo "✓ Added gst column\n";
    }
} else {
    echo "✓ gst column already exists\n";
}

echo "\nMigration completed!\n";

