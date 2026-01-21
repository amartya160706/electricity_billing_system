# Authentication System Overhaul - COMPLETED

## Summary of Changes

### 1. auth.php - Updated for username-based login
- ✅ Login now uses `username` field instead of `identifier` (service_number/mobile)
- ✅ Password verification supports both hashed and plain text (for migration)
- ✅ Redirects consumers with first_login=1 to change_password.php
- ✅ Stores first_login in session

### 2. index.php - Updated login form
- ✅ Changed label from "Username / Mobile / Service Number" to "Username"
- ✅ Changed input name from "identifier" to "username"

### 3. change_password.php - NEW FILE
- ✅ Created dedicated first-login password change page
- ✅ Validates password minimum 6 characters
- ✅ Validates passwords match
- ✅ Hashes new password with password_hash()
- ✅ Sets first_login=0 after change
- ✅ Redirects to consumer dashboard

### 4. consumer/dashboard.php - Updated access control
- ✅ Added first_login check at top
- ✅ Redirects to change_password.php if first_login=1
- ✅ Removed inline password change form (moved to dedicated page)

### 5. consumer/bill_view.php - Updated access control
- ✅ Added first_login check
- ✅ Redirects to change_password.php if first_login=1

### 6. employee/requests.php - Updated consumer creation
- ✅ Added username field to form
- ✅ Password is now hashed with password_hash()
- ✅ Sets first_login=1 for new consumers

### 7. migration_add_username.sql - Database migration
- ✅ SQL to add username column (UNIQUE)
- ✅ SQL to add first_login column
- ✅ Sample admin and employee users with username

---

## Database Changes Required

Run this SQL in phpMyAdmin or MySQL:

```sql
ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE;
ALTER TABLE users ADD COLUMN first_login BOOLEAN DEFAULT 1;
UPDATE users SET username = service_number WHERE username IS NULL AND role = 'consumer';
UPDATE users SET username = LOWER(REPLACE(name, ' ', '_')) WHERE username IS NULL;
```

---

## Login Credentials

- **Admin**: username=`admin`, password=`user123`
- **Employee**: username=`employee`, password=`user123`
- **New Consumer**: username=`<as entered>`, password=`user123`

---

## Login Flow

1. User enters username + password
2. System validates credentials
3. If consumer with first_login=1 → redirect to change_password.php
4. User must change password (min 6 chars)
5. Password hashed and stored, first_login set to 0
6. Redirect to consumer dashboard

