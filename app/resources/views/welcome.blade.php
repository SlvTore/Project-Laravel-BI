
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
            <div class="card shadow h-100 p-3">
                <div class="feature-icon mb-3">
                    <i class="bi bi-bar-chart-line-fill fs-1"></i>
                </div>
                <h4 class="fw-bold">Pelacakan Metrik</h4>
                <p>Lacak metrik penting seperti total penjualan, pertumbuhan pendapatan, dan retensi pelanggan secara real-time.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3">
                <div class="feature-icon mb-3">
                    <i class="bi bi-display-fill text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Dashboard Dinamis</h4>
                <p>Visualisasikan data kinerja bisnis Anda dalam satu dashboard komprehensif yang mudah dipahami.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3">
                <div class="feature-icon mb-3">
                    <i class="bi bi-lightbulb-fill text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Keputusan Berbasis Data</h4>
                <p>Dapatkan wawasan untuk mengidentifikasi area perbaikan dan mengambil keputusan strategis yang lebih cerdas.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow h-100 p-3">
                <div class="feature-icon mb-3">
                    <i class="bi bi-people-fill text-primary fs-1"></i>
                </div>
                <h4 class="fw-bold">Kolaborasi Tim & Mentor</h4>
                <p>Fasilitasi interaksi dan umpan balik antara tim startup dan mentor untuk pertumbuhan yang lebih terarah.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container text-center">
        <h2 class="fw-bold">Siap Mengambil Kendali atas Pertumbuhan Bisnis Anda?</h2>
        <p class="lead">Bergabunglah dengan ratusan bisnis lain yang telah bertransformasi.</p>
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-3">Daftar Sekarang, Gratis!</a>
    </div>
</div>

@endsection
