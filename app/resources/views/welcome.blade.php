@extends('layouts.landingPage')

@section('title', 'Traction Tracker - Pantau Kinerja Bisnis Anda')

@section('content')

<div class="hero-section text-center py-5">
    <div class="hero-overlay"></div>
    <div class="container py-5 text-white hero-content">
        <h1 class="display-4 fw-bold">Ukur dan Tingkatkan Kinerja Bisnis Anda</h1>
        <p class="lead col-md-8 mx-auto">
          Platform "Traction Tracker" membantu Anda memantau metrik bisnis penting, membuat keputusan berbasis data, dan mencapai target pertumbuhan dengan lebih efektif.
        </p>
        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg mt-3">Mulai Ukur Traksi Anda Sekarang</a>
    </div>
</div>

<div class="container features-container">
    <div class="row text-center g-4">
        <div class="col-md-3">
            <div class="card shadow h-100 p-3 feature-card">
                <div class="feature-icon mb-3">
                    <i class="bi bi-bar-chart-line-fill fs-1" style="color: #7cb947"></i>
                </div>
                <h4 class="fw-bold">Pelacakan Metrik</h4>
                <p>Lacak metrik penting seperti total penjualan, pertumbuhan pendapatan, dan retensi pelanggan secara real-time.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3 feature-card">
                <div class="feature-icon mb-3">
                    <i class="bi bi-display-fill fs-1" style="color: #1e3c80"></i>
                </div>
                <h4 class="fw-bold">Dashboard Dinamis</h4>
                <p>Visualisasikan data kinerja bisnis Anda dalam satu dashboard komprehensif yang mudah dipahami.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3 feature-card">
                <div class="feature-icon mb-3">
                    <i class="bi bi-lightbulb-fill fs-1" style="color: #7cb947"></i>
                </div>
                <h4 class="fw-bold">Keputusan Berbasis Data</h4>
                <p>Dapatkan wawasan untuk mengidentifikasi area perbaikan dan mengambil keputusan strategis yang lebih cerdas.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3 feature-card">
                <div class="feature-icon mb-3">
                    <i class="bi bi-people-fill fs-1" style="color: #1e3c80"></i>
                </div>
                <h4 class="fw-bold">Kolaborasi Tim & Mentor</h4>
                <p>Fasilitasi interaksi dan umpan balik antara tim startup dan mentor untuk pertumbuhan yang lebih terarah.</p>
            </div>
        </div>
    </div>
</div>

<!-- About Us Section -->
<section class="about-section py-5 mt-5">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-6 px-5 about-text">
                <h2 class="display-5 fw-bold mb-4" style="color: #1e3c80">Tentang Traction Tracker</h2>
                <p class="lead mb-4">Kami adalah platform business intelligence yang didedikasikan untuk membantu bisnis dari berbagai skala mencapai potensi maksimal mereka.</p>
                <p class="mb-4">Dengan pengalaman lebih dari 5 tahun dalam industri teknologi dan bisnis, tim kami memahami tantangan yang dihadapi entrepreneur dan business owner dalam mengukur dan meningkatkan performa bisnis.</p>
                <div class="row">
                    <div class="col-6">
                        <div class="stat-item text-center">
                            <h3 class="fw-bold" style="color: #7cb947">500+</h3>
                            <p>Bisnis Terdaftar</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item text-center">
                            <h3 class="fw-bold" style="color: #7cb947">95%</h3>
                            <p>Tingkat Kepuasan</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 px-5">
                <div class="about-image-placeholder">
                    <img src={{ asset('images/statistic.jpg') }} alt="About Us" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Offerings Section -->
