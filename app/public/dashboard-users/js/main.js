// Dashboard Users - Main functionality
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
            url: usersDataRoute,
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
        ].concat(showActions ? [{
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
        }] : []),
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

    // Business Owner specific functions
    if (isBusinessOwner) {
        // Load business codes
        loadBusinessCodes();

        function loadBusinessCodes() {
            fetch(businessCodesRoute)
                .then(response => response.json())
                .then(data => {
                    if (data.public_id && data.invitation_code) {
                        document.getElementById('businessPublicId').textContent = data.public_id;
                        document.getElementById('businessInviteCode').textContent = data.invitation_code;
                        if (document.getElementById('modalPublicId')) {
                            document.getElementById('modalPublicId').value = data.public_id;
                        }
                        if (document.getElementById('modalInviteCode')) {
                            document.getElementById('modalInviteCode').value = data.invitation_code;
                        }
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
        const refreshBtn = document.getElementById('refreshInviteCode');
        const regenerateBtn = document.getElementById('regenerateInviteCodeModal');
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', regenerateInviteCode);
        }
        if (regenerateBtn) {
            regenerateBtn.addEventListener('click', regenerateInviteCode);
        }

        function regenerateInviteCode() {
            if (confirm('Are you sure you want to regenerate the invitation code? The old code will no longer work.')) {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise fa-spin me-2"></i>Regenerating...';
                btn.disabled = true;

                fetch(regenerateInviteRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('businessInviteCode').textContent = data.new_code;
                        if (document.getElementById('modalInviteCode')) {
                            document.getElementById('modalInviteCode').value = data.new_code;
                        }
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
    }

    // User management functions
    if (canManageUsers) {
        // Promote user function
        window.promoteUser = function(userId) {
            if (confirm('Are you sure you want to promote this user to Administrator?')) {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-arrow-up-circle fa-spin"></i> Promoting...';
                btn.disabled = true;

                fetch(`${usersBaseUrl}/${userId}/promote`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
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

                fetch(`${usersBaseUrl}/${userId}/remove`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
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
    }

    // Refresh table data
    window.refreshTable = function() {
        usersTable.ajax.reload();
        toastr.info('Users table refreshed');
    };
});