@extends('layouts.setup')

@section('title', 'Setup Akun - Selamat Datang')

@section('content')
<div class="wizard-container">
    <!-- Progress Bar -->
    <div class="mb-2">
        <div class="d-flex justify-content-center">
            <div class="progress-wizard d-flex align-items-center">
                <div class="step-item active" data-step="1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Role</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-item" data-step="2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Bisnis</span>
                </div>
                <div class="step-connector"></div>
                <div class="step-item" data-step="3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Target</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Step Content -->
    <div class="wizard-card">
        <!-- Step 1: Role Selection -->
        <div class="step-content" id="step-1">
            <div class="text-center mb-5">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-person-badge text-primary fs-1"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Pilih Role Anda</h2>
                <p class="text-muted lead">
                    Silakan pilih peran yang paling sesuai dengan posisi Anda dalam bisnis.
                </p>
            </div>

            <div class="row g-4 mb-5">
                @foreach($roles as $role)
                    @php
                        $roleConfig = [
                            'business-owner' => ['icon' => 'bi-crown', 'color' => 'warning', 'bg' => 'warning'],
                            'administrator' => ['icon' => 'bi-gear', 'color' => 'primary', 'bg' => 'primary'],
                            'staff' => ['icon' => 'bi-person-check', 'color' => 'success', 'bg' => 'success'],
                            'business-investigator' => ['icon' => 'bi-search', 'color' => 'info', 'bg' => 'info'],
                        ][$role->name] ?? ['icon' => 'bi-person', 'color' => 'secondary', 'bg' => 'secondary'];
                    @endphp
                    <div class="col-md-6">
                        <div class="role-card-container">
                            <input
                                type="radio"
                                name="role_id"
                                id="role_{{ $role->id }}"
                                value="{{ $role->id }}"
                                class="d-none role-input"
                                data-role-name="{{ $role->name }}"
                            >
                            <label class="card role-card h-100 w-100 cursor-pointer" for="role_{{ $role->id }}">
                                <div class="card-body text-center p-4">
                                    <div class="role-icon bg-{{ $roleConfig['bg'] }} bg-opacity-15 mx-auto mb-3">
                                        <i class="{{ $roleConfig['icon'] }} text-{{ $roleConfig['color'] }} fs-3"></i>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mb-2">{{ $role->display_name }}</h5>
                                    <p class="card-text text-muted small">{{ $role->description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-primary btn-lg px-5" id="nextStep1" disabled>
                    <i class="bi bi-arrow-right me-2"></i>
                    Lanjutkan
                </button>
            </div>
        </div>

        <!-- Step 2: Business Information -->
        <div class="step-content d-none" id="step-2">
            <div class="text-center mb-5">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-building text-primary fs-1"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Informasi Bisnis</h2>
                <p class="text-muted lead">
                    Ceritakan tentang bisnis Anda untuk personalisasi dashboard.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Bisnis <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="business_name" placeholder="Masukkan nama bisnis">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Industri <span class="text-danger">*</span></label>
                    <select class="form-select text-dark" name="industry">
                        <option value="">Pilih industri</option>
                        <option value="Technology">Teknologi</option>
                        <option value="E-commerce">E-commerce</option>
                        <option value="Healthcare">Kesehatan</option>
                        <option value="Education">Pendidikan</option>
                        <option value="Finance">Keuangan</option>
                        <option value="Food & Beverage">Makanan & Minuman</option>
                        <option value="Retail">Retail</option>
                        <option value="Manufacturing">Manufaktur</option>
                        <option value="Services">Jasa</option>
                        <option value="Other">Lainnya</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Deskripsi Bisnis</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Jelaskan tentang bisnis Anda..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tanggal Didirikan</label>
                    <input type="date" class="form-control" name="founded_date">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Website</label>
                    <input type="url" class="form-control" name="website" placeholder="https://example.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pendapatan Awal (Rp)</label>
                    <input type="number" class="form-control" name="initial_revenue" placeholder="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Jumlah Pelanggan Awal</label>
                    <input type="number" class="form-control" name="initial_customers" placeholder="0">
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="button" class="btn btn-outline-secondary me-3" id="prevStep2">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </button>
                <button type="button" class="btn btn-primary btn-lg px-5" id="nextStep2">
                    <i class="bi bi-arrow-right me-2"></i>
                    Lanjutkan
                </button>
            </div>
        </div>

        <!-- Step 3: Goals & Targets -->
        <div class="step-content d-none" id="step-3">
            <div class="text-center mb-5">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-target text-primary fs-1"></i>
                </div>
                <h2 class="fw-bold text-dark mb-3">Target & Tujuan</h2>
                <p class="text-muted lead">
                    Tetapkan target bisnis untuk mengukur progress Anda.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Target Pendapatan (Rp) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="revenue_target" placeholder="100000000">
                    <small class="form-text text-muted">Target pendapatan dalam 1 tahun</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Target Jumlah Pelanggan <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="customer_target" placeholder="500">
                    <small class="form-text text-muted">Target jumlah pelanggan dalam 1 tahun</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Target Pertumbuhan (%) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="growth_rate_target" placeholder="20" min="0" max="100">
                    <small class="form-text text-muted">Target pertumbuhan bulanan</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Metrik Utama</label>
                    <div class="key-metrics-container">
                        <input type="text" class="form-control mb-2" name="key_metrics[]" placeholder="Contoh: Tingkat konversi">
                        <input type="text" class="form-control mb-2" name="key_metrics[]" placeholder="Contoh: Customer lifetime value">
                        <input type="text" class="form-control" name="key_metrics[]" placeholder="Contoh: Monthly recurring revenue">
                    </div>
                    <small class="form-text text-muted">Metrik yang ingin Anda pantau secara khusus</small>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="button" class="btn btn-outline-secondary me-3" id="prevStep3">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </button>
                <button type="button" class="btn btn-success btn-lg px-5" id="completeSetup">
                    <i class="bi bi-check-circle me-2"></i>
                    Selesaikan Setup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invitation Modal -->
<div class="modal fade" id="invitationModal" tabindex="-1" aria-labelledby="invitationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="invitationModalLabel">
                    <i class="bi bi-key me-2"></i>Masukkan Kode Akses
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold text-white">ID Dashboard Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="publicId" placeholder="Contoh: BIZ-ABC123DEF" autocomplete="off">
                    <small class="form-text text-muted">ID ini diberikan oleh Business Owner</small>
                </div>
                <div class="mb-4" id="invitationCodeField" style="display: none;">
                    <label class="form-label fw-semibold text-white">Kode Undangan Staff <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="invitationCode" placeholder="Contoh: ABC12345" autocomplete="off">
                    <small class="form-text text-muted">Kode rahasia yang diberikan oleh Business Owner</small>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="submitInvitation">
                    <i class="bi bi-check-circle me-2"></i>Bergabung
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/setup/wizard.css') }}">
@endpush

@push('scripts')
<script>
    // Configuration for external scripts
    window.csrfToken = "{{ csrf_token() }}";
    window.routes = {
        setup: {
            store: "{{ route('setup.store') }}"
        }
    };
</script>
<script src="{{ asset('js/setup/wizard.js') }}"></script>
@endpush
