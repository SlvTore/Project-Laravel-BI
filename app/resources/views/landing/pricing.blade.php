@extends('layouts.landingPage')

@section('title', 'Pricing - Traction Tracker')

@section('content')

<!-- Hero Section -->
<section class="hero-section-pricing">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-8 mx-auto text-center">
                <div class="hero-content text-white">
                    <h1 class="display-3 fw-bold mb-4">Paket yang Sesuai untuk Setiap Bisnis</h1>
                    <p class="lead mb-4">Pilih paket yang tepat untuk skala bisnis Anda. Mulai gratis, upgrade kapan saja.</p>
                    <div class="pricing-toggle mb-4">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="billing" id="monthly" checked>
                            <label class="btn btn-outline-light" for="monthly">Bulanan</label>

                            <input type="radio" class="btn-check" name="billing" id="yearly">
                            <label class="btn btn-outline-light" for="yearly">Tahunan <span class="badge bg-success ms-2">Hemat 20%</span></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Plans -->
<section class="pricing-plans py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Starter Plan -->
            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <div class="pricing-header text-center">
                        <h3 class="fw-bold" style="color: #1e3c80">Starter</h3>
                        <p class="text-muted">Untuk bisnis yang baru memulai</p>
                        <div class="price-display">
                            <span class="price monthly-price">
                                <span class="currency">Rp</span>
                                <span class="amount">0</span>
                                <span class="period">/bulan</span>
                            </span>
                            <span class="price yearly-price d-none">
                                <span class="currency">Rp</span>
                                <span class="amount">0</span>
                                <span class="period">/tahun</span>
                            </span>
                        </div>
                        <p class="small text-muted">Gratis selamanya</p>
                    </div>

                    <div class="pricing-features">
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Dashboard dasar</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> 3 metrik tracking</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Data history 30 hari</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Email support</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Export data basic</li>
                            <li class="text-muted"><i class="bi bi-x-circle"></i> Advanced analytics</li>
                            <li class="text-muted"><i class="bi bi-x-circle"></i> Custom reports</li>
                            <li class="text-muted"><i class="bi bi-x-circle"></i> API access</li>
                        </ul>
                    </div>

                    <div class="pricing-footer">
                        <a href="{{ route('register') }}" class="btn btn-outline-primary w-100 rounded-pill py-3">Mulai Gratis</a>
                    </div>
                </div>
            </div>

            <!-- Professional Plan -->
            <div class="col-lg-4">
                <div class="pricing-card pricing-card-featured h-100">
                    <div class="popular-badge">Most Popular</div>
                    <div class="pricing-header text-center">
                        <h3 class="fw-bold" style="color: #1e3c80">Professional</h3>
                        <p class="text-muted">Untuk UMKM dan bisnis berkembang</p>
                        <div class="price-display">
                            <span class="price monthly-price">
                                <span class="currency">Rp</span>
                                <span class="amount">299.000</span>
                                <span class="period">/bulan</span>
                            </span>
                            <span class="price yearly-price d-none">
                                <span class="currency">Rp</span>
                                <span class="amount">2.870.400</span>
                                <span class="period">/tahun</span>
                            </span>
                        </div>
                        <p class="small text-success yearly-savings d-none">Hemat Rp 617.600/tahun</p>
                    </div>

                    <div class="pricing-features">
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Semua fitur Starter</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Dashboard advanced</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> 10 metrik tracking</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Data history 1 tahun</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Custom reports</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Priority support</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Mobile app access</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Team collaboration (5 users)</li>
                        </ul>
                    </div>

                    <div class="pricing-footer">
                        <a href="{{ route('register') }}" class="btn btn-primary w-100 rounded-pill py-3">Pilih Professional</a>
                    </div>
                </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="col-lg-4">
                <div class="pricing-card h-100">
                    <div class="pricing-header text-center">
                        <h3 class="fw-bold" style="color: #1e3c80">Enterprise</h3>
                        <p class="text-muted">Untuk perusahaan besar</p>
                        <div class="price-display">
                            <span class="price monthly-price">
                                <span class="currency">Rp</span>
                                <span class="amount">999.000</span>
                                <span class="period">/bulan</span>
                            </span>
                            <span class="price yearly-price d-none">
                                <span class="currency">Rp</span>
                                <span class="amount">9.590.400</span>
                                <span class="period">/tahun</span>
                            </span>
                        </div>
                        <p class="small text-success yearly-savings d-none">Hemat Rp 2.398.600/tahun</p>
                    </div>

                    <div class="pricing-features">
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Semua fitur Professional</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Unlimited metrik tracking</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Data history unlimited</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> AI-powered insights</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> API access</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> White-label solution</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Dedicated account manager</li>
                            <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Unlimited users</li>
                        </ul>
                    </div>

                    <div class="pricing-footer">
                        <a href="mailto:sales@tractiontracker.com" class="btn btn-outline-primary w-100 rounded-pill py-3">Hubungi Sales</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature Comparison -->
