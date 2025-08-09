@extends('layouts.dashboard')

@section('title', 'User Management')

@section('content')
<div class="container mx-auto p-5">
    <!-- Header Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="content-title text-white mb-2">
                                <i class="bi bi-people me-3"></i>User Management
                            </h1>
                            <p class="content-subtitle text-white-50 mb-0">Manage users, roles, and permissions for your business</p>
                        </div>
                        @if(auth()->user()->isBusinessOwner())
                        <div class="d-flex gap-2">
                            <button class="btn btn-excel" id="refreshInviteCode">
                                <i class="bi bi-arrow-clockwise me-2"></i>Regenerate Invite Code
                            </button>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#businessCodesModal">
                                <i class="bi bi-key me-2"></i>View Invitation Codes
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isBusinessOwner())
    <!-- Business Codes Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="content-card">
                <div class="card-body border-start border-primary border-4">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-primary me-3">
                            <i class="bi bi-building text-white"></i>
                        </div>
                        <div class="flex-fill">
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">ID Dashboard Perusahaan</h6>
                            <h5 class="fw-bold text-primary mb-1" id="businessPublicId">Loading...</h5>
                            <small class="text-white-50">Berikan ID ini kepada Staff dan Business Investigator</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="content-card">
                <div class="card-body border-start border-success border-4">
                    <div class="d-flex align-items-center">
                        <div class="metric-icon bg-success me-3">
                            <i class="bi bi-key text-white"></i>
                        </div>
                        <div class="flex-fill">
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">Kode Undangan Staff</h6>
                            <h5 class="fw-bold text-success mb-1" id="businessInviteCode">Loading...</h5>
                            <small class="text-white-50">Kode rahasia khusus untuk Staff</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Users Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title fw-bold text-white mb-0">
                            <i class="bi bi-people-fill me-2"></i>Business Users
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-light btn-sm" onclick="$('#usersTable').DataTable().ajax.reload()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                            </button>
                        </div>
                    </div>

                    <div class="datatable-container">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
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
</div>

    @if(auth()->user()->isBusinessOwner())
    <!-- Business Codes Modal -->
    <div class="modal fade" id="businessCodesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-glass">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bi bi-key me-2"></i>Business Invitation Codes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-white">ID Dashboard Perusahaan</label>
                        <div class="input-group">
                            <input type="text" class="form-control modal-input" id="modalPublicId" readonly>
                            <button class="btn btn-outline-light" type="button" onclick="copyToClipboard('modalPublicId')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="form-text text-white-50">Berikan ID ini kepada Staff dan Business Investigator untuk bergabung</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-white">Kode Undangan Staff</label>
                        <div class="input-group">
                            <input type="text" class="form-control modal-input" id="modalInviteCode" readonly>
                            <button class="btn btn-outline-light" type="button" onclick="copyToClipboard('modalInviteCode')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="form-text text-white-50">Kode rahasia khusus untuk Staff</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-excel-gradient" id="regenerateInviteCodeModal">
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
    /* Content Cards */
    .content-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .content-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .content-card .card-body {
        padding: 2rem;
    }

    /* Content Title */
    .content-title {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .content-subtitle {
        font-size: 1rem;
        opacity: 0.8;
    }

    /* Metric Icons */
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* DataTable Container */
    .datatable-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Table Styling */
    #usersTable {
        background: transparent;
        border: none;
    }

    #usersTable thead th {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
        padding: 1rem;
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }

    #usersTable tbody td {
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.9);
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        vertical-align: middle;
    }

    #usersTable tbody tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    #usersTable tbody tr:hover td {
        color: rgba(255, 255, 255, 1);
    }

    /* Button Styling */
    .btn-excel {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-excel:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .btn-excel-gradient {
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(124, 185, 71, 0.3);
    }

    .btn-excel-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(124, 185, 71, 0.4);
        color: white;
    }

    /* User Avatar */
    .user-avatar .rounded-circle {
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .user-avatar .rounded-circle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Badge Styling */
    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Modal Styling */
    .modal-glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    }

    .modal-glass .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px 20px 0 0;
    }

    .modal-glass .modal-body {
        background: rgba(255, 255, 255, 0.02);
    }

    .modal-glass .modal-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0 0 20px 20px;
    }

    .modal-input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.9);
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .modal-input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #7cb947;
        box-shadow: 0 0 0 0.25rem rgba(124, 185, 71, 0.25);
        color: rgba(255, 255, 255, 1);
    }

    .modal-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    /* DataTables Pagination */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: rgba(255, 255, 255, 0.8) !important;
        margin: 0 2px;
        border-radius: 8px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(124, 185, 71, 0.3) !important;
        color: rgba(255, 255, 255, 1) !important;
        border-color: rgba(124, 185, 71, 0.5) !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%) !important;
        color: white !important;
        border-color: #7cb947 !important;
    }

    /* DataTables Info and Search */
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .dataTables_wrapper .dataTables_filter input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.9);
        padding: 8px 12px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #7cb947;
        outline: none;
    }

    .dataTables_wrapper .dataTables_length select {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.9);
        padding: 6px 10px;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .container-fluid {
            margin-left: 0 !important;
            padding: 1rem !important;
        }

        .content-card .card-body {
            padding: 1rem;
        }

        .content-title {
            font-size: 1.5rem;
        }

        .datatable-container {
            padding: 1rem;
        }

        .btn-excel {
            padding: 8px 16px;
            font-size: 0.875rem;
        }
    }

    /* Custom scrollbar for table */
    .datatable-container::-webkit-scrollbar {
        height: 8px;
    }

    .datatable-container::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .datatable-container::-webkit-scrollbar-thumb {
        background: rgba(124, 185, 71, 0.5);
        border-radius: 10px;
    }

    .datatable-container::-webkit-scrollbar-thumb:hover {
        background: rgba(124, 185, 71, 0.7);
    }
