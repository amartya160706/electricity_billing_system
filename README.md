# Electricity Service Billing System

A complete PHP-based web application for managing electricity billing, service connections, and customer management.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Features](#features)
3. [User Roles](#user-roles)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [Installation Guide](#installation-guide)
7. [Usage Guide](#usage-guide)
8. [Bill Calculation Details](#bill-calculation-details)
9. [Technical Implementation](#technical-implementation)

---

## Project Overview

This is a full-stack electricity billing management system that handles:
- New service connections (New Meter Connections)
- Electricity bill generation
- Bill payment processing
- Overdue bill notifications
- Multi-role authentication system

---

## Features

### Admin Features
- Create and manage service requests for new connections
- Approve/Reject service requests
- Complete service registration (meter assignment, consumer account creation)
- View and manage all bills
- Update bill payment status
- Manage employees
- View notifications for overdue bills

### Employee Features
- Generate electricity bills for consumers
- View all bills
- View detailed bill information
- Handle bill-related notifications

### Consumer Features
- View personal profile and service details
- View all bills and payment history
- Pay bills online
- View notifications
- Detailed bill breakdown with print capability

---

## User Roles

### 1. Admin
- **Username:** admin 
- **Password:** admin123
- **Capabilities:** Full system management

### 2. Employee
- **Username:** employee1, employee2, etc. (auto-generated)
- **Default Password:** emp123
- **Capabilities:** Bill generation and management

### 3. Consumer
- **Username:** Service Number (e.g., 6000001)
- **Default Password:** user123
- **Capabilities:** View and pay bills

---

## Project Structure

```
electricity-system/
├── index.php                 # Main login page
├── auth.php                  # Authentication handler
├── db.php                    # Database connection and functions
├── change_password.php       # First-time password change
├── logout.php                # Logout handler
├── notifications.php         # Global notifications page
├── script.js                 # JavaScript utilities
├── style.css                 # Main stylesheet
│
├── admin/
│   ├── services.php          # Service request management
│   ├── bills.php             # Bill overview and management
│   └── employees.php         # Employee management
│
├── consumer/
│   ├── dashboard.php         # Consumer dashboard
│   └── bill_view.php         # Consumer bill view with payment
│
└── employee/
    ├── requests.php          # Redirect to bills
    ├── bills.php             # Bill generation
    └── bill_view.php         # Detailed bill view (printable)
```

---

## Database Schema

### Users Table
Stores all users (admin, employee, consumer)

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key, auto-increment |
| name | VARCHAR(100) | User's name |
| mobile | VARCHAR(10) | 10-digit mobile number |
| address | VARCHAR(255) | Full address |
| pincode | VARCHAR(6) | PIN code |
| service_number | VARCHAR(20) | Unique service/meter number |
| meter_number | VARCHAR(20) | Physical meter identifier |
| service_category | ENUM | Household, Commercial, Industrial |
| service_start_date | DATE | Connection start date |
| sanctioned_load | DECIMAL | Approved load in kW |
| role | ENUM | admin, employee, consumer |
| password | VARCHAR(255) | Hashed password |
| first_login | TINYINT | 1 if password change required |

### Bills Table
Stores all electricity bills

| Column | Type | Description |
|--------|------|-------------|
| bill_id | INT | Primary key, auto-increment |
| service_number | VARCHAR(20) | Consumer's service number |
| billing_from | DATE | Billing period start |
| billing_to | DATE | Billing period end |
| previous_reading | INT | Previous meter reading |
| current_reading | INT | Current meter reading |
| units_consumed | INT | Total units used |
| energy_charges | DECIMAL | Cost based on units |
| fixed_charges | DECIMAL | Fixed monthly charge |
| fsa | DECIMAL | Fuel Surcharge Adjustment |
| duty | DECIMAL | 5% duty on energy charges |
| gst | DECIMAL | 18% GST on energy + duty |
| tax | DECIMAL | Total tax (duty + gst) |
| arrears | DECIMAL | Previous unpaid amount |
| penalty | DECIMAL | Late payment penalty |
| total_amount | DECIMAL | Final amount due |
| due_date | DATE | Payment due date |
| status | ENUM | Paid, Unpaid, Overdue |
| created_at | DATETIME | Bill generation timestamp |

### Service Requests Table
Tracks new connection requests

| Column | Type | Description |
|--------|------|-------------|
| request_id | INT | Primary key |
| service_category | ENUM | Type of connection |
| status | ENUM | Pending, Approved, Rejected, Completed |
| created_by_employee | INT | Employee ID who created |
| created_at | DATETIME | Request creation time |

### Notifications Table
Stores system notifications

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| service_number | VARCHAR(20) | Target consumer |
| bill_id | INT | Related bill (if any) |
| message | VARCHAR(255) | Notification text |
| type | ENUM | bill_generated, overdue |
| is_seen | TINYINT | 1 if read |
| created_at | DATETIME | Creation time |

---

## Installation Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server

### Step 1: Database Setup

```sql
CREATE DATABASE electricity_service_billing;
USE electricity_service_billing;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    mobile VARCHAR(10),
    address VARCHAR(255),
    pincode VARCHAR(6),
    service_number VARCHAR(20),
    meter_number VARCHAR(20),
    service_category ENUM('Household', 'Commercial', 'Industrial'),
    service_start_date DATE,
    sanctioned_load DECIMAL(10,2),
    role ENUM('admin', 'employee', 'consumer'),
    password VARCHAR(255),
    first_login TINYINT DEFAULT 1
);

-- Create bills table
CREATE TABLE bills (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    service_number VARCHAR(20),
    billing_from DATE,
    billing_to DATE,
    previous_reading INT,
    current_reading INT,
    units_consumed INT,
    energy_charges DECIMAL(10,2),
    fixed_charges DECIMAL(10,2),
    fsa DECIMAL(10,2),
    duty DECIMAL(10,2),
    gst DECIMAL(10,2),
    tax DECIMAL(10,2),
    arrears DECIMAL(10,2),
    penalty DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    due_date DATE,
    status ENUM('Paid', 'Unpaid', 'Overdue'),
    created_at DATETIME
);

-- Create service_requests table
CREATE TABLE service_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    service_category ENUM('Household', 'Commercial', 'Industrial'),
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed'),
    created_by_employee INT,
    created_at DATETIME
);

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_number VARCHAR(20),
    bill_id INT,
    message VARCHAR(255),
    type ENUM('bill_generated', 'overdue'),
    is_seen TINYINT DEFAULT 0,
    created_at DATETIME
);

-- Create admin user
INSERT INTO users (name, mobile, role, password, first_login, service_number)
VALUES ('admin', '0000000000', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'ADMIN001');
```

### Step 2: Configure Database Connection

Edit `db.php` with your database credentials:

```php
$DB_HOST = 'localhost';
$DB_USER = 'your_username';
$DB_PASS = 'your_password';
$DB_NAME = 'electricity_service_billing';
$DB_PORT = 3306;
```

### Step 3: Deploy Files

Copy all files to your web server's document root:
```
/var/www/html/electricity-system/
```

### Step 4: Set Permissions

```bash
chmod 755 -R /var/www/html/electricity-system/
```

---

## Usage Guide

### Login Process

1. Open `index.php` in browser
2. Select user role (Admin/Employee/Consumer)
3. Enter credentials
4. First-time users must change password

### Admin Workflow

#### Creating a New Service Request

1. Login as Admin
2. Navigate to "Admin Services"
3. Select service category (Household/Commercial/Industrial)
4. Click "Create Request"

#### Approving a Service Request

1. Find request in table
2. Click "Approve" or "Reject"
3. If approved, fill consumer details:
   - Consumer Name
   - Mobile Number
   - Full Address
   - PIN Code
   - Sanctioned Load (kW)
4. Click "Register" to create consumer account

#### Managing Bills

1. Navigate to "Bills" section
2. View latest bills per consumer
3. Click service number to see consumer profile
4. Update bill status (Paid/Unpaid/Overdue)

### Employee Workflow

#### Generating a Bill

1. Login as Employee
2. Navigate to "Bills"
3. Select service number from dropdown
4. Consumer details auto-populate
5. Enter billing period (From/To dates)
6. Enter meter readings (Previous/Current)
7. Select due date
8. Click "Generate Bill"

### Consumer Workflow

#### Viewing and Paying Bills

1. Login as Consumer
2. View profile and bill history
3. Click "View Bill" for details
4. Click "Pay Now" to pay unpaid bills
5. View notifications for bill updates

---

## Bill Calculation Details

### Energy Charges (per unit rates)

#### Household Category
| Units Consumed | Rate per Unit |
|----------------|---------------|
| 0-50 | ₹1.50 |
| 51-100 | ₹2.00 |
| 101+ | ₹2.50 |

**Calculation Example (120 units):**
- First 50 units: 50 × 1.50 = ₹75.00
- Next 50 units: 50 × 2.00 = ₹100.00
- Remaining 20 units: 20 × 2.50 = ₹50.00
- **Total Energy Charges: ₹225.00**

#### Commercial Category
| Units Consumed | Rate per Unit |
|----------------|---------------|
| 0-100 | ₹6.00 |
| 101-200 | ₹8.00 |
| 201+ | ₹10.00 |

#### Industrial Category
- All units: ₹12.00 per unit

### Fixed Charges (monthly)

| Category | Fixed Charge |
|----------|-------------|
| Household | ₹50.00 |
| Commercial | ₹100.00 |
| Industrial | ₹150.00 |

### Additional Charges

| Charge | Rate |
|--------|------|
| FSA (Fuel Surcharge) | ₹1.00 per unit |
| Duty | 5% of energy charges |
| GST | 18% on (energy charges + duty) |

### Total Bill Formula

```
Total = Energy Charges + Fixed Charges + FSA + Duty + GST + Arrears + Penalty

Where:
- Duty = Energy Charges × 0.05
- GST = (Energy Charges + Duty) × 0.18
```

### Arrears and Penalty

- **Arrears:** Previous unpaid bill amount
- **Penalty:** ₹100.00 for late payment

---

## Technical Implementation

### Security Features

1. **Password Hashing:** Uses PHP `password_hash()` and `password_verify()`
2. **SQL Injection Prevention:** Prepared statements with parameter binding
3. **XSS Protection:** `htmlspecialchars()` on all output
4. **Session Management:** Secure session handling with role-based access
5. **Input Validation:** Server-side validation for all user inputs

### Session Variables

| Variable | Description |
|----------|-------------|
| user_id | Logged-in user's ID |
| role | User role (admin/employee/consumer) |
| service_number | User's service number (for consumers) |
| first_login | 1 if password change required |

### Automatic Features

1. **Overdue Bill Check:** Runs on every page load, checks bills past due date
2. **Auto-Notifications:** Generates notifications for new bills and overdue status
3. **Status Updates:** Auto-updates bills from Unpaid to Overdue
4. **Default Due Date:** Auto-sets due date to 15 days from current date

### Key Functions (db.php)

```php
// Convert string to title case
to_title_case(string $str): string

// Check and update overdue bills
check_overdue_bills_and_notify(mysqli $conn): void

// Get notification count
get_notification_count_for_user(
    mysqli $conn,
    string $role,
    ?string $service_number = null
): int
```

---

## Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Employee | employee1 | emp123 |
| Consumer | (Service Number) | user123 |

---
