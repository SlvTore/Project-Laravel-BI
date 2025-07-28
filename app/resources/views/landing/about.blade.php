@extends('layouts.landingPage')

@section('title', 'About Us - Traction Tracker')

@section('content')

<!-- Hero Section -->
<section class="hero-section-about">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-8 mx-auto text-center">
                <div class="hero-content text-white">
                    <h1 class="display-3 fw-bold mb-4">Tentang Traction Tracker</h1>
                    <p class="lead mb-4">Kami berkomitmen membantu bisnis Indonesia berkembang dengan data-driven insights yang actionable dan mudah dipahami.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="mission-vision py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6">
                <div class="mission-card h-100">
                    <div class="icon-wrapper mb-4">
                        <i class="bi bi-bullseye display-4" style="color: #7cb947"></i>
                    </div>
                    <h3 class="fw-bold mb-4" style="color: #1e3c80">Misi Kami</h3>
                    <p class="lead">Memberdayakan setiap bisnis, dari UMKM hingga enterprise, dengan tools analytics yang powerful namun mudah digunakan untuk membuat keputusan bisnis yang lebih cerdas.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="vision-card h-100">
                    <div class="icon-wrapper mb-4">
                        <i class="bi bi-eye display-4" style="color: #1e3c80"></i>
                    </div>
                    <h3 class="fw-bold mb-4" style="color: #1e3c80">Visi Kami</h3>
                    <p class="lead">Menjadi platform business intelligence terdepan di Indonesia yang membantu jutaan bisnis mencapai potensi maksimal mereka melalui data-driven decision making.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="our-story py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4" style="color: #1e3c80">Cerita Kami</h2>
                <p class="lead mb-4">Dimulai dari pengalaman pribadi para founder yang mengalami kesulitan dalam menganalisis data bisnis, Traction Tracker lahir untuk mengatasi pain point yang dirasakan banyak business owner.</p>
                <p class="mb-4">Pada tahun 2023, tim kami yang terdiri dari data scientists, software engineers, dan business strategists berkumpul dengan satu tujuan: membuat business intelligence yang accessible untuk semua.</p>
                <p class="mb-4">Hari ini, kami bangga melayani lebih dari 500+ bisnis di Indonesia, dari startup hingga perusahaan enterprise, membantu mereka membuat keputusan yang lebih baik dengan data.</p>
            </div>
            <div class="col-lg-6">
                <img src="{{ asset('images/statistic.jpg') }}" alt="Our Story" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Tim Kami</h2>
            <p class="lead text-muted">Para ahli yang berdedikasi untuk kesuksesan bisnis Anda</p>
        </div>

        <div class="row g-4">
            <!-- Team Member 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="team-card text-center">
                    <div class="team-image mb-4">
                        <img src="{{ asset('images/man1.jpg') }}" alt="CEO" class="rounded-circle">
                    </div>
                    <h4 class="fw-bold" style="color: #1e3c80">Ahmad Rizaldi</h4>
                    <p class="text-muted mb-3">Co-Founder & CEO</p>
                    <p class="small">Ex-consultant McKinsey dengan 10+ tahun pengalaman dalam business strategy dan digital transformation.</p>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>

            <!-- Team Member 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="team-card text-center">
                    <div class="team-image mb-4">
                        <img src="{{ asset('images/woman1.jpg') }}" alt="CTO" class="rounded-circle">
                    </div>
                    <h4 class="fw-bold" style="color: #1e3c80">Sarah Putri</h4>
                    <p class="text-muted mb-3">Co-Founder & CTO</p>
                    <p class="small">Data scientist dengan background dari Google dan pengalaman membangun machine learning systems untuk enterprise.</p>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-github"></i></a>
                    </div>
                </div>
            </div>

            <!-- Team Member 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="team-card text-center">
                    <div class="team-image mb-4">
                        <img src="{{ asset('images/man2.jpg') }}" alt="CPO" class="rounded-circle">
                    </div>
                    <h4 class="fw-bold" style="color: #1e3c80">Budi Santoso</h4>
                    <p class="text-muted mb-3">Chief Product Officer</p>
                    <p class="small">Product leader dengan pengalaman di Gojek dan Tokopedia, expert dalam user experience dan product development.</p>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Nilai-Nilai Kami</h2>
            <p class="lead text-muted">Prinsip yang memandu setiap keputusan dan tindakan kami</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="value-card text-center h-100">
                    <div class="value-icon mb-3">
                        <i class="bi bi-heart-fill display-4" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Customer First</h4>
                    <p class="text-muted">Kebutuhan dan kesuksesan customer adalah prioritas utama dalam setiap keputusan produk.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="value-card text-center h-100">
                    <div class="value-icon mb-3">
                        <i class="bi bi-lightning-fill display-4" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Innovation</h4>
                    <p class="text-muted">Terus berinovasi untuk memberikan solusi terdepan yang memecahkan masalah bisnis real.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="value-card text-center h-100">
                    <div class="value-icon mb-3">
                        <i class="bi bi-shield-check-fill display-4" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Transparency</h4>
                    <p class="text-muted">Transparansi dalam komunikasi, pricing, dan setiap aspek hubungan dengan customer.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="value-card text-center h-100">
                    <div class="value-icon mb-3">
                        <i class="bi bi-people-fill display-4" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Collaboration</h4>
                    <p class="text-muted">Percaya pada kekuatan kolaborasi tim dan partnership yang saling menguntungkan.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Achievements -->
<section class="achievements py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Pencapaian Kami</h2>
            <p class="lead text-muted">Milestone yang telah kami capai bersama customer</p>
        </div>

        <div class="row text-center">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="achievement-item">
                    <h2 class="display-4 fw-bold counter" data-target="500" style="color: #7cb947">0</h2>
                    <p class="lead" style="color: #1e3c80">Bisnis Terdaftar</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="achievement-item">
                    <h2 class="display-4 fw-bold counter" data-target="95" style="color: #7cb947">0</h2>
                    <p class="lead" style="color: #1e3c80">% Customer Satisfaction</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="achievement-item">
                    <h2 class="display-4 fw-bold counter" data-target="24" style="color: #7cb947">0</h2>
                    <p class="lead" style="color: #1e3c80">Bulan Beroperasi</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="achievement-item">
                    <h2 class="display-4 fw-bold counter" data-target="15" style="color: #7cb947">0</h2>
                    <p class="lead" style="color: #1e3c80">Kota di Indonesia</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5" style="background: linear-gradient(135deg, #1e3c80 0%, #7cb947 100%);">
    <div class="container text-center text-white">
        <h2 class="display-5 fw-bold mb-4">Bergabunglah dengan Kami</h2>
        <p class="lead mb-4">Mari bersama-sama membangun ekosistem bisnis Indonesia yang lebih data-driven</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg rounded-pill px-5 py-3">Mulai Sekarang</a>
            <a href="mailto:info@tractiontracker.com" class="btn btn-outline-light btn-lg rounded-pill px-5 py-3">Hubungi Kami</a>
        </div>
    </div>
</section>

@endsection