</style>
@endpush

@push('scripts')
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Toastr for notifications -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script>
$(function() {
    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Initialize DataTable
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("users.data") }}',
            type: 'GET',
            error: function(xhr, error, code) {
                toastr.error('Failed to load users data');
                console.error('DataTable error:', error);
            }
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
                                <small class="text-white-50">${row.email}</small>
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

                    return actions || '<span class="text-white-50">No actions</span>';
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
            zeroRecords: "No users match your search",
            search: "Search users:",
            lengthMenu: "Show _MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        drawCallback: function(settings) {
            // Update table styling after each draw
            $('#usersTable_wrapper .dataTables_info, #usersTable_wrapper .dataTables_paginate').css('color', 'rgba(255, 255, 255, 0.8)');
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
                } else {
                    toastr.error('Failed to load business codes');
                }
            })
            .catch(error => {
                console.error('Error loading business codes:', error);
                document.getElementById('businessPublicId').textContent = 'Error loading';
                document.getElementById('businessInviteCode').textContent = 'Error loading';
                toastr.error('Error loading business codes');
            });
    }

    // Regenerate invitation code
    document.getElementById('refreshInviteCode').addEventListener('click', regenerateInviteCode);
    document.getElementById('regenerateInviteCodeModal').addEventListener('click', regenerateInviteCode);

    function regenerateInviteCode() {
        if (confirm('Are you sure you want to regenerate the invitation code? The old code will no longer work.')) {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise fa-spin me-2"></i>Regenerating...';
            btn.disabled = true;

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
                    toastr.success('Invitation code regenerated successfully!');
                } else {
                    toastr.error('Error: ' + (data.error || 'Failed to regenerate invitation code'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error regenerating invitation code');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    }
    @endif

    @if(auth()->user()->canManageUsers())
    // Promote user function
    window.promoteUser = function(userId) {
        if (confirm('Are you sure you want to promote this user to Administrator?')) {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-up-circle fa-spin"></i> Promoting...';
            btn.disabled = true;

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
                    toastr.success('User promoted successfully!');
                    usersTable.ajax.reload();
                } else {
                    toastr.error('Error: ' + (data.error || 'Failed to promote user'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error promoting user');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    };

    // Remove user function
    window.removeUser = function(userId) {
        if (confirm('Are you sure you want to remove this user from the business?')) {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-trash fa-spin"></i> Removing...';
            btn.disabled = true;

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
                    toastr.success('User removed successfully!');
                    usersTable.ajax.reload();
                } else {
                    toastr.error('Error: ' + (data.error || 'Failed to remove user'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error removing user');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    };
    @endif

    // Copy to clipboard function with improved feedback
    window.copyToClipboard = function(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        element.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(element.value).then(() => {
            toastr.success('Copied to clipboard!');

            // Visual feedback on button
            const btn = event.target.closest('button');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i>';
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
            }, 1000);
        }).catch(() => {
            toastr.error('Failed to copy to clipboard');
        });
    };

    // Refresh table data
    window.refreshTable = function() {
        usersTable.ajax.reload();
        toastr.info('Users table refreshed');
    };
});
</script>
@endpush
