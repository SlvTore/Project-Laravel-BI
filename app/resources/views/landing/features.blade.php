@extends('layouts.landingPage')

@section('title', 'Features - Traction Tracker')

@section('content')

<!-- Hero Section -->
<section class="hero-section-features">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="hero-content text-white">
                    <h1 class="display-3 fw-bold mb-4">Fitur Lengkap untuk Bisnis Modern</h1>
                    <p class="lead mb-4">Dapatkan insight mendalam dengan dashboard yang powerful, analytics real-time, dan tools yang dirancang untuk mengoptimalkan performa bisnis Anda.</p>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-3">Coba Gratis Sekarang</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="https://via.placeholder.com/600x400/f8f9fa/1e3c80?text=Dashboard+Preview" alt="Dashboard Preview" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Features Grid -->
<section class="features-grid py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Semua yang Anda Butuhkan</h2>
            <p class="lead text-muted">Tools lengkap untuk menganalisis, memantau, dan mengoptimalkan bisnis Anda</p>
        </div>

        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-graph-up-arrow" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Real-time Analytics</h4>
                    <p class="text-muted mb-4">Monitor performa bisnis Anda secara real-time dengan dashboard yang responsif dan data yang selalu update.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Live data tracking</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Custom dashboards</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Interactive charts</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-people-fill" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Customer Insights</h4>
                    <p class="text-muted mb-4">Pahami perilaku customer Anda dengan analytics mendalam dan segmentasi yang akurat.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Customer segmentation</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Behavior tracking</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Retention analysis</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-bar-chart-line" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Revenue Tracking</h4>
                    <p class="text-muted mb-4">Lacak revenue stream, forecast penjualan, dan identifikasi peluang pertumbuhan baru.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Revenue forecasting</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Profit margin analysis</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Growth tracking</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-shield-check" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Data Security</h4>
                    <p class="text-muted mb-4">Keamanan data tingkat enterprise dengan enkripsi end-to-end dan backup otomatis.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> End-to-end encryption</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Auto backup</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> GDPR compliant</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-phone" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Mobile App</h4>
                    <p class="text-muted mb-4">Akses data bisnis Anda kapan saja, dimana saja dengan mobile app yang user-friendly.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> iOS & Android app</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Offline access</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Push notifications</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card-detailed h-100">
                    <div class="feature-icon-large">
                        <i class="bi bi-puzzle" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Integrations</h4>
                    <p class="text-muted mb-4">Integrasi mudah dengan tools favorit Anda untuk workflow yang seamless.</p>
                    <ul class="feature-list">
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> 50+ integrations</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> API access</li>
                        <li><i class="bi bi-check-circle-fill" style="color: #7cb947"></i> Webhook support</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Features -->
<section class="advanced-features py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Fitur Advanced</h2>
            <p class="lead text-muted">Untuk bisnis yang membutuhkan analisis lebih mendalam</p>
        </div>

        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4" style="color: #1e3c80">AI-Powered Insights</h3>
                <p class="lead mb-4">Machine learning yang menganalisis pola bisnis Anda dan memberikan rekomendasi actionable untuk meningkatkan performa.</p>
                <ul class="advanced-feature-list">
                    <li><i class="bi bi-robot" style="color: #7cb947"></i> Predictive analytics</li>
                    <li><i class="bi bi-lightbulb" style="color: #7cb947"></i> Smart recommendations</li>
                    <li><i class="bi bi-graph-up" style="color: #7cb947"></i> Trend forecasting</li>
                    <li><i class="bi bi-bell" style="color: #7cb947"></i> Anomaly detection</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/500x350/f8f9fa/1e3c80?text=AI+Analytics" alt="AI Analytics" class="img-fluid rounded shadow">
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2">
                <h3 class="fw-bold mb-4" style="color: #1e3c80">Custom Reports</h3>
                <p class="lead mb-4">Buat laporan yang sesuai dengan kebutuhan spesifik bisnis Anda dengan drag-and-drop report builder.</p>
                <ul class="advanced-feature-list">
                    <li><i class="bi bi-file-earmark-text" style="color: #7cb947"></i> Drag & drop builder</li>
                    <li><i class="bi bi-download" style="color: #7cb947"></i> Export ke PDF/Excel</li>
                    <li><i class="bi bi-calendar-event" style="color: #7cb947"></i> Scheduled reports</li>
                    <li><i class="bi bi-share" style="color: #7cb947"></i> Easy sharing</li>
                </ul>
            </div>
            <div class="col-lg-6 order-lg-1">
                <img src="https://via.placeholder.com/500x350/f8f9fa/1e3c80?text=Custom+Reports" alt="Custom Reports" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5" style="background: linear-gradient(135deg, #1e3c80 0%, #7cb947 100%);">
    <div class="container text-center text-white">
        <h2 class="display-5 fw-bold mb-4">Siap Menggunakan Semua Fitur Ini?</h2>
        <p class="lead mb-4">Mulai gratis hari ini dan rasakan perbedaannya dalam 30 hari</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg rounded-pill px-5 py-3">Mulai Gratis</a>
            <a href="#demo" class="btn btn-outline-light btn-lg rounded-pill px-5 py-3">Lihat Demo</a>
        </div>
    </div>
</section>

@endsection
