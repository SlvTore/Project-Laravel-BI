@extends('layouts.setup')

@section('title', 'Setup Akun - Selamat Datang')

@section('content')
<div class="wizard-container">
    <!-- Invitation Notice -->
    @if($hasInvitation ?? false)
    <div class="alert alert-info text-center mb-4" role="alert">
        <i class="bi bi-envelope-check me-2"></i>
        <strong>Welcome!</strong> 
        @if($inviterName ?? false)
            {{ $inviterName }} invited you to join 
        @else
            You've been invited to join 
        @endif
        <strong>{{ $businessName ?? 'a business' }}</strong>. 
        Select your role and we'll complete your registration.
    </div>
    @endif

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
<style>
/* Wizard Card with Transparent Blur Effect */
.wizard-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    padding: 3rem;
    box-shadow:
        0 20px 40px rgba(0, 0, 0, 0.1),
        0 8px 16px rgba(0, 0, 0, 0.05);
    animation: fadeInUp 0.8s ease-out;
    position: relative;
    overflow: hidden;
}

.wizard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
}

/* Progress Wizard Styles */
.progress-wizard {
    padding: 2rem 0;
    animation: slideInLeft 0.8s ease-out;
}

.progress-wizard .step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    transition: all 0.4s ease;
}

.progress-wizard .step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 12px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

.progress-wizard .step-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
    transition: all 0.3s ease;
}

.progress-wizard .step-item.active .step-circle {
    background: rgba(255, 255, 255, 0.95);
    color: #1e3c80;
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.8);
}

.progress-wizard .step-item.active .step-label {
    color: #ffffff;
    font-weight: 700;
}

.progress-wizard .step-item.completed .step-circle {
    background: rgba(124, 185, 71, 0.9);
    color: white;
    border-color: rgba(124, 185, 71, 0.5);
}

.progress-wizard .step-item.completed .step-label {
    color: rgba(124, 185, 71, 0.9);
    font-weight: 600;
}

.progress-wizard .step-connector {
    width: 80px;
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0 25px 32px 25px;
    border-radius: 2px;
    transition: all 0.4s ease;
}

.progress-wizard .step-connector.active {
    background: rgba(124, 185, 71, 0.7);
    box-shadow: 0 0 10px rgba(124, 185, 71, 0.4);
}

/* Step Content Styles */
.step-content {
    animation: scaleIn 0.6s ease-out;
}

.step-content .text-center {
    animation: fadeInDown 0.8s ease-out 0.2s both;
}

/* Role Card Styles with Transparent Design */
.role-card {
    cursor: pointer !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    user-select: none;
    background: rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border-radius: 16px !important;
    animation: slideInRight 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.role-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.6s ease;
}

.role-card:hover::before {
    left: 100%;
}

.role-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.15) !important;
}

.role-card.selected {
    border-color: rgba(124, 185, 71, 0.8) !important;
    background: rgba(124, 185, 71, 0.1) !important;
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(124, 185, 71, 0.2);
}

.role-card .card-title {
    color: white !important;
    font-weight: 700;
    margin-bottom: 0.8rem;
}

.role-card .card-text {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 0.9rem;
    line-height: 1.5;
}

.role-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: all 0.3s ease;
}

/* Form Styles with Transparent Design */
.form-label {
    color: white !important;
    font-weight: 600;
    margin-bottom: 0.8rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 2px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 12px !important;
    padding: 0.8rem 1rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.form-control::placeholder, .form-select option {
    color: rgba(255, 255, 255, 0.6) !important;
}

.form-control:focus, .form-select:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(124, 185, 71, 0.8) !important;
    box-shadow: 0 0 0 0.2rem rgba(124, 185, 71, 0.25) !important;
    color: white !important;
}

.form-control option {
    background: #1e3c80 !important;
    color: white !important;
}

