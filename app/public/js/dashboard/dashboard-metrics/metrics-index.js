// Dashboard Metrics Index JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable if metrics exist
    const metricsTable = document.getElementById('metricsTable');
    if (metricsTable && metricsTable.querySelector('tbody tr')) {
        const table = $('#metricsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[4, 'desc']], // Sort by updated at
            columnDefs: [
                { orderable: false, targets: [5] } // Disable sorting for actions column
            ],
            language: {
                search: "Search metrics:",
                lengthMenu: "Show _MENU_ metrics per page",
                info: "Showing _START_ to _END_ of _TOTAL_ metrics",
                infoEmpty: "No metrics found",
                infoFiltered: "(filtered from _MAX_ total metrics)",
                zeroRecords: "No matching metrics found",
                emptyTable: "No metrics available"
            }
        });

        // Custom search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                table.search(this.value).draw();
            });
        }

        // Category filter
        const categoryFilter = document.querySelector('select[name="category"]');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', function() {
                const selectedCategory = this.value;
                if (selectedCategory) {
                    table.column(0).search(selectedCategory).draw();
                } else {
                    table.column(0).search('').draw();
                }
            });
        }
    }

    // Initialize DateRangePicker
    const dateRangePicker = document.getElementById('daterangepicker');
    if (dateRangePicker && typeof $ !== 'undefined' && $.fn.daterangepicker) {
        $(dateRangePicker).daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        $(dateRangePicker).on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            // Here you can add logic to filter metrics by date range
            filterMetricsByDateRange(picker.startDate, picker.endDate);
        });

        $(dateRangePicker).on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            // Clear date filter
            clearDateFilter();
        });
    }

    // Metric details modal/popup functionality
    window.viewMetricDetails = function(metricId) {
        // Here you can implement a modal to show metric details
        // For now, let's just show an alert with the metric ID
        console.log('View details for metric ID:', metricId);

        // You could make an AJAX call to get metric details
        // and show them in a modal
        showMetricDetailsModal(metricId);
    };

    function showMetricDetailsModal(metricId) {
        // Create and show a modal with metric details
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'metricDetailsModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Metric Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Clean up modal when hidden
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modal);
        });

        // Load metric details (you can implement this with an actual API call)
        loadMetricDetails(metricId, modal);
    }

    function loadMetricDetails(metricId, modal) {
        // Simulate API call - replace with actual implementation
        setTimeout(() => {
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Metric Information</h6>
                        <p><strong>Name:</strong> Sample Metric</p>
                        <p><strong>Category:</strong> Sales</p>
                        <p><strong>Current Value:</strong> 1,234</p>
                        <p><strong>Previous Value:</strong> 1,100</p>
                        <p><strong>Change:</strong> <span class="text-success">+12.2%</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Recent Activity</h6>
                        <div class="timeline">
                            <div class="timeline-item">
                                <small class="text-muted">2 hours ago</small>
                                <p>Value updated to 1,234</p>
                            </div>
                            <div class="timeline-item">
                                <small class="text-muted">1 day ago</small>
                                <p>Previous value was 1,100</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }, 1000);
    }

    function filterMetricsByDateRange(startDate, endDate) {
        // Implement date range filtering logic
        console.log('Filtering metrics from', startDate.format('YYYY-MM-DD'), 'to', endDate.format('YYYY-MM-DD'));

        // You can add custom filtering logic here
        // For example, hide/show table rows based on date range
        const table = $('#metricsTable').DataTable();

        // Custom date range filter
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const createdAt = moment(data[3], 'MMM DD, YYYY'); // Assuming created at is in column 3
            return createdAt.isBetween(startDate, endDate, null, '[]');
        });

        table.draw();
    }

    function clearDateFilter() {
        // Clear custom date filter
        $.fn.dataTable.ext.search.pop();
        const table = $('#metricsTable').DataTable();
        table.draw();
    }

    // Floating action button animation
    const floatingBtn = document.querySelector('.floating-add-btn .btn');
    if (floatingBtn) {
        floatingBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(90deg)';
        });

        floatingBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    }

    // Confirm delete actions
    const deleteForms = document.querySelectorAll('form[method="POST"]');
    deleteForms.forEach(form => {
        const deleteButton = form.querySelector('button[type="submit"]');
        if (deleteButton && deleteButton.querySelector('.bi-trash')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (confirm('Are you sure you want to delete this metric? This action cannot be undone.')) {
                    // Show loading state
                    deleteButton.disabled = true;
                    deleteButton.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';

                    // Submit form
                    this.submit();
                }
            });
        }
    });

    // Add hover effects to metric cards
    const metricRows = document.querySelectorAll('#metricsTable tbody tr');
    metricRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(124, 185, 71, 0.1)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Success/Error message auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});
