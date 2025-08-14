@extends('layouts.dashboard')

@section('title', 'Data Integrity Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Integrity Report - {{ $business->name }}</h5>
                    <div>
                        <button class="btn btn-primary btn-sm" onclick="refreshReport()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-success btn-sm" onclick="downloadReport()">
                            <i class="bi bi-download"></i> Download Report
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Integrity Score -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">{{ number_format($report['statistics']['integrity_score'], 1) }}%</h3>
                                    <p class="mb-0">Integrity Score</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">{{ $report['statistics']['total_records'] }}</h3>
                                    <p class="mb-0">Total Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">{{ $report['statistics']['records_with_anomalies'] }}</h3>
                                    <p class="mb-0">Records with Issues</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0">{{ $report['statistics']['total_records'] - $report['statistics']['records_with_anomalies'] }}</h3>
                                    <p class="mb-0">Clean Records</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Anomalies Section -->
                    @if(!empty($report['anomalies']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Detected Anomalies ({{ count($report['anomalies']) }})
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($report['anomalies'] as $anomaly)
                                                <tr>
                                                    <td>{{ $anomaly['date'] }}</td>
                                                    <td>
                                                        <span class="badge bg-warning">{{ ucfirst($anomaly['type']) }}</span>
                                                    </td>
                                                    <td>{{ $anomaly['message'] }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" 
                                                                onclick="recoverData('{{ $anomaly['date'] }}', 'SalesData')">
                                                            <i class="bi bi-arrow-clockwise"></i> Recover
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <strong>Excellent!</strong> No data integrity issues detected.
                    </div>
                    @endif

                    <!-- Recommendations -->
                    @if(!empty($report['recommendations']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-lightbulb"></i>
                                        Recommendations
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @foreach($report['recommendations'] as $recommendation)
                                        <li class="list-group-item">
                                            <i class="bi bi-check2"></i> {{ $recommendation }}
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Backup History -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-shield-check"></i>
                                        Backup History (Last 7 Days)
                                    </h6>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="loadBackupHistory()">
                                        <i class="bi bi-refresh"></i> Refresh
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="backup-history-container">
                                        <div class="text-center">
                                            <div class="spinner-border" role="status">
                                                <span class="visually-hidden">Loading...</span>
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

<!-- Recovery Modal -->
<div class="modal fade" id="recoveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Recovery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will restore data from the most recent backup for the selected date.
                    Current data will be overwritten.
                </div>
                <p>Are you sure you want to recover data for <strong id="recovery-date"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmRecovery()">
                    <i class="bi bi-arrow-clockwise"></i> Recover Data
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentRecoveryDate = null;
let currentRecoveryType = null;

function refreshReport() {
    window.location.reload();
}

function downloadReport() {
    window.open("{{ route('data-integrity.download-report') }}", '_blank');
}

function recoverData(date, modelType) {
    currentRecoveryDate = date;
    currentRecoveryType = modelType;
    
    document.getElementById('recovery-date').textContent = date;
    
    const modal = new bootstrap.Modal(document.getElementById('recoveryModal'));
    modal.show();
}

function confirmRecovery() {
    if (!currentRecoveryDate || !currentRecoveryType) {
        return;
    }
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('recoveryModal'));
    modal.hide();
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Recovering...';
    button.disabled = true;
    
    fetch("{{ route('data-integrity.recover-data') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            date: currentRecoveryDate,
            model_type: currentRecoveryType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Recovery failed. Please try again.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function loadBackupHistory() {
    const container = document.getElementById('backup-history-container');
    container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    const endDate = new Date().toISOString().split('T')[0];
    const startDate = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    fetch(`{{ route('data-integrity.backup-history') }}?start_date=${startDate}&end_date=${endDate}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.backups.length > 0) {
            let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Action</th><th>User</th></tr></thead><tbody>';
            
            data.backups.forEach(backup => {
                html += `<tr>
                    <td>${new Date(backup.date).toLocaleString()}</td>
                    <td><span class="badge bg-secondary">${backup.model_type}</span></td>
                    <td><span class="badge bg-info">${backup.action}</span></td>
                    <td>${backup.user_name}</td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted text-center">No backup history found for the last 7 days.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<p class="text-danger text-center">Failed to load backup history.</p>';
    });
}

function showAlert(type, message) {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertContainer.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    setTimeout(() => {
        alertContainer.remove();
    }, 5000);
}

// Load backup history on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBackupHistory();
});
</script>
@endpush
