@extends('layouts.dashboard')

@section('title', 'Edit Profile - Traction Tracker')

@section('content')
<div class="dashboard-content ms-4">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Edit Profile</h1>
                <p class="content-subtitle">Update your account information and settings</p>
            </div>
            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Profile
            </a>
        </div>
    </div>

    <div class="content-body">
        <!-- Success Messages -->
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                @if(session('status') == 'profile-updated')
                    Profile updated successfully!
                @elseif(session('status') == 'password-updated')
                    Password updated successfully!
                @else
                    {{ session('status') }}
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Update Profile Information -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-person-circle me-2"></i>
                            Profile Information
                        </h5>
                        <p class="card-subtitle">Update your account's profile information and email address.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('patch')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', Auth::user()->name) }}"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email"
                                           name="email"
                                           value="{{ old('email', Auth::user()->email) }}"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-shield-lock me-2"></i>
                            Update Password
                        </h5>
                        <p class="card-subtitle">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       id="current_password"
                                       name="current_password"
                                       required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           id="password"
                                           name="password"
                                           required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password"
                                           class="form-control"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="col-lg-4">
                <!-- Account Status -->
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-shield-check me-2"></i>
                            Account Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="status-item">
                            <span class="status-label">Account Status:</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Email Verified:</span>
                            @if(Auth::user()->email_verified_at)
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-warning">Unverified</span>
                            @endif
                        </div>
                        <div class="status-item">
                            <span class="status-label">Setup Status:</span>
                            @if(Auth::user()->setup_completed)
                                <span class="badge bg-success">Completed</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-lightning me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(!Auth::user()->setup_completed)
                            <a href="{{ route('setup.wizard') }}" class="btn btn-warning">
                                <i class="bi bi-gear me-2"></i>
                                Complete Setup
                            </a>
                            @endif
                            <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-primary">
                                <i class="bi bi-gear me-2"></i>
                                Account Settings
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-success">
                                <i class="bi bi-house me-2"></i>
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="dashboard-card border-danger mt-4">
                    <div class="card-header bg-danger bg-opacity-10">
                        <h5 class="card-title text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Danger Zone
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Once you delete your account, all of its resources and data will be permanently deleted.
                        </p>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="bi bi-trash me-2"></i>
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-danger" id="deleteAccountModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-white">Are you sure you want to delete your account?</p>
                <p class="text-muted small">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                    Please enter your password to confirm you would like to permanently delete your account.
                </p>
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Password</label>
                        <input type="password"
                               class="form-control"
                               id="delete_password"
                               name="password"
                               placeholder="Enter your password"
                               required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Edit specific styles */
.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.status-item:last-child {
    margin-bottom: 0;
}

.status-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.card-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    margin-bottom: 0;
    margin-top: 0.5rem;
}

.border-danger {
    border-color: #dc3545 !important;
}

.modal-content.bg-dark {
    background: rgba(30, 60, 128, 0.95) !important;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.modal-header.border-danger {
    border-bottom-color: rgba(220, 53, 69, 0.3) !important;
}
</style>
@endsection
