// Dashboard Metrics Index JavaScript

$(document).ready(function() {
    // Initialize DataTables
    const metricsTable = $('#metricsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            search: "",
            searchPlaceholder: "Search metrics...",
            lengthMenu: "Show _MENU_ metrics",
            info: "Showing _START_ to _END_ of _TOTAL_ metrics",
            infoEmpty: "No metrics found",
            infoFiltered: "(filtered from _MAX_ total metrics)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        order: [[4, 'desc']], // Order by Last Updated column
        columnDefs: [
            { orderable: false, targets: -1 } // Disable ordering on Actions column
        ]
    });

    // Custom search functionality
    $('#searchInput').on('keyup', function() {
        metricsTable.search(this.value).draw();
    });

    // Category filter functionality
    $('#categoryFilter').on('change', function() {
        const selectedCategory = this.value;

        if (selectedCategory === '') {
            // Show all rows
            $('tbody tr').show();
        } else {
            // Hide all rows first
            $('tbody tr').hide();
            // Show only rows with matching category
            $('tbody tr[data-category="' + selectedCategory + '"]').show();
        }
    });
            },
            { data: 'last_updated' },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="editMetric(${data})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="viewMetric(${data})" title="View">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMetric(${data})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search metrics...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            },
            emptyTable: 'No metrics available'
        },
        drawCallback: function() {
            // Initialize tooltips after table redraw
            $('[title]').tooltip();
        }
    });

    // Initialize DateRangePicker
    $('#daterangepicker').daterangepicker({
        opens: 'left',
        autoUpdateInput: true,
        locale: {
            format: 'YYYY-MM-DD',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(30, 'days'),
        endDate: moment()
    });

    // Date range filter
    $('#daterangepicker').on('apply.daterangepicker', function(ev, picker) {
        const startDate = picker.startDate.format('YYYY-MM-DD');
        const endDate = picker.endDate.format('YYYY-MM-DD');

        // Filter table data by date range
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            // You can implement date filtering logic here
            return true; // For now, show all data
        });

        metricsTable.draw();
    });

    // Category filter
    $('.form-select').on('change', function() {
        const category = $(this).val();
        if (category === 'All Categories') {
            metricsTable.column(1).search('').draw();
        } else {
            metricsTable.column(1).search(category).draw();
        }
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Search functionality
    $('.form-control[placeholder="Search metrics..."]').on('keyup', function() {
        metricsTable.search(this.value).draw();
    });
});

// Sample data function
function getSampleData() {
    return [
        {
            id: 1,
            metric_name: 'Total Sales',
            category: 'Revenue',
            current_value: '$84,500',
            target: '$90,000',
            progress: 75,
            status: 'On Track',
            last_updated: '2 hours ago'
        },
        {
            id: 2,
            metric_name: 'New Customers',
            category: 'Customer',
            current_value: '156',
            target: '200',
            progress: 78,
            status: 'On Track',
            last_updated: '1 day ago'
        },
        {
            id: 3,
            metric_name: 'Conversion Rate',
            category: 'Marketing',
            current_value: '3.2%',
            target: '4.0%',
            progress: 80,
            status: 'Good',
            last_updated: '3 hours ago'
        },
        {
            id: 4,
            metric_name: 'Average Order Value',
            category: 'Revenue',
            current_value: '$542',
            target: '$600',
            progress: 90,
            status: 'Excellent',
            last_updated: '5 hours ago'
        },
        {
            id: 5,
            metric_name: 'Customer Retention',
            category: 'Customer',
            current_value: '87%',
            target: '90%',
            progress: 97,
            status: 'On Track',
            last_updated: '1 day ago'
        }
    ];
}

// Helper functions for styling
function getCategoryBadgeClass(category) {
    const classes = {
        'Revenue': 'bg-success-subtle text-success',
        'Customer': 'bg-primary-subtle text-primary',
        'Marketing': 'bg-warning-subtle text-warning',
        'Operations': 'bg-info-subtle text-info'
    };
    return classes[category] || 'bg-secondary-subtle text-secondary';
}

function getProgressBarClass(progress) {
    if (progress >= 80) return 'bg-success';
    if (progress >= 60) return 'bg-warning';
    return 'bg-danger';
}

function getStatusBadgeClass(status) {
    const classes = {
        'On Track': 'bg-success',
        'Good': 'bg-primary',
        'Excellent': 'bg-success',
        'Warning': 'bg-warning',
        'Critical': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}

// CRUD Operations
function editMetric(id) {
    window.location.href = `/dashboard/metrics/${id}/edit`;
}

function viewMetric(id) {
    window.location.href = `/dashboard/metrics/${id}`;
}

function deleteMetric(id) {
    // Show confirmation dialog
    if (!confirm('Are you sure you want to delete this metric? This action cannot be undone.')) {
        return;
    }

    // Show loading state
    const button = $(`button[onclick="deleteMetric(${id})"]`);
    const originalHtml = button.html();
    button.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);

    // Send AJAX request to delete metric
    $.ajax({
        url: `/dashboard/metrics/${id}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                // Remove row from table
                const table = $('#metricsTable').DataTable();
                button.closest('tr').fadeOut(400, function() {
                    table.row($(this)).remove().draw();
                });

                // Show success notification
                showNotification(response.message || 'Metric deleted successfully!', 'success');

                // Check if no metrics left and redirect to empty state
                setTimeout(() => {
                    if (table.rows().count() === 0) {
                        window.location.reload();
                    }
                }, 500);
            } else {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('Failed to delete metric. Please try again.', 'error');
            }
        },
        error: function(xhr, status, error) {
            // Restore button state
            button.html(originalHtml).prop('disabled', false);

            let errorMessage = 'Failed to delete metric. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            showNotification(errorMessage, 'error');
            console.error('Delete metric error:', error);
        }
    });
}

// Notification function
function showNotification(message, type = 'info') {
    let alertClass = 'alert-info';
    let iconClass = 'bi-info-circle';

    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            iconClass = 'bi-check-circle';
            break;
        case 'error':
        case 'danger':
            alertClass = 'alert-danger';
            iconClass = 'bi-exclamation-triangle';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            iconClass = 'bi-exclamation-triangle';
            break;
    }

    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="bi ${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.alert('close');
    }, 5000);
}