<section class="feature-comparison py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Perbandingan Fitur Lengkap</h2>
            <p class="lead text-muted">Lihat detail fitur yang tersedia di setiap paket</p>
        </div>

        <div class="table-responsive">
            <table class="comparison-table table">
                <thead>
                    <tr>
                        <th>Fitur</th>
                        <th class="text-center">Starter</th>
                        <th class="text-center featured-col">Professional</th>
                        <th class="text-center">Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold">Dashboard</td>
                        <td class="text-center">Basic</td>
                        <td class="text-center">Advanced</td>
                        <td class="text-center">Custom</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Metrik Tracking</td>
                        <td class="text-center">3</td>
                        <td class="text-center">10</td>
                        <td class="text-center">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Data History</td>
                        <td class="text-center">30 hari</td>
                        <td class="text-center">1 tahun</td>
                        <td class="text-center">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Users</td>
                        <td class="text-center">1</td>
                        <td class="text-center">5</td>
                        <td class="text-center">Unlimited</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Mobile App</td>
                        <td class="text-center"><i class="bi bi-x text-danger"></i></td>
                        <td class="text-center"><i class="bi bi-check text-success"></i></td>
                        <td class="text-center"><i class="bi bi-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">API Access</td>
                        <td class="text-center"><i class="bi bi-x text-danger"></i></td>
                        <td class="text-center"><i class="bi bi-x text-danger"></i></td>
                        <td class="text-center"><i class="bi bi-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">AI Insights</td>
                        <td class="text-center"><i class="bi bi-x text-danger"></i></td>
                        <td class="text-center"><i class="bi bi-x text-danger"></i></td>
                        <td class="text-center"><i class="bi bi-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Support</td>
                        <td class="text-center">Email</td>
                        <td class="text-center">Priority</td>
                        <td class="text-center">Dedicated</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Frequently Asked Questions</h2>
            <p class="lead text-muted">Jawaban untuk pertanyaan yang sering ditanyakan</p>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="pricingFAQ">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Apakah ada free trial?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Ya! Paket Starter gratis selamanya. Untuk paket Professional dan Enterprise, kami menyediakan free trial 14 hari tanpa perlu kartu kredit.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Bagaimana cara upgrade/downgrade paket?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Anda bisa upgrade atau downgrade paket kapan saja melalui dashboard. Perubahan akan berlaku di billing cycle berikutnya.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Apakah data saya aman?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Absolut! Kami menggunakan enkripsi tingkat enterprise, backup otomatis, dan fully compliant dengan standar keamanan data internasional.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Metode pembayaran apa saja yang diterima?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFAQ">
                            <div class="accordion-body">
                                Kami menerima kartu kredit/debit (Visa, Mastercard), bank transfer, dan e-wallet (GoPay, OVO, DANA).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5" style="background: linear-gradient(135deg, #1e3c80 0%, #7cb947 100%);">
    <div class="container text-center text-white">
        <h2 class="display-5 fw-bold mb-4">Siap Memulai?</h2>
        <p class="lead mb-4">Bergabunglah dengan 500+ bisnis yang sudah merasakan manfaatnya</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg rounded-pill px-5 py-3">Mulai Gratis</a>
            <a href="mailto:sales@tractiontracker.com" class="btn btn-outline-light btn-lg rounded-pill px-5 py-3">Konsultasi Sales</a>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pricing toggle functionality
    const monthlyRadio = document.getElementById('monthly');
    const yearlyRadio = document.getElementById('yearly');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');
    const yearlySavings = document.querySelectorAll('.yearly-savings');

    function togglePricing() {
        if (yearlyRadio.checked) {
            monthlyPrices.forEach(price => price.classList.add('d-none'));
            yearlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlySavings.forEach(saving => saving.classList.remove('d-none'));
        } else {
            monthlyPrices.forEach(price => price.classList.remove('d-none'));
            yearlyPrices.forEach(price => price.classList.add('d-none'));
            yearlySavings.forEach(saving => saving.classList.add('d-none'));
        }
    }

    monthlyRadio.addEventListener('change', togglePricing);
    yearlyRadio.addEventListener('change', togglePricing);
});
</script>
@endsection