<section class="products-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Solusi untuk Setiap Skala Bisnis</h2>
            <p class="lead">Pilih paket yang sesuai dengan kebutuhan bisnis Anda</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 shadow-lg product-card border-0">
                    <div class="card-body text-center p-5">
                        <div class="product-icon mb-4">
                            <i class="bi bi-shop fs-1" style="color: #7cb947"></i>
                        </div>
                        <h3 class="fw-bold mb-3" style="color: #1e3c80">UMKM Solution</h3>
                        <p class="lead mb-4">Solusi khusus untuk Usaha Mikro, Kecil, dan Menengah</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #7cb947"></i>Dashboard sederhana dan mudah digunakan</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #7cb947"></i>Pelacakan penjualan harian</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #7cb947"></i>Analisis pelanggan dasar</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #7cb947"></i>Laporan bulanan otomatis</li>
                        </ul>
                        <div class="price mb-3">
                            <span class="h2 fw-bold" style="color: #7cb947">Rp 99.000</span>
                            <span class="text-muted">/bulan</span>
                        </div>
                        <a href="{{ route('register') }}" class="btn btn-lg px-4" style="background-color: #7cb947; color: white; border: none;">Mulai Gratis</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 shadow-lg product-card border-0">
                    <div class="card-body text-center p-5">
                        <div class="product-icon mb-4">
                            <i class="bi bi-building fs-1" style="color: #1e3c80"></i>
                        </div>
                        <h3 class="fw-bold mb-3" style="color: #1e3c80">Enterprise Solution</h3>
                        <p class="lead mb-4">Solusi lengkap untuk Startup dan Perusahaan Menengah</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #1e3c80"></i>Dashboard analytics mendalam</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #1e3c80"></i>Prediksi tren dan forecasting</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #1e3c80"></i>Integrasi dengan sistem existing</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2" style="color: #1e3c80"></i>Dedicated support & mentoring</li>
                        </ul>
                        <div class="price mb-3">
                            <span class="h2 fw-bold" style="color: #1e3c80">Rp 499.000</span>
                            <span class="text-muted">/bulan</span>
                        </div>
                        <a href="{{ route('register') }}" class="btn btn-lg px-4" style="background-color: #1e3c80; color: white; border: none;">Konsultasi Gratis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Parallax Achievement Section -->
<section class="parallax-section">
    <div class="parallax-overlay-green" data-bg-image="{{ asset('images/statistic2.jpg') }}">
        <div class="container text-center text-white py-5">
            <h2 class="display-4 fw-bold mb-3">Mengubah Data Menjadi Kesuksesan</h2>
            <p class="lead mb-5">Bergabunglah dengan revolusi digital untuk bisnis yang lebih cerdas dan berkelanjutan</p>
        </div>
    </div>
    <div class="parallax-overlay-blue" data-bg-image="{{ asset('images/businessOwner.jpg') }}">
        <div class="container py-5">
            <div class="row text-center text-white">
                <div class="col-md-4 mb-4">
                    <div class="achievement-item">
                        <i class="bi bi-trophy-fill fs-1 mb-3"></i>
                        <h2 class="fw-bold counter" data-target="500">0</h2>
                        <p class="lead">Bisnis Berhasil</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="achievement-item">
                        <i class="bi bi-graph-up-arrow fs-1 mb-3"></i>
                        <h2 class="fw-bold counter" data-target="85">0</h2>
                        <p class="lead">% Peningkatan Rata-rata</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="achievement-item">
                        <i class="bi bi-people-fill fs-1 mb-3"></i>
                        <h2 class="fw-bold counter" data-target="10000">0</h2>
                        <p class="lead">Pengguna Aktif</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Marquee Section -->
<section class="marquee-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-5" style="color: #1e3c80">Dipercaya oleh Perusahaan Terkemuka</h2>
        <div class="marquee-wrapper">
            <div class="marquee-content marquee-left">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+1" alt="Company 1">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+2" alt="Company 2">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+3" alt="Company 3">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+4" alt="Company 4">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+5" alt="Company 5">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+6" alt="Company 6">
            </div>
        </div>
        <div class="marquee-wrapper mt-4">
            <div class="marquee-content marquee-right">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+7" alt="Company 7">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+8" alt="Company 8">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+9" alt="Company 9">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+10" alt="Company 10">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+11" alt="Company 11">
                <img src="https://via.placeholder.com/150x80/f8f9fa/6c757d?text=Company+12" alt="Company 12">
            </div>
        </div>
    </div>
