-- Migration: Add username column to users table
-- Run this SQL to add the username column if it doesn't exist

-- Add username column (if not exists)
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE;

-- Add first_login column (if not exists) 
ALTER TABLE users ADD COLUMN first_login BOOLEAN DEFAULT 1;

-- Update existing users without username to have a username based on their name
-- For consumers without username, use service_number as username
UPDATE users SET username = service_number WHERE username IS NULL AND role = 'consumer';

-- For employees/admin without username, use name as username (lowercase, no spaces)
UPDATE users SET username = LOWER(REPLACE(name, ' ', '_')) WHERE username IS NULL;

-- Update existing plain text passwords to be hashed (optional - for migration)
-- This should be done when user logs in or changes password

-- Add sample admin user if not exists
INSERT INTO users (name, username, mobile, role, password, first_login)
SELECT 'Admin', 'admin', '9999999999', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Add sample employee user if not exists
INSERT INTO users (name, username, mobile, role, password, first_login)
SELECT 'Employee', 'employee', '8888888888', 'employee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'employee');

-- Note: The password hash above is for 'user123'

