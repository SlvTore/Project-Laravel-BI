@extends('layouts.dashboard')

@section('title', 'My Profile - Traction Tracker')

@section('content')
<div class="dashboard-content ms-4">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">My Profile</h1>
                <p class="content-subtitle">Manage your account information and settings</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Dashboard
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
            <!-- Avatar and Quick Info Section -->
            <div class="col-lg-4">
                <div class="dashboard-card text-center">
                    <div class="card-body">
                        <!-- Avatar Display/Upload Section -->
                        <div class="avatar-section mb-4">
                            <div class="avatar-container mx-auto mb-3" style="width: 120px; height: 120px;">
                                <img id="avatarPreview" src="{{ $user->getAvatarUrl() }}" alt="Avatar"
                                     class="img-fluid rounded-circle border-2 border-primary"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            </div>

                            <!-- Avatar Upload Form -->
                            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="avatar-upload-form">
                                @csrf
                                @method('patch')

                                <div class="mb-3">
                                    <label for="avatar" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-camera me-1"></i>
                                        Change Avatar
                                    </label>
                                    <input type="file" class="d-none @error('avatar') is-invalid @enderror"
                                           id="avatar" name="avatar" accept="image/*">
                                    @error('avatar')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($user->avatar_path)
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="removeAvatar">
                                        <i class="bi bi-trash me-1"></i>Remove
                                    </button>
                                    <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                                @endif

                                <button type="submit" class="btn btn-success btn-sm d-none" id="saveAvatarBtn">
                                    <i class="bi bi-check me-1"></i>Save Avatar
                                </button>
                            </form>

                            <h5 class="fw-bold text-primary mt-3">{{ $user->name }}</h5>
                            <p class="text-muted mb-2">{{ $user->email }}</p>
                            <span class="badge bg-primary fs-6">{{ $user->userRole->display_name ?? 'User' }}</span>
                        </div>

                        <!-- User Stats -->
                        <div class="stats-section">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <h6 class="stat-number">{{ $user->created_at->diffInDays(now()) }}</h6>
                                        <small class="text-muted">Days Active</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <h6 class="stat-number">
                                            @if($user->isBusinessOwner())
                                                {{ $user->ownedBusinesses()->count() }}
                                            @else
                                                {{ $user->businesses()->count() }}
                                            @endif
                                        </h6>
                                        <small class="text-muted">Business{{ $user->businesses()->count() > 1 ? 'es' : '' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick-actions mt-4">
                            @if($user->isBusinessOwner())
                                <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-gear me-1"></i>Settings
                                </a>
                            @endif
                            <a href="{{ route('help-center.index') }}" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-question-circle me-1"></i>Help
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Profile Content -->
            <div class="col-lg-8">
                <!-- Profile Information Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-person-circle me-2"></i>
                            Personal Information
                        </h5>
                        <p class="card-subtitle">Update your account's profile information and email address.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('patch')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $user->name) }}" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                        <div class="mt-2">
                                            <p class="text-sm text-muted">
                                                Your email address is unverified.
                                                <button form="send-verification" class="btn btn-link p-0 text-sm text-decoration-underline">
                                                    Click here to re-send the verification email.
                                                </button>
                                            </p>

                                            @if (session('status') === 'verification-link-sent')
                                                <p class="mt-2 text-sm text-success">
                                                    A new verification link has been sent to your email address.
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $user->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Account Status</label>
                                    <div class="form-control-plaintext">
                                        @if($user->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                        <small class="text-muted ms-2">Member since {{ $user->created_at->format('M Y') }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check me-2"></i>
                                    Save Changes
                                </button>

                                @if (session('status') === 'profile-updated')
                                    <p class="text-sm text-success mb-0">Saved.</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Update Password Form -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-key me-2"></i>
                            Update Password
                        </h5>
                        <p class="card-subtitle">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="update_password_current_password" class="form-label">Current Password</label>
                                    <input id="update_password_current_password" name="current_password" type="password"
                                           class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                                    @error('current_password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="update_password_password" class="form-label">New Password</label>
                                    <input id="update_password_password" name="password" type="password"
                                           class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                                    @error('password', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
                                    <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                                           class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror">
                                    @error('password_confirmation', 'updatePassword')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Update Password
                                </button>

                                @if (session('status') === 'password-updated')
                                    <p class="text-sm text-success mb-0">Password updated.</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Business Information (if applicable) -->
                @if($user->isBusinessOwner() && $user->ownedBusinesses()->count() > 0)
                    <div class="dashboard-card mt-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="bi bi-building me-2"></i>
                                Business Information
                            </h5>
                        </div>
                        <div class="card-body">
                            @foreach($user->ownedBusinesses as $business)
                                <div class="business-item mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">{{ $business->name }}</h6>
                                            <p class="text-muted mb-1">{{ $business->industry ?? 'Industry not specified' }}</p>
                                            <small class="text-muted">Created: {{ $business->created_at->format('M j, Y') }}</small>
                                        </div>
                                        <span class="badge bg-primary">Owner</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Delete Account Section -->
                <div class="dashboard-card mt-4 border-danger">
                    <div class="card-header border-danger">
                        <h5 class="card-title text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Delete Account
                        </h5>
                        <p class="card-subtitle">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
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
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-body">
                    <p class="text-light">Are you sure you want to delete your account?</p>
                    <p class="text-muted small">Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.</p>

                    <div class="mt-3">
                        <label for="password" class="form-label text-light">Password</label>
                        <input id="password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-danger">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Email Verification Form (hidden) -->
@if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" style="display: none;">
        @csrf
    </form>
@endif

<style>
/* Profile specific styles */
.avatar-container {
    position: relative;
}

.stat-item {
    padding: 0.5rem 0;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #007bff;
    line-height: 1;
}

.business-item {
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.business-item:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.quick-actions .btn {
    border-radius: 20px;
}

.card-subtitle {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0;
    margin-top: 0.5rem;
}

.avatar-upload-form {
    display: inline-block;
}

#saveAvatarBtn.show {
    display: inline-block !important;
}

.border-danger {
    border-color: #dc3545 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Avatar preview functionality
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const removeAvatarBtn = document.getElementById('removeAvatar');
    const removeAvatarInput = document.getElementById('removeAvatarInput');
    const saveAvatarBtn = document.getElementById('saveAvatarBtn');

    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    saveAvatarBtn.classList.remove('d-none');
                    saveAvatarBtn.classList.add('show');
                };
                reader.readAsDataURL(file);

                // Reset remove avatar flag
                if (removeAvatarInput) {
                    removeAvatarInput.value = '0';
                }
            }
        });
    }

    if (removeAvatarBtn) {
        removeAvatarBtn.addEventListener('click', function() {
            // Set remove avatar flag
            removeAvatarInput.value = '1';

            // Clear file input
            avatarInput.value = '';

            // Reset preview to default avatar using user's initials
            const userName = '{{ $user->name }}';
            const initials = userName.split(' ').map(name => name.charAt(0)).join('');
            avatarPreview.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&size=120&background=007bff&color=ffffff&format=png`;

            // Show save button
            saveAvatarBtn.classList.remove('d-none');
            saveAvatarBtn.classList.add('show');

            // Hide the remove button after clicking
            removeAvatarBtn.style.display = 'none';
        });
    }
});
</script>
@endsection
