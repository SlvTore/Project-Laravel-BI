@extends('layouts.dashboard')

@section('title', 'User Management')

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">User Management</h1>
                <p class="content-subtitle">Manage users, roles, and permissions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download me-2"></i>Export
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-lg me-2"></i>Add User
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
    <!-- User Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="content-card border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Total Users</h6>
                            <h3 class="fw-bold text-primary mb-0">1,247</h3>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 8% increase
                            </small>
                        </div>
                        <div class="user-stat-icon">
                            <i class="bi bi-people text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Active Users</h6>
                            <h3 class="fw-bold text-success mb-0">892</h3>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 12% increase
                            </small>
                        </div>
                        <div class="user-stat-icon">
                            <i class="bi bi-person-check text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">New This Month</h6>
                            <h3 class="fw-bold text-warning mb-0">156</h3>
                            <small class="text-warning">
                                <i class="bi bi-dash"></i> 2% decrease
                            </small>
                        </div>
                        <div class="user-stat-icon">
                            <i class="bi bi-person-plus text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Roles</h6>
                            <h3 class="fw-bold text-info mb-0">8</h3>
                            <small class="text-muted">
                                <i class="bi bi-dash"></i> No change
                            </small>
                        </div>
                        <div class="user-stat-icon">
                            <i class="bi bi-shield-check text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Search Users</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="userSearch" placeholder="Search by name or email...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Role</label>
                            <select class="form-select" id="roleFilter">
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="user">User</option>
                                <option value="guest">Guest</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Date Range</label>
                            <input type="text" class="form-control" id="dateFilter" placeholder="Select dates">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary w-100" id="applyFilters">
                                    <i class="bi bi-funnel me-2"></i>Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary" id="clearFilters">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0">Users List</h5>
                        <div class="d-flex gap-2">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="viewType" id="tableView" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="tableView">
                                    <i class="bi bi-table"></i>
                                </label>

                                <input type="radio" class="btn-check" name="viewType" id="cardView" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm" for="cardView">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div id="tableViewContent" class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th class="fw-semibold">User</th>
                                    <th class="fw-semibold">Role</th>
                                    <th class="fw-semibold">Status</th>
                                    <th class="fw-semibold">Last Active</th>
                                    <th class="fw-semibold">Join Date</th>
                                    <th class="fw-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input user-checkbox">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=50&h=50&fit=crop&crop=face"
                                                     alt="User" class="rounded-circle" width="40" height="40">
                                            </div>
                                            <div>
                                                <div class="fw-semibold">John Doe</div>
                                                <small class="text-muted">john.doe@example.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-danger-subtle text-danger">Admin</span></td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>2 hours ago</td>
                                    <td>Jan 15, 2024</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-key me-2"></i>Reset Password</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-ban me-2"></i>Suspend</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input user-checkbox">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=50&h=50&fit=crop&crop=face"
                                                     alt="User" class="rounded-circle" width="40" height="40">
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Jane Smith</div>
                                                <small class="text-muted">jane.smith@example.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary">Manager</span></td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>1 day ago</td>
                                    <td>Feb 08, 2024</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-key me-2"></i>Reset Password</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-ban me-2"></i>Suspend</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input user-checkbox">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=50&h=50&fit=crop&crop=face"
                                                     alt="User" class="rounded-circle" width="40" height="40">
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Mike Johnson</div>
                                                <small class="text-muted">mike.johnson@example.com</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success-subtle text-success">User</span></td>
                                    <td><span class="badge bg-warning">Inactive</span></td>
                                    <td>1 week ago</td>
                                    <td>Mar 22, 2024</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-key me-2"></i>Reset Password</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="bi bi-check-circle me-2"></i>Activate</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card View (Hidden by default) -->
                    <div id="cardViewContent" class="d-none">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="user-card">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face"
                                                 alt="User" class="rounded-circle mb-3" width="80" height="80">
                                            <h6 class="fw-bold mb-1">John Doe</h6>
                                            <p class="text-muted small mb-2">john.doe@example.com</p>
                                            <span class="badge bg-danger-subtle text-danger mb-3">Admin</span>
                                            <div class="d-flex justify-content-between text-muted small mb-3">
                                                <span>Last Active:</span>
                                                <span>2 hours ago</span>
                                            </div>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Repeat for other users -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <select class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="user">User</option>
                                    <option value="guest">Guest</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" class="form-control" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Add User</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .user-stat-icon {
        width: 50px;
        height: 50px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .user-avatar img {
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .user-card .card {
        border: none;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .user-card .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

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
            responsive: true,
            pageLength: 25,
            order: [[5, 'desc']], // Sort by join date
            columnDefs: [
                { orderable: false, targets: [0, 6] } // Disable sorting for checkbox and actions
            ]
        });

        // Select All Checkbox
        $('#selectAll').change(function() {
            $('.user-checkbox').prop('checked', this.checked);
        });

        // Individual Checkbox
        $('.user-checkbox').change(function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            }

            if ($('.user-checkbox:checked').length === $('.user-checkbox').length) {
                $('#selectAll').prop('checked', true);
            }
        });

        // View Toggle
        $('input[name="viewType"]').change(function() {
            if ($(this).attr('id') === 'tableView') {
                $('#tableViewContent').removeClass('d-none');
                $('#cardViewContent').addClass('d-none');
            } else {
                $('#tableViewContent').addClass('d-none');
                $('#cardViewContent').removeClass('d-none');
            }
        });

        // Filters
        $('#applyFilters').click(function() {
            // Apply filters logic here
            console.log('Applying filters...');
        });

        $('#clearFilters').click(function() {
            $('#userSearch').val('');
            $('#roleFilter').val('');
            $('#statusFilter').val('');
            $('#dateFilter').val('');
            usersTable.search('').draw();
        });

        // Real-time search
        $('#userSearch').on('keyup', function() {
            usersTable.search(this.value).draw();
        });

        // Filter by role
        $('#roleFilter').change(function() {
            const role = this.value;
            if (role) {
                usersTable.column(2).search(role).draw();
            } else {
                usersTable.column(2).search('').draw();
            }
        });

        // Filter by status
        $('#statusFilter').change(function() {
            const status = this.value;
            if (status) {
                usersTable.column(3).search(status).draw();
            } else {
                usersTable.column(3).search('').draw();
            }
        });

        // Date filter (you can implement date range picker here)
        $('#dateFilter').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('#dateFilter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        $('#dateFilter').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    });
</script>
@endpush

    </div>
@endsection
