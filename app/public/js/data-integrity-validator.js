/**
 * Enhanced Data Validation for Customer and Sales Forms
 * Mencegah data loss dengan validasi real-time dan konfirmasi
 */

class DataIntegrityValidator {
    constructor() {
        this.previousData = {};
        this.validationRules = {
            customer: {
                maxChangePercentage: 50,
                minTotal: 0,
                warnOnDecrease: 0.7 // Warn if decrease more than 30%
            },
            sales: {
                maxChangePercentage: 200,
                minRevenue: 0,
                maxCogsPercentage: 100 // COGS should not exceed 100% of revenue
            }
        };
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPreviousData();
    }

    bindEvents() {
        // Customer form validation
        $(document).on('input', '#new_customer_count, #total_customer_count', (e) => {
            this.validateCustomerData();
        });

        // Sales form validation
        $(document).on('input', '#total_revenue, #total_cogs', (e) => {
            this.validateSalesData();
        });

        // Form submission validation
        $(document).on('submit', '.metric-form', (e) => {
            if (!this.validateBeforeSubmit()) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time calculation updates
        $(document).on('input', '.auto-calculate', (e) => {
            this.updateCalculations();
        });
    }

    async loadPreviousData() {
        try {
            const metricId = $('input[name="business_metric_id"]').val();
            if (!metricId) return;

            const response = await fetch(`/dashboard/metrics/${metricId}/previous-data`);
            if (response.ok) {
                this.previousData = await response.json();
            }
        } catch (error) {
            console.warn('Could not load previous data:', error);
        }
    }

    validateCustomerData() {
        const newCustomers = parseInt($('#new_customer_count').val()) || 0;
        const totalCustomers = parseInt($('#total_customer_count').val()) || 0;
        
        this.clearValidationErrors(['new_customer_count', 'total_customer_count']);

        // Basic validation
        if (newCustomers < 0) {
            this.showValidationError('new_customer_count', 'Jumlah pelanggan baru tidak boleh negatif');
            return false;
        }

        if (totalCustomers < 0) {
            this.showValidationError('total_customer_count', 'Total pelanggan tidak boleh negatif');
            return false;
        }

        if (newCustomers > totalCustomers) {
            this.showValidationError('new_customer_count', 'Pelanggan baru tidak boleh lebih besar dari total pelanggan');
            return false;
        }

        // Advanced validation against previous data
        if (this.previousData.total_customer_count) {
            const previousTotal = this.previousData.total_customer_count;
            const changePercentage = Math.abs(totalCustomers - previousTotal) / previousTotal * 100;

            if (changePercentage > this.validationRules.customer.maxChangePercentage) {
                this.showValidationWarning('total_customer_count', 
                    `Perubahan sangat besar (${changePercentage.toFixed(1)}%). Apakah data ini benar?`);
            }

            if (totalCustomers < previousTotal * this.validationRules.customer.warnOnDecrease) {
                this.showValidationWarning('total_customer_count', 
                    'Total pelanggan turun drastis. Periksa kembali data Anda.');
            }
        }

        // Update customer loyalty percentage
        this.updateCustomerLoyaltyPercentage(newCustomers, totalCustomers);
        
        return true;
    }

    validateSalesData() {
        const totalRevenue = parseFloat($('#total_revenue').val()) || 0;
        const totalCogs = parseFloat($('#total_cogs').val()) || 0;

        this.clearValidationErrors(['total_revenue', 'total_cogs']);

        // Basic validation
        if (totalRevenue < 0) {
            this.showValidationError('total_revenue', 'Total revenue tidak boleh negatif');
            return false;
        }

        if (totalCogs < 0) {
            this.showValidationError('total_cogs', 'Total COGS tidak boleh negatif');
            return false;
        }

        if (totalCogs > totalRevenue && totalRevenue > 0) {
            this.showValidationError('total_cogs', 'COGS tidak boleh lebih besar dari revenue');
            return false;
        }

        // Advanced validation against previous data
        if (this.previousData.total_revenue && this.previousData.total_revenue > 0) {
            const previousRevenue = this.previousData.total_revenue;
            const changePercentage = Math.abs(totalRevenue - previousRevenue) / previousRevenue * 100;

            if (changePercentage > this.validationRules.sales.maxChangePercentage) {
                this.showValidationWarning('total_revenue', 
                    `Perubahan revenue sangat besar (${changePercentage.toFixed(1)}%). Verifikasi data Anda.`);
            }
        }

        // Update profit margin
        this.updateProfitMargin(totalRevenue, totalCogs);

        return true;
    }

    validateBeforeSubmit() {
        const form = event.target;
        const formData = new FormData(form);
        const metricType = $(form).find('input[name="metric_type"]').val();

        // Get all validation warnings
        const warnings = $(form).find('.validation-warning').length;
        
        if (warnings > 0) {
            return this.showConfirmationDialog(
                'Peringatan Validasi',
                'Terdapat peringatan pada data yang Anda masukkan. Apakah Anda yakin ingin melanjutkan?',
                'Data mungkin tidak akurat dan dapat mempengaruhi analisis bisnis Anda.'
            );
        }

        // Check for significant changes
        if (this.hasSignificantChanges(formData, metricType)) {
            return this.showConfirmationDialog(
                'Perubahan Signifikan',
                'Data yang Anda masukkan sangat berbeda dari data sebelumnya. Apakah Anda yakin?',
                'Perubahan besar dapat mengindikasikan kesalahan input atau perubahan bisnis yang signifikan.'
            );
        }

        return true;
    }

    hasSignificantChanges(formData, metricType) {
        if (!this.previousData || Object.keys(this.previousData).length === 0) {
            return false;
        }

        switch (metricType) {
            case 'customer':
                const totalCustomers = parseInt(formData.get('total_customer_count')) || 0;
                const previousTotal = this.previousData.total_customer_count || 0;
                return previousTotal > 0 && Math.abs(totalCustomers - previousTotal) / previousTotal > 0.5;

            case 'sales':
                const revenue = parseFloat(formData.get('total_revenue')) || 0;
                const previousRevenue = this.previousData.total_revenue || 0;
                return previousRevenue > 0 && Math.abs(revenue - previousRevenue) / previousRevenue > 1.0;

            default:
                return false;
        }
    }

    updateCustomerLoyaltyPercentage(newCustomers, totalCustomers) {
        if (totalCustomers > 0) {
            const loyalCustomers = totalCustomers - newCustomers;
            const loyaltyPercentage = (loyalCustomers / totalCustomers * 100).toFixed(1);
            
            $('#loyalty_percentage_display').text(`${loyaltyPercentage}%`);
            $('#loyalty_calculation_info').html(`
                <small class="text-muted">
                    Perhitungan: (${totalCustomers} - ${newCustomers}) / ${totalCustomers} × 100% = ${loyaltyPercentage}%
                </small>
            `);
        }
    }

    updateProfitMargin(revenue, cogs) {
        if (revenue > 0) {
            const margin = ((revenue - cogs) / revenue * 100).toFixed(1);
            const marginClass = margin > 20 ? 'text-success' : margin > 10 ? 'text-warning' : 'text-danger';
            
            $('#margin_percentage_display').html(`<span class="${marginClass}">${margin}%</span>`);
            $('#margin_calculation_info').html(`
                <small class="text-muted">
                    Perhitungan: (${this.formatCurrency(revenue)} - ${this.formatCurrency(cogs)}) / ${this.formatCurrency(revenue)} × 100% = ${margin}%
                </small>
            `);
        }
    }

    updateCalculations() {
        // Update any auto-calculations based on current form values
        this.validateCustomerData();
        this.validateSalesData();
    }

    showValidationError(fieldName, message) {
        const field = $(`#${fieldName}`);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.siblings('.invalid-feedback').remove();
        
        // Add new error message
        field.after(`<div class="invalid-feedback">${message}</div>`);
    }

    showValidationWarning(fieldName, message) {
        const field = $(`#${fieldName}`);
        field.addClass('border-warning');
        
        // Remove existing warning
        field.siblings('.validation-warning').remove();
        
        // Add warning message
        field.after(`<div class="validation-warning text-warning small mt-1">
            <i class="bi bi-exclamation-triangle"></i> ${message}
        </div>`);
    }

    clearValidationErrors(fieldNames) {
        fieldNames.forEach(fieldName => {
            const field = $(`#${fieldName}`);
            field.removeClass('is-invalid border-warning');
            field.siblings('.invalid-feedback, .validation-warning').remove();
        });
    }

    showConfirmationDialog(title, message, details) {
        return new Promise((resolve) => {
            const modal = $(`
                <div class="modal fade" id="validationConfirmModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <strong>${message}</strong>
                                </div>
                                <p class="mb-0">${details}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Batal & Periksa Lagi
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmSubmit">
                                    Ya, Lanjutkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(modal);
            
            const modalInstance = new bootstrap.Modal(modal[0]);
            modalInstance.show();

            $('#confirmSubmit').on('click', () => {
                modalInstance.hide();
                modal.remove();
                resolve(true);
            });

            modal.on('hidden.bs.modal', () => {
                modal.remove();
                resolve(false);
            });
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }

    // Method untuk backup data sebelum submit
    async backupBeforeSubmit(formData) {
        try {
            const response = await fetch('/api/backup-before-submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    form_data: Object.fromEntries(formData),
                    backup_reason: 'before_form_submit'
                })
            });

            if (!response.ok) {
                console.warn('Backup failed, but continuing with submission');
            }
        } catch (error) {
            console.warn('Backup error:', error);
        }
    }
}

// Initialize validator when document is ready
$(document).ready(function() {
    window.dataValidator = new DataIntegrityValidator();
    
    // Add global success handler for form submissions
    $(document).on('ajax:success', '.metric-form', function(event, data) {
        if (data.success) {
            // Show success message
            showAlert('success', data.message);
            
            // Update previous data for next validation
            window.dataValidator.loadPreviousData();
        }
    });
});

// Utility function to show alerts
function showAlert(type, message) {
    const alertContainer = $(`
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(alertContainer);
    
    setTimeout(() => {
        alertContainer.alert('close');
    }, 5000);
}
