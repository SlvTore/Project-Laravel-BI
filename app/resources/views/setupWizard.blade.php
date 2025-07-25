<?php
@extends('layouts.setup')

@section('title', 'Setup Akun - Pilih Role Anda')

@section('content')
<div class="wizard-container">
    <!-- Progress Header -->
    <div class="text-center mb-5">
        <div class="d-inline-flex align-items-center bg-white rounded-pill px-4 py-2 shadow-sm">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                <i class="bi bi-1-circle-fill"></i>
            </div>
            <span class="fw-semibold text-muted">Langkah 1 dari 1</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-4 shadow-sm p-5">
        <div class="text-center mb-5">
            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-person-badge text-primary fs-1"></i>
            </div>
            <h2 class="fw-bold text-dark mb-3">Pilih Role Anda</h2>
            <p class="text-muted lead">
                Silakan pilih peran yang paling sesuai dengan posisi Anda.
                Ini akan menentukan akses dan fitur yang tersedia di dashboard.
            </p>
        </div>

        <!-- Alert untuk error -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Oops!</strong> {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('setup.store') }}" id="roleForm">
            @csrf

            <div class="row g-4 mb-5">
                @foreach($roles as $role)
                    @php
                        $roleConfig = [
                            'owner' => [
                                'icon' => 'bi-crown-fill',
                                'color' => 'warning',
                                'bg' => 'warning'
                            ],
                            'admin' => [
                                'icon' => 'bi-gear-fill',
                                'color' => 'primary',
                                'bg' => 'primary'
                            ],
                            'mentor' => [
                                'icon' => 'bi-lightbulb-fill',
                                'color' => 'success',
                                'bg' => 'success'
                            ],
                            'investigator' => [
                                'icon' => 'bi-search',
                                'color' => 'info',
                                'bg' => 'info'
                            ]
                        ][$role->name] ?? [
                            'icon' => 'bi-person-fill',
                            'color' => 'secondary',
                            'bg' => 'secondary'
                        ];
                    @endphp

                    <div class="col-md-6">
                        <input
                            type="radio"
                            class="btn-check"
                            name="role_id"
                            id="role_{{ $role->id }}"
                            value="{{ $role->id }}"
                            {{ old('role_id') == $role->id ? 'checked' : '' }}
                        >
                        <label class="card role-card h-100 w-100" for="role_{{ $role->id }}">
                            <div class="card-body text-center p-4">
                                <div class="role-icon bg-{{ $roleConfig['bg'] }} bg-opacity-15 mx-auto">
                                    <i class="{{ $roleConfig['icon'] }} text-{{ $roleConfig['color'] }} fs-3"></i>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">{{ $role->display_name }}</h5>
                                <p class="card-text text-muted small mb-3">{{ $role->description }}</p>

                                <!-- Feature list berdasarkan role -->
                                <div class="text-start">
                                    <small class="text-muted">Fitur yang tersedia:</small>
                                    <ul class="list-unstyled mt-2 small">
                                        @if($role->name === 'owner')
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Akses penuh dashboard</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Manajemen tim & undangan</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Laporan komprehensif</li>
                                        @elseif($role->name === 'admin')
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Dashboard manajemen</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Input & edit data</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Laporan operasional</li>
                                        @elseif($role->name === 'mentor')
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> View dashboard</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Akses data historis</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Export laporan</li>
                                        @elseif($role->name === 'investigator')
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> View analytics</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Data exploration</li>
                                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Custom reports</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </label>
                    </div>
                @endforeach
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button
                    type="submit"
                    class="btn btn-primary btn-lg px-5 py-3"
                    id="submitBtn"
                    disabled
                >
                    <i class="bi bi-arrow-right-circle me-2"></i>
                    Lanjutkan ke Dashboard
                </button>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-check me-1"></i>
                        Role Anda dapat diubah nanti oleh Owner
                    </small>
                </div>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="text-center mt-4">
        <div class="bg-light rounded-3 p-4">
            <h6 class="fw-semibold text-dark mb-2">
                <i class="bi bi-question-circle me-2"></i>
                Butuh Bantuan?
            </h6>
            <p class="text-muted small mb-3">
                Tidak yakin role mana yang tepat? Berikut penjelasan singkatnya:
            </p>
            <div class="row g-3 text-start">
                <div class="col-md-6">
                    <div class="d-flex">
                        <i class="bi bi-crown text-warning me-2 mt-1"></i>
                        <div>
                            <strong class="small">Owner:</strong>
                            <span class="small text-muted">Pemilik bisnis dengan kontrol penuh</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <i class="bi bi-gear text-primary me-2 mt-1"></i>
                        <div>
                            <strong class="small">Admin:</strong>
                            <span class="small text-muted">Pegawai yang mengelola operasional</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <i class="bi bi-lightbulb text-success me-2 mt-1"></i>
                        <div>
                            <strong class="small">Mentor:</strong>
                            <span class="small text-muted">Pembimbing yang memantau progress</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <i class="bi bi-search text-info me-2 mt-1"></i>
                        <div>
                            <strong class="small">Investigator:</strong>
                            <span class="small text-muted">Peneliti yang menganalisis data</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleInputs = document.querySelectorAll('input[name="role_id"]');
    const submitBtn = document.getElementById('submitBtn');
    const roleCards = document.querySelectorAll('.role-card');

    // Enable submit button when role is selected
    function updateSubmitButton() {
        const selectedRole = document.querySelector('input[name="role_id"]:checked');
        submitBtn.disabled = !selectedRole;
    }

    // Handle role selection
    roleInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Remove selected class from all cards
            roleCards.forEach(card => card.classList.remove('selected'));

            // Add selected class to current card
            if (this.checked) {
                this.closest('.role-card').classList.add('selected');
            }

            updateSubmitButton();
        });
    });

    // Handle card click
    roleCards.forEach(card => {
        card.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    // Initial check
    updateSubmitButton();

    // Check for pre-selected role (old input)
    const selectedInput = document.querySelector('input[name="role_id"]:checked');
    if (selectedInput) {
        selectedInput.closest('.role-card').classList.add('selected');
    }
});
</script>
@endpush
