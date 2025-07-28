@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Settings</h1>
                <p class="content-subtitle">Manage your account and application preferences</p>
            </div>
        </div>
    </div>

    <div class="content-body">
        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-md-3 mb-4">
                <div class="dashboard-card">
                    <div class="card-body p-0">
                        <div class="settings-nav">
                        <a href="#profile" class="settings-nav-item active" data-target="profile">
                            <i class="bi bi-person-circle me-3"></i>
                            Profile Settings
                        </a>
                        <a href="#account" class="settings-nav-item" data-target="account">
                            <i class="bi bi-gear me-3"></i>
                            Account Settings
                        </a>
                        <a href="#security" class="settings-nav-item" data-target="security">
                            <i class="bi bi-shield-lock me-3"></i>
                            Security
                        </a>
                        <a href="#notifications" class="settings-nav-item" data-target="notifications">
                            <i class="bi bi-bell me-3"></i>
                            Notifications
                        </a>
                        <a href="#billing" class="settings-nav-item" data-target="billing">
                            <i class="bi bi-credit-card me-3"></i>
                            Billing & Plans
                        </a>
                        <a href="#team" class="settings-nav-item" data-target="team">
                            <i class="bi bi-people me-3"></i>
                            Team Management
                        </a>
                        <a href="#integrations" class="settings-nav-item" data-target="integrations">
                            <i class="bi bi-plug me-3"></i>
                            Integrations
                        </a>
                        <a href="#advanced" class="settings-nav-item" data-target="advanced">
                            <i class="bi bi-sliders me-3"></i>
                            Advanced
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-md-9">
            <!-- Profile Settings -->
            <div id="profile" class="settings-section">
                <div class="content-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Profile Information</h5>
                        <form>
                            <div class="row g-3">
                                <div class="col-12 text-center mb-4">
                                    <div class="profile-image-container">
                                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop&crop=face"
                                             alt="Profile" class="profile-image rounded-circle" width="120" height="120">
                                        <button type="button" class="btn btn-sm btn-primary profile-image-overlay">
                                            <i class="bi bi-camera"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">First Name</label>
                                    <input type="text" class="form-control" value="John">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last Name</label>
                                    <input type="text" class="form-control" value="Doe">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control" value="john.doe@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <input type="tel" class="form-control" value="+1 (555) 123-4567">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Bio</label>
                                    <textarea class="form-control" rows="3">Business Intelligence Specialist with 5+ years of experience in data analysis and visualization.</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Company</label>
                                    <input type="text" class="form-control" value="Tech Solutions Inc.">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Position</label>
                                    <input type="text" class="form-control" value="Senior BI Analyst">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-outline-secondary me-2">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div id="account" class="settings-section d-none">
                <div class="content-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Account Preferences</h5>
                        <form>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Language</label>
                                    <select class="form-select">
                                        <option selected>English (US)</option>
                                        <option>English (UK)</option>
                                        <option>Spanish</option>
                                        <option>French</option>
                                        <option>German</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Timezone</label>
                                    <select class="form-select">
                                        <option selected>UTC-05:00 (Eastern Time)</option>
                                        <option>UTC-08:00 (Pacific Time)</option>
                                        <option>UTC+00:00 (GMT)</option>
                                        <option>UTC+01:00 (Central European Time)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date Format</label>
                                    <select class="form-select">
                                        <option selected>MM/DD/YYYY</option>
                                        <option>DD/MM/YYYY</option>
                                        <option>YYYY-MM-DD</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Currency</label>
                                    <select class="form-select">
                                        <option selected>USD ($)</option>
                                        <option>EUR (€)</option>
                                        <option>GBP (£)</option>
                                        <option>JPY (¥)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="darkMode">
                                        <label class="form-check-label fw-semibold" for="darkMode">
                                            Enable Dark Mode
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoSave" checked>
                                        <label class="form-check-label fw-semibold" for="autoSave">
                                            Auto-save changes
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-outline-secondary me-2">Reset</button>
                                <button type="submit" class="btn btn-primary">Save Preferences</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div id="security" class="settings-section d-none">
                <div class="content-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Password & Security</h5>
                        <form>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Current Password</label>
                                    <input type="password" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <input type="password" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm New Password</label>
                                    <input type="password" class="form-control">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="content-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Two-Factor Authentication</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1 fw-semibold">Authenticator App</p>
                                <small class="text-muted">Use an authenticator app to generate verification codes</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="twoFA">
                                <label class="form-check-label" for="twoFA"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Login Activity</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <i class="bi bi-laptop me-2"></i>
                                            Chrome on Windows
                                        </td>
                                        <td>New York, US</td>
                                        <td>2 hours ago</td>
                                        <td><span class="badge bg-success">Current</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-phone me-2"></i>
                                            Mobile App
                                        </td>
                                        <td>New York, US</td>
                                        <td>1 day ago</td>
                                        <td><span class="badge bg-secondary">Inactive</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Settings -->
            <div id="notifications" class="settings-section d-none">
                <div class="content-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Notification Preferences</h5>
                        <div class="notification-settings">
                            <div class="notification-category">
                                <h6 class="fw-semibold mb-3">Email Notifications</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="emailReports" checked>
                                        <label class="form-check-label" for="emailReports">
                                            Weekly reports
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="emailAlerts" checked>
                                        <label class="form-check-label" for="emailAlerts">
                                            System alerts
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="emailMarketing">
                                        <label class="form-check-label" for="emailMarketing">
                                            Marketing emails
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="notification-category">
                                <h6 class="fw-semibold mb-3">Push Notifications</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="pushGoals" checked>
                                        <label class="form-check-label" for="pushGoals">
                                            Goal achievements
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="pushComments" checked>
                                        <label class="form-check-label" for="pushComments">
                                            New comments
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="pushUpdates">
                                        <label class="form-check-label" for="pushUpdates">
                                            System updates
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Settings -->
            <div id="billing" class="settings-section d-none">
                <div class="content-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Current Plan</h5>
                        <div class="current-plan">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold text-primary">Professional Plan</h6>
                                    <p class="text-muted mb-0">$29/month • Billed monthly</p>
                                </div>
                                <button class="btn btn-outline-primary">Upgrade Plan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Payment Methods</h5>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card text-primary me-3" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <p class="mb-0 fw-semibold">•••• •••• •••• 4242</p>
                                            <small class="text-muted">Expires 12/25</small>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary">Edit</button>
                                        <button class="btn btn-outline-danger">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary mt-3">
                            <i class="bi bi-plus-lg me-2"></i>Add Payment Method
                        </button>
                    </div>
                </div>
            </div>

            <!-- Team Management -->
            <div id="team" class="settings-section d-none">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title fw-bold mb-0">Team Members</h5>
                            <button class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Invite Member
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=40&h=40&fit=crop&crop=face"
                                                     alt="User" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <div class="fw-semibold">Jane Smith</div>
                                                    <small class="text-muted">jane@example.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary">Admin</span></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                        <td>Jan 15, 2024</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-secondary">Edit</button>
                                                <button class="btn btn-outline-danger">Remove</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations -->
            <div id="integrations" class="settings-section d-none">
                <div class="content-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Connected Integrations</h5>
                        <div class="integrations-grid">
                            <div class="integration-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="integration-icon me-3">
                                            <i class="bi bi-google text-danger"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Google Analytics</h6>
                                            <small class="text-muted">Track website performance</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-success">Connected</button>
                                </div>
                            </div>
                            <div class="integration-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="integration-icon me-3">
                                            <i class="bi bi-slack text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Slack</h6>
                                            <small class="text-muted">Get notifications in Slack</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary">Connect</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div id="advanced" class="settings-section d-none">
                <div class="content-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Advanced Settings</h5>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            These settings are for advanced users only. Changing these settings may affect your account functionality.
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="debugMode">
                                    <label class="form-check-label fw-semibold" for="debugMode">
                                        Enable Debug Mode
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="apiAccess">
                                    <label class="form-check-label fw-semibold" for="apiAccess">
                                        API Access
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr>
                                <h6 class="text-danger fw-bold">Danger Zone</h6>
                                <button class="btn btn-outline-danger me-2">Export Data</button>
                                <button class="btn btn-danger">Delete Account</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .settings-nav {
        padding: 0;
    }

    .settings-nav-item {
        display: block;
        padding: 1rem 1.25rem;
        color: #6c757d;
        text-decoration: none;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.3s ease;
    }

    .settings-nav-item:hover,
    .settings-nav-item.active {
        background: linear-gradient(135deg, rgba(124, 185, 71, 0.1) 0%, rgba(30, 60, 128, 0.1) 100%);
        color: #1e3c80;
        border-left: 3px solid #7cb947;
    }

    .settings-nav-item:last-child {
        border-bottom: none;
    }

    .profile-image-container {
        position: relative;
        display: inline-block;
    }

    .profile-image {
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .profile-image-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-category {
        margin-bottom: 1.5rem;
    }

    .form-check-input:checked {
        background-color: #7cb947;
        border-color: #7cb947;
    }

    .payment-method {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .integration-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .integration-icon {
        width: 40px;
        height: 40px;
        background: #f8f9fa;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
    $(function() {
        // Settings Navigation
        $('.settings-nav-item').click(function(e) {
            e.preventDefault();

            // Remove active class from all nav items
            $('.settings-nav-item').removeClass('active');

            // Add active class to clicked item
            $(this).addClass('active');

            // Hide all settings sections
            $('.settings-section').addClass('d-none');

            // Show target section
            const target = $(this).data('target');
            $('#' + target).removeClass('d-none');
        });

        // Profile image upload simulation
        $('.profile-image-overlay').click(function() {
            // Create a file input
            const fileInput = $('<input type="file" accept="image/*" style="display: none;">');

            fileInput.change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-image').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Append to body and trigger click
            $('body').append(fileInput);
            fileInput.click();
            fileInput.remove();
        });

        // Form submissions (simulated)
        $('form').submit(function(e) {
            e.preventDefault();

            // Show success toast
            const toast = `
                <div class="toast-container position-fixed bottom-0 end-0 p-3">
                    <div class="toast show" role="alert">
                        <div class="toast-header">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                        </div>
                        <div class="toast-body">
                            Settings saved successfully!
                        </div>
                    </div>
                </div>
            `;

            $('body').append(toast);

            // Remove toast after 3 seconds
            setTimeout(() => {
                $('.toast-container').remove();
            }, 3000);
        });
    });
</script>
@endpush

        </div>
    </div>
@endsection
