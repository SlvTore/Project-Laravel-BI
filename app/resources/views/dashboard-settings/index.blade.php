@extends('layouts.dashboard')

@section('title', 'Settings')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $canManageBusiness = $user->isBusinessOwner() || $user->isAdministrator();
    $canManageOwnership = $user->isBusinessOwner();
    $accentColor = $user->accent_color ?? '#0d6efd';
    if ($accentColor && !Str::startsWith($accentColor, '#')) {
        $accentColor = '#' . ltrim($accentColor, '#');
    }
    $initials = static function (string $name): string {
        return collect(explode(' ', $name))
            ->filter()
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    };
@endphp

@section('content')
<div class="container-fluid px-4 pb-5">
    <div class="row g-4 align-items-stretch mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <span class="badge bg-primary-subtle text-primary fw-semibold text-uppercase mb-2">Business Intelligence Brief</span>
                            <h1 class="h3 fw-bold text-primary mb-2">Settings & BI Experience</h1>
                            <p class="text-muted mb-0">Tune your workspace, control access, and keep your intelligence rhythm smooth for every role.</p>
                        </div>
                        <span class="badge bg-info-subtle text-info align-self-start">{{ $user->userRole->display_name ?? 'Team Member' }}</span>
                    </div>
                    <hr class="my-4">
                    <div class="row g-3">
                        @foreach($biHighlights as $highlight)
                            <div class="col-md-4">
                                <div class="d-flex h-100">
                                    <span class="me-3 fs-3 lh-1"><i class="{{ $highlight['icon'] }}"></i></span>
                                    <div>
                                        <h6 class="fw-semibold mb-1">{{ $highlight['title'] }}</h6>
                                        <p class="text-muted small mb-0">{{ $highlight['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Team Snapshot</h5>
                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Active members</span>
                            <span class="badge bg-primary-subtle text-primary">{{ $stats['team_count'] }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Live invitations</span>
                            <span class="badge bg-success-subtle text-success">{{ $stats['active_invitations'] }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Published metrics</span>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $stats['metrics_count'] }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Last activity</span>
                            <span class="text-muted">{{ $stats['last_activity'] ? $stats['last_activity']->diffForHumans() : 'No signals yet' }}</span>
                        </li>
                    </ul>
                    <h6 class="text-uppercase text-muted fw-semibold small mb-2">Recently engaged</h6>
                    @forelse($teamMembers->take(4) as $member)
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-initial me-2">{{ $initials($member->name) }}</div>
                            <div>
                                <div class="fw-semibold">{{ $member->name }}</div>
                                <small class="text-muted">{{ $member->userRole->display_name ?? 'Member' }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Invite your first teammate to unlock collaboration insights.</p>
                    @endforelse
                    @if($stats['team_count'] > 4 && $user->canManageUsers())
                        <a href="{{ route('dashboard.users') }}" class="small text-decoration-none">View full team roster</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="accordion" id="settingsAccordion">
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="headingPersonalization">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#personalizationSection" aria-expanded="true" aria-controls="personalizationSection">
                            <i class="bi bi-palette-fill text-primary me-3"></i>
                            <div>
                                <div class="fw-semibold">Dashboard Personalization</div>
                                <small class="text-muted">Align branding, storytelling, and personal workspace preferences.</small>
                            </div>
                        </button>
                    </h2>
                    <div id="personalizationSection" class="accordion-collapse collapse show" aria-labelledby="headingPersonalization" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row g-4 align-items-stretch">
                                <div class="col-lg-7">
                                    <div class="card border-0 h-100 bg-light">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h5 class="fw-semibold mb-0"><i class="bi bi-building-gear me-2 text-primary"></i>Business identity</h5>
                                                @if(!$canManageBusiness)
                                                    <span class="badge bg-secondary">View only</span>
                                                @endif
                                            </div>
                                            <p class="text-muted small mb-4">
                                                {{ $canManageBusiness ? 'Update how your business is introduced across dashboards, exports, and invitations.' : 'Only business owners or administrators can adjust business identity details.' }}
                                            </p>
                                            <form id="identityForm" action="{{ route('dashboard.settings.update') }}" method="POST" class="needs-validation" novalidate>
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label for="businessName" class="form-label">Registered business name</label>
                                                        <input type="text" class="form-control" id="businessName" name="business_name" value="{{ old('business_name', $business->business_name) }}" {{ $canManageBusiness ? 'required' : 'disabled' }}>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="dashboardDisplayName" class="form-label">Dashboard display name</label>
                                                        <input type="text" class="form-control" id="dashboardDisplayName" name="dashboard_display_name" value="{{ old('dashboard_display_name', $business->dashboard_display_name ?? $business->business_name) }}" {{ $canManageBusiness ? '' : 'disabled' }} placeholder="Shown across BI widgets and exports">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="businessIndustry" class="form-label">Industry focus</label>
                                                        <input type="text" class="form-control" id="businessIndustry" name="industry" value="{{ old('industry', $business->industry) }}" {{ $canManageBusiness ? '' : 'disabled' }}>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="businessWebsite" class="form-label">Website</label>
                                                        <input type="url" class="form-control" id="businessWebsite" name="website" value="{{ old('website', $business->website) }}" {{ $canManageBusiness ? '' : 'disabled' }} placeholder="https://example.com">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="businessDescription" class="form-label">Story that appears in BI summaries</label>
                                                        <textarea class="form-control" id="businessDescription" name="description" rows="3" {{ $canManageBusiness ? '' : 'disabled' }} placeholder="Capture how your team uses BI to drive the business.">{{ old('description', $business->description) }}</textarea>
                                                    </div>
                                                </div>
                                                @if($canManageBusiness)
                                                    <div class="d-flex align-items-center gap-2 mt-4">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bi bi-save me-2"></i>Save business profile
                                                        </button>
                                                        <small class="text-muted">Changes update identity across dashboards instantly.</small>
                                                    </div>
                                                @endif
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="card border-0 h-100">
                                        <div class="card-body">
                                            <h5 class="fw-semibold mb-3 d-flex align-items-center">
                                                <i class="bi bi-brush-fill text-primary me-2"></i>Branding assets
                                            </h5>
                                            <div class="text-center mb-3">
                                                <div class="logo-preview border rounded d-flex align-items-center justify-content-center mx-auto mb-3 bg-light">
                                                    @if($business->logo_path)
                                                        <img id="logoPreviewImage" src="{{ Storage::url($business->logo_path) }}" alt="Business logo" class="img-fluid rounded">
                                                    @else
                                                        <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                                                    @endif
                                                </div>
                                                <p class="text-muted small mb-3">Consistent logos help team members recognise your workspace instantly.</p>
                                                <form id="logoForm" action="{{ route('dashboard.settings.branding') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">
                                                    <div class="d-grid gap-2">
                                                        <label for="logoInput" class="btn btn-outline-primary btn-sm {{ $canManageBusiness ? '' : 'disabled' }}">
                                                            <i class="bi bi-upload me-2"></i>Upload logo
                                                        </label>
                                                        <input type="file" class="d-none" id="logoInput" name="logo" accept="image/*" {{ $canManageBusiness ? '' : 'disabled' }}>
                                                        <small class="text-muted">Accepted: JPG, PNG, WEBP, SVG (max 2MB)</small>
                                                        @if($business->logo_path && $canManageBusiness)
                                                            <button type="button" class="btn btn-outline-danger btn-sm" id="logoRemoveBtn">
                                                                <i class="bi bi-trash me-2"></i>Remove logo
                                                            </button>
                                                        @endif
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="alert alert-info small mb-0">
                                                <i class="bi bi-info-circle me-2"></i>Upload square images for best results in dashboards and executive exports.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100 bg-light">
                                        <div class="card-body">
                                            <h5 class="fw-semibold mb-3"><i class="bi bi-display-fill me-2 text-primary"></i>Personal workspace preferences</h5>
                                            <p class="text-muted small">Switch themes and accent colours to suit your daily BI rhythm.</p>
                                            <form id="preferencesForm" action="{{ route('dashboard.settings.preferences') }}" method="POST">
                                                @csrf
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="theme" class="form-label">Theme mode</label>
                                                        <select class="form-select" id="theme" name="theme">
                                                            <option value="light" {{ ($user->theme ?? 'light') === 'light' ? 'selected' : '' }}>Light — best for daytime analysis</option>
                                                            <option value="dark" {{ ($user->theme ?? 'light') === 'dark' ? 'selected' : '' }}>Dark — great for control rooms</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="accentColor" class="form-label">Accent colour</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text border-end-0 pe-1">
                                                                <input type="color" class="form-control form-control-color border-0 p-0" id="accentColor" name="accent_color" value="{{ $accentColor }}" title="Choose an accent colour">
                                                            </span>
                                                            <input type="text" class="form-control border-start-0" id="accentColorValue" value="{{ Str::upper($accentColor) }}" aria-label="Accent colour hex">
                                                        </div>
                                                        <small class="text-muted">Use your brand colour to align widgets and callouts.</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-3">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-magic me-1"></i>Save preferences
                                                    </button>
                                                    <small class="text-muted">Updates apply instantly to your account.</small>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100">
                                        <div class="card-body">
                                            <h5 class="fw-semibold mb-3"><i class="bi bi-bullseye me-2 text-primary"></i>UX guidance for BI adoption</h5>
                                            <ul class="list-unstyled small text-muted mb-0">
                                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Pin favourite metrics and dashboards to keep decision loops fast.</li>
                                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Use contrasting accent colours to highlight emerging trends.</li>
                                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Encourage staff to set their preferred theme to boost adoption.</li>
                                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Refresh branding after major campaigns to maintain consistency.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="headingSecurity">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#securitySection" aria-expanded="false" aria-controls="securitySection">
                            <i class="bi bi-shield-lock-fill me-3 text-success"></i>
                            <div>
                                <div class="fw-semibold">Security & Access</div>
                                <small class="text-muted">Manage invitation codes, guardrails, and onboarding tips.</small>
                            </div>
                        </button>
                    </h2>
                    <div id="securitySection" class="accordion-collapse collapse" aria-labelledby="headingSecurity" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row g-4 align-items-stretch">
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100 bg-light">
                                        <div class="card-body">
                                            <h5 class="fw-semibold mb-3"><i class="bi bi-link-45deg text-success me-2"></i>Team invitation code</h5>
                                            <p class="text-muted small">Share this code with staff members to unlock the BI workspace.</p>
                                            <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                                                <span class="badge bg-primary text-uppercase fs-6 px-3" id="currentInvitationCode">{{ $business->invitation_code ?? 'N/A' }}</span>
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="copyCodeBtn">
                                                    <i class="bi bi-clipboard-check me-1"></i>Copy
                                                </button>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <button type="button" class="btn btn-warning btn-sm" id="regenerateCodeBtn" {{ $canManageOwnership ? '' : 'disabled' }}>
                                                    <i class="bi bi-arrow-repeat me-1"></i>Regenerate code
                                                </button>
                                                <small class="text-muted">Regeneration invalidates the previous code immediately.</small>
                                            </div>
                                            @unless($canManageOwnership)
                                                <div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-lock-fill me-1"></i>Only business owners can regenerate the invitation code.</div>
                                            @endunless
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100">
                                        <div class="card-body">
                                            <h5 class="fw-semibold mb-3"><i class="bi bi-building-check me-2 text-success"></i>Business overview</h5>
                                            <dl class="row small mb-0">
                                                <dt class="col-sm-5 text-muted">Business ID</dt>
                                                <dd class="col-sm-7 fw-semibold">{{ $business->public_id ?? '—' }}</dd>
                                                <dt class="col-sm-5 text-muted">Owner</dt>
                                                <dd class="col-sm-7 fw-semibold">{{ $business->owner->name ?? $user->name }}</dd>
                                                <dt class="col-sm-5 text-muted">Active members</dt>
                                                <dd class="col-sm-7">{{ $stats['team_count'] }}</dd>
                                                <dt class="col-sm-5 text-muted">Created</dt>
                                                <dd class="col-sm-7">{{ $business->created_at?->format('M d, Y') ?? '—' }}</dd>
                                            </dl>
                                            <div class="alert alert-info small mt-3 mb-0">
                                                <i class="bi bi-lightbulb me-1"></i>Use roles to keep investigators focused on insights while staff manage day-to-day data feeds.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4">
                            <div class="row g-4">
                                <div class="col-lg-5">
                                    <div class="card border-0 h-100 bg-white">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3"><i class="bi bi-shield-check me-2 text-success"></i>Access guardrails</h6>
                                            <ul class="list-unstyled small text-muted mb-0">
                                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Staff see: Home, Metrics, Data Feeds, Activity Log, Settings, Profile.</li>
                                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Investigators focus on insights with access to Home, Metrics, Settings, Profile.</li>
                                                <li><i class="bi bi-check-circle-fill text-success me-2"></i>Every role can reach their own profile for security updates.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card border-0 h-100 bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3"><i class="bi bi-layers-fill me-2 text-success"></i>UI/UX adjustments for smoother BI adoption</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <i class="bi bi-columns-gap text-primary me-2 fs-4"></i>
                                                        <p class="small text-muted mb-0">Group related widgets and keep summaries above the fold to speed executive briefings.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <i class="bi bi-bell-fill text-warning me-2 fs-4"></i>
                                                        <p class="small text-muted mb-0">Use badges and alerts from the Activity Log to highlight anomalies during stand-ups.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <i class="bi bi-clock-history text-success me-2 fs-4"></i>
                                                        <p class="small text-muted mb-0">Rotate invitation codes regularly and remove inactive accounts to maintain data trust.</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex">
                                                        <i class="bi bi-people text-info me-2 fs-4"></i>
                                                        <p class="small text-muted mb-0">Pair new teammates with role-specific walkthroughs to reduce onboarding friction.</p>
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

                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header" id="headingDanger">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dangerSection" aria-expanded="false" aria-controls="dangerSection">
                            <i class="bi bi-exclamation-triangle-fill me-3 text-danger"></i>
                            <div>
                                <div class="fw-semibold">Danger Zone</div>
                                <small class="text-muted">Irreversible actions — proceed with caution.</small>
                            </div>
                        </button>
                    </h2>
                    <div id="dangerSection" class="accordion-collapse collapse" aria-labelledby="headingDanger" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            @if($canManageOwnership)
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <div class="card border border-warning-subtle h-100">
                                            <div class="card-header bg-warning-subtle">
                                                <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-left-right me-2"></i>Transfer ownership</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Pass control to another teammate. You will be moved to the administrator role.</p>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#transferOwnershipModal">
                                                    <i class="bi bi-arrow-repeat me-2"></i>Transfer ownership
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card border border-danger-subtle h-100">
                                            <div class="card-header bg-danger-subtle">
                                                <h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-trash-fill me-2"></i>Delete business</h6>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small">Permanently remove this business and all BI data. This cannot be undone.</p>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBusinessModal">
                                                    <i class="bi bi-trash me-2"></i>Delete business
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0"><i class="bi bi-lock-fill me-2"></i>Only business owners can manage critical operations such as transfers or deletions.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 shadow-sm">
                    <h2 class="accordion-header" id="headingAppInfo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#appInfoSection" aria-expanded="false" aria-controls="appInfoSection">
                            <i class="bi bi-info-circle-fill me-3 text-info"></i>
                            <div>
                                <div class="fw-semibold">App Information & Support</div>
                                <small class="text-muted">System diagnostics, support channels, and BI operating rhythm.</small>
                            </div>
                        </button>
                    </h2>
                    <div id="appInfoSection" class="accordion-collapse collapse" aria-labelledby="headingAppInfo" data-bs-parent="#settingsAccordion">
                        <div class="accordion-body">
                            <div class="row g-4 align-items-stretch">
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100 bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3"><i class="bi bi-cpu-fill me-2 text-info"></i>System information</h6>
                                            <ul class="list-group list-group-flush small">
                                                <li class="list-group-item px-0 d-flex justify-content-between">
                                                    <span>Laravel version</span>
                                                    <span class="fw-semibold">{{ app()->version() }}</span>
                                                </li>
                                                <li class="list-group-item px-0 d-flex justify-content-between">
                                                    <span>PHP version</span>
                                                    <span class="fw-semibold">{{ PHP_VERSION }}</span>
                                                </li>
                                                <li class="list-group-item px-0 d-flex justify-content-between">
                                                    <span>Environment</span>
                                                    <span class="fw-semibold text-capitalize">{{ app()->environment() }}</span>
                                                </li>
                                                <li class="list-group-item px-0 d-flex justify-content-between">
                                                    <span>Last update</span>
                                                    <span class="fw-semibold">{{ now()->format('M d, Y') }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3"><i class="bi bi-life-preserver me-2 text-info"></i>Support & next steps</h6>
                                            <div class="d-grid gap-2 mb-3">
                                                <a href="{{ route('help-center.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-question-circle me-1"></i>Help Center</a>
                                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope me-1"></i>Contact support</a>
                                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-richtext me-1"></i>Documentation</a>
                                            </div>
                                            <p class="text-muted small mb-2">Operational checklist:</p>
                                            <ul class="list-unstyled small text-muted mb-0">
                                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Review Activity Log weekly to track BI usage and detect anomalies.</li>
                                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Refresh onboarding materials whenever you adjust navigation or roles.</li>
                                                <li><i class="bi bi-check-circle text-success me-2"></i>Capture feedback from staff to keep the BI experience accessible and efficient.</li>
                                            </ul>
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

<div class="modal fade" id="transferOwnershipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Transfer ownership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transferOwnershipForm" action="{{ route('dashboard.settings.ownership.transfer') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        You will lose owner privileges. The recipient must already belong to this business.
                    </div>
                    <div class="mb-3">
                        <label for="newOwnerEmail" class="form-label">New owner email</label>
                        <input type="email" class="form-control" id="newOwnerEmail" name="new_owner_email" placeholder="team@company.com" required>
                        <div class="form-text">We’ll promote this user and update your role to administrator.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="transferOwnershipSubmit">Transfer ownership</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteBusinessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete business</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteBusinessForm" action="{{ route('dashboard.settings.business.destroy') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        <i class="bi bi-exclamation-octagon me-2"></i>
                        This action is permanent. All metrics, feeds, and history will be removed.
                    </div>
                    <p class="mb-3">Type <strong>DELETE</strong> to confirm:</p>
                    <input type="text" class="form-control" id="deleteConfirmation" name="confirmation" placeholder="DELETE" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deleteConfirmButton" disabled>Delete business</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .logo-preview {
        height: 140px;
        width: 140px;
    }
    .logo-preview img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
    .avatar-initial {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: rgba(13, 110, 253, 0.15);
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .dark-theme .card.bg-light {
        background-color: rgba(33, 37, 41, 0.7) !important;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const routes = {
            identity: @json(route('dashboard.settings.update')),
            branding: @json(route('dashboard.settings.branding')),
            preferences: @json(route('dashboard.settings.preferences')),
            regenerate: @json(route('dashboard.settings.invitation.regenerate')),
            transfer: @json(route('dashboard.settings.ownership.transfer')),
            deleteBusiness: @json(route('dashboard.settings.business.destroy')),
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const canManageBusiness = @json($canManageBusiness);
        const canManageOwnership = @json($canManageOwnership);
        const alertsContainer = document.querySelector('.container-fluid');

        const identityForm = document.getElementById('identityForm');
        if (identityForm && canManageBusiness) {
            identityForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButton = identityForm.querySelector('button[type="submit"]');
                setLoading(submitButton, true);
                const formData = new FormData(identityForm);
                formData.append('_token', csrfToken);
                formData.append('_method', 'PUT');
                try {
                    const data = await requestJson(routes.identity, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });
                    showAlert('success', data.message ?? 'Business profile updated.');
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    setLoading(submitButton, false);
                }
            });
        }

        const logoInput = document.getElementById('logoInput');
        const logoForm = document.getElementById('logoForm');
        const logoRemoveBtn = document.getElementById('logoRemoveBtn');
        const logoPreviewImage = document.getElementById('logoPreviewImage');
        const logoPreviewContainer = document.querySelector('.logo-preview');
        const removeLogoInput = document.getElementById('removeLogoInput');

        if (logoInput && canManageBusiness) {
            logoInput.addEventListener('change', async () => {
                if (!logoInput.files.length) {
                    return;
                }
                const file = logoInput.files[0];
                const formData = new FormData();
                formData.append('logo', file);
                formData.append('_token', csrfToken);
                await submitBranding(formData);
                logoInput.value = '';
            });
        }

        if (logoRemoveBtn && canManageBusiness) {
            logoRemoveBtn.addEventListener('click', async () => {
                const formData = new FormData();
                formData.append('remove_logo', '1');
                formData.append('_token', csrfToken);
                await submitBranding(formData);
            });
        }

        async function submitBranding(formData) {
            try {
                const data = await requestJson(routes.branding, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });

                if (data.logo_url) {
                    updateLogoPreview(data.logo_url);
                } else if (formData.get('remove_logo') === '1') {
                    updateLogoPreview(null);
                }

                if (data.dashboard_display_name) {
                    const displayNameField = document.getElementById('dashboardDisplayName');
                    if (displayNameField) {
                        displayNameField.value = data.dashboard_display_name;
                    }
                }

                showAlert('success', data.message ?? 'Branding updated.');
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                if (removeLogoInput) {
                    removeLogoInput.value = '0';
                }
            }
        }

        function updateLogoPreview(url) {
            if (!logoPreviewContainer) return;
            if (url) {
                if (logoPreviewImage) {
                    logoPreviewImage.src = url;
                } else {
                    logoPreviewContainer.innerHTML = `<img id="logoPreviewImage" src="${url}" alt="Business logo" class="img-fluid rounded">`;
                }
            } else {
                logoPreviewContainer.innerHTML = '<i class="bi bi-building text-muted" style="font-size: 3rem;"></i>';
            }
        }

        const preferencesForm = document.getElementById('preferencesForm');
        const accentColorInput = document.getElementById('accentColor');
        const accentColorValue = document.getElementById('accentColorValue');

        if (preferencesForm) {
            preferencesForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButton = preferencesForm.querySelector('button[type="submit"]');
                setLoading(submitButton, true);
                const formData = new FormData(preferencesForm);
                formData.append('_token', csrfToken);
                try {
                    const data = await requestJson(routes.preferences, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });
                    showAlert('success', data.message ?? 'Preferences saved.');
                    applyThemeChanges(data.theme, data.accent_color);
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    setLoading(submitButton, false);
                }
            });
        }

        if (accentColorInput && accentColorValue) {
            accentColorInput.addEventListener('input', () => {
                accentColorValue.value = accentColorInput.value.toUpperCase();
                applyThemeChanges(document.getElementById('theme')?.value ?? 'light', accentColorInput.value);
            });

            accentColorValue.addEventListener('blur', () => {
                let value = accentColorValue.value.trim();
                if (!value) {
                    value = '#0D6EFD';
                }
                if (!value.startsWith('#')) {
                    value = '#' + value;
                }
                accentColorValue.value = value.toUpperCase();
                accentColorInput.value = value;
                applyThemeChanges(document.getElementById('theme')?.value ?? 'light', value);
            });
        }

        const copyCodeBtn = document.getElementById('copyCodeBtn');
        if (copyCodeBtn) {
            copyCodeBtn.addEventListener('click', async () => {
                const codeElement = document.getElementById('currentInvitationCode');
                const code = codeElement ? codeElement.textContent.trim() : '';
                if (!code) {
                    showAlert('warning', 'No invitation code available.');
                    return;
                }
                try {
                    await navigator.clipboard.writeText(code);
                    showAlert('success', 'Invitation code copied to clipboard.');
                } catch (error) {
                    showAlert('danger', 'Failed to copy invitation code.');
                }
            });
        }

        const regenerateCodeBtn = document.getElementById('regenerateCodeBtn');
        if (regenerateCodeBtn && canManageOwnership) {
            regenerateCodeBtn.addEventListener('click', async () => {
                if (!confirm('Regenerate invitation code? The previous code will stop working.')) {
                    return;
                }
                regenerateCodeBtn.disabled = true;
                try {
                    const data = await requestJson(routes.regenerate, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                    const codeElement = document.getElementById('currentInvitationCode');
                    if (codeElement) {
                        codeElement.textContent = data.new_code;
                    }
                    showAlert('success', data.message ?? 'Invitation code regenerated.');
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    regenerateCodeBtn.disabled = false;
                }
            });
        }

        const transferOwnershipForm = document.getElementById('transferOwnershipForm');
        if (transferOwnershipForm && canManageOwnership) {
            transferOwnershipForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButton = document.getElementById('transferOwnershipSubmit');
                setLoading(submitButton, true);
                const formData = new FormData(transferOwnershipForm);
                formData.append('_token', csrfToken);
                try {
                    const data = await requestJson(routes.transfer, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });
                    showAlert('success', data.message ?? 'Ownership transferred.');
                    bootstrap.Modal.getInstance(document.getElementById('transferOwnershipModal')).hide();
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 1500);
                    }
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    setLoading(submitButton, false);
                }
            });
        }

        const deleteBusinessForm = document.getElementById('deleteBusinessForm');
        const deleteConfirmationInput = document.getElementById('deleteConfirmation');
        const deleteConfirmButton = document.getElementById('deleteConfirmButton');
        if (deleteConfirmationInput && deleteConfirmButton) {
            deleteConfirmationInput.addEventListener('input', () => {
                deleteConfirmButton.disabled = deleteConfirmationInput.value.trim().toUpperCase() !== 'DELETE';
            });
        }
        if (deleteBusinessForm && canManageOwnership) {
            deleteBusinessForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                setLoading(deleteConfirmButton, true);
                const formData = new FormData(deleteBusinessForm);
                formData.append('_token', csrfToken);
                formData.append('_method', 'DELETE');
                try {
                    const data = await requestJson(routes.deleteBusiness, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData,
                    });
                    showAlert('success', data.message ?? 'Business deleted.');
                    bootstrap.Modal.getInstance(document.getElementById('deleteBusinessModal')).hide();
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 1500);
                    }
                } catch (error) {
                    showAlert('danger', error.message);
                } finally {
                    setLoading(deleteConfirmButton, false);
                }
            });
        }

        function setLoading(button, isLoading) {
            if (!button) return;
            if (isLoading) {
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.innerHTML;
                }
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            } else {
                button.disabled = false;
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                    delete button.dataset.originalText;
                }
            }
        }

        async function requestJson(url, options = {}) {
            const response = await fetch(url, options);
            let payload = {};
            try {
                payload = await response.json();
            } catch (error) {
                payload = {};
            }
            if (!response.ok) {
                throw new Error(extractErrorMessage(payload));
            }
            return payload;
        }

        function extractErrorMessage(payload) {
            if (!payload) {
                return 'Unexpected error occurred.';
            }
            if (payload.message) {
                return payload.message;
            }
            if (payload.error) {
                return payload.error;
            }
            if (payload.errors) {
                const flattened = Object.values(payload.errors).flat();
                if (flattened.length) {
                    return flattened[0];
                }
            }
            return 'Unexpected error occurred.';
        }

        function showAlert(type, message) {
            if (!alertsContainer) return;
            const iconClass = type === 'success' ? 'bi-check-circle-fill' : type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi ${iconClass} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
            alertsContainer.prepend(wrapper.firstElementChild);
            if (type === 'success') {
                setTimeout(() => {
                    const alertNode = alertsContainer.querySelector('.alert');
                    if (alertNode) {
                        bootstrap.Alert.getOrCreateInstance(alertNode).close();
                    }
                }, 5000);
            }
        }

        function applyThemeChanges(theme, accentColor) {
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }
            if (accentColor) {
                document.documentElement.style.setProperty('--bs-primary', accentColor);
                document.documentElement.style.setProperty('--bs-link-color', accentColor);
                document.documentElement.style.setProperty('--bs-link-hover-color', accentColor);
                document.documentElement.style.setProperty('--bs-primary-rgb', hexToRgb(accentColor));
            }
        }

        function hexToRgb(hex) {
            const sanitized = hex.replace('#', '');
            if (sanitized.length !== 6) {
                return '13, 110, 253';
            }
            const bigint = parseInt(sanitized, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `${r}, ${g}, ${b}`;
        }
    });
</script>
@endsection
