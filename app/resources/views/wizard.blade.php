@extends('layouts.setup')

@section('title', 'Setup Akun - Selamat Datang')

@section('content')
<div class="wizard-container">
    <!-- Progress Bar -->
    <div class="mb-5">
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
    <div class="bg-white rounded-4 shadow-sm p-5">
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
                            'owner' => ['icon' => 'bi-crown', 'color' => 'warning', 'bg' => 'warning'],
                            'admin' => ['icon' => 'bi-gear', 'color' => 'primary', 'bg' => 'primary'],
                            'mentor' => ['icon' => 'bi-lightbulb', 'color' => 'success', 'bg' => 'success'],
                            'investigator' => ['icon' => 'bi-search', 'color' => 'info', 'bg' => 'info'],
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
                    <select class="form-select" name="industry">
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
@endsection

@push('styles')
<style>
/* Progress Wizard Styles */
.progress-wizard .step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.progress-wizard .step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.progress-wizard .step-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.progress-wizard .step-item.active .step-circle {
    background: #0d6efd;
    color: white;
}

.progress-wizard .step-item.active .step-label {
    color: #0d6efd;
}

.progress-wizard .step-item.completed .step-circle {
    background: #198754;
    color: white;
}

.progress-wizard .step-item.completed .step-label {
    color: #198754;
}

.progress-wizard .step-connector {
    width: 60px;
    height: 2px;
    background: #e9ecef;
    margin: 0 20px 24px 20px;
}

.progress-wizard .step-connector.active {
    background: #0d6efd;
}

/* Role Card Styles */
.role-card {
    cursor: pointer !important;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    user-select: none;
}

.role-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.role-card.selected {
    border-color: #0d6efd !important;
    background: #f8f9ff !important;
}

.role-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cursor-pointer {
    cursor: pointer !important;
}

/* Form Styles */
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Button disabled state */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed !important;
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
                saveStepData('role', { role_id: selectedRole.value }, () => {
                    console.log('Role saved, showing step 2');
                    showStep(2);
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
});
</script>
@endpush
