@extends('layouts.dashboard')

@section('title', 'Activity Log')

@section('content')
<div class="container-fluid p-4 ms-4 activity-log-page">
    <div class="row align-items-center g-3 mb-4">
        <div class="col-lg-8">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-1">Activity Log</h1>
                    <p class="text-white-50 mb-0">{{ $summaryStats['window_label'] }} · {{ $summaryStats['date_range'] }}</p>
                </div>
                <span class="badge bg-glass text-white border border-secondary-subtle">
                    {{ $summaryStats['total'] }} activities
                </span>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                <div class="input-group input-group-sm activity-search-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="activitySearch" class="form-control" placeholder="Search activities (title, user, type)...">
                </div>
                <button class="btn btn-outline-light btn-sm flex-shrink-0" id="refreshActivitiesBtn">
                    <i class="bi bi-arrow-repeat me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-glass summary-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-white-50 text-uppercase small">Total Activity</div>
                        <span class="summary-icon text-primary"><i class="bi bi-activity"></i></span>
                    </div>
                    <div class="display-6 fw-semibold text-white mb-1">{{ $summaryStats['total'] }}</div>
                    <p class="text-white-50 small mb-0">Across {{ $summaryStats['day_count'] }} days</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-glass summary-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-white-50 text-uppercase small">Unique Users</div>
                        <span class="summary-icon text-info"><i class="bi bi-people"></i></span>
                    </div>
                    <div class="display-6 fw-semibold text-white mb-1">{{ $summaryStats['unique_users'] }}</div>
                    <p class="text-white-50 small mb-0">People who took action</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-glass summary-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-white-50 text-uppercase small">Average / Day</div>
                        <span class="summary-icon text-success"><i class="bi bi-graph-up"></i></span>
                    </div>
                    <div class="display-6 fw-semibold text-white mb-1">{{ number_format($summaryStats['avg_per_day'], 1) }}</div>
                    <p class="text-white-50 small mb-0">Rolling three-day cadence</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-glass summary-card h-100">
                <div class="card-body">
                    @php $topType = $summaryStats['top_type'] ?? null; @endphp
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-white-50 text-uppercase small">Top Activity</div>
                        <span class="summary-icon text-warning"><i class="bi bi-lightning-charge"></i></span>
                    </div>
                    @if($topType)
                        <div class="h4 fw-semibold text-white mb-1">{{ $topType['label'] }}</div>
                        <p class="text-white-50 small mb-0">{{ $topType['count'] }} events in the window</p>
                    @else
                        <div class="h4 fw-semibold text-white mb-1">No data yet</div>
                        <p class="text-white-50 small mb-0">Activities will appear once logged</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-glass mb-4">
        <div class="card-header border-0 bg-transparent d-flex align-items-center justify-content-between">
            <h5 class="mb-0 text-white"><i class="bi bi-bar-chart-line me-2 text-info"></i>Activity Insights</h5>
            <span class="badge bg-glass text-white border border-secondary-subtle">Last 7 days</span>
        </div>
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="chart-wrapper">
                        <canvas id="activityTrendChart" data-chart='@json($activityTrends)'></canvas>
                    </div>
                </div>
                <div class="col-lg-5">
                    <ul class="list-unstyled activity-type-legend mb-0">
                        @forelse($summaryStats['types'] as $typeStat)
                            @php
                                $typeShare = $summaryStats['total'] ? round(($typeStat['count'] / max($summaryStats['total'], 1)) * 100) : 0;
                            @endphp
                            <li class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="legend-marker bg-{{ $typeStat['color'] ?? 'secondary' }}"></span>
                                    <div>
                                        <div class="text-white fw-semibold">{{ $typeStat['label'] }}</div>
                                        <small class="text-white-50">{{ $typeStat['count'] }} events</small>
                                    </div>
                                </div>
                                <span class="badge bg-dark-subtle text-white border">{{ $typeShare }}%</span>
                            </li>
                        @empty
                            <li class="text-white-50 small">No activity logged yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xxl-8">
            <div class="card card-glass timeline-card h-100">
                <div class="card-header border-0 bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                        <h5 class="mb-0 text-white">Timeline</h5>
                        <span class="badge bg-primary" id="visibleActivityCount">0</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <select id="activityTypeFilter" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="all">All Types</option>
                            @foreach($summaryStats['types'] as $typeStat)
                                <option value="{{ $typeStat['type'] }}">{{ $typeStat['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($groupedActivities as $date => $items)
                        @php
                            $formattedDate = \Carbon\Carbon::parse($date);
                            $visibleItems = $items->take(3);
                            $hiddenItems = $items->slice(3);
                            $collapseId = 'dayActivities'.$formattedDate->format('Ymd');
                        @endphp
                        <div class="timeline-day mb-4" data-date="{{ $date }}">
                            <div class="timeline-day-header d-flex align-items-center mb-3">
                                <div class="flex-grow-1 date-divider-line"></div>
                                <div class="date-chip mx-3">
                                    <div class="fw-semibold text-white">{{ $formattedDate->format('l') }}</div>
                                    <small class="text-white-50">{{ $formattedDate->format('M d, Y') }}</small>
                                </div>
                                <div class="flex-grow-1 date-divider-line"></div>
                            </div>
                            <div class="timeline-list">
                                @foreach($visibleItems as $activity)
                                    @php
                                        $colorTone = $colorMap[$activity->type] ?? $activity->color ?? 'secondary';
                                        $typeLabel = \Illuminate\Support\Str::headline($activity->type);
                                    @endphp
                                    <div class="activity-item" data-type="{{ $activity->type }}" data-user="{{ $activity->user?->name }}">
                                        <div class="activity-icon bg-{{ $colorTone }}">
                                            <i class="{{ $activity->icon ?? 'bi-activity' }}"></i>
                                        </div>
                                        <div class="activity-body">
                                            <div class="d-flex justify-content-between align-items-start gap-3 mb-1">
                                                <div>
                                                    <h6 class="mb-1 text-white">{{ $activity->title }}</h6>
                                                    <p class="mb-1 text-white-50 small">{{ $activity->description }}</p>
                                                </div>
                                                <small class="text-white-50 text-nowrap">{{ $activity->created_at->format('H:i') }}</small>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 small">
                                                <span class="badge rounded-pill bg-dark-subtle text-white border">{{ $typeLabel }}</span>
                                                @if($activity->user)
                                                    <span class="badge rounded-pill user-badge"><i class="bi bi-person me-1"></i>{{ $activity->user->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($hiddenItems->count())
                                    <div class="collapse mt-2" id="{{ $collapseId }}">
                                        @foreach($hiddenItems as $activity)
                                            @php
                                                $colorTone = $colorMap[$activity->type] ?? $activity->color ?? 'secondary';
                                                $typeLabel = \Illuminate\Support\Str::headline($activity->type);
                                            @endphp
                                            <div class="activity-item" data-type="{{ $activity->type }}" data-user="{{ $activity->user?->name }}">
                                                <div class="activity-icon bg-{{ $colorTone }}">
                                                    <i class="{{ $activity->icon ?? 'bi-activity' }}"></i>
                                                </div>
                                                <div class="activity-body">
                                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-1">
                                                        <div>
                                                            <h6 class="mb-1 text-white">{{ $activity->title }}</h6>
                                                            <p class="mb-1 text-white-50 small">{{ $activity->description }}</p>
                                                        </div>
                                                        <small class="text-white-50 text-nowrap">{{ $activity->created_at->format('H:i') }}</small>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2 small">
                                                        <span class="badge rounded-pill bg-dark-subtle text-white border">{{ $typeLabel }}</span>
                                                        @if($activity->user)
                                                            <span class="badge rounded-pill user-badge"><i class="bi bi-person me-1"></i>{{ $activity->user->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="text-center mt-2">
                                        <button class="btn btn-outline-light btn-sm toggle-day-btn" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false">
                                            <span class="when-collapsed"><i class="bi bi-chevron-down me-1"></i>Show more</span>
                                            <span class="when-expanded"><i class="bi bi-chevron-up me-1"></i>Show less</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-white-50">
                            <i class="bi bi-activity fs-1 d-block mb-2"></i>
                            No activities recorded in the last three days.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="card card-glass mb-4">
                <div class="card-header border-0 bg-transparent d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-white"><i class="bi bi-trophy-fill text-warning me-2"></i>Top Active Users (30d)</h6>
                    <span class="badge bg-glass text-white border border-secondary-subtle">{{ $totalLast30 }} total</span>
                </div>
                <div class="card-body p-0">
                    @if($topUsers->count())
                        <ul class="list-group list-group-flush activity-top-users">
                            @foreach($topUsers as $index => $userStat)
                                @php
                                    $pct = $totalLast30 ? round(($userStat['count'] / $totalLast30) * 100) : 0;
                                @endphp
                                <li class="list-group-item bg-transparent text-white d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="rank-badge rank-{{ $index + 1 }}">{{ $index + 1 }}</span>
                                        <div class="avatar-sm rounded-circle bg-primary d-flex align-items-center justify-content-center fw-semibold">
                                            {{ strtoupper(mb_substr($userStat['user']->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $userStat['user']->name }}</div>
                                            <small class="text-white-50">{{ $userStat['count'] }} activities</small>
                                        </div>
                                    </div>
                                    <div class="progress flex-grow-1 mx-3" style="height:6px; max-width:140px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="badge bg-dark-subtle text-white border">{{ $pct }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="p-4 text-center text-white-50 small">No user activity recorded yet.</div>
                    @endif
                </div>
            </div>

            <div class="card card-glass mb-4">
                <div class="card-header border-0 bg-transparent">
                    <h6 class="mb-0 text-white"><i class="bi bi-funnel text-info me-2"></i>Quick Filters</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2" id="quickFilterButtons">
                        <button class="btn btn-sm btn-outline-light filter-chip" data-type="all">All</button>
                        @foreach($summaryStats['types'] as $typeStat)
                            <button class="btn btn-sm btn-outline-{{ $typeStat['color'] ?? 'secondary' }} filter-chip" data-type="{{ $typeStat['type'] }}">
                                {{ $typeStat['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card card-glass">
                <div class="card-header border-0 bg-transparent">
                    <h6 class="mb-0 text-white"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Recent Alerts</h6>
                </div>
                <div class="card-body">
                    @if($recentAlerts->count())
                        <ul class="list-unstyled mb-0">
                            @foreach($recentAlerts as $alert)
                                @php
                                    $alertColor = $colorMap[$alert->type] ?? $alert->color ?? 'secondary';
                                    $alertTypeLabel = \Illuminate\Support\Str::headline($alert->type);
                                @endphp
                                <li class="d-flex align-items-start gap-3 mb-3 recent-alert">
                                    <span class="alert-dot bg-{{ $alertColor }}"><i class="{{ $alert->icon ?? 'bi-bell' }}"></i></span>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <strong class="text-white">{{ $alert->title }}</strong>
                                            <span class="badge rounded-pill bg-dark-subtle text-white border">{{ $alertTypeLabel }}</span>
                                        </div>
                                        <p class="mb-1 text-white-50 small">{{ $alert->description }}</p>
                                        <div class="text-white-50 small">
                                            {{ $alert->created_at->diffForHumans() }}
                                            @if($alert->user)
                                                · {{ $alert->user->name }}
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-white-50 small">No alerts detected recently.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .activity-log-page .card-glass {
        background: rgba(17, 24, 39, 0.65);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 1rem;
        backdrop-filter: blur(16px);
    }
    .activity-log-page .summary-card .summary-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.08);
        font-size: 1.1rem;
    }
    .activity-search-group .input-group-text,
    .activity-search-group .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
    }
    .activity-search-group .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    .activity-search-group .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        border-color: rgba(59, 130, 246, 0.6);
    }
    .badge.bg-glass {
        background: rgba(255, 255, 255, 0.08);
    }
    .bg-dark-subtle {
        background: rgba(255, 255, 255, 0.08) !important;
    }
    .timeline-card .card-body {
        max-height: 600px;
        overflow-y: auto;
        padding-right: 1rem;
    }
    .timeline-day-header .date-chip {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(107, 114, 128, 0.15));
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.75rem 1.25rem;
        border-radius: 1rem;
        min-width: 160px;
    }
    .date-divider-line {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
    }
    .activity-item {
        display: flex;
        gap: 16px;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.04);
        transition: transform 0.2s ease, background 0.2s ease;
        margin-bottom: 12px;
    }
    .activity-item:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, 0.08);
    }
    .activity-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.25rem;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }
    .user-badge {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.16);
    }
    .legend-marker {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        display: inline-block;
        background: rgba(255, 255, 255, 0.4);
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.15);
    }
    .activity-type-legend .badge {
        font-size: 0.75rem;
    }
    .chart-wrapper {
        position: relative;
        height: 240px;
    }
    .activity-top-users .list-group-item {
        border-color: rgba(255, 255, 255, 0.08);
    }
    .rank-badge {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.08);
    }
    .rank-1 {
        background: linear-gradient(135deg, #fbbf24, #f97316);
        color: #111;
    }
    .rank-2 {
        background: linear-gradient(135deg, #cbd5f5, #a5b4fc);
        color: #111;
    }
    .rank-3 {
        background: linear-gradient(135deg, #fbd5c0, #ef4444);
        color: #111;
    }
    .progress {
        background: rgba(255, 255, 255, 0.08);
    }
    .progress-bar {
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
    }
    .filter-chip {
        border-radius: 999px;
        padding-inline: 1rem;
        transition: all 0.2s ease;
    }
    .filter-chip.active {
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.6);
    }
    .toggle-day-btn .when-expanded {
        display: none;
    }
    .toggle-day-btn[aria-expanded="true"] .when-expanded {
        display: inline-flex;
    }
    .toggle-day-btn[aria-expanded="true"] .when-collapsed {
        display: none;
    }
    .recent-alert .alert-dot {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }
    @media (max-width: 992px) {
        .timeline-card .card-body {
            max-height: none;
            overflow: visible;
        }
        .activity-log-page .summary-card .summary-icon {
            width: 32px;
            height: 32px;
        }
    }
    @media (max-width: 576px) {
        .activity-log-page .card-glass {
            border-radius: 0.75rem;
        }
        .activity-item {
            flex-direction: column;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }
        .activity-search-group .input-group-text,
        .activity-search-group .form-control {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeFilter = document.getElementById('activityTypeFilter');
    const searchInput = document.getElementById('activitySearch');
    const visibleCountEl = document.getElementById('visibleActivityCount');
    const filterChips = document.querySelectorAll('.filter-chip');
    const timelineDays = document.querySelectorAll('.timeline-day');
    const trendCanvas = document.getElementById('activityTrendChart');

    function applyFilters() {
        const selectedType = window.currentTypeFilter || (typeFilter ? typeFilter.value : 'all');
        const query = (searchInput?.value || '').toLowerCase();
        let visible = 0;

        document.querySelectorAll('.activity-item').forEach(item => {
            const itemType = item.getAttribute('data-type');
            const user = (item.getAttribute('data-user') || '').toLowerCase();
            const text = item.innerText.toLowerCase();
            const typeMatch = selectedType === 'all' || itemType === selectedType;
            const queryMatch = !query || text.includes(query) || user.includes(query);

            if (typeMatch && queryMatch) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        timelineDays.forEach(day => {
            const items = day.querySelectorAll('.activity-item');
            const someVisible = Array.from(items).some(item => {
                const parentCollapse = item.closest('.collapse');
                const collapsedHidden = parentCollapse && !parentCollapse.classList.contains('show');
                return item.style.display !== 'none' && !collapsedHidden;
            });
            day.classList.toggle('d-none', !someVisible);
        });

        document.querySelectorAll('.activity-item').forEach(item => {
            const parentCollapse = item.closest('.collapse');
            const isCollapsedHidden = parentCollapse && !parentCollapse.classList.contains('show');
            if (item.style.display !== 'none' && !isCollapsedHidden) {
                visible += 1;
            }
        });

        if (visibleCountEl) {
            visibleCountEl.textContent = visible;
        }
    }

    function highlightChip(value) {
        filterChips.forEach(chip => {
            chip.classList.toggle('active', chip.dataset.type === value);
        });
    }

    typeFilter?.addEventListener('change', () => {
        window.currentTypeFilter = typeFilter.value;
        applyFilters();
        highlightChip(typeFilter.value);
    });

    searchInput?.addEventListener('input', () => {
        applyFilters();
    });

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            window.currentTypeFilter = chip.dataset.type;
            if (typeFilter) {
                typeFilter.value = chip.dataset.type;
            }
            applyFilters();
            highlightChip(chip.dataset.type);
        });
    });

    document.querySelectorAll('.timeline-card .collapse').forEach(collapseEl => {
        collapseEl.addEventListener('shown.bs.collapse', () => applyFilters());
        collapseEl.addEventListener('hidden.bs.collapse', () => applyFilters());
    });

    document.getElementById('refreshActivitiesBtn')?.addEventListener('click', () => window.location.reload());

    if (trendCanvas) {
        try {
            const dataset = JSON.parse(trendCanvas.dataset.chart || '{}');
            if (dataset.labels && dataset.values) {
                new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: dataset.labels,
                        datasets: [{
                            label: 'Daily activity',
                            data: dataset.values,
                            fill: true,
                            tension: 0.35,
                            borderColor: '#60a5fa',
                            backgroundColor: (context) => {
                                const {ctx, chartArea} = context.chart;
                                if (!chartArea) {
                                    return 'rgba(96, 165, 250, 0.2)';
                                }
                                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                gradient.addColorStop(0, 'rgba(96, 165, 250, 0.05)');
                                gradient.addColorStop(1, 'rgba(96, 165, 250, 0.45)');
                                return gradient;
                            },
                            pointBackgroundColor: '#93c5fd',
                            pointBorderColor: '#60a5fa',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#cbd5f5',
                                padding: 12,
                                borderColor: 'rgba(255, 255, 255, 0.08)',
                                borderWidth: 1
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#e5e7eb' },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { color: '#e5e7eb', precision: 0 },
                                grid: { color: 'rgba(255, 255, 255, 0.05)' }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Failed to render activity trend chart', error);
        }
    }

    applyFilters();
    highlightChip(window.currentTypeFilter || 'all');
});
</script>
@endpush

