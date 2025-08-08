# Role-Based Access Control (RBAC) Implementation

## Overview

This Laravel BI platform now includes a comprehensive Role-Based Access Control (RBAC) system with four distinct user roles, each with specific permissions and workflows.

## User Roles

### 1. Business Owner
- **Full access** to all features and data
- Can generate and manage dashboard ID and invitation codes
- Can promote Staff to Administrator
- Can remove users from the business
- Automatically assigned when creating a new business

### 2. Administrator
- **Promoted from Staff** by Business Owner or another Administrator
- Can manage data, import/delete metrics
- Can manage team members (promote Staff, remove users)
- Cannot delete Business Owner
- Has all Staff permissions plus management capabilities

### 3. Staff
- Can **create and view** data in dashboard metrics
- Has access to dashboard feeds and profile
- Requires **Dashboard ID + Invitation Code** to join business
- Can be promoted to Administrator

### 4. Business Investigator
- **View-only access** to summary statistics
- Can access dashboard main and metrics (summary view only)
- Requires only **Dashboard ID** to join business
- Cannot access detailed data, feeds, or other features

## Setup Workflows

### Business Owner Registration
1. Register new account
2. Select "Business Owner" role in wizard
3. Complete business information
4. Set goals and targets
5. System auto-generates:
   - Public Dashboard ID (BIZ-XXXXXXXX)
   - Staff Invitation Code (12-character code)

### Staff Registration
1. Register new account
2. Select "Staff" role in wizard
3. Enter Dashboard ID + Invitation Code in validation modal
4. System validates and adds user to business

### Business Investigator Registration
1. Register new account
2. Select "Business Investigator" role in wizard
3. Enter Dashboard ID in validation modal
4. System validates and grants view-only access

### Administrator Assignment
- Staff can be promoted to Administrator by:
  - Business Owner
  - Existing Administrator
- Promotion happens via dashboard users management page

## Access Codes

### Dashboard ID (Public)
- Format: `BIZ-XXXXXXXX`
- Shared with both Staff and Business Investigators
- Used to identify the business for access requests

### Staff Invitation Code (Private)
- 12-character alphanumeric code
- Only shared with Staff members
- Can be refreshed by Business Owner if compromised
- Required along with Dashboard ID for Staff access

## Database Structure

### New Tables
- `business_user` - Pivot table linking users to businesses with roles
- Added fields to `businesses` table:
  - `public_id` - Public dashboard identifier
  - `invitation_code` - Staff invitation code
  - `invitation_code_generated_at` - When code was generated

### Updated Roles
- `business-owner` - Full access
- `administrator` - Management access (promoted from staff)
- `staff` - Limited data access
- `business-investigator` - View-only access

## Technical Implementation

### Middleware
- `CheckRole` middleware with multiple role support
- Usage: `Route::middleware(['check.role:business-owner,administrator'])`

### User Model Helper Methods
```php
$user->hasRole('business-owner')
$user->isBusinessOwner()
$user->canManageUsers()
$user->promoteTo('administrator')
```

### Business Model Helper Methods
```php
$business->generatePublicId()
$business->generateInvitationCode()
$business->addUserWithRole($user, 'staff')
$business->promoteUser($user, 'administrator')
```

## Route Protection

Routes are protected based on role requirements:

- **Business Owner + Administrator**: User management, metric deletion
- **All except Investigator**: Metrics, feeds, notifications
- **Investigator only**: Limited dashboard view
- **Public**: Landing pages, authentication

## Frontend Features

### Wizard Optimization
- Shows only 3 initial roles (Business Owner, Staff, Business Investigator)
- Dynamic access validation modal based on selected role
- Consistent design with existing dashboard theme

### User Management Dashboard
- DataTables implementation with search, sort, pagination
- Role-based action buttons (promote, delete)
- Business codes display and management
- Real-time code refresh functionality

### Role-Based UI
- Different dashboard views based on user role
- Conditional menu items and action buttons
- Access limitation notices for investigators

## Demo Data

Run the demo seeder to create test users:

```bash
php artisan db:seed --class=RBACDemoSeeder
```

This creates:
- Business Owner: `owner@example.com`
- Administrator: `admin@example.com`
- Staff: `staff1@example.com`, `staff2@example.com`
- Investigator: `investigator@example.com`

All passwords: `password`

## Security Features

1. **Role Validation**: Server-side role checking on all protected routes
2. **Code Generation**: Cryptographically secure unique code generation
3. **Access Logging**: Join timestamps for audit trails
4. **Permission Inheritance**: Business Owner has all permissions by default
5. **Graceful Degradation**: Appropriate error messages for unauthorized access

## Testing

Run RBAC tests to verify functionality:

```bash
php artisan test tests/Feature/RBACTest.php
```

Tests cover:
- Role-based route access
- User helper methods
- Business code generation
- Permission inheritance
- Promotion workflows