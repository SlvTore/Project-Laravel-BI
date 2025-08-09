@extends('layouts.dashboard')

@section('title', 'Activities')

@section('content')
<!-- Centered Header -->
<div class="content-header text-center mb-4">
    <h1 class="display-5 fw-bold text-white mb-2">Activities</h1>
    <p class="lead text-white">Stay updated with your business activities and insights</p>
</div>

<div class="content-body ms-5">
    <div class="row g-4">
        <!-- Main Activities Section -->
        <div class="col-12">
            <div class="content-card p-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Activities
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <!-- Date Range Picker -->
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-white text-white">
                                <i class="bi bi-calendar3"></i>
                            </span>
                            <input type="text" id="daterangepicker" class="form-control bg-transparent border-white text-white" 
                                   style="min-width: 280px; cursor: pointer;"
                                   placeholder="Select date range"
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="bottom"
                                   title="Click to select date range for filtering activities"
                                   readonly>
                            <span class="input-group-text bg-transparent border-white text-white">
                                <span id="activity-count" class="badge bg-primary" title="Visible activities">
                                    {{ $activities->count() + $alerts->count() }}
                                </span>
                            </span>
                        </div>

                        <button type="button" class="btn btn-outline-light" onclick="refreshFeeds()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Refresh
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel me-1"></i>
                                Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="filterActivities('all')">All Activities</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterActivities('user_joined')">New Users</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterActivities('data_input')">Data Updates</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterActivities('promotion')">Role Changes</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterActivities('auth')">Login/Register</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="activity-timeline" class="timeline">
                        @if($alerts->count() > 0 || $activities->count() > 0)
                            @php
                                $allItems = collect();

                                                            // Merge alerts only when today is within selected date range
                                                            $startSel = \Carbon\Carbon::parse($startDate);
                                                            $endSel = \Carbon\Carbon::parse($endDate);
                                                            if ($startSel->lte(\Carbon\Carbon::today()) && $endSel->gte(\Carbon\Carbon::today())) {
                                                                foreach($alerts as $alert) {
                                                                    $allItems->push([
                                                                        'type' => 'alert',
                                                                        'title' => $alert['title'],
                                                                        'description' => $alert['description'],
                                                                        'time' => now(),
                                                                        'icon' => $alert['icon'],
                                                                        'color' => $alert['severity'],
                                                                        'is_alert' => true,
                                                                        'change' => $alert['change'] ?? null,
                                                                        'date_group' => now()->format('Y-m-d')
                                                                    ]);
                                                                }
                                                            }

                                foreach($activities as $activity) {
                                    $allItems->push($activity);
                                }

                                // Group by date and limit to today + 2 previous days
                                $groupedItems = $allItems->groupBy('date_group')->sortKeysDesc();
                                $limitedDates = $groupedItems->keys()->take(3);
                            @endphp

                            @foreach($groupedItems as $date => $dateItems)
                                @if($limitedDates->contains($date))
                                    <!-- Date Divider -->
                                    <div class="timeline-date-divider text-white m-3">
                                        <div class="date-line"></div>
                                        <div class="date-badge">
                                            {{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}
                                        </div>
                                        <div class="date-line"></div>
                                    </div>

                                    @php
                                        $visibleItems = $dateItems->take(3);
                                        $hiddenItems = $dateItems->slice(3);
                                        $collapseId = 'collapse-' . str_replace('-', '', $date);
                                    @endphp

                                    @foreach($visibleItems as $item)
                                        <div class="timeline-item" data-type="{{ $item['type'] }}">
                                            <div class="timeline-marker bg-{{ $item['color'] }}">
                                                <i class="{{ $item['icon'] }}"></i>
                                            </div>
                                            <div class="timeline-content text-white">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="timeline-title">{{ $item['title'] }}</h6>
                                                        <p class="timeline-description">{{ $item['description'] }}</p>

                                                        @if(isset($item['is_alert']) && $item['is_alert'])
                                                            <span class="badge bg-{{ $item['color'] }} me-2">
                                                                <i class="bi bi-exclamation-triangle me-1"></i>Alert
                                                            </span>
                                                            @if(isset($item['change']))
                                                                <span class="badge bg-secondary">
                                                                    {{ $item['change'] > 0 ? '+' : '' }}{{ round($item['change'], 1) }}%
                                                                </span>
                                                            @endif
                                                        @else
                                                            @if(isset($item['value']))
                                                                <span class="badge bg-secondary">
                                                                    Value: {{ number_format($item['value']) }}
                                                                </span>
                                                            @endif
                                                            @if(isset($item['user']) && $item['user'])
                                                                <small class="text-muted d-block">
                                                                    <i class="bi bi-person me-1"></i>{{ $item['user']->name }}
                                                                </small>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <small class="text-muted timeline-time">
                                                        {{ \Carbon\Carbon::parse($item['time'])->format('H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($hiddenItems->count() > 0)
                                        <div class="collapse" id="{{ $collapseId }}">
                                            @foreach($hiddenItems as $item)
                                                <div class="timeline-item" data-type="{{ $item['type'] }}">
                                                    <div class="timeline-marker bg-{{ $item['color'] }}">
                                                        <i class="{{ $item['icon'] }}"></i>
                                                    </div>
                                                    <div class="timeline-content text-white">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="timeline-title">{{ $item['title'] }}</h6>
                                                                <p class="timeline-description">{{ $item['description'] }}</p>
                                                            </div>
                                                            <small class="text-muted timeline-time">
                                                                {{ \Carbon\Carbon::parse($item['time'])->format('H:i') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="text-center mb-4">
                                            <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                <i class="bi bi-plus-circle me-1"></i>
                                                Load more activity
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-activity display-4"></i>
                                <p class="mt-2">No activities found for the selected date range</p>
                            </div>
                        @endif
                    </div>

                    <!-- Removed global Load More; per-date collapse is used instead -->
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Sidebar -->
        <div class="col-lg-10 mx-auto mt-2">
            <div class="row g-3">
            <!-- Quick Stats -->
            <div class="col-md-6 mb-3">
            <div class="content-card p-3 h-100">
            <div class="card-header">
                <h6 class="card-title">
                <i class="bi bi-speedometer2 me-2"></i>
                Quick Stats
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                <div class="col-6 ">
                <div class="stat-item text-center">
                <div class="stat-number text-primary">{{ $business->users->count() }}</div>
                <div class="stat-label text-white">Team Members</div>
                </div>
                </div>
                <div class="col-6">
                <div class="stat-item text-center">
                <div class="stat-number text-success">{{ $business->metrics()->where('is_active', true)->count() }}</div>
                <div class="stat-label text-white">Active Metrics</div>
                </div>
                </div>
                </div>
            </div>
            </div>
            </div>

            <!-- Metric Insights -->
            <div class="col-md-6 mb-3">
            <div class="content-card p-3 h-100">
            <div class="card-header">
                <h6 class="card-title">
                <i class="bi bi-graph-up me-2"></i>
                Metric Insights
                </h6>
            </div>
            <div class="card-body">
                @if($insights->count() > 0)
                @foreach($insights as $insight)
                <div class="insight-item mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                    <h6 class="mb-1">{{ $insight['metric']->metric_name }}</h6>
                    <small class="text-muted">Latest: {{ number_format($insight['latest_value']) }}</small>
                    </div>
                    <div class="text-end">
                    @if($insight['trend'] === 'increasing')
                    <span class="badge bg-success">
                    <i class="bi bi-trending-up"></i> Rising
                    </span>
                    @elseif($insight['trend'] === 'decreasing')
                    <span class="badge bg-danger">
                    <i class="bi bi-trending-down"></i> Falling
                    </span>
                    @else
                    <span class="badge bg-secondary">
                    <i class="bi bi-dash"></i> Stable
                    </span>
                    @endif
                    </div>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-{{ $insight['trend'] === 'increasing' ? 'success' : ($insight['trend'] === 'decreasing' ? 'danger' : 'secondary') }}"
                    role="progressbar" style="width: {{ min(100, $insight['records_count'] * 5) }}%"></div>
                </div>
                </div>
                @endforeach
                @else
                <div class="text-center text-white">
                <i class="bi bi-graph-up display-6"></i>
                <p class="mt-2 small">Add some data to see insights</p>
                </div>
                @endif
            </div>
            </div>
            </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<!-- DateRangePicker CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<style>
/* Transparent daterange input with white border */
.input-group.input-group-sm .form-control#daterangepicker {
    background-color: transparent !important;
    color: #ffffff !important;
    border-color: #ffffff !important;
}
.input-group.input-group-sm .input-group-text {
    background-color: transparent !important;
    color: #ffffff !important;
    border-color: #ffffff !important;
}
.input-group.input-group-sm .form-control#daterangepicker::placeholder {
    color: rgba(255,255,255,0.7) !important;
}
.input-group.input-group-sm .form-control#daterangepicker:focus {
    box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25) !important;
}
/* DateRangePicker Dark Theme */
.daterangepicker {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
}

.daterangepicker .calendar-table {
    background: #2d3748 !important;
    border: 1px solid #4a5568 !important;
}

.daterangepicker .calendar-table th,
.daterangepicker .calendar-table td {
    color: #e2e8f0 !important;
    border: 1px solid #4a5568 !important;
}

.daterangepicker .calendar-table td.available:hover {
    background: #4a5568 !important;
    color: white !important;
}

.daterangepicker .calendar-table td.active,
.daterangepicker .calendar-table td.active:hover {
    background: #3182ce !important;
    color: white !important;
}

.daterangepicker .calendar-table td.in-range {
    background: rgba(49, 130, 206, 0.2) !important;
    color: #e2e8f0 !important;
}

.daterangepicker .calendar-table td.start-date,
.daterangepicker .calendar-table td.end-date {
    background: #3182ce !important;
    color: white !important;
}

.daterangepicker .ranges li {
    color: #e2e8f0 !important;
}

.daterangepicker .ranges li:hover {
    background: #4a5568 !important;
    color: white !important;
}

.daterangepicker .ranges li.active {
    background: #3182ce !important;
    color: white !important;
}

.daterangepicker .calendar-table .next,
.daterangepicker .calendar-table .prev {
    color: #e2e8f0 !important;
}

.daterangepicker .calendar-table .next:hover,
.daterangepicker .calendar-table .prev:hover {
    color: #3182ce !important;
}

.daterangepicker select {
    background: #2d3748 !important;
    color: #e2e8f0 !important;
    border: 1px solid #4a5568 !important;
}

.daterangepicker .drp-buttons .btn {
    margin-right: 8px;
}

/* Input styling */
#daterangepicker:focus {
    background-color: #2d3748 !important;
    border-color: #3182ce !important;
    box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25) !important;
}

