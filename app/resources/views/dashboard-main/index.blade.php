@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <!-- Welcome Hero -->
    <div class="content-header ms-5 mb-3">
        <div class="content-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="hero-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 text-white">{{ $greeting ?? 'Welcome' }}, {{ $user->name ?? Auth::user()->name }}!</h2>
                        <div class="text-white small">
                            <i class="bi bi-clock me-1"></i>{{ (isset($now) ? $now->format('l, d M Y - H:i') : now()->format('l, d M Y - H:i')) }}
                            <span class="mx-2">•</span>
                            <i class="bi bi-shield-check me-1"></i>{{ $user->userRole->name ?? 'Member' }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if(!empty($weatherData))
                    <div class="d-flex align-items-center text-white">
                        @if($weatherData['icon'])
                            <img src="{{ $weatherData['icon'] }}" alt="weather" width="42" height="42" class="me-2" />
                        @endif
                        <div class="text-end">
                            <div class="fw-semibold">{{ $weatherData['city'] }}</div>
                            <small class="text-white">{{ $weatherData['temp'] }}°C • {{ $weatherData['desc'] }}</small>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('dashboard.feeds') }}" class="btn btn-outline-secondary"><i class="bi bi-activity me-1"></i>Activity Feeds</a>
                </div>
            </div>
        </div>
    </div>

    <div class="content-body ms-5">
        <!-- Metrics Row -->
        <div class="row g-3 mb-4 p-4 ">
            @if($metrics->count() > 0)
            <div class="col-12">
                <div id="metricsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                    <div class="carousel-inner">
                        @php
                            $chunks = $metrics->chunk(4);
                        @endphp
                        @foreach($chunks as $index => $chunk)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="row g-3">
                                @foreach($chunk as $metric)
                                <div class="col-md-6 col-lg-3">
                                    <div class="content-card metric-card h-100 p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="text-white fw-semibold">{{ $metric->metric_name }}</div>
                                            <i class="bi {{ $metric->icon ?? 'bi-graph-up' }} text-primary"></i>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-end mb-2">
                                            <div class="display-6 fw-bold text-white">{{ $metric->formatted_value ?? number_format($metric->current_value) }}</div>
                                            <span class="badge {{ $metric->change_status === 'decrease' ? 'bg-danger' : ($metric->change_status === 'increase' ? 'bg-success' : 'bg-secondary') }}">{{ $metric->formatted_change ?? '0%' }}</span>
                                        </div>
                                        <!-- Mini Chart -->
                                        <div class="metric-chart-container mb-2" style="height: 60px;">
                                            <div id="metricChart{{ $metric->id }}" style="width: 100%; height: 60px;"></div>
                                        </div>
                                        <div class="mt-2">
                                            <a href="{{ route('dashboard.metrics.records.show', ['businessMetric' => $metric->id]) }}" class="btn btn-sm btn-outline-light w-100">
                                                <i class="bi bi-arrow-right-short"></i> View details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($chunks->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#metricsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#metricsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    @endif
                </div>
            </div>
            @else
            <div class="col-12">
                <div class="content-card p-3 text-center text-muted">
                    <i class="bi bi-graph-up"></i> No metrics yet. <a href="{{ route('dashboard.metrics') }}">Import or create metrics</a>.
                </div>
            </div>
            @endif
        </div>

        <!-- Statistics + Goals -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="content-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-white"><i class="bi bi-bar-chart-line me-2"></i>Statistics Activity</h5>
                        <button id="refreshInsight" class="btn btn-outline-light btn-sm"><i class="bi bi-stars me-1"></i>Refresh AI Insight</button>
                    </div>

                    <!-- Combined Metrics Chart -->
                    <div class="mb-3">
                        <div class="chart-container" style="position: relative; height: 200px;">
                            <div id="combinedMetricsChart" style="width: 100%; height: 200px;"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Statistics Slideshow -->
                        <div class="col-12">
                            <div id="statsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Total Updates</div>
                                                    <div class="stat-value">{{ number_format($stats['total_updates'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Average Value</div>
                                                    <div class="stat-value">{{ number_format($stats['avg_value'] ?? 0, 2) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Last Update</div>
                                                    <div class="stat-value">{{ isset($stats['last_update']) && $stats['last_update'] ? \Carbon\Carbon::parse($stats['last_update'])->diffForHumans() : '-' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Business Metrics</div>
                                                    <div class="stat-value">{{ $metrics->count() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($business)
                                    <div class="carousel-item">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Active Users</div>
                                                    <div class="stat-value">{{ $orgUsers->count() }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Business Name</div>
                                                    <div class="stat-value" style="font-size: 0.9rem;">{{ Str::limit($business->business_name, 15) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Created</div>
                                                    <div class="stat-value">{{ $business->created_at->format('M Y') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="stat-tile">
                                                    <div class="stat-label">Recent Activities</div>
                                                    <div class="stat-value">{{ $recentActivities->count() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#statsCarousel" data-bs-slide="prev" style="width: 5%;">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#statsCarousel" data-bs-slide="next" style="width: 5%;">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="ai-box mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-robot text-white me-2"></i>
                            <div class="text-white fw-semibold">AI Gemini Insight</div>
                        </div>
                        <div id="aiInsight" class="text-white">{!! nl2br(e($aiResponse ?? 'AI not configured or no insight yet.')) !!}</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="content-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-white"><i class="bi bi-clipboard-check me-2"></i>Goals</h5>
                        <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#goalModal"><i class="bi bi-plus-circle"></i></button>
                    </div>
                    <ul id="goalList" class="list-group list-group-flush goals-list">
                        <!-- Filled by JS -->
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activities + Hierarchy -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="content-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-white"><i class="bi bi-activity me-2"></i>Recent Activities</h5>
                        <a href="{{ route('dashboard.feeds') }}" class="btn btn-outline-light btn-sm">See all</a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($recentActivities as $act)
                        <div class="list-group-item bg-transparent text-white d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold"><i class="bi {{ $act->icon ?? 'bi-lightning' }} me-2 text-{{ $act->color ?? 'secondary' }}"></i>{{ $act->title }}</div>
                                <small class="text-muted">{{ $act->description }}</small>
                            </div>
                            <small class="text-muted">{{ $act->created_at->diffForHumans() }}</small>
                        </div>
                        @empty
                        <div class="text-muted text-center py-3">No recent activity</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="content-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-white"><i class="bi bi-diagram-3 me-2"></i>Hierarchy</h5>
                        <a href="{{ route('dashboard.users') }}" class="btn btn-outline-light btn-sm">Manage Users</a>
                    </div>
                    <div class="org-tree">
                        @forelse($orgUsers as $u)
                        <div class="org-item">
                            <div class="org-avatar bg-secondary text-white"><i class="bi bi-person"></i></div>
                            <div>
                                <div class="text-white fw-semibold">{{ $u->name }}</div>
                                <small class="text-muted">{{ $u->userRole->name ?? 'member' }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-muted">No users found</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- News Slideshow -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="content-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-white"><i class="bi bi-newspaper me-2"></i>Business News</h5>
                    </div>
                    @if(count($articles) > 0)
                    <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                        <div class="carousel-indicators">
                            @foreach($articles as $i => $a)
                            <button type="button" data-bs-target="#newsCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $i + 1 }}"></button>
                            @endforeach
                        </div>
                        <div class="carousel-inner">
                            @foreach($articles as $i => $a)
                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                <div class="news-card">
                                    <div class="row g-0">
                                        <div class="col-md-4">
                                            @if($a['image'])
                                            <img src="{{ $a['image'] }}" class="img-fluid news-image h-100 w-100 object-cover" alt="news" style="border-radius: 8px 0 0 8px;">
                                            @else
                                            <div class="news-placeholder d-flex align-items-center justify-content-center" style="border-radius: 8px 0 0 8px;">
                                                <i class="bi bi-newspaper text-muted" style="font-size: 3rem;"></i>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="col-md-8">
                                            <div class="news-content p-4">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge bg-primary">{{ $a['source'] ?? 'News' }}</span>
                                                    <small class="text-muted">{{ isset($a['publishedAt']) ? \Carbon\Carbon::parse($a['publishedAt'])->diffForHumans() : '' }}</small>
                                                </div>
                                                <h5 class="news-title text-white mb-3">{{ Str::limit($a['title'], 80) }}</h5>
                                                <p class="news-description text-muted mb-3">{{ Str::limit($a['desc'], 120) }}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <a href="{{ $a['url'] }}" target="_blank" class="btn btn-outline-light btn-sm">
                                                        <i class="bi bi-arrow-up-right-square me-1"></i> Read Full Article
                                                    </a>
                                                    <small class="text-muted">{{ $i + 1 }} of {{ count($articles) }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bi bi-newspaper text-muted mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted mb-3">Connect NEWS_API_KEY to show business news.</p>
                        <small class="text-muted">Add your NewsAPI key to .env file to enable this feature.</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-main.css') }}">
@endpush

@push('scripts')
<script>
    // Configuration for external scripts
    window.routes = {
        goals: {
            index: "{{ route('dashboard.goals.index') }}",
            store: "{{ route('dashboard.goals.store') }}",
            base: "{{ url('/dashboard/goals') }}"
        },
        ai: {
            insight: "{{ route('dashboard.ai.insight') }}"
        }
    };
    window.csrfToken = "{{ csrf_token() }}";
    window.combinedChartData = @json($combinedChartData ?? []);
    window.metricsChartData = @json($metrics ?? []);
</script>
<script src="{{ asset('js/dashboard/dashboard-main/index.js') }}"></script>

<!-- Goal Modal -->
<div class="modal fade" id="goalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header">
                <h5 class="modal-title">Add Goal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="goalForm">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target (%)</label>
                        <input type="number" class="form-control" name="target_percent" min="1" max="100" value="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitGoal()">Save</button>
            </div>
        </div>
    </div>
    <!-- ApexCharts Library -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Charts JavaScript -->
    <script src="{{ asset('js/dashboard/dashboard-main/charts.js') }}"></script>
</div>
@endpush

    </div>

