@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
<div class="container-fluid p-4 ms-4">
    {{-- Settings Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="h3 mb-1 text-primary fw-bold">Settings</h1>
                            <p class="text-muted mb-0">Manage your business preferences and configuration</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary">Business Owner</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Sections --}}
    <div class="row">
        <div class="col-12">
            <div class="accordion" id="settingsAccordion">

                {{-- Dashboard Personalization Section --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#personalizationSection" aria-expanded="true">
                            <i class="fas fa-palette me-3 text-primary"></i>
                            <div>
                                <div class="fw-semibold">Dashboard Personalization</div>
                                <small class="text-muted">Customize your dashboard branding and appearance</small>
                            </div>
                        </button>
                    </h2>
                    <div id="personalizationSection" class="accordion-collapse collapse show" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                {{-- Logo Upload --}}
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-semibold text-dark mb-3">Business Logo</h6>
                                    <div class="text-center">
                                        <div class="logo-preview mb-3" style="height: 120px; width: 120px; margin: 0 auto;">
                                            @if($business->logo_path)
                                                <img src="{{ Storage::url($business->logo_path) }}" alt="Business Logo" class="img-fluid rounded border" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center h-100 bg-light rounded border">
                                                    <i class="fas fa-building text-muted" style="font-size: 2.5rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <form id="logoForm" enctype="multipart/form-data">
                                            @csrf
                                            <div class="mb-3">
                                                <input type="file" class="form-control" id="logoInput" name="logo" accept="image/*">
                                                <div class="form-text">Upload a logo (JPEG, PNG, JPG - Max: 2MB)</div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                {{-- Display Name --}}
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-semibold text-dark mb-3">Dashboard Display Name</h6>
                                    <form id="brandingForm">
                                        @csrf
                                        <div class="mb-3">
                                            <input type="text" class="form-control" id="dashboardDisplayName" name="dashboard_display_name"
                                                   value="{{ $business->dashboard_display_name ?? $business->business_name }}"
                                                   placeholder="Enter dashboard display name">
                                            <div class="form-text">This name will appear on your dashboard</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Branding
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Theme Preferences --}}
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-dark mb-3">Theme Preferences</h6>
                                    <form id="preferencesForm">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="theme" class="form-label">Theme</label>
                                                <select class="form-select" id="theme" name="theme">
                                                    <option value="light" {{ ($user->theme ?? 'light') == 'light' ? 'selected' : '' }}>Light</option>
                                                    <option value="dark" {{ ($user->theme ?? 'light') == 'dark' ? 'selected' : '' }}>Dark</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="accentColor" class="form-label">Accent Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="accentColor" name="accent_color"
                                                           value="{{ $user->accent_color ?? '#007bff' }}" title="Choose accent color">
                                                    <input type="text" class="form-control" value="{{ $user->accent_color ?? '#007bff' }}" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Preferences
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security & Access Section --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#securitySection">
                            <i class="fas fa-shield-alt me-3 text-success"></i>
                            <div>
                                <div class="fw-semibold">Security & Access</div>
                                <small class="text-muted">Manage invitation codes and ownership settings</small>
                            </div>
                        </button>
                    </h2>
                    <div id="securitySection" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                {{-- Invitation Code --}}
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-semibold text-dark mb-3">
                                        <i class="fas fa-link me-2"></i>Invitation Code
                                    </h6>
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div>
                                                    <p class="mb-1 text-muted">Current Code:</p>
                                                    <span class="badge bg-primary fs-6" id="currentInvitationCode">{{ $business->invitation_code }}</span>
                                                </div>
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyInvitationCode()">
                                                    <i class="fas fa-copy me-1"></i>Copy
                                                </button>
                                            </div>
                                            <p class="text-muted small mb-3">Share this code with team members to join your business.</p>
                                            <button type="button" class="btn btn-warning btn-sm" onclick="regenerateInvitationCode()">
                                                <i class="fas fa-refresh me-1"></i>Regenerate Code
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Business Information --}}
                                <div class="col-md-6 mb-4">
                                    <h6 class="fw-semibold text-dark mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Business Information
                                    </h6>
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="mb-2">
                                                <strong>Business ID:</strong> {{ $business->public_id }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Owner:</strong> {{ $user->name }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Members:</strong> {{ $business->users()->count() }} users
                                            </div>
                                            <div>
                                                <strong>Created:</strong> {{ $business->created_at->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone Section --}}
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dangerSection">
                            <i class="fas fa-exclamation-triangle me-3 text-danger"></i>
                            <div>
                                <div class="fw-semibold">Danger Zone</div>
                                <small class="text-muted">Irreversible actions - proceed with caution</small>
                            </div>
                        </button>
                    </h2>
                    <div id="dangerSection" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                {{-- Transfer Ownership --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning-subtle">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="fas fa-exchange-alt me-2"></i>Transfer Ownership
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small mb-3">Transfer business ownership to another team member.</p>
                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#transferOwnershipModal">
                                                <i class="fas fa-exchange-alt me-2"></i>Transfer Ownership
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Delete Business --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger-subtle">
                                            <h6 class="mb-0 fw-semibold">
                                                <i class="fas fa-trash me-2"></i>Delete Business
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small mb-3">Permanently delete this business and all associated data.</p>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBusinessModal">
                                                <i class="fas fa-trash me-2"></i>Delete Business
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- App Information Section --}}
                <div class="accordion-item border-0 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#appInfoSection">
                            <i class="fas fa-info me-3 text-info"></i>
                            <div>
                                <div class="fw-semibold">App Information</div>
                                <small class="text-muted">System information and support</small>
                            </div>
                        </button>
                    </h2>
                    <div id="appInfoSection" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-semibold text-dark mb-3">System Information</h6>
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="mb-2"><strong>Laravel Version:</strong> {{ app()->version() }}</div>
                                            <div class="mb-2"><strong>PHP Version:</strong> {{ PHP_VERSION }}</div>
                                            <div class="mb-2"><strong>Environment:</strong> {{ app()->environment() }}</div>
                                            <div><strong>Last Update:</strong> {{ date('M d, Y') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-semibold text-dark mb-3">Support & Resources</h6>
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="#" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-question-circle me-2"></i>Help Center
                                                </a>
                                                <a href="#" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-envelope me-2"></i>Contact Support
                                                </a>
                                                <a href="#" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-file-alt me-2"></i>Documentation
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transfer Ownership Modal --}}
<div class="modal fade" id="transferOwnershipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>Transfer Ownership
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transferOwnershipForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. You will lose ownership of this business.
                    </div>
                    <div class="mb-3">
                        <label for="newOwnerEmail" class="form-label">New Owner Email</label>
                        <input type="email" class="form-control" id="newOwnerEmail" name="new_owner_email" required>
                        <div class="form-text">Enter the email address of the team member who will become the new owner.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Transfer Ownership</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Business Modal --}}
