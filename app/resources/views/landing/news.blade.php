@extends('layouts.landingPage')

@section('title', 'News & Updates - Traction Tracker')

@section('content')

<!-- Hero Section -->
<section class="hero-section-news">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-8 mx-auto text-center">
                <div class="hero-content text-white">
                    <h1 class="display-3 fw-bold mb-4">News & Updates</h1>
                    <p class="lead mb-4">Tetap update dengan perkembangan terbaru, feature releases, dan insights dari dunia business intelligence</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Article -->
<section class="featured-article py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="featured-card">
                    <div class="featured-badge">
                        <span class="badge bg-primary rounded-pill px-3 py-2">Featured</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <img src="https://via.placeholder.com/500x300/1e3c80/ffffff?text=AI+Analytics+Launch" alt="Featured News" class="img-fluid rounded">
                        </div>
                        <div class="col-md-6">
                            <div class="featured-content">
                                <div class="article-meta mb-3">
                                    <span class="badge" style="background-color: #7cb947">Product Update</span>
                                    <span class="text-muted ms-2">28 Juli 2025</span>
                                </div>
                                <h2 class="fw-bold mb-3" style="color: #1e3c80">Launching AI-Powered Analytics: Revolutionizing Business Intelligence</h2>
                                <p class="text-muted mb-4">Kami dengan bangga memperkenalkan fitur AI Analytics yang akan mengubah cara Anda menganalisis data bisnis. Dengan machine learning yang canggih, dapatkan insights yang lebih mendalam dan prediksi yang akurat.</p>
                                <a href="#" class="btn btn-primary rounded-pill px-4">Baca Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- News Categories -->
<section class="news-categories py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Kategori Berita</h2>
            <p class="lead text-muted">Pilih kategori yang ingin Anda ikuti</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="category-card text-center h-100">
                    <div class="category-icon mb-3">
                        <i class="bi bi-rocket-takeoff display-4" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Product Updates</h4>
                    <p class="text-muted">Fitur terbaru, improvement, dan roadmap produk</p>
                    <a href="#product-updates" class="btn btn-outline-primary rounded-pill">Lihat Artikel</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="category-card text-center h-100">
                    <div class="category-icon mb-3">
                        <i class="bi bi-graph-up display-4" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Business Insights</h4>
                    <p class="text-muted">Tips, strategi, dan best practices untuk bisnis</p>
                    <a href="#business-insights" class="btn btn-outline-primary rounded-pill">Lihat Artikel</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="category-card text-center h-100">
                    <div class="category-icon mb-3">
                        <i class="bi bi-trophy display-4" style="color: #7cb947"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Success Stories</h4>
                    <p class="text-muted">Kisah sukses customer dan case studies</p>
                    <a href="#success-stories" class="btn btn-outline-primary rounded-pill">Lihat Artikel</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="category-card text-center h-100">
                    <div class="category-icon mb-3">
                        <i class="bi bi-megaphone display-4" style="color: #1e3c80"></i>
                    </div>
                    <h4 class="fw-bold mb-3" style="color: #1e3c80">Company News</h4>
                    <p class="text-muted">Update perusahaan, partnership, dan milestone</p>
                    <a href="#company-news" class="btn btn-outline-primary rounded-pill">Lihat Artikel</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Articles -->
<section class="latest-articles py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold" style="color: #1e3c80">Artikel Terbaru</h2>
            <p class="lead text-muted">Update terkini dari Traction Tracker</p>
        </div>

        <div class="row g-4">
            <!-- Article 1 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/7cb947/ffffff?text=Data+Security" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge bg-primary">Product Update</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">25 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">Enhanced Data Security: New Encryption Standards</h3>
                        <p class="news-excerpt text-muted">Kami meningkatkan standar keamanan data dengan implementasi enkripsi AES-256 dan sertifikasi ISO 27001...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>

            <!-- Article 2 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/1e3c80/ffffff?text=Mobile+App" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge" style="background-color: #7cb947">Product Update</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">22 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">Mobile App 2.0: Redesign & New Features</h3>
                        <p class="news-excerpt text-muted">Mobile app terbaru dengan interface yang lebih modern, performance yang lebih cepat, dan fitur offline access...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>

            <!-- Article 3 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/7cb947/ffffff?text=Success+Story" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge bg-success">Success Story</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">20 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">Customer Spotlight: UMKM Sejahtera Increases Revenue 300%</h3>
                        <p class="news-excerpt text-muted">Kisah inspiratif bagaimana UMKM Sejahtera menggunakan Traction Tracker untuk meningkatkan revenue hingga 300% dalam 6 bulan...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>

            <!-- Article 4 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/1e3c80/ffffff?text=Business+Tips" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge bg-info">Business Insights</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">18 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">5 KPI yang Wajib Ditrack oleh Setiap UMKM</h3>
                        <p class="news-excerpt text-muted">Pelajari 5 Key Performance Indicators yang paling penting untuk memantau kesehatan dan pertumbuhan bisnis UMKM...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>

            <!-- Article 5 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/7cb947/ffffff?text=Partnership" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge bg-warning text-dark">Company News</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">15 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">Strategic Partnership with Google Cloud Indonesia</h3>
                        <p class="news-excerpt text-muted">Traction Tracker bermitra dengan Google Cloud Indonesia untuk menyediakan infrastruktur yang lebih robust dan scalable...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>

            <!-- Article 6 -->
            <div class="col-lg-4 col-md-6">
                <article class="news-card h-100">
                    <div class="news-image">
                        <img src="https://via.placeholder.com/350x200/1e3c80/ffffff?text=Industry+Report" alt="Article" class="img-fluid">
                        <div class="news-category">
                            <span class="badge bg-info">Business Insights</span>
                        </div>
                    </div>
                    <div class="news-content">
                        <div class="news-meta mb-2">
                            <span class="text-muted small">12 Juli 2025</span>
                        </div>
                        <h3 class="news-title mb-3">Indonesia SME Digital Transformation Report 2025</h3>
                        <p class="news-excerpt text-muted">Laporan komprehensif tentang tren digitalisasi UMKM di Indonesia dan peluang yang tersedia di tahun 2025...</p>
                        <a href="#" class="read-more">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                    </div>
                </article>
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-5">
            <button class="btn btn-outline-primary btn-lg rounded-pill px-5">Muat Lebih Banyak</button>
        </div>
    </div>
</section>

<!-- Newsletter Subscription -->
<section class="newsletter-section py-5" style="background: linear-gradient(135deg, #1e3c80 0%, #7cb947 100%);">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center text-white">
                <h2 class="display-5 fw-bold mb-4">Jangan Lewatkan Update Terbaru</h2>
                <p class="lead mb-4">Dapatkan artikel terbaru, product updates, dan business insights langsung di inbox Anda</p>

                <form class="newsletter-form">
                    <div class="row g-3 justify-content-center">
                        <div class="col-md-6">
                            <input type="email" class="form-control form-control-lg rounded-pill" placeholder="Masukkan email Anda" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-light btn-lg rounded-pill w-100">Subscribe</button>
                        </div>
                    </div>
                </form>

                <p class="small mt-3 opacity-75">* Kami menghormati privasi Anda. Unsubscribe kapan saja.</p>
            </div>
        </div>
    </div>
</section>

@endsection