</section>

<!-- Testimonial Section -->
<section class="testimonial-section py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5" style="color: #1e3c80">Apa Kata Klien Kami</h2>
        <div class="testimonial-slider">
            <button class="testimonial-nav prev-btn">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="testimonial-container">
                <div class="testimonial-card active">
                    <img src="https://via.placeholder.com/80x80/f8f9fa/6c757d?text=User" alt="Customer" class="testimonial-avatar">
                    <div class="stars mb-3">
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                    </div>
                    <p class="testimonial-text">"Traction Tracker benar-benar mengubah cara kami melihat bisnis. Sekarang semua keputusan berbasis data yang akurat."</p>
                    <h5 class="fw-bold" style="color: #1e3c80">Sarah Johnson</h5>
                    <p class="text-muted">CEO, TechStart Indonesia</p>
                </div>
                <div class="testimonial-card">
                    <img src="https://via.placeholder.com/80x80/f8f9fa/6c757d?text=User" alt="Customer" class="testimonial-avatar">
                    <div class="stars mb-3">
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                    </div>
                    <p class="testimonial-text">"Platform yang sangat user-friendly. Tim kami dapat langsung menggunakan tanpa training yang rumit."</p>
                    <h5 class="fw-bold" style="color: #1e3c80">Ahmad Rahman</h5>
                    <p class="text-muted">Founder, Digital Commerce Co</p>
                </div>
                <div class="testimonial-card">
                    <img src="https://via.placeholder.com/80x80/f8f9fa/6c757d?text=User" alt="Customer" class="testimonial-avatar">
                    <div class="stars mb-3">
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                        <i class="bi bi-star-fill" style="color: #ffc107"></i>
                    </div>
                    <p class="testimonial-text">"ROI kami meningkat 150% dalam 6 bulan setelah menggunakan Traction Tracker. Luar biasa!"</p>
                    <h5 class="fw-bold" style="color: #1e3c80">Maria Gonzales</h5>
                    <p class="text-muted">Marketing Director, Growth Hub</p>
                </div>
            </div>
            <button class="testimonial-nav next-btn">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="contact-overlay">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="contact-form-wrapper bg-white rounded shadow-lg p-5">
                        <h2 class="text-center fw-bold mb-4" style="color: #1e3c80">Hubungi Kami</h2>
                        <p class="text-center text-muted mb-4">Siap memulai transformasi digital bisnis Anda? Tim ahli kami siap membantu!</p>
                        <form class="contact-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">Nama Depan</label>
                                    <input type="text" class="form-control" id="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Nama Belakang</label>
                                    <input type="text" class="form-control" id="lastName" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="phone" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="company" class="form-label">Nama Perusahaan</label>
                                <input type="text" class="form-control" id="company" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Pesan</label>
                                <textarea class="form-control" id="message" rows="4" placeholder="Ceritakan kebutuhan bisnis Anda..." required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-lg px-5" style="background-color: #7cb947; color: white; border: none;">Kirim Pesan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="bg-light py-5">
    <div class="container text-center">
        <h2 class="fw-bold">Siap Mengambil Kendali atas Pertumbuhan Bisnis Anda?</h2>
        <p class="lead">Bergabunglah dengan ratusan bisnis lain yang telah bertransformasi.</p>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-3">Daftar Sekarang, Gratis!</a>
    </div>
</div>

@endsection


@section('scripts')
<script src="{{ asset('js/landing.js') }}"></script>
<script>
    // Set critical images for preloading
    window.criticalImages = [
        '{{ asset("images/statistic2.jpg") }}',
        '{{ asset("images/businessOwner.jpg") }}'
    ];
</script>
@endsection