/* Button Styles */
.btn {
    border-radius: 12px !important;
    padding: 0.8rem 2rem;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%) !important;
    border: none !important;
    box-shadow: 0 8px 20px rgba(124, 185, 71, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(124, 185, 71, 0.4);
    background: linear-gradient(135deg, #6da53c 0%, #1a3470 100%) !important;
}

.btn-success {
    background: linear-gradient(135deg, #7cb947 0%, #5a9a3a 100%) !important;
    border: none !important;
    box-shadow: 0 8px 20px rgba(124, 185, 71, 0.4);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(124, 185, 71, 0.5);
}

.btn-outline-secondary {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
    backdrop-filter: blur(10px);
}

.btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    color: white !important;
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    transform: none !important;
}

/* Text Colors */
.text-dark {
    color: white !important;
}

.text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.lead {
    font-size: 1.1rem;
    font-weight: 400;
    line-height: 1.6;
}

/* Icon Styles */
.bg-primary {
    background: rgba(124, 185, 71, 0.2) !important;
    border: 2px solid rgba(124, 185, 71, 0.3);
    backdrop-filter: blur(10px);
}

.text-primary {
    color: #7cb947 !important;
}

/* Small Text */
.form-text {
    color: rgba(255, 255, 255, 0.7) !important;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    font-weight: 400;
}

/* Row Animation */
.row.g-4 .col-md-6 {
    animation: slideInUp 0.6s ease-out;
}

.row.g-4 .col-md-6:nth-child(2) {
    animation-delay: 0.1s;
}

.row.g-4 .col-md-6:nth-child(3) {
    animation-delay: 0.2s;
}

.row.g-4 .col-md-6:nth-child(4) {
    animation-delay: 0.3s;
}

/* Required Asterisk */
.text-danger {
    color: #ff6b6b !important;
    font-weight: bold;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: rgba(124, 185, 71, 0.6);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(124, 185, 71, 0.8);
}

/* Animation Classes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .wizard-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
        border-radius: 20px;
    }

    .progress-wizard .step-connector {
        width: 60px;
        margin: 0 15px 25px 15px;
    }

    .progress-wizard .step-circle {
        width: 45px;
        height: 45px;
        font-size: 1rem;
    }

    .role-card {
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    .wizard-card {
        padding: 1.5rem 1rem;
        border-radius: 16px;
    }

    .progress-wizard {
        padding: 1.5rem 0;
    }

    .progress-wizard .step-connector {
        width: 40px;
        margin: 0 10px 20px 10px;
    }

    .progress-wizard .step-circle {
        width: 40px;
        height: 40px;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    .progress-wizard .step-label {
        font-size: 0.8rem;
    }
}

/* Ensure modal overlays everything and inputs are clickable */
.modal {
    z-index: 2000 !important;
    position: fixed !important;
}
.modal-backdrop {
    z-index: 1990 !important;
}
.modal-content {
    pointer-events: auto !important;
    position: relative !important;
}

/* Modal input specific styles - simplified and working */
.modal .form-control {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
    pointer-events: auto !important;
    user-select: text !important;
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    cursor: text !important;
}

.modal .form-control:focus {
    background: rgba(255, 255, 255, 0.25) !important;
    border-color: rgba(124, 185, 71, 0.8) !important;
    outline: none !important;
    box-shadow: 0 0 0 0.2rem rgba(124, 185, 71, 0.25) !important;
}

.modal .form-control::placeholder {
    color: rgba(255, 255, 255, 0.6) !important;
}

/* Ensure modal dialog is properly interactive */
.modal-dialog {
    pointer-events: none !important;
}

.modal-dialog .modal-content {
    pointer-events: auto !important;
}

/* Ensure inputs inside modal are always interactive */
.modal input[type="text"] {
    pointer-events: auto !important;
    user-select: text !important;
    cursor: text !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Wizard JavaScript loaded');

    let currentStep = 1;
    const totalSteps = 3;

    // Elements
    const stepItems = document.querySelectorAll('.step-item');
    const stepContents = document.querySelectorAll('.step-content');
    const roleInputs = document.querySelectorAll('.role-input');
    const roleCards = document.querySelectorAll('.role-card');

    // Navigation buttons
    const nextStep1 = document.getElementById('nextStep1');
    const nextStep2 = document.getElementById('nextStep2');
    const prevStep2 = document.getElementById('prevStep2');
    const prevStep3 = document.getElementById('prevStep3');
    const completeSetup = document.getElementById('completeSetup');

    // Debug log
    console.log('Found elements:', {
        stepItems: stepItems.length,
        stepContents: stepContents.length,
        roleInputs: roleInputs.length,
        roleCards: roleCards.length,
        nextStep1: nextStep1 ? 'found' : 'not found'
    });

    // Role selection logic
    roleCards.forEach((card, index) => {
        card.addEventListener('click', function(e) {
            console.log('Role card clicked:', index);

            // Remove selected from all cards and radios
            roleCards.forEach(c => c.classList.remove('selected'));
            roleInputs.forEach(r => r.checked = false);

            // Add selected to clicked card
            this.classList.add('selected');

            // Find and check the corresponding radio input
            const roleId = this.getAttribute('for');
            const radio = document.getElementById(roleId);

            if (radio) {
                radio.checked = true;
                console.log('Radio checked:', radio.value);

                // Enable next button
                if (nextStep1) {
                    nextStep1.disabled = false;
                    console.log('Next button enabled');
                }
            } else {
                // Fallback: try to find radio by index
                if (roleInputs[index]) {
                    roleInputs[index].checked = true;
                    console.log('Fallback: Radio checked by index:', roleInputs[index].value);

                    if (nextStep1) {
                        nextStep1.disabled = false;
                        console.log('Next button enabled (fallback)');
                    }
                }
            }
        });
    });

    // Navigation functions
    function showStep(step) {
        console.log('Showing step:', step);
        // Hide all steps
        stepContents.forEach(content => content.classList.add('d-none'));

        // Show current step
        document.getElementById(`step-${step}`).classList.remove('d-none');

        // Update progress
        updateProgress(step);
        currentStep = step;
    }

    function updateProgress(step) {
        stepItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index + 1 < step) {
                item.classList.add('completed');
            } else if (index + 1 === step) {
                item.classList.add('active');
            }
        });
    }

    // Step navigation
    if (nextStep1) {
        nextStep1.addEventListener('click', function() {
            console.log('Next step 1 clicked');

            const selectedRole = document.querySelector('input[name="role_id"]:checked');
            console.log('Selected role:', selectedRole ? selectedRole.value : 'none');

            if (selectedRole && selectedRole.value) {
                console.log('Saving role data...');
                const roleName = selectedRole.getAttribute('data-role-name');

                saveStepData('role', { role_id: selectedRole.value }, (response) => {
                    console.log('Role saved, response:', response);

                    if (response.next_step === 'accept_invitation') {
                        // User has invitation, accept it directly
                        showAcceptInvitationModal();
                    } else if (response.next_step === 'invitation') {
                        // Show invitation modal for staff and business-investigator
                        showInvitationModal(roleName);
                    } else {
                        // Continue to business step for business-owner
                        showStep(2);
                    }
                });
            } else {
                alert('Silakan pilih role terlebih dahulu!');
            }
        });
    }

    if (nextStep2) {
        nextStep2.addEventListener('click', function() {
            console.log('Next step 2 clicked');

            const businessData = {
                business_name: document.querySelector('[name="business_name"]').value,
                industry: document.querySelector('[name="industry"]').value,
                description: document.querySelector('[name="description"]').value,
                founded_date: document.querySelector('[name="founded_date"]').value,
                website: document.querySelector('[name="website"]').value,
                initial_revenue: document.querySelector('[name="initial_revenue"]').value,
                initial_customers: document.querySelector('[name="initial_customers"]').value,
            };

            console.log('Business data to save:', businessData);

            // Validate required fields
            if (!businessData.business_name || !businessData.industry) {
                alert('Nama bisnis dan industri wajib diisi!');
                return;
            }

            saveStepData('business', businessData, () => {
                showStep(3);
            });
        });
    }

    if (prevStep2) {
        prevStep2.addEventListener('click', () => showStep(1));
    }

    if (prevStep3) {
        prevStep3.addEventListener('click', () => showStep(2));
    }

    if (completeSetup) {
        completeSetup.addEventListener('click', function() {
            const goalsData = {
                revenue_target: document.querySelector('[name="revenue_target"]').value,
                customer_target: document.querySelector('[name="customer_target"]').value,
                growth_rate_target: document.querySelector('[name="growth_rate_target"]').value,
                key_metrics: Array.from(document.querySelectorAll('[name="key_metrics[]"]'))
                                  .map(input => input.value)
                                  .filter(value => value.trim() !== ''),
            };

            // Validate required fields
            if (!goalsData.revenue_target || !goalsData.customer_target || !goalsData.growth_rate_target) {
                alert('Semua target wajib diisi!');
                return;
            }

            saveStepData('goals', goalsData, (response) => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        });
    }

    // Save step data function with better error handling
    function saveStepData(step, data, callback) {
        console.log('Saving step data:', step, data);
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('step', step);

        Object.keys(data).forEach(key => {
            if (Array.isArray(data[key])) {
                data[key].forEach(value => {
                    formData.append(`${key}[]`, value);
                });
            } else {
                formData.append(key, data[key]);
            }
        });

        fetch('{{ route("setup.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success) {
                callback(data);
            } else {
                console.error('Server error:', data);
                if (data.errors) {
                    let errorMessages = [];
                    Object.values(data.errors).forEach(errorArray => {
                        errorMessages = errorMessages.concat(errorArray);
                    });
                    alert('Error: ' + errorMessages.join(', '));
                } else {
                    alert(data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        });
    }

    // Invitation modal functions
    function showInvitationModal(roleName) {
        console.log('showInvitationModal called with role:', roleName);
        const modal = document.getElementById('invitationModal');
        const invitationCodeField = document.getElementById('invitationCodeField');
        const publicIdInput = document.getElementById('publicId');
        const invitationCodeInput = document.getElementById('invitationCode');

        if (!modal || !publicIdInput) {
            console.error('Modal elements not found');
            return;
        }

        // Clear previous values and ensure inputs are fully enabled
        publicIdInput.value = '';
        if (invitationCodeInput) {
            invitationCodeInput.value = '';
        }

        // Show invitation code field for staff only
        if (roleName === 'staff') {
            invitationCodeField.style.display = 'block';
        } else {
            invitationCodeField.style.display = 'none';
        }

        // Show modal using Bootstrap if available
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            console.log('Using Bootstrap modal');
            const instance = window.bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: 'static',
                keyboard: false
            });
            instance.show();
        } else {
            console.log('Using fallback modal');
            // Simplified fallback without complex focus management
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    // Function to show accept invitation modal for invited users
    function showAcceptInvitationModal() {
        // For invited users, we auto-accept the invitation
        const businessName = @json($businessName ?? 'this business');
        
        if (confirm(`Welcome! You've been invited to join ${businessName}. Click OK to complete your registration and join the business.`)) {
            saveStepData('accept_invitation', {}, (response) => {
                if (response.redirect) {
                    alert(response.message || 'Welcome to the team!');
                    window.location.href = response.redirect;
                }
            });
        }
    }

    // Setup input event listeners - simplified approach
    ['publicId', 'invitationCode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            console.log('Setting up event listeners for:', id);

            // Ensure input is enabled
            el.disabled = false;
            el.readOnly = false;
            
            // Basic event listeners without interference
            el.addEventListener('keydown', (e) => {
                console.log('Keydown on', id, ':', e.key);
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('submitInvitation')?.click();
                }
            });

            el.addEventListener('input', (e) => {
                console.log('Input event on', id, ':', e.target.value);
            });

            el.addEventListener('click', (e) => {
                console.log('Click event on', id);
                e.target.focus();
            });
        }
    });

    // Handle invitation form submission
    const submitInvitationBtn = document.getElementById('submitInvitation');
    if (submitInvitationBtn) {
        submitInvitationBtn.addEventListener('click', function() {
            const publicId = document.getElementById('publicId').value;
            const invitationCode = document.getElementById('invitationCode').value;
            const selectedRole = document.querySelector('input[name="role_id"]:checked');

            if (!publicId) {
                alert('ID Dashboard Perusahaan wajib diisi!');
                return;
            }

            const roleName = selectedRole ? selectedRole.getAttribute('data-role-name') : '';

            // Only staff needs invitation code, business-investigator just needs public_id
            if (roleName === 'staff' && !invitationCode) {
                alert('Kode Undangan Staff wajib diisi!');
                return;
            }

            const invitationData = {
                public_id: publicId,
            };

            // Only add invitation code for staff
            if (roleName === 'staff') {
                invitationData.invitation_code = invitationCode;
            }

            console.log('Submitting invitation data:', invitationData);

            saveStepData('invitation', invitationData, (response) => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        });
    }
});
</script>
@endpush
