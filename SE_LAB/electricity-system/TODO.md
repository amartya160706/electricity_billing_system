# Task TODO List

## Admin Panel Service Requests Modification

### 1. Database Changes
- [ ] Add 'Rejected' status to service_requests.status enum

### 2. Admin Services Page (services.php)
- [ ] Remove Approved Requests section
- [ ] Remove Completed Requests section  
- [ ] Remove Rejected Requests section
- [ ] Remove All Services (Consumers) section
- [ ] Create single unified Service Requests table with all statuses
- [ ] Add color-coded status badges (Orange=Pending, Green=Approved, Red=Rejected)
- [ ] Show Approve/Reject buttons only for Pending requests
- [ ] Update navigation to: Services, Bills, Notifications, Logout

### 3. Home Page (index.php)
- [ ] Remove role selection dropdown (keep admin login only)

### 4. Styles (style.css)
- [ ] Add status-pending class (orange)
- [ ] Add status-approved class (green)
- [ ] Add status-rejected class (red)

### 5. Testing
- [ ] Test reject functionality works without error
- [ ] Verify only admin role option on login
- [ ] Verify single unified table shows all requests