<div class="modal fade" id="deleteBusinessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2"></i>Delete Business
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteBusinessForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Danger:</strong> This action is irreversible! All business data, metrics, and user associations will be permanently deleted.
                    </div>
                    <p>To confirm deletion, type <strong>DELETE</strong> in the field below:</p>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="deleteConfirmation" name="confirmation" placeholder="Type DELETE to confirm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" disabled id="deleteConfirmButton">Delete Business</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle logo upload
    document.getElementById('logoInput').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            handleLogoUpload();
        }
    });

    // Handle branding form
    document.getElementById('brandingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateBranding();
    });

    // Handle preferences form
    document.getElementById('preferencesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updatePreferences();
    });

    // Handle transfer ownership form
    document.getElementById('transferOwnershipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        transferOwnership();
    });

    // Handle delete business form
    document.getElementById('deleteBusinessForm').addEventListener('submit', function(e) {
        e.preventDefault();
        deleteBusiness();
    });

    // Enable/disable delete button based on confirmation text
    document.getElementById('deleteConfirmation').addEventListener('input', function() {
        const deleteButton = document.getElementById('deleteConfirmButton');
        deleteButton.disabled = this.value !== 'DELETE';
    });

    // Update accent color text when color picker changes
    document.getElementById('accentColor').addEventListener('change', function() {
        this.nextElementSibling.value = this.value;
    });
});

