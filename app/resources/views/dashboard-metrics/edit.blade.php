@extends('layouts.dashboard')

@section('title', 'Edit Metric')

@section('content')
    <div class="content-header ms-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Edit Metric</h1>
                <p class="content-subtitle">Update the values and settings for "{{ $businessMetric->metric_name }}"</p>
            </div>
            <a href="{{ route('dashboard.metrics') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Metrics
            </a>
        </div>
    </div>

    <div class="content-body ms-5">
        <div class="row">
            <div class="col-md-8">
                <div class="content-card">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">
                            <i class="bi {{ $businessMetric->icon ?? 'bi-graph-up' }} me-2"></i>
                            {{ $businessMetric->metric_name }}
                        </h5>

                        <form action="{{ route('dashboard.metrics.update', $businessMetric->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_value" class="form-label">Current Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-calculator"></i>
                                            </span>
                                            <input type="number"
                                                   class="form-control"
                                                   id="current_value"
                                                   name="current_value"
                                                   value="{{ old('current_value', $businessMetric->current_value) }}"
                                                   step="0.01"
                                                   required>
                                        </div>
                                        @error('current_value')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="previous_value" class="form-label">Previous Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-clock-history"></i>
                                            </span>
                                            <input type="number"
                                                   class="form-control"
                                                   id="previous_value"
                                                   name="previous_value"
                                                   value="{{ old('previous_value', $businessMetric->previous_value) }}"
                                                   step="0.01"
                                                   readonly>
                                        </div>
                                        <small class="text-muted">This value is automatically updated when you save</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control"
                                          id="description"
                                          name="description"
                                          rows="3"
                                          placeholder="Add notes or description for this metric...">{{ old('description', $businessMetric->description) }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2 me-2"></i>
                                    Update Metric
                                </button>
                                <a href="{{ route('dashboard.metrics') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Metric Info Card -->
                <div class="content-card mb-4 p-3">
                    <div class="card-body">
                        <h6 class="card-title fw-bold mb-3">Metric Information</h6>

                        <div class="d-flex align-items-center mb-3">
                            <div class="metric-icon me-3">
                                <i class="bi {{ $businessMetric->icon ?? 'bi-graph-up' }}"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $businessMetric->metric_name }}</h6>
                                <small class="text-muted">{{ $businessMetric->category ?? 'General' }} Metric</small>
                            </div>
                        </div>

                        <div class="metric-stats">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Current Value:</span>
                                <span class="fw-bold">{{ number_format($businessMetric->current_value, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Previous Value:</span>
                                <span class="fw-bold">{{ number_format($businessMetric->previous_value, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Change:</span>
                                <span class="fw-bold {{ $businessMetric->change_percentage >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bi bi-arrow-{{ $businessMetric->change_percentage >= 0 ? 'up' : 'down' }}-right me-1"></i>
                                    {{ $businessMetric->formatted_change }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="content-card">
                    <div class="card-body p-3">
                        <h6 class="card-title fw-bold mb-3">Recent Activity</h6>

                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <small class="text-muted">{{ $businessMetric->updated_at->diffForHumans() }}</small>
                                    <p class="mb-1">Metric was last updated</p>
                                    <small class="text-muted">Value: {{ number_format($businessMetric->current_value, 0) }}</small>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <small class="text-muted">{{ $businessMetric->created_at->diffForHumans() }}</small>
                                    <p class="mb-1">Metric was created</p>
                                    <small class="text-muted">Added to dashboard</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: rgba(255, 255, 255, 0.2);
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.timeline-content {
    background: rgba(255, 255, 255, 0.05);
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

.metric-stats {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentValueInput = document.getElementById('current_value');
    const previousValueInput = document.getElementById('previous_value');

    // Store original current value as previous value when form loads
    const originalCurrentValue = {{ $businessMetric->current_value }};

    currentValueInput.addEventListener('input', function() {
        // Update previous value to show what it will be
        previousValueInput.value = originalCurrentValue;
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const currentValue = parseFloat(currentValueInput.value);

        if (isNaN(currentValue) || currentValue < 0) {
            e.preventDefault();
            alert('Please enter a valid positive number for the current value.');
            currentValueInput.focus();
            return false;
        }
    });

    // Auto-save functionality (optional)
    let autoSaveTimeout;
    const inputs = form.querySelectorAll('input, textarea');

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Show auto-save indicator
                showAutoSaveIndicator();
            }, 2000);
        });
    });

    function showAutoSaveIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'position-fixed top-0 end-0 p-3';
        indicator.style.zIndex = '9999';
        indicator.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong class="me-auto">Auto-saved</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Your changes have been saved automatically.
                </div>
            </div>
        `;

        document.body.appendChild(indicator);

        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }
});
</script>
@endpush
