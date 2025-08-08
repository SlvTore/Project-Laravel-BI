@extends('layouts.dashboard')

@section('title', 'User Management')

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">User Management</h1>
                <p class="content-subtitle">Manage users, roles, and permissions for your business</p>
            </div>
            @if(auth()->user()->isBusinessOwner())
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="refreshInviteCode">
                    <i class="bi bi-arrow-clockwise me-2"></i>Regenerate Invite Code
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#businessCodesModal">
                    <i class="bi bi-key me-2"></i>View Invitation Codes
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="content-body">
        @if(auth()->user()->isBusinessOwner())
        <!-- Business Codes Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="content-card border-start border-primary border-4">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-2">ID Dashboard Perusahaan</h6>
                        <h5 class="fw-bold text-primary mb-1" id="businessPublicId">Loading...</h5>
                        <small class="text-muted">Berikan ID ini kepada Staff dan Business Investigator</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-card border-start border-success border-4">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-bold mb-2">Kode Undangan Staff</h6>
                        <h5 class="fw-bold text-success mb-1" id="businessInviteCode">Loading...</h5>
                        <small class="text-muted">Kode rahasia khusus untuk Staff</small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Users Table -->
        <div class="row">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title fw-bold mb-0">Business Users</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">User</th>
                                        <th class="fw-semibold">Role</th>
                                        <th class="fw-semibold">Status</th>
                                        <th class="fw-semibold">Joined Date</th>
                                        @if(auth()->user()->canManageUsers())
                                        <th class="fw-semibold">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isBusinessOwner())
    <!-- Business Codes Modal -->
    <div class="modal fade" id="businessCodesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-key me-2"></i>Business Invitation Codes
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">ID Dashboard Perusahaan</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalPublicId" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modalPublicId')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Berikan ID ini kepada Staff dan Business Investigator untuk bergabung</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Kode Undangan Staff</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalInviteCode" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modalInviteCode')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Kode rahasia khusus untuk Staff</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="regenerateInviteCodeModal">
                        <i class="bi bi-arrow-clockwise me-2"></i>Regenerate Staff Code
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    .table th {
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .user-avatar img {
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@push('scripts')
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function() {
    // Initialize DataTable
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("users.data") }}',
            type: 'GET'
        },
        columns: [
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px; color: white; font-weight: bold;">
                                    ${data.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div>
                                <div class="fw-semibold">${data}</div>
                                <small class="text-muted">${row.email}</small>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'role',
                name: 'role',
                render: function(data, type, row) {
                    const badgeClass = {
                        'Business Owner': 'bg-warning',
                        'Administrator': 'bg-primary',
                        'Staff': 'bg-success',
                        'Business Investigator': 'bg-info'
                    }[data] || 'bg-secondary';
                    
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            {
                data: 'is_active',
                name: 'is_active',
                render: function(data, type, row) {
                    return data ? 
                        '<span class="badge bg-success">Active</span>' : 
                        '<span class="badge bg-warning">Inactive</span>';
                }
            },
            {
                data: 'joined_at',
                name: 'joined_at'
            }
            @if(auth()->user()->canManageUsers())
            ,{
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let actions = '';
                    
                    if (row.can_promote) {
                        actions += `
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="promoteUser(${row.id})" title="Promote to Administrator">
                                <i class="bi bi-arrow-up-circle"></i> Promote
                            </button>
                        `;
                    }
                    
                    if (row.can_delete) {
                        actions += `
                            <button class="btn btn-sm btn-outline-danger" onclick="removeUser(${row.id})" title="Remove from Business">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        `;
                    }
                    
                    return actions || '<span class="text-muted">No actions</span>';
                }
            }
            @endif
        ],
        responsive: true,
        pageLength: 25,
        order: [[3, 'desc']], // Sort by joined date
        language: {
            processing: "Loading users...",
            emptyTable: "No users found in this business",
            zeroRecords: "No users match your search"
        }
    });

    @if(auth()->user()->isBusinessOwner())
    // Load business codes
    loadBusinessCodes();

    function loadBusinessCodes() {
        fetch('{{ route("users.business-codes") }}')
            .then(response => response.json())
            .then(data => {
                if (data.public_id && data.invitation_code) {
                    document.getElementById('businessPublicId').textContent = data.public_id;
                    document.getElementById('businessInviteCode').textContent = data.invitation_code;
                    document.getElementById('modalPublicId').value = data.public_id;
                    document.getElementById('modalInviteCode').value = data.invitation_code;
                }
            })
            .catch(error => {
                console.error('Error loading business codes:', error);
                document.getElementById('businessPublicId').textContent = 'Error loading';
                document.getElementById('businessInviteCode').textContent = 'Error loading';
            });
    }

    // Regenerate invitation code
    document.getElementById('refreshInviteCode').addEventListener('click', regenerateInviteCode);
    document.getElementById('regenerateInviteCodeModal').addEventListener('click', regenerateInviteCode);

    function regenerateInviteCode() {
        if (confirm('Are you sure you want to regenerate the invitation code? The old code will no longer work.')) {
            fetch('{{ route("users.regenerate-invite-code") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('businessInviteCode').textContent = data.new_code;
                    document.getElementById('modalInviteCode').value = data.new_code;
                    alert('Invitation code regenerated successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Failed to regenerate invitation code'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error regenerating invitation code');
            });
        }
    }
    @endif

    @if(auth()->user()->canManageUsers())
    // Promote user function
    window.promoteUser = function(userId) {
        if (confirm('Are you sure you want to promote this user to Administrator?')) {
            fetch(`{{ url('users') }}/${userId}/promote`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User promoted successfully!');
                    usersTable.ajax.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to promote user'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error promoting user');
            });
        }
    };

    // Remove user function
    window.removeUser = function(userId) {
        if (confirm('Are you sure you want to remove this user from the business?')) {
            fetch(`{{ url('users') }}/${userId}/remove`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User removed successfully!');
                    usersTable.ajax.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to remove user'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing user');
            });
        }
    };
    @endif

    // Copy to clipboard function
    window.copyToClipboard = function(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(element.value).then(() => {
            alert('Copied to clipboard!');
        });
    };
});
</script>
@endpush