#daterangepicker::placeholder {
    color: #a0aec0 !important;
}

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
    background: var(--bs-border-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
    opacity: 0;
    animation: fadeInUp 0.5s ease-out forwards;
}

.timeline-item:nth-child(1) { animation-delay: 0.1s; }
.timeline-item:nth-child(2) { animation-delay: 0.2s; }
.timeline-item:nth-child(3) { animation-delay: 0.3s; }
.timeline-item:nth-child(4) { animation-delay: 0.4s; }
.timeline-item:nth-child(5) { animation-delay: 0.5s; }

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    z-index: 2;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: var(--card-bg, white);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 15px;
    margin-left: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timeline-content:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.timeline-title {
    color: var(--primary-color, #0d6efd);
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 1rem;
}

.timeline-description {
    color: var(--text-color, #212529);
    margin-bottom: 10px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.timeline-time {
    font-size: 0.75rem;
    font-weight: 500;
}
    z-index: 2;
}

.timeline-content {
    background: var(--card-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 15px;
    margin-left: 15px;
    transition: all 0.3s ease;
}

.timeline-content:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.timeline-title {
    color: var(--primary-color);
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-description {
    color: var(--text-color);
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.timeline-time {
    font-size: 0.8rem;
}

.stat-item {
    padding: 10px;
    border-radius: 8px;
    background: rgba(var(--primary-color-rgb), 0.1);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.insight-item {
    padding: 10px;
    border-radius: 8px;
    background: rgba(var(--primary-color-rgb), 0.05);
    border-left: 3px solid var(--primary-color);
}

.alerts-container .alert {
    border-left: 4px solid;
    margin-bottom: 10px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.timeline-item[data-type="user_joined"] .timeline-marker {
    background: var(--bs-success);
}

.timeline-item[data-type="data_input"] .timeline-marker {
    background: var(--bs-primary);
}

.timeline-item[data-type="promotion"] .timeline-marker {
    background: var(--bs-warning);
}

@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }

    .timeline-marker {
        left: -15px;
        width: 15px;
        height: 15px;
        font-size: 8px;
    }

    .timeline-content {
        margin-left: 10px;
    }
}
</style>
@endpush

@push('scripts')
<!-- jQuery, Moment.js, and DateRangePicker JS -->
<script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
let currentFilter = 'all';
let activityPage = 1;

// Initialize DateRangePicker
$(document).ready(function() {
    // Use server-provided dates (max 3-day range enforced server-side)
    const initialStartDate = moment('{{ $startDate }}', 'YYYY-MM-DD');
    const initialEndDate = moment('{{ $endDate }}', 'YYYY-MM-DD');

    $('#daterangepicker').daterangepicker({
        startDate: initialStartDate,
        endDate: initialEndDate,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           // Limited presets for max 3-day span
           'Last 3 Days': [moment().subtract(2, 'days'), moment()],
        },
        locale: {
            format: 'MMM DD, YYYY',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        },
        showDropdowns: true,
        showWeekNumbers: true,
        timePicker: false,
        timePickerIncrement: 1,
        timePicker12Hour: true,
        opens: 'left',
        drops: 'down',
        buttonClasses: ['btn', 'btn-sm'],
        applyClass: 'btn-primary',
        cancelClass: 'btn-secondary',
        autoApply: false,
    alwaysShowCalendars: true,
    // Hard-limit selection to at most 3 calendar days
    maxSpan: { days: 2 }
    }, function(start, end, label) {
        console.log('Date range selected:', start.format('YYYY-MM-DD'), 'to', end.format('YYYY-MM-DD'));
        // On date change
        applyDateRangeFilter(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
    });

    // Set initial display value
    const picker = $('#daterangepicker').data('daterangepicker');
    $('#daterangepicker').val(initialStartDate.format('MMM DD, YYYY') + ' - ' + initialEndDate.format('MMM DD, YYYY'));

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Update activity count when filtering
    updateActivityCountDisplay();

    // Per-date collapse handles additional items
});

function updateActivityCountDisplay() {
    const visibleItems = document.querySelectorAll('.timeline-item:not([style*="display: none"])').length;
    const countBadge = document.getElementById('activity-count');
    if (countBadge) {
        countBadge.textContent = visibleItems;
        countBadge.title = `${visibleItems} activities found in selected date range`;
    }
}

function applyDateRangeFilter(startDate, endDate) {
    console.log('Applying date filter:', startDate, 'to', endDate);

    // Show loading state with better UX
    const timeline = document.getElementById('activity-timeline');
    if (timeline) {
        timeline.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h5 class="text-white mb-2">Filtering Activities</h5>
                <p class="text-muted mb-0">Loading activities from ${moment(startDate).format('MMM DD, YYYY')} to ${moment(endDate).format('MMM DD, YYYY')}</p>
            </div>
        `;
    }

    // Build URL with date parameters and preserve other params
    const url = new URL(window.location.href);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);
    url.searchParams.delete('page');

    // Redirect after a short delay to allow spinner to be seen
    setTimeout(() => {
        window.location.href = url.toString();
    }, 300);

// Auto-refresh every 5 minutes
setInterval(refreshFeeds, 300000);

// Add enhanced styling for timeline
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .timeline-date-divider {
        display: flex;
        align-items: center;
        margin: 2rem 0 1.5rem 0;
        position: relative;
        z-index: 1;
    }

    .date-line {
        flex: 1;
        height: 1px;
        background: linear-gradient(to right, transparent, rgba(134, 142, 150, 0.4), transparent);
    }

    .date-badge {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        border-radius: 25px;
        padding: 0.6rem 1.2rem;
        margin: 0 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
        white-space: nowrap;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        position: relative;
    }

    .date-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, transparent 50%);
        border-radius: 25px;
        pointer-events: none;
    }

    .dark-theme .date-badge {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-color: #4a5568;
        color: #e2e8f0;
    }

    /* Override duplicate timeline styles */
    .timeline-item {
        margin-bottom: 1.5rem !important;
    }

    .timeline-content {
        margin-left: 15px !important;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .timeline-date-divider {
            margin: 1.5rem 0 1rem 0;
        }

        .date-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
        }

        .timeline {
            padding-left: 20px;
        }

        .timeline-marker {
            left: -20px;
            width: 16px;
            height: 16px;
            font-size: 8px;
        }

        .timeline-content {
            margin-left: 10px !important;
            padding: 12px;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection
