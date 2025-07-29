// Dashboard Metrics Records JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart periods
    initializeChartPeriods();

    // Initialize form calculations
    initializeFormCalculations();

    // Initialize date filters
    initializeDateFilters();
});

function initializeChartPeriods() {
    const periodButtons = document.querySelectorAll('[data-period]');

    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');

            // Update active button
            periodButtons.forEach(btn => btn.classList.remove('btn-primary'));
            periodButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            // Reload chart with new period
            loadChartData(period);
        });
    });
}

function loadChartData(period) {
    // Show loading state
    const chartContainer = document.getElementById('metricChart');
    chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="height: 350px;"><div class="spinner-border text-primary" role="status"></div></div>';

    // Fetch new data
    fetch(`${window.location.href}?period=${period}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            // Update chart
            updateChart(data.chartData);
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
            chartContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center text-danger" style="height: 350px;">Error loading chart data</div>';
        });
}

function updateChart(chartData) {
    // This will be implemented based on your chart library
    console.log('Updating chart with data:', chartData);
}

function initializeFormCalculations() {
    // Auto-calculate revenue from unit price and quantity
    const unitPriceInput = document.getElementById('unit_price');
    const quantityInput = document.getElementById('quantity_sold');
    const valueInput = document.getElementById('value');

    if (unitPriceInput && quantityInput && valueInput) {
        function calculateRevenue() {
            const unitPrice = parseFloat(unitPriceInput.value) || 0;
            const quantity = parseFloat(quantityInput.value) || 0;
            const revenue = unitPrice * quantity;

            valueInput.value = revenue.toFixed(2);

            // Also update revenue field if it exists
            const revenueInput = document.getElementById('total_revenue');
            if (revenueInput) {
                revenueInput.value = revenue.toFixed(2);
            }
        }

        unitPriceInput.addEventListener('input', calculateRevenue);
        quantityInput.addEventListener('input', calculateRevenue);
    }

    // Auto-calculate profit margin
    const revenueInput = document.getElementById('total_revenue');
    const cogsInput = document.getElementById('total_cogs');

    if (revenueInput && cogsInput) {
        function calculateMargin() {
            const revenue = parseFloat(revenueInput.value) || 0;
            const cogs = parseFloat(cogsInput.value) || 0;

            if (revenue > 0) {
                const margin = ((revenue - cogs) / revenue) * 100;

                // Show margin info
                showMarginInfo(margin, revenue - cogs);
            }
        }

        revenueInput.addEventListener('input', calculateMargin);
        cogsInput.addEventListener('input', calculateMargin);
    }
}

function showMarginInfo(margin, profit) {
    const existingInfo = document.getElementById('margin-info');
    if (existingInfo) {
        existingInfo.remove();
    }

    const marginInfo = document.createElement('div');
    marginInfo.id = 'margin-info';
    marginInfo.className = 'alert alert-info mt-2';
    marginInfo.innerHTML = `
        <small>
            <i class="bi bi-calculator me-1"></i>
            Laba Kotor: <strong>Rp ${profit.toLocaleString()}</strong> |
            Margin: <strong>${margin.toFixed(1)}%</strong>
        </small>
    `;

    const cogsInput = document.getElementById('total_cogs');
    if (cogsInput) {
        cogsInput.parentNode.parentNode.appendChild(marginInfo);
    }
}

function initializeDateFilters() {
    // Set default date to today
    const dateInput = document.getElementById('record_date');
    if (dateInput && !dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    }

    // Prevent future dates
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('max', today);
    }
}

// Validation functions
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Custom validations
    const valueField = form.querySelector('[name="value"]');
    if (valueField && parseFloat(valueField.value) < 0) {
        valueField.classList.add('is-invalid');
        isValid = false;
    }

    return isValid;
}

// Export functions
function exportData() {
    const params = new URLSearchParams({
        export: 'csv',
        period: document.querySelector('[data-period].btn-primary')?.getAttribute('data-period') || '30'
    });

    window.location.href = `${window.location.href}?${params.toString()}`;
}

function exportToExcel() {
    const params = new URLSearchParams({
        export: 'excel',
        period: document.querySelector('[data-period].btn-primary')?.getAttribute('data-period') || '30'
    });

    window.location.href = `${window.location.href}?${params.toString()}`;
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function formatPercentage(percentage) {
    return new Intl.NumberFormat('id-ID', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    }).format(percentage / 100);
}

// Error handling
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alert);

    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alert);

    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 3000);
}