// Logo upload function
function handleLogoUpload() {
    const formData = new FormData();
    const logoFile = document.getElementById('logoInput').files[0];
    const displayName = document.getElementById('dashboardDisplayName').value;

    formData.append('logo', logoFile);
    formData.append('dashboard_display_name', displayName);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/settings/branding', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.logo_url) {
                document.querySelector('.logo-preview img, .logo-preview div').outerHTML =
                    `<img src="${data.logo_url}" alt="Business Logo" class="img-fluid rounded border" style="max-height: 100%; max-width: 100%; object-fit: contain;">`;
            }
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.error || 'Failed to upload logo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while uploading the logo');
    });
}

// Update branding function
function updateBranding() {
    const formData = new FormData(document.getElementById('brandingForm'));

    fetch('/settings/branding', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.error || 'Failed to update branding');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while updating branding');
    });
}

// Update preferences function
function updatePreferences() {
    const formData = new FormData(document.getElementById('preferencesForm'));

    fetch('/settings/preferences', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Apply theme changes immediately if needed
            applyThemeChanges(formData.get('theme'), formData.get('accent_color'));
        } else {
            showAlert('danger', data.error || 'Failed to update preferences');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while updating preferences');
    });
}

// Copy invitation code function
function copyInvitationCode() {
    const code = document.getElementById('currentInvitationCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showAlert('success', 'Invitation code copied to clipboard!');
    }).catch(() => {
        showAlert('warning', 'Failed to copy to clipboard');
    });
}

// Regenerate invitation code function
function regenerateInvitationCode() {
    if (!confirm('Are you sure you want to regenerate the invitation code? The old code will no longer work.')) {
        return;
    }

    fetch('/settings/invitation/regenerate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('currentInvitationCode').textContent = data.new_code;
            showAlert('success', data.message);
        } else {
            showAlert('danger', data.error || 'Failed to regenerate invitation code');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while regenerating invitation code');
    });
}

// Transfer ownership function
function transferOwnership() {
    const formData = new FormData(document.getElementById('transferOwnershipForm'));

    fetch('/settings/ownership/transfer', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('transferOwnershipModal')).hide();
            // Redirect to dashboard after successful transfer
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        } else {
            showAlert('danger', data.error || 'Failed to transfer ownership');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while transferring ownership');
    });
}

// Delete business function
function deleteBusiness() {
    const formData = new FormData(document.getElementById('deleteBusinessForm'));

    fetch('/settings/business', {
        method: 'DELETE',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('deleteBusinessModal')).hide();
            // Redirect after successful deletion
            setTimeout(() => {
                window.location.href = data.redirect || '/dashboard';
            }, 2000);
        } else {
            showAlert('danger', data.error || 'Failed to delete business');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while deleting business');
    });
}

// Apply theme changes function
function applyThemeChanges(theme, accentColor) {
    // Apply theme changes to the current page
    if (theme === 'dark') {
        document.body.classList.add('dark-theme');
    } else {
        document.body.classList.remove('dark-theme');
    }

    // Update CSS custom properties for accent color
    document.documentElement.style.setProperty('--bs-primary', accentColor);
}

// Show alert function
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    // Remove existing alerts
    document.querySelectorAll('.alert').forEach(alert => alert.remove());

    // Add new alert at the top of the container
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);

    // Auto-hide success alerts after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    }
}
</script>
@endsection
