@extends('layouts.dashboard')

@section('title', 'Profile - Traction Tracker')

@section('content')
<div class="dashboard-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">My Profile</h1>
                <p class="content-subtitle">Manage your account settings and preferences</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>
                Edit Profile
            </a>
        </div>
    </div>

    <div class="content-body">
        <div class="row">
            <!-- Profile Information Card -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-person-circle me-2"></i>
                            Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="form-control-plaintext">{{ Auth::user()->name }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="form-control-plaintext">{{ Auth::user()->email }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <div class="form-control-plaintext">
                                    <span class="badge bg-primary">{{ Auth::user()->userRole->display_name ?? 'User' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Status</label>
                                <div class="form-control-plaintext">
                                    @if(Auth::user()->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Setup Status</label>
                                <div class="form-control-plaintext">
                                    @if(Auth::user()->setup_completed)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member Since</label>
                                <div class="form-control-plaintext">{{ Auth::user()->created_at->format('F j, Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Statistics & Quick Actions -->
            <div class="col-lg-4">
                <!-- Quick Stats -->
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up me-2"></i>
                            Quick Stats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-number">{{ Auth::user()->businesses->count() }}</div>
                                <div class="stat-label">Business{{ Auth::user()->businesses->count() !== 1 ? 'es' : '' }}</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="bi bi-calendar"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-number">{{ Auth::user()->created_at->diffInDays() }}</div>
                                <div class="stat-label">Days Active</div>
                            </div>
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
                            <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-2"></i>
                                Edit Profile
                            </a>
                            <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-secondary">
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
            </div>
        </div>

        @if(Auth::user()->primaryBusiness)
        <!-- Business Information -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-briefcase me-2"></i>
                            Business Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Name</label>
                                <div class="form-control-plaintext">{{ Auth::user()->primaryBusiness->business_name }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Industry</label>
                                <div class="form-control-plaintext">{{ Auth::user()->primaryBusiness->industry }}</div>
                            </div>
                            @if(Auth::user()->primaryBusiness->website)
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <div class="form-control-plaintext">
                                    <a href="{{ Auth::user()->primaryBusiness->website }}" target="_blank" class="text-decoration-none">
                                        {{ Auth::user()->primaryBusiness->website }}
                                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            @endif
                            @if(Auth::user()->primaryBusiness->founded_date)
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Founded Date</label>
                                <div class="form-control-plaintext">{{ Auth::user()->primaryBusiness->founded_date->format('F j, Y') }}</div>
                            </div>
                            @endif
                            @if(Auth::user()->primaryBusiness->description)
                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <div class="form-control-plaintext">{{ Auth::user()->primaryBusiness->description }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
/* Profile specific styles */
.form-control-plaintext {
    padding: 0.5rem 0;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: rgba(124, 185, 71, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 1.5rem;
    color: #7cb947;
}

.stat-details {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 0.25rem;
}

.badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
}
</style>
@endsection
