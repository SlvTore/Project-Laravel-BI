@extends('layouts.dashboard')

@section('content')
<div class="container-fluid ms-4" id="data-feeds-content">
    <!-- Clean Header -->
    <div class="row mb-4 p-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 fw-bold text-white mb-0">Data Feeds Management</h1>
                    <p class="text-white">Kelola data produk Anda!</p>
                </div>
                <div class="d-flex gap-3">
                    <button onclick="showImportModal()" class="btn btn-liquid-glass btn-import">
                        <i class="bi bi-upload me-2"></i>
                        Import CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload History Section (moved to top) -->
    <div class="row p-3 gy-4 mb-2" id="uploadHistorySection">
        <div class="col-12">
            <div class="card card-liquid-transparent">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h3 class="text-white mb-1">Riwayat Upload Data Feed</h3>
                            <p class="text-white-50 mb-0 small">Pantau dan kelola file data feed yang sudah diupload. Klik baris untuk memfilter transaksi berdasarkan upload.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="refreshUploadsList()">
                                <i class="bi bi-arrow-repeat me-2"></i>Refresh
                            </button>
                            <button type="button" id="clearUploadFilterBtn" class="btn btn-outline-warning btn-sm d-none" onclick="clearUploadFilter()">
                                <i class="bi bi-x-circle me-1"></i>Hapus Filter Upload
                            </button>
                        </div>
                    </div>
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-white-50 small text-uppercase">Pencarian</label>
                            <input type="search" id="uploadSearch" class="form-control bg-dark text-white border-secondary" placeholder="Cari nama file..." style="border-color: rgba(255,255,255,0.2) !important;">
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label class="form-label text-white-50 small text-uppercase">Limit</label>
                            <select id="uploadLimit" class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2 d-flex align-items-end">
                            <button type="button" class="btn btn-liquid-glass w-100" onclick="refreshUploadsList()">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive rounded-4 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.03);">
                        <table class="table table-dark table-hover align-middle mb-0" id="uploadsTable">
                            <thead>
                                <tr>
                                    <th class="text-white-50 text-uppercase small">Waktu</th>
                                    <th class="text-white-50 text-uppercase small">Nama File</th>
                                    <th class="text-white-50 text-uppercase small">Tipe</th>
                                    <th class="text-white-50 text-uppercase small text-end">Records</th>
                                    <th class="text-white-50 text-uppercase small">Status</th>
                                    <th class="text-white-50 text-uppercase small">Pesan</th>
                                    <th class="text-white-50 text-uppercase small text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="uploadsTableBody">
                                <tr id="emptyUploadsMessage">
                                    <td colspan="7" class="text-center text-white-50 py-4">Belum ada data upload.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Import Modal -->
    <div class="modal fade" id="salesImportModal" tabindex="-1" aria-labelledby="salesImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="background: rgba(24,24,24,0.95); color: #f8f9fa; border-radius: 18px; border: 1px solid rgba(255,255,255,0.08);">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold" id="salesImportModalLabel">
                            <i class="bi bi-cloud-arrow-up-fill me-2 text-info"></i>
                            Import Data Universal
                        </h5>
                        <p class="mb-0 text-white-50 small">Upload file CSV universal untuk memproses data customer, produk, penjualan, dan BOM secara bersamaan ke dalam sistem.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="salesImportForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="salesImportFile" class="form-label fw-semibold text-white">File CSV Universal</label>
                                <input type="file" class="form-control bg-dark text-white border-secondary" id="salesImportFile" name="file" accept=".csv" required>
                                <div class="form-text text-white-50">
                                    Template universal mencakup data customer, produk, penjualan, dan BOM dalam satu file.
                                    <strong>Unduh template</strong> terlebih dahulu untuk melihat struktur yang benar.
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-light w-100" onclick="downloadUniversalTemplate('csv')">
                                    <i class="bi bi-download me-2"></i>Unduh Template Universal
                                </button>
                            </div>
                        </div>
                    </form>

                    <div id="importPreviewSection" style="display: none;">
                        <div id="importPreviewContent" class="p-3 rounded-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                                <div>
                                    <span class="badge bg-info text-dark" id="previewDataTypeBadge">Data Universal</span>
                                </div>
                                <div id="previewSummaryBadges" class="d-flex flex-wrap gap-2 text-nowrap">
                                    <span class="badge bg-secondary">Belum ada data preview</span>
                                </div>
                            </div>

                            <div id="previewIssuesContainer" class="mb-3"></div>

                            <div id="previewNewProducts" class="mb-3"></div>

                            <div id="autoCreateProductsControl" class="mb-3" style="display: none;">
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                                    <div class="form-check form-switch text-white">
                                        <input class="form-check-input" type="checkbox" role="switch" id="autoCreateProductsToggle">
                                        <label class="form-check-label" for="autoCreateProductsToggle">
                                            Otomatis buat produk baru yang belum terdaftar
                                        </label>
                                    </div>
                                    <button type="button" id="autoCreateProductsButton" class="btn btn-outline-warning btn-sm" style="display:none;"
                                        onclick="autoCreateMissingProducts()">
                                        <i class="bi bi-stars me-2"></i>Buat Produk Baru Sekarang
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive bg-dark rounded-4 border border-secondary-subtle">
                                <table class="table table-dark table-hover align-middle mb-0" id="previewTable">
                                    <thead>
                                        <tr style="font-size: 0.75rem;">
                                            <th style="min-width: 80px;">Tanggal</th>
                                            <th style="min-width: 120px;">Customer</th>
                                            <th style="min-width: 150px;">Email</th>
                                            <th style="min-width: 110px;">Telepon</th>
                                            <th style="min-width: 150px;">Produk</th>
                                            <th style="min-width: 80px;">Kategori</th>
                                            <th style="min-width: 50px;">Qty</th>
                                            <th style="min-width: 60px;">Unit</th>
                                            <th style="min-width: 80px;">Harga</th>
                                            <th style="min-width: 70px;">Diskon</th>
                                            <th style="min-width: 60px;">Pajak</th>
                                            <th style="min-width: 80px;">Ongkir</th>
                                            <th style="min-width: 80px;">Bayar</th>
                                            <th style="min-width: 100px;">Catatan</th>
                                            <th style="min-width: 80px;">Cost</th>
                                            <th style="min-width: 100px;">Material</th>
                                            <th style="min-width: 60px;">Mat Qty</th>
                                            <th style="min-width: 60px;">Mat Unit</th>
                                            <th style="min-width: 80px;">Mat Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="previewEmptyRow">
                                            <td colspan="19" class="text-center text-white-50 py-4">Upload file untuk melihat pratinjau data.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-warning" onclick="previewImportData()" id="previewBtn">
                        <i class="bi bi-eye me-2"></i>Preview Data
                    </button>
                    <button type="button" class="btn btn-success" onclick="processSalesImport()" id="importBtn" style="display: none;">
                        <i class="bi bi-cloud-upload me-2"></i>Proses Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Management Card -->
    <div class="row p-3">
        <div class="col-12">
            <div class="card card-liquid-transparent mb-4">
                <div class="card-body">
                    <!-- Add Product Button -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-white mb-0">Manajemen Produk</h3>
                        <div class="d-flex gap-2">
                            <button type="button" onclick="addProductCard()" class="btn btn-liquid-glass btn-add-product">
                                <i class="bi bi-plus-circle me-2"></i>
                                Tambah Produk
                            </button>
                        </div>
                    </div>

                    <div id="productCardsContainer" class="row g-4">
                        <div class="col-12" id="productCardsEmptyState">
                            <div class="text-center text-white-50 py-5 border border-dashed rounded-4" style="border-color: rgba(255,255,255,0.2) !important;">
                                <i class="bi bi-box-seam display-5 d-block mb-3"></i>
                                <p class="mb-0">Belum ada kartu produk. Klik <strong>Tambah Produk</strong> untuk memulai.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Performance and Transactions Section -->
    <div class="row p-3 gy-4">
        <div class="col-12 col-xl-4">
            <div class="card card-liquid-transparent h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="text-white mb-1">Ringkasan Penjualan</h4>
                            <p class="text-white-50 mb-0 small">Pantau performa penjualan dari transaksi terbaru.</p>
                        </div>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="openSalesTransactionModal()">
                            <i class="bi bi-plus-circle me-1"></i>
                            Transaksi
                        </button>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="p-3 rounded-4 border" style="border-color: rgba(255,255,255,0.12) !important; background: rgba(255,255,255,0.04);">
                                <div class="text-white-50 small text-uppercase">Penjualan Hari Ini</div>
                                <div class="text-white fw-semibold fs-4" data-stat="daily-sales" id="todaySales">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 border" style="border-color: rgba(255,255,255,0.12) !important; background: rgba(255,255,255,0.04);">
                                <div class="text-white-50 small text-uppercase">Pekan Ini</div>
                                <div class="text-white fw-semibold fs-5" data-stat="weekly-sales" id="weeklySales">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 border" style="border-color: rgba(255,255,255,0.12) !important; background: rgba(255,255,255,0.04);">
                                <div class="text-white-50 small text-uppercase">Bulan Ini</div>
                                <div class="text-white fw-semibold fs-5" data-stat="monthly-sales" id="monthlySales">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-white-50 text-uppercase small mb-3">Transaksi Terakhir</h6>
                    <ul id="incomeOverviewList" class="list-unstyled flex-grow-1 mb-0 overflow-auto" style="max-height: 280px; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; background: rgba(255,255,255,0.02); padding: 1rem;">
                        <li class="text-white-50 small">Memuat transaksi...</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card card-liquid-transparent">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                        <div>
                            <h3 class="text-white mb-1">Transaksi Penjualan Terbaru</h3>
                            <p class="text-white-50 mb-0 small">Filter dan kelola transaksi penjualan Anda.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-liquid-glass btn-sm" onclick="openSalesTransactionModal()">
                                <i class="bi bi-plus-lg me-2"></i>
                                Tambah Transaksi
                            </button>
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="openSalesImportModal()">
                                <i class="bi bi-upload me-2"></i>
                                Import Penjualan
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-12 col-md-4">
                            <label for="transactionSearch" class="form-label text-white-50 small text-uppercase">Pencarian</label>
                            <input type="search" id="transactionSearch" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" placeholder="Cari pelanggan atau produk...">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <label for="statusFilter" class="form-label text-white-50 small text-uppercase">Status</label>
                            <select id="statusFilter" class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;">
                                <option value="">Semua</option>
                                <option value="completed">Selesai</option>
                                <option value="pending">Proses</option>
                                <option value="review">Perlu Ditinjau</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="dateStart" class="form-label text-white-50 small text-uppercase">Dari</label>
                            <input type="date" id="dateStart" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;">
                        </div>
                        <div class="col-6 col-md-2 col-lg-2">
                            <label for="dateEnd" class="form-label text-white-50 small text-uppercase">Sampai</label>
                            <input type="date" id="dateEnd" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;">
                        </div>
                        <div class="col-6 col-md-1 col-lg-2 d-flex align-items-end">
                            <button type="button" id="refreshTransactions" class="btn btn-outline-light w-100">
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Refresh
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive rounded-4 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.03);">
                        <table class="table table-dark table-hover align-middle mb-0" id="transactionsTable">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-white-50 text-uppercase small sortable" data-sort-by="transaction_date">Tanggal</th>
                                    <th scope="col" class="text-white-50 text-uppercase small sortable" data-sort-by="customer_name">Pelanggan</th>
                                    <th scope="col" class="text-white-50 text-uppercase small">Detail</th>
                                    <th scope="col" class="text-white-50 text-uppercase small sortable text-end" data-sort-by="total_amount">Total</th>
                                    <th scope="col" class="text-white-50 text-uppercase small">Status</th>
                                    <th scope="col" class="text-white-50 text-uppercase small text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="recentTransactionsBody">
                                <tr id="emptyTransactionsMessage">
                                    <td colspan="6" class="text-center text-white-50 py-4">Memuat transaksi...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Manage Data Modal -->
<div class="modal fade" id="manageDataModal" tabindex="-1" aria-labelledby="manageDataModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; color: white;">
            <div class="modal-header border-0" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;">
                <h5 class="modal-title fw-bold text-white" id="manageDataModalLabel">
                    <i class="bi bi-gear-fill me-2 text-info"></i>
                    Kelola Data Produk
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" id="manageDataTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-white border-0" id="product-info-tab" data-bs-toggle="tab" data-bs-target="#product-info" type="button" style="background-color: rgba(255, 255, 255, 0.1); border-radius: 8px 8px 0 0;">
                            <i class="bi bi-info-circle me-2 text-info"></i>
                            Info Produk
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-white border-0" id="bom-tab" data-bs-toggle="tab" data-bs-target="#bom" type="button" style="border-radius: 8px 8px 0 0;">
                            <i class="bi bi-list-ul me-2 text-warning"></i>
                            Bill of Material
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="manageDataTabContent">
                    <!-- Product Info Tab -->
                    <div class="tab-pane fade show active" id="product-info" role="tabpanel">
                        <form id="productInfoForm" class="mb-4">
                            @csrf
                            <input type="hidden" id="productId" name="product_id">
                            <input type="hidden" id="cardId" name="card_id">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">
                                        Nama Produk
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Tips Naming Produk"
                                           data-bs-content="Gunakan nama yang mudah diingat dan menjelaskan produk. Contoh: <br>• <b>Nasi Goreng Spesial</b> (tidak hanya 'Nasi Goreng')<br>• <b>Kopi Arabica Premium</b><br>• <b>Kaos Polos Cotton Combed</b>"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productName" name="name" placeholder="Masukkan nama produk" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">
                                        Kategori
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Pentingnya Kategorisasi"
                                           data-bs-content="Kategori membantu analisis bisnis:<br>• <b>Performa per kategori</b><br>• <b>Identifikasi produk terlaris</b><br>• <b>Perencanaan stok yang tepat</b><br>• <b>Laporan penjualan yang rapi</b>"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productCategory" name="category" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Makanan">Makanan</option>
                                        <option value="Minuman">Minuman</option>
                                        <option value="Fashion">Fashion</option>
                                        <option value="Jasa">Jasa</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-white">
                                        Harga Jual (Rp)
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Strategi Pricing"
                                           data-bs-content="Rumus sederhana: <br><b>Harga Jual = Harga Pokok + Margin Keuntungan</b><br><br>Tips:<br>• Riset harga kompetitor<br>• Pertimbangkan target market<br>• Margin 30-50% untuk produk baru<br>• Sesuaikan dengan positioning brand"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-secondary">Rp</span>
                                        <input type="text" class="form-control bg-dark text-white border-secondary currency-input" style="border-color: rgba(255,255,255,0.2) !important;" id="productSellingPriceDisplay" placeholder="0" autocomplete="off">
                                    </div>
                                    <input type="hidden" id="productSellingPrice" name="selling_price">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-white">
                                        Harga Pokok (Rp)
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Menghitung Harga Pokok"
                                           data-bs-content="Komponen Harga Pokok:<br>• <b>Bahan baku</b> (gunakan tab BOM)<br>• <b>Tenaga kerja</b><br>• <b>Overhead</b> (listrik, sewa)<br>• <b>Packaging</b><br><br>💡 <i>Akan otomatis terisi dari total BOM bila diaktifkan</i>"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-secondary">Rp</span>
                                        <input type="text" class="form-control bg-dark text-white border-secondary currency-input" style="border-color: rgba(255,255,255,0.2) !important;" id="productCostPriceDisplay" placeholder="0" autocomplete="off">
                                    </div>
                                    <input type="hidden" id="productCostPrice" name="cost_price">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check form-switch text-white">
                                        <input class="form-check-input" type="checkbox" id="autoCostSyncToggle" checked>
                                        <label class="form-check-label" for="autoCostSyncToggle">
                                            Isi otomatis dari total BOM
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">Satuan Penjualan</label>
                                    <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productUnit" name="unit" required>
                                        <option value="Pcs">Pcs</option>
                                        <option value="Kg">Kg</option>
                                        <option value="Gram">Gram</option>
                                        <option value="Liter">Liter</option>
                                        <option value="Meter">Meter</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-white">Deskripsi</label>
                                    <textarea class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" rows="3" id="productDescription" name="description" placeholder="Deskripsi produk (opsional)"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-white-50">Gunakan tombol di bawah untuk menyimpan info produk atau lanjut mengelola BOM.</small>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-light" onclick="switchManageDataTab('bom')">
                                        <i class="bi bi-list-ul me-1"></i>
                                        Kelola BOM
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i>
                                        Simpan Info Produk
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- BOM Tab -->
                    <div class="tab-pane fade" id="bom" role="tabpanel">
                        <form id="bomForm" class="mb-4">
                            @csrf
                            <input type="hidden" id="bomProductId" name="product_id">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-white small">
                                        Nama Bahan
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Bill of Materials"
                                           data-bs-content="Tambahkan setiap bahan baku yang digunakan untuk memproduksi 1 unit produk."
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.8em;"></i>
                                    </label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomMaterialName" name="material_name" placeholder="Nama bahan" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold text-white small">Qty</label>
                                    <input type="number" step="0.01" min="0" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomQuantity" name="quantity" placeholder="Qty" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold text-white small">Unit</label>
                                    <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomUnit" name="unit" required>
                                        <option value="kg">Kg</option>
                                        <option value="gram">Gram</option>
                                        <option value="liter">Liter</option>
                                        <option value="ml">ML</option>
                                        <option value="pcs">Pcs</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-white small">Harga/Unit</label>
                                    <div class="input-group">
                                    <span class="input-group-text bg-dark text-white border-secondary">Rp</span>
                                        <input type="text" class="form-control bg-dark text-white border-secondary currency-input" style="border-color: rgba(255,255,255,0.2) !important;" id="bomCostPerUnitDisplay" placeholder="0" autocomplete="off">
                                    </div>
                                    <input type="hidden" id="bomCostPerUnit" name="cost_per_unit">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- BOM List -->
                        <div class="card" style="background: rgba(30, 30, 30, 0.7); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <div class="card-header" style="background: rgba(255, 255, 255, 0.1); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                                <h6 class="mb-0 text-white">
                                    <i class="bi bi-list-ul me-2 text-warning"></i>
                                    Daftar Bahan Baku
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover mb-0">
                                        <thead style="background: rgba(255, 255, 255, 0.1);">
                                            <tr>
                                                <th class="text-white">Bahan</th>
                                                <th class="text-white">Qty</th>
                                                <th class="text-white">Unit</th>
                                                <th class="text-white">Harga/Unit</th>
                                                <th class="text-white">Total</th>
                                                <th class="text-white" width="80">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bomTableBody">
                                            <tr id="emptyBomMessage">
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                                                    <span class="text-white-50">Belum ada bahan baku</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot id="bomTotalFooter" style="background: rgba(40, 167, 69, 0.2); display: none;">
                                            <tr>
                                                <th colspan="4" class="text-end text-white">Total Biaya Bahan Baku:</th>
                                                <th class="text-success fw-bold" id="totalBomCost">Rp 0</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-white-50">Total BOM akan disinkronkan ke harga pokok jika opsi otomatis aktif.</small>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-light" onclick="switchManageDataTab('product-info')">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Info Produk
                                </button>
                                <button type="button" class="btn btn-success" onclick="saveBomItems()">
                                    <i class="bi bi-save me-2"></i>
                                    Simpan BOM
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); background: rgba(30,30,30,0.9);">
                <button type="button" class="btn btn-secondary bg-dark text-white" style="border-color: rgba(255,255,255,0.2);" data-bs-dismiss="modal">
                    Tutup
                </button>
                <button type="button" class="btn btn-success" onclick="saveAllData()">
                    <i class="bi bi-check-lg me-2"></i>
                    Simpan Semua
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sales Transaction Modal -->
<div class="modal fade" id="salesTransactionModal" tabindex="-1" aria-labelledby="salesTransactionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background: rgba(24,24,24,0.95); color: #f8f9fa; border-radius: 20px; border: 1px solid rgba(255,255,255,0.08);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold text-white" id="salesTransactionModalLabel">
                        <i class="bi bi-receipt-cutoff me-2 text-warning"></i>
                        Transaksi Penjualan
                    </h5>
                    <p class="mb-0 text-white-50 small">Catat transaksi penjualan baru dan kelola item pesanan.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="salesTransactionForm" onsubmit="event.preventDefault(); saveSalesTransaction();">
                    @csrf
                    <div class="row g-4">
                        <div class="col-12 col-lg-7">
                            <div class="mb-3">
                                <label for="transactionDateTime" class="form-label text-white-50 small text-uppercase">Tanggal &amp; Waktu</label>
                                <input type="datetime-local" id="transactionDateTime" name="transaction_date" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" required>
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="customerName" class="form-label text-white-50 small text-uppercase">Pelanggan</label>
                                <div class="input-group">
                                    <input type="text" id="customerName" name="customer_name" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" placeholder="Masukkan nama pelanggan" autocomplete="off" required>
                                    <button type="button" class="btn btn-outline-light" onclick="addNewCustomer()">
                                        <i class="bi bi-person-plus me-2"></i>
                                        Tambah
                                    </button>
                                </div>
                                <div id="customerSuggestions" class="dropdown-menu w-100 mt-1" style="display: none; max-height: 250px; overflow-y: auto; background: rgba(30,30,30,0.95); border: 1px solid rgba(255,255,255,0.15);"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-white-50 small text-uppercase d-flex justify-content-between align-items-center">
                                    <span>Item Transaksi</span>
                                    <button type="button" class="btn btn-outline-light btn-sm" onclick="addTransactionItem()">
                                        <i class="bi bi-plus me-1"></i>
                                        Tambah Item
                                    </button>
                                </label>
                                <div id="transactionItemsContainer" class="rounded-4 border p-3" style="border-color: rgba(255,255,255,0.1) !important; background: rgba(255,255,255,0.03);">
                                    <div class="transaction-item border-bottom pb-3 mb-3" data-item-index="0">
                                        <div class="row align-items-end">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label fw-semibold text-white small">Produk</label>
                                                <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary product-search" style="border-color: rgba(255,255,255,0.2) !important;" name="items[0][product_name]" placeholder="Cari produk..." autocomplete="off" required>
                                                <input type="hidden" name="items[0][product_id]" class="product-id">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label fw-semibold text-white small">Qty</label>
                                                <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary quantity-input" style="border-color: rgba(255,255,255,0.2) !important;" name="items[0][quantity]" placeholder="1" min="1" step="0.01" value="1" required>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label fw-semibold text-white small">Harga</label>
                                                <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary price-input" style="border-color: rgba(255,255,255,0.2) !important;" name="items[0][selling_price]" placeholder="0" min="0" step="0.01" required>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label class="form-label fw-semibold text-white small">Diskon</label>
                                                <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary discount-input" style="border-color: rgba(255,255,255,0.2) !important;" name="items[0][discount]" placeholder="0" min="0" step="0.01" value="0">
                                            </div>
                                            <div class="col-md-1 mb-2 text-end">
                                                <label class="form-label fw-semibold text-white small">Subtotal</label>
                                                <div class="text-success fw-bold item-subtotal">Rp 0</div>
                                            </div>
                                            <div class="col-md-1 mb-2 text-end">
                                                <label class="form-label fw-semibold text-white small">&nbsp;</label>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTransactionItem(0)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="p-3 rounded-4 border mb-3" style="border-color: rgba(255,255,255,0.12) !important; background: rgba(255,255,255,0.03);">
                                <h6 class="text-white mb-3">
                                    <i class="bi bi-calculator me-2 text-warning"></i>
                                    Ringkasan Pembayaran
                                </h6>
                                <div class="mb-3">
                                    <label for="transactionTax" class="form-label text-white-50 small text-uppercase">Pajak</label>
                                    <input type="number" step="0.01" min="0" id="transactionTax" name="tax_amount" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="shippingCost" class="form-label text-white-50 small text-uppercase">Biaya Pengiriman</label>
                                    <input type="number" step="0.01" min="0" id="shippingCost" name="shipping_cost" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" value="0">
                                </div>
                                <div class="border-top pt-3" style="border-color: rgba(255,255,255,0.08) !important;">
                                    <div class="d-flex justify-content-between text-white-50 small mb-2">
                                        <span>Subtotal Item</span>
                                        <span id="itemsSubtotal" class="text-white fw-semibold">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-white-50 small mb-2">
                                        <span>Pajak</span>
                                        <span id="displayTax" class="text-white">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-white-50 small mb-3">
                                        <span>Pengiriman</span>
                                        <span id="displayShipping" class="text-white">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-white fw-semibold">Total Akhir</span>
                                        <span id="finalTotal" class="text-success fw-bold fs-5">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 rounded-4 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.02);">
                                <h6 class="text-white mb-2">
                                    <i class="bi bi-journal-text me-2 text-info"></i>
                                    Catatan Transaksi
                                </h6>
                                <textarea id="transactionNotes" name="notes" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" rows="5" placeholder="Catatan tambahan transaksi"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success" form="salesTransactionForm">
                    <i class="bi bi-save me-2"></i>
                    Simpan Transaksi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Liquid Glass Styling -->
<style>
/* Scoped styling untuk halaman data feeds - tidak mempengaruhi navigasi */
#sidebar {
    /* Preserve sidebar styling */
    z-index: 1000 !important;
}

#sidebar .nav-link {
    /* Preserve navigation link styling */
    position: relative !important;
}

/* Scoped styling untuk halaman data feeds */
#data-feeds-content .btn-liquid-glass {
    position: relative;
    padding: 12px 24px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg,
        rgba(255, 255, 255, 0.1),
        rgba(255, 255, 255, 0.05)
    );
    backdrop-filter: blur(20px);
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2),
        inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    color: white !important;
    font-weight: 600;
    text-decoration: none;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

#data-feeds-content .btn-liquid-glass::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.5s ease;
}

#data-feeds-content .btn-liquid-glass:hover {
    transform: translateY(-2px);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    color: white !important;
}

#data-feeds-content .btn-liquid-glass:hover::before {
    left: 100%;
}

#data-feeds-content .btn-liquid-glass:active {
    transform: translateY(0);
    box-shadow:
        0 6px 24px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2),
        inset 0 -1px 0 rgba(0, 0, 0, 0.1);
}

#data-feeds-content .btn-import {
    background: linear-gradient(135deg,
        rgba(34, 197, 94, 0.8),
        rgba(22, 163, 74, 0.8)
    );
}

#data-feeds-content .btn-add-product {
    background: linear-gradient(135deg,
        rgba(59, 130, 246, 0.8),
        rgba(37, 99, 235, 0.8)
    );
}

#data-feeds-content .card-liquid-glass {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

#data-feeds-content .card-liquid-transparent {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
}

#data-feeds-content .card-liquid-glass:hover {
    transform: translateY(-3px);
    box-shadow:
        0 12px 48px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

#data-feeds-content .editable-title {
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid transparent;
}

#data-feeds-content .editable-title:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
}

#data-feeds-content .editable-title input {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 6px;
    padding: 4px 8px;
    color: #333;
    font-weight: 600;
    width: 100%;
}

#data-feeds-content .editable-title input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

/* Custom Popover Styling */
.custom-popover.popover {
    --bs-popover-max-width: 350px;
    --bs-popover-border-color: rgba(59, 130, 246, 0.3);
    --bs-popover-header-bg: rgba(15, 15, 15, 0.98);
    --bs-popover-header-color: #60a5fa;
    --bs-popover-body-bg: rgba(20, 20, 20, 0.98);
    --bs-popover-body-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 12px;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.custom-popover.popover .popover-arrow::before {
    border-color: rgba(59, 130, 246, 0.3);
}

.custom-popover.popover .popover-arrow::after {
    border-color: rgba(20, 20, 20, 0.98);
}

.custom-popover.popover .popover-body {
    font-size: 0.875rem;
    line-height: 1.6;
    background-color: rgba(20, 20, 20, 0.98) !important;
    color: rgba(255, 255, 255, 0.95) !important;
}

.custom-popover.popover .popover-header {
    font-weight: 600;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
    background-color: rgba(15, 15, 15, 0.98) !important;
    color: #60a5fa !important;
}

/* Info icon hover effect */
.bi-info-circle:hover {
    color: rgba(59, 130, 246, 0.9) !important;
    transform: scale(1.15);
    transition: all 0.3s ease;
    filter: drop-shadow(0 0 6px rgba(59, 130, 246, 0.4));
}

/* Override Bootstrap popover styling for dark theme */
.popover {
    --bs-popover-bg: rgba(20, 20, 20, 0.98) !important;
    --bs-popover-body-color: rgba(255, 255, 255, 0.95) !important;
    --bs-popover-header-bg: rgba(15, 15, 15, 0.98) !important;
    --bs-popover-header-color: #60a5fa !important;
    --bs-popover-border-color: rgba(59, 130, 246, 0.3) !important;
}

.popover .popover-body {
    background-color: rgba(20, 20, 20, 0.98) !important;
    color: rgba(255, 255, 255, 0.95) !important;
}

.popover .popover-header {
    background-color: rgba(15, 15, 15, 0.98) !important;
    color: #60a5fa !important;
}
</style>

<script>
// Global variables
let bomItems = [];
let currentCardId = null;
let currentProductId = null;

const PREVIEW_ISSUE_LABELS = {
    missing_product_name: 'Nama produk kosong',
    invalid_quantity: 'Kuantitas tidak valid',
    invalid_price: 'Harga tidak valid',
    invalid_discount: 'Diskon tidak valid',
    invalid_transaction_date: 'Tanggal transaksi tidak valid',
    missing_category: 'Kategori wajib diisi',
    missing_description: 'Deskripsi wajib diisi',
    invalid_amount: 'Nilai tidak valid',
    invalid_cost_date: 'Tanggal biaya tidak valid'
};

const PRODUCT_MATCH_STATUS = {
    exact: { label: 'Produk cocok', className: 'bg-success' },
    fuzzy: { label: 'Butuh konfirmasi', className: 'bg-warning text-dark' },
    missing: { label: 'Produk belum ada', className: 'bg-danger' }
};

const rupiahFormatter = new Intl.NumberFormat('id-ID');
const currencyFormatters = {};

function formatCurrencyWithSymbol(value) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
        return 'Rp 0';
    }
    return 'Rp ' + rupiahFormatter.format(Math.round(numeric));
}

function createCurrencyFormatter(displayId, hiddenId) {
    const display = document.getElementById(displayId);
    const hidden = document.getElementById(hiddenId);

    if (!display || !hidden) {
        return null;
    }

    const parseDigits = (value) => {
        if (value === null || value === undefined) return null;
        const cleaned = value.toString().replace(/[^0-9]/g, '');
        if (!cleaned) return null;
        return Number(cleaned);
    };

    const syncHidden = (numeric) => {
        if (numeric === null || Number.isNaN(numeric)) {
            hidden.value = '';
        } else {
            hidden.value = String(Math.round(numeric));
        }
    };

    const formatDisplayValue = (numeric) => {
        if (numeric === null || Number.isNaN(numeric)) {
            display.value = '';
        } else {
            display.value = rupiahFormatter.format(Math.round(numeric));
        }
    };

    const handleInput = () => {
        const numeric = parseDigits(display.value);
        if (numeric === null) {
            syncHidden(null);
            display.value = '';
        } else {
            syncHidden(numeric);
            formatDisplayValue(numeric);
            display.setSelectionRange(display.value.length, display.value.length);
        }
    };

    if (!display.dataset.formatterBound) {
        display.addEventListener('input', handleInput);
        display.addEventListener('focus', () => {
            display.value = hidden.value || '';
            display.setSelectionRange(display.value.length, display.value.length);
        });
        display.addEventListener('blur', () => {
            const numeric = parseDigits(display.value);
            if (numeric === null) {
                syncHidden(null);
                display.value = '';
            } else {
                syncHidden(numeric);
                formatDisplayValue(numeric);
            }
        });
        display.dataset.formatterBound = 'true';
    }

    return {
        set(value) {
            if (value === null || value === undefined || value === '') {
                syncHidden(null);
                display.value = '';
                return;
            }

            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                syncHidden(null);
                display.value = '';
                return;
            }

            syncHidden(numeric);
            formatDisplayValue(numeric);
        },
        get() {
            if (hidden.value === '') {
                return null;
            }
            const numeric = Number(hidden.value);
            return Number.isFinite(numeric) ? numeric : null;
        },
        clear() {
            syncHidden(null);
            display.value = '';
        }
    };
}

function initCurrencyFormatters() {
    currencyFormatters.productSellingPrice = createCurrencyFormatter('productSellingPriceDisplay', 'productSellingPrice');
    currencyFormatters.productCostPrice = createCurrencyFormatter('productCostPriceDisplay', 'productCostPrice');
    currencyFormatters.bomCostPerUnit = createCurrencyFormatter('bomCostPerUnitDisplay', 'bomCostPerUnit');

    const sellingHidden = document.getElementById('productSellingPrice');
    if (currencyFormatters.productSellingPrice && sellingHidden?.value) {
        currencyFormatters.productSellingPrice.set(sellingHidden.value);
    }

    const costHidden = document.getElementById('productCostPrice');
    if (currencyFormatters.productCostPrice && costHidden?.value) {
        currencyFormatters.productCostPrice.set(costHidden.value);
    }

    const bomHidden = document.getElementById('bomCostPerUnit');
    if (currencyFormatters.bomCostPerUnit && bomHidden?.value) {
        currencyFormatters.bomCostPerUnit.set(bomHidden.value);
    }
}

function createDefaultPreviewState() {
    return {
        token: null,
        dataType: 'sales',
        rows: [],
        summary: null,
        fileName: null,
        autoCreateProducts: false,
        newProductCandidates: []
    };
}

let dataFeedPreviewState = createDefaultPreviewState();

function resetPreviewUI() {
    dataFeedPreviewState = createDefaultPreviewState();

    const previewSection = document.getElementById('importPreviewSection');
    const previewContent = document.getElementById('importPreviewContent');
    const previewBadges = document.getElementById('previewSummaryBadges');
    const previewIssues = document.getElementById('previewIssuesContainer');
    const previewTableBody = document.querySelector('#previewTable tbody');
    const previewBadge = document.getElementById('previewDataTypeBadge');
    const previewNewProducts = document.getElementById('previewNewProducts');
    const autoCreateButton = document.getElementById('autoCreateProductsButton');
    const autoCreateControl = document.getElementById('autoCreateProductsControl');
    const autoCreateToggle = document.getElementById('autoCreateProductsToggle');
    const previewBtn = document.getElementById('previewBtn');
    const importBtn = document.getElementById('importBtn');
    const dataTypeSelect = document.getElementById('salesImportType');

    if (previewSection) {
        previewSection.style.display = 'none';
    }

    if (previewContent) {
        previewContent.classList.remove('border', 'border-success');
    }

    if (previewBadges) {
        previewBadges.innerHTML = '<span class="badge bg-secondary">Belum ada data preview</span>';
    }

    if (previewIssues) {
        previewIssues.innerHTML = '';
    }

    if (previewTableBody) {
        previewTableBody.innerHTML = '<tr id="previewEmptyRow"><td colspan="19" class="text-center text-white-50 py-4">Upload file untuk melihat pratinjau data.</td></tr>';
    }

    if (previewBadge) {
        previewBadge.textContent = 'Sales';
    }

    if (previewNewProducts) {
        previewNewProducts.textContent = '';
    }

    if (autoCreateButton) {
        autoCreateButton.disabled = false;
        autoCreateButton.style.display = 'none';
    }

    if (autoCreateToggle) {
        autoCreateToggle.checked = false;
    }

    if (autoCreateControl) {
        autoCreateControl.style.display = 'none';
    }

    if (previewBtn) {
        if (!previewBtn.dataset.originalText) {
            previewBtn.dataset.originalText = previewBtn.innerHTML;
        }
        previewBtn.disabled = false;
        previewBtn.innerHTML = previewBtn.dataset.originalText;
        previewBtn.style.display = 'inline-block';
    }

    if (importBtn) {
        if (!importBtn.dataset.originalText) {
            importBtn.dataset.originalText = importBtn.innerHTML;
        }
        importBtn.disabled = false;
        importBtn.innerHTML = importBtn.dataset.originalText;
        importBtn.style.display = 'none';
    }
}

function setButtonLoading(button, loading, loadingText = 'Memproses...') {
    if (!button) return;
    if (!button.dataset.originalText) {
        button.dataset.originalText = button.innerHTML;
    }

    if (loading) {
        button.disabled = true;
        button.innerHTML = loadingText;
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText;
    }
}

function translateIssue(issue) {
    return PREVIEW_ISSUE_LABELS[issue] || issue.replace(/_/g, ' ');
}

function buildIssueBadges(issues) {
    if (!issues || issues.length === 0) {
        return '<span class="text-white-50">Tidak ada catatan</span>';
    }

    return issues.map(key => `<span class="badge bg-danger text-white me-1">${translateIssue(key)}</span>`).join(' ');
}

function buildProductMatchBadge(match) {
    if (!match || !match.status) {
        return '<span class="badge bg-secondary">Status tidak diketahui</span>';
    }

    const meta = PRODUCT_MATCH_STATUS[match.status] || { label: match.status, className: 'bg-secondary' };
    const details = match.status === 'fuzzy' && match.score ? ` (${Math.round(match.score)}%)` : '';
    return `<span class="badge ${meta.className} me-1">${meta.label}${details}</span>`;
}

function formatPreviewNumber(value, decimals = 2) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const parsed = typeof value === 'number' ? value : parseFloat(value);
    if (Number.isNaN(parsed)) {
        return '-';
    }

    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: decimals
    }).format(parsed);
}

function renderPreviewRows(rows) {
    console.log('renderPreviewRows called with:', rows);
    const tbody = document.querySelector('#previewTable tbody');
    if (!tbody) {
        console.error('Preview table tbody not found');
        return;
    }

    tbody.innerHTML = '';

    if (!rows || rows.length === 0) {
        console.log('No rows to display, showing empty message');
        tbody.innerHTML = '<tr id="previewEmptyRow"><td colspan="19" class="text-center text-white-50 py-4">File berhasil dibaca namun contoh data kosong.</td></tr>';
        return;
    }

    console.log('Processing', rows.length, 'rows for display');

    rows.forEach((row, index) => {
        const tr = document.createElement('tr');
        tr.classList.add('align-middle');
        tr.style.fontSize = '0.75rem';

        let rowClass = row.valid ? 'table-success' : 'table-danger';
        if (row.product_match?.status === 'missing') {
            rowClass = 'table-warning';
        }
        tr.classList.add(rowClass);

        const normalized = row.normalized || {};
        const original = row.original || {};

        tr.innerHTML = `
            <td style="padding: 0.25rem;">${normalized.transaction_date || '-'}</td>
            <td style="padding: 0.25rem;">${normalized.customer_name || '-'}</td>
            <td style="padding: 0.25rem;">${normalized.customer_email || '-'}</td>
            <td style="padding: 0.25rem;">${normalized.customer_phone || '-'}</td>
            <td style="padding: 0.25rem;">
                <div class="fw-semibold">${normalized.product_name || '-'}</div>
                ${original.product_name && original.product_name !== normalized.product_name ? `<small class="text-white-50">Asli: ${original.product_name}</small>` : ''}
            </td>
            <td style="padding: 0.25rem;">${normalized.product_category || '-'}</td>
            <td style="padding: 0.25rem;">${formatPreviewNumber(normalized.quantity, 3)}</td>
            <td style="padding: 0.25rem;">${normalized.unit || '-'}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.selling_price)}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.discount)}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.tax_amount)}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.shipping_cost)}</td>
            <td style="padding: 0.25rem;">${normalized.payment_method || '-'}</td>
            <td style="padding: 0.25rem;">${normalized.notes || '-'}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.product_cost_price)}</td>
            <td style="padding: 0.25rem;">${normalized.material_name || '-'}</td>
            <td style="padding: 0.25rem;">${formatPreviewNumber(normalized.material_quantity, 3)}</td>
            <td style="padding: 0.25rem;">${normalized.material_unit || '-'}</td>
            <td style="padding: 0.25rem;">Rp ${formatPreviewNumber(normalized.material_cost_per_unit)}</td>
        `;

        tbody.appendChild(tr);
    });
}

function renderPreviewData(payload) {
    console.log('renderPreviewData called with payload:', payload);
    const previousAutoCreate = dataFeedPreviewState?.autoCreateProducts ?? false;

    dataFeedPreviewState = {
        token: payload.upload_token,
        dataType: payload.data_type,
        rows: payload.rows || [],
        summary: payload.summary || null,
        fileName: payload.file_name || null,
        autoCreateProducts: previousAutoCreate,
        newProductCandidates: payload.summary?.new_product_candidates || []
    };

    console.log('Updated dataFeedPreviewState:', dataFeedPreviewState);

    const previewSection = document.getElementById('importPreviewSection');
    const previewBadge = document.getElementById('previewDataTypeBadge');
    const previewBadges = document.getElementById('previewSummaryBadges');
    const previewIssues = document.getElementById('previewIssuesContainer');
    const previewNewProducts = document.getElementById('previewNewProducts');
    const autoCreateControl = document.getElementById('autoCreateProductsControl');
    const autoCreateToggle = document.getElementById('autoCreateProductsToggle');
    const autoCreateButton = document.getElementById('autoCreateProductsButton');
    const previewBtn = document.getElementById('previewBtn');
    const importBtn = document.getElementById('importBtn');

    if (previewSection) {
        previewSection.style.display = 'block';
    }

    if (previewBadge) {
        const label = payload.data_type === 'universal' ? 'Data Universal' :
                     (payload.data_type === 'costs' ? 'Costs' : 'Sales');
        previewBadge.textContent = label;
    }

    if (previewBadges && payload.summary) {
        const { total_rows = 0, valid_rows = 0, invalid_rows = 0 } = payload.summary;
        previewBadges.innerHTML = `
            <span class="badge bg-info text-dark">Total Baris: ${total_rows}</span>
            <span class="badge bg-success">Valid: ${valid_rows}</span>
            <span class="badge bg-danger">Perlu Perbaikan: ${invalid_rows}</span>
        `;
    }

    if (previewIssues) {
        const invalid = payload.summary?.invalid_rows ?? 0;
        if (invalid > 0) {
            previewIssues.innerHTML = `
                <div class="alert alert-warning border-warning text-dark">
                    <strong>${invalid} baris</strong> memerlukan perbaikan sebelum dapat diproses. Periksa kolom "Catatan" untuk detail.
                </div>
            `;
        } else {
            previewIssues.innerHTML = '<div class="alert alert-success border-success">Seluruh baris sample valid dan siap diproses.</div>';
        }
    }

    if (previewNewProducts) {
        const candidates = payload.summary?.new_product_candidates || [];
        console.log('New product candidates found:', candidates);
        if (candidates.length > 0) {
            const candidateNames = candidates.map(c => {
                if (typeof c === 'string') return c;
                return c.name || c.product_name || 'Produk Baru';
            }).slice(0, 10);
            previewNewProducts.innerHTML = `
                <div class="alert alert-info p-2 mb-2">
                    <small><strong>🆕 ${candidates.length} Produk Baru Ditemukan:</strong></small>
                    <div class="mt-1 d-flex flex-wrap gap-2">
                        ${candidateNames.map(name => `<span class="badge bg-warning text-dark">${name}</span>`).join(' ')}
                    </div>
                </div>
            `;
        } else {
            previewNewProducts.innerHTML = '';
        }
    }

    if (autoCreateControl) {
        const shouldShowAutoCreate = (payload.summary?.new_product_candidates?.length || 0) > 0 &&
                                    (payload.data_type === 'sales' || payload.data_type === 'universal');
        autoCreateControl.style.display = shouldShowAutoCreate ? 'block' : 'none';
    }

    if (autoCreateButton) {
        const candidates = dataFeedPreviewState.newProductCandidates || [];
        autoCreateButton.style.display = candidates.length > 0 ? 'inline-flex' : 'none';
        autoCreateButton.disabled = candidates.length === 0;
    }

    if (autoCreateToggle) {
        autoCreateToggle.checked = dataFeedPreviewState.autoCreateProducts;
        autoCreateToggle.onchange = (event) => {
            dataFeedPreviewState.autoCreateProducts = !!event.target.checked;
        };
    }

    // Render the preview rows in the table
    console.log('About to render rows:', payload.rows);
    renderPreviewRows(payload.rows || []);

    if (previewBtn) {
        previewBtn.style.display = 'inline-block';
        previewBtn.disabled = false;
        previewBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Preview Ulang';
    }

    if (importBtn) {
        importBtn.style.display = 'inline-block';
        importBtn.disabled = false;
    }

    showAlert('Preview data berhasil dimuat', 'success');
}

function handleCommitErrorContext(context = {}) {
    const previewIssues = document.getElementById('previewIssuesContainer');
    const previewNewProducts = document.getElementById('previewNewProducts');
    const autoCreateControl = document.getElementById('autoCreateProductsControl');
    const autoCreateButton = document.getElementById('autoCreateProductsButton');

    const sections = [];

    if (Array.isArray(context.invalid_rows) && context.invalid_rows.length > 0) {
        const examples = context.invalid_rows.slice(0, 5).map(row => {
            const rowNumber = row.row ?? '-';
            const issues = Array.isArray(row.issues) ? row.issues.join(', ') : 'Masalah pada baris ini';
            return `<li><strong>Baris ${rowNumber}:</strong> ${issues}</li>`;
        }).join('');

        sections.push(`
            <div class="alert alert-danger border-danger text-dark">
                <strong>${context.invalid_rows.length} baris</strong> masih perlu diperbaiki sebelum commit.
                <ul class="mb-0 mt-2 text-start">${examples}</ul>
            </div>
        `);
    }

    if (Array.isArray(context.unresolved_products) && context.unresolved_products.length > 0) {
        const unresolvedList = context.unresolved_products.slice(0, 5).map(row => {
            const suggestions = Array.isArray(row.suggestions) ? row.suggestions.slice(0, 3).join(', ') : 'Periksa kembali nama produk.';
            return `<li><strong>Baris ${row.row ?? '-'}:</strong> ${row.product_name} <br><span class="text-white-50">Saran: ${suggestions}</span></li>`;
        }).join('');

        sections.push(`
            <div class="alert alert-warning border-warning text-dark">
                <strong>${context.unresolved_products.length} produk</strong> membutuhkan konfirmasi manual sebelum dapat diproses.
                <ul class="mb-0 mt-2 text-start">${unresolvedList}</ul>
            </div>
        `);
    }

    if (Array.isArray(context.missing_products) && context.missing_products.length > 0) {
        const missingBadges = context.missing_products.map(item => `<span class="badge bg-warning text-dark">${item.product_name}</span>`).join(' ');
        if (previewNewProducts) {
            previewNewProducts.innerHTML = `
                <div class="alert alert-warning border-warning text-dark">
                    <strong>${context.missing_products.length} produk</strong> belum terdaftar dalam master produk.<br>
                    Aktifkan opsi auto-create atau buat produk secara manual terlebih dahulu.
                    <div class="mt-2 d-flex flex-wrap gap-2">${missingBadges}</div>
                </div>
            `;
        }

        if (autoCreateControl) {
            autoCreateControl.style.display = 'block';
        }

        if (autoCreateButton) {
            autoCreateButton.style.display = 'inline-flex';
            autoCreateButton.disabled = false;
        }
    }

    if (previewIssues && sections.length > 0) {
        previewIssues.innerHTML = sections.join('');
    }
}

// DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Data Feeds App initialized');
    initCurrencyFormatters();
    initializeEventListeners();
    loadExistingProducts();
    initializePopovers();
});

// Initialize Bootstrap popovers
function initializePopovers() {
    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            trigger: 'hover focus',
            container: 'body',
            customClass: 'custom-popover'
        });
    });
}

// Initialize all event listeners
function initializeEventListeners() {
    // Product Info Form
    const productForm = document.getElementById('productInfoForm');
    if (productForm) {
        productForm.addEventListener('submit', handleProductInfoSubmit);
    }

    // BOM Form
    const bomForm = document.getElementById('bomForm');
    if (bomForm) {
        bomForm.addEventListener('submit', handleBomSubmit);
    }

    const autoCostToggle = document.getElementById('autoCostSyncToggle');
    if (autoCostToggle) {
        autoCostToggle.addEventListener('change', handleAutoCostToggle);
        applyAutoCostReadOnlyState();
    }

    // Modal events
    const modal = document.getElementById('manageDataModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function () {
            loadProductData(currentCardId);
            // Re-initialize popovers after modal is shown
            setTimeout(() => {
                initializePopovers();
            }, 100);
        });

        modal.addEventListener('hidden.bs.modal', function () {
            resetForms();
        });
    }
}

// Load existing products on page load
async function loadExistingProducts() {
    const container = document.getElementById('productCardsContainer');
    const emptyState = document.getElementById('productCardsEmptyState');

    if (!container) {
        console.error('Product cards container not found');
        return;
    }

    try {
        const response = await fetch('/api/products/all');
        if (response.ok) {
            const result = await response.json();
            if (result.success && Array.isArray(result.products)) {
                result.products.forEach(product => {
                    if (product.card_id) {
                        createProductCard(product.card_id, product);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error loading existing products:', error);
    } finally {
        const hasCards = container.querySelectorAll('.card.card-liquid-glass').length > 0;
        if (emptyState) {
            emptyState.classList.toggle('d-none', hasCards);
        }
    }
}

// Show import modal (placeholder)
function showImportModal() {
    openSalesImportModal();
}

// Add new product card
async function addProductCard() {
    const cardId = 'product-card-' + Date.now();

    try {
        // Create draft product in backend first
        const response = await fetch('/api/products/create-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                card_id: cardId,
                name: 'Produk Baru',
                status: 'draft'
            })
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                createProductCard(cardId, result.product);
            } else {
                showAlert('Gagal membuat produk: ' + result.message, 'danger');
            }
        } else {
            showAlert('Terjadi kesalahan saat membuat produk', 'danger');
        }
    } catch (error) {
        console.error('Error creating product draft:', error);
        showAlert('Terjadi kesalahan saat membuat produk', 'danger');
    }
}

// Create product card HTML
function createProductCard(cardId, product = null) {
    const container = document.getElementById('productCardsContainer');
    if (!container) {
        console.error('Product cards container not found');
        return;
    }

    const emptyState = document.getElementById('productCardsEmptyState');
    const productName = product?.name || 'Produk Baru';
    const category = product?.category || '-';
    const sellingPriceValue = product?.selling_price;
    const sellingPrice = sellingPriceValue !== undefined && sellingPriceValue !== null && sellingPriceValue !== ''
        ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(sellingPriceValue))
        : '-';

    const existingCard = document.getElementById(cardId);
    if (existingCard) {
        const titleElement = existingCard.querySelector('.card-title');
        if (titleElement && !titleElement.querySelector('input')) {
            titleElement.textContent = productName;
        }

        const categoryElement = existingCard.querySelector('.category-text');
        if (categoryElement) {
            categoryElement.textContent = category || '-';
        }

        const priceElement = existingCard.querySelector('.price-text');
        if (priceElement) {
            priceElement.textContent = sellingPrice;
        }

        if (product?.id) {
            existingCard.dataset.productId = product.id;
        }

        if (emptyState) {
            emptyState.classList.add('d-none');
        }

        return existingCard;
    }

    const cardHTML = `
        <div class="col-md-6 col-lg-4 mb-4" id="${cardId}">
            <div class="card card-liquid-glass">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title editable-title" onclick="makeEditable(this, '${cardId}')">
                            ${productName}
                        </h5>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeCard('${cardId}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="card-info">
                        <p class="card-text text-white mb-2">
                            <small>Kategori: <span class="category-text">${category}</span></small>
                        </p>
                        <p class="card-text text-white  mb-2">
                            <small>Harga: <span class="price-text">${sellingPrice}</span></small>
                        </p>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-light btn-sm" onclick="openManageDataModal('${cardId}')">
                            <i class="bi bi-table me-2"></i>
                            Kelola Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', cardHTML);

    const newCard = document.getElementById(cardId);
    if (newCard && product?.id) {
        newCard.dataset.productId = product.id;
    }

    if (emptyState) {
        emptyState.classList.add('d-none');
    }

    return newCard;
}

// Make title editable
function makeEditable(element, cardId) {
    if (element.querySelector('input')) return; // Already editing

    const currentText = element.textContent.trim();
    element.innerHTML = `<input type="text" class="form-control form-control-sm" value="${currentText}">`;

    const input = element.querySelector('input');
    input.focus();
    input.select();

    function saveEdit() {
        const newText = input.value.trim() || 'Produk Baru';
        element.innerHTML = newText;
        element.onclick = () => makeEditable(element, cardId);

        // Update card data
        updateCardTitle(cardId, newText);
    }

    input.addEventListener('blur', saveEdit);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            saveEdit();
        }
    });
}

// Update card title in backend
async function updateCardTitle(cardId, title) {
    try {
        const response = await fetch('/api/products/update-title', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                card_id: cardId,
                title: title
            })
        });

        if (!response.ok) {
            console.error('Failed to update card title');
        }
    } catch (error) {
        console.error('Error updating card title:', error);
    }
}

// Sync name with card title (live sync)
function syncNameWithCard(cardId, newName) {
    const card = document.getElementById(cardId);
    if (card) {
        const titleElement = card.querySelector('.card-title');
        if (titleElement && !titleElement.querySelector('input')) {
            titleElement.textContent = newName || 'Produk Baru';
        }
    }
}

// Remove card
function removeCard(cardId) {
    if (confirm('Hapus kartu produk ini?')) {
        const cardElement = document.getElementById(cardId);
        if (cardElement) {
            cardElement.remove();
        }

        const container = document.getElementById('productCardsContainer');
        const hasCards = container?.querySelectorAll('.card.card-liquid-glass').length;
        if (!hasCards) {
            const emptyState = document.getElementById('productCardsEmptyState');
            if (emptyState) {
                emptyState.classList.remove('d-none');
            }
        }

        // Delete from backend if has product ID
        deleteProduct(cardId);
    }
}

// Delete product from backend
async function deleteProduct(cardId) {
    try {
        const response = await fetch('/api/products/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                card_id: cardId
            })
        });

        if (!response.ok) {
            console.error('Failed to delete product');
        }
    } catch (error) {
        console.error('Error deleting product:', error);
    }
}

// Open manage data modal
function openManageDataModal(cardId) {
    currentCardId = cardId;
    const modal = new bootstrap.Modal(document.getElementById('manageDataModal'));
    modal.show();
}

// Load product data into modal
async function loadProductData(cardId) {
    try {
        const response = await fetch(`/api/products/get/${cardId}`);

        if (response.ok) {
            const data = await response.json();

            if (data.success && data.product) {
                populateProductForm(data.product);
                currentProductId = data.product.id;
            } else {
                // New product - clear form
                resetProductForm();
            }

            if (data.bom) {
                bomItems = data.bom;
                renderBomTable();
            }
        } else {
            console.error('Failed to load product data');
            resetProductForm();
        }
    } catch (error) {
        console.error('Error loading product data:', error);
        resetProductForm();
    }
}

// Populate product form
function populateProductForm(product) {
    document.getElementById('productId').value = product.id || '';
    document.getElementById('cardId').value = currentCardId;
    document.getElementById('productName').value = product.name || '';
    document.getElementById('productCategory').value = product.category || '';
    document.getElementById('productUnit').value = product.unit || 'Pcs';
    document.getElementById('productDescription').value = product.description || '';

    if (currencyFormatters.productSellingPrice) {
        currencyFormatters.productSellingPrice.set(product.selling_price ?? null);
    } else {
        document.getElementById('productSellingPrice').value = product.selling_price || '';
    }

    if (currencyFormatters.productCostPrice) {
        currencyFormatters.productCostPrice.set(product.cost_price ?? null);
    } else {
        document.getElementById('productCostPrice').value = product.cost_price || '';
    }

    // Add event listener to sync name changes with card title
    const nameField = document.getElementById('productName');
    if (nameField && !nameField.hasAttribute('data-sync-listener')) {
        nameField.addEventListener('input', function() {
            syncNameWithCard(currentCardId, this.value);
        });
        nameField.setAttribute('data-sync-listener', 'true');
    }
}

// Reset product form
function resetProductForm() {
    const form = document.getElementById('productInfoForm');
    if (form) form.reset();
    document.getElementById('productId').value = '';
    document.getElementById('cardId').value = currentCardId;
    currentProductId = null;

    if (currencyFormatters.productSellingPrice) {
        currencyFormatters.productSellingPrice.clear();
    } else {
        document.getElementById('productSellingPrice').value = '';
        document.getElementById('productSellingPriceDisplay').value = '';
    }

    if (currencyFormatters.productCostPrice) {
        currencyFormatters.productCostPrice.clear();
    } else {
        document.getElementById('productCostPrice').value = '';
        document.getElementById('productCostPriceDisplay').value = '';
    }

    applyAutoCostReadOnlyState();
}

// Reset all forms
function resetForms() {
    resetProductForm();
    resetBomForm();
    bomItems = [];
    renderBomTable();
    currentCardId = null;
    currentProductId = null;
}

// Handle product info form submission
async function handleProductInfoSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const productData = Object.fromEntries(formData);

    try {
        const response = await fetch('/api/products/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(productData)
        });

        if (response.ok) {
            const result = await response.json();

            if (result.success) {
                currentProductId = result.product.id;
                document.getElementById('productId').value = result.product.id;

                // Update card display
                updateCardDisplay(currentCardId, result.product);

                // Show success message
                showAlert('Info produk berhasil disimpan!', 'success');
            } else {
                showAlert('Gagal menyimpan info produk: ' + result.message, 'danger');
            }
        } else {
            showAlert('Terjadi kesalahan saat menyimpan data', 'danger');
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showAlert('Terjadi kesalahan saat menyimpan data', 'danger');
    }
}

// Update card display with new data
function updateCardDisplay(cardId, product) {
    const card = document.getElementById(cardId);
    if (card) {
        const titleElement = card.querySelector('.card-title');
        const categoryElement = card.querySelector('.category-text');
        const priceElement = card.querySelector('.price-text');

        if (titleElement && product.name) {
            titleElement.textContent = product.name;
            // Sync with modal name field if modal is open
            const modalNameField = document.getElementById('productName');
            if (modalNameField && currentCardId === cardId) {
                modalNameField.value = product.name;
            }
        }

        if (categoryElement && product.category) {
            categoryElement.textContent = product.category;
        }

        if (priceElement && product.selling_price) {
            priceElement.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(product.selling_price);
        }
    }
}

// Handle BOM form submission
async function handleBomSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const bomData = Object.fromEntries(formData);

    // Validate
    if (!bomData.material_name || !bomData.quantity || !bomData.cost_per_unit) {
        showAlert('Mohon lengkapi semua field BOM', 'warning');
        return;
    }

    // Add to local array
    bomItems.push({
        id: Date.now(), // temporary ID
        material_name: bomData.material_name,
        quantity: parseFloat(bomData.quantity),
        unit: bomData.unit,
        cost_per_unit: parseFloat(bomData.cost_per_unit)
    });

    // Reset form
    resetBomForm();

    // Re-render table
    renderBomTable();

    showAlert('Bahan baku ditambahkan', 'success');
}

// Reset BOM form
function resetBomForm() {
    const form = document.getElementById('bomForm');
    if (form) form.reset();

    if (currencyFormatters.bomCostPerUnit) {
        currencyFormatters.bomCostPerUnit.clear();
    } else {
        const hidden = document.getElementById('bomCostPerUnit');
        const display = document.getElementById('bomCostPerUnitDisplay');
        if (hidden) hidden.value = '';
        if (display) display.value = '';
    }
}

// Render BOM table
function renderBomTable() {
    const tbody = document.getElementById('bomTableBody');
    const footer = document.getElementById('bomTotalFooter');
    const emptyMessage = document.getElementById('emptyBomMessage');
    const totalCost = calculateBomTotalCost();

    // Clear existing rows except empty message
    tbody.querySelectorAll('tr:not(#emptyBomMessage)').forEach(row => row.remove());

    if (!Array.isArray(bomItems) || bomItems.length === 0) {
        emptyMessage.style.display = '';
        footer.style.display = 'none';
    } else {
        emptyMessage.style.display = 'none';
        footer.style.display = '';

        bomItems.forEach((item, index) => {
            const quantity = parseFloat(item.quantity) || 0;
            const costPerUnit = parseFloat(item.cost_per_unit) || 0;
            const itemTotal = quantity * costPerUnit;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-white">${item.material_name}</td>
                <td class="text-white">${quantity}</td>
                <td class="text-white">${item.unit}</td>
                <td class="text-white">Rp ${rupiahFormatter.format(costPerUnit)}</td>
                <td class="fw-bold text-success">Rp ${rupiahFormatter.format(itemTotal)}</td>
                <td>
                    <button onclick="removeBomItem(${index})" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
        });
    }

    const totalLabel = document.getElementById('totalBomCost');
    if (totalLabel) {
        totalLabel.textContent = 'Rp ' + rupiahFormatter.format(totalCost);
    }

    syncCostWithBom(totalCost);
    applyAutoCostReadOnlyState();
}

function calculateBomTotalCost() {
    if (!Array.isArray(bomItems) || bomItems.length === 0) {
        return 0;
    }

    return bomItems.reduce((total, item) => {
        const quantity = parseFloat(item.quantity) || 0;
        const costPerUnit = parseFloat(item.cost_per_unit) || 0;
        return total + (quantity * costPerUnit);
    }, 0);
}

function applyAutoCostReadOnlyState() {
    const toggle = document.getElementById('autoCostSyncToggle');
    const display = document.getElementById('productCostPriceDisplay');

    if (!display || !toggle) {
        return;
    }

    display.readOnly = toggle.checked;
    display.classList.toggle('opacity-75', toggle.checked);
}

function syncCostWithBom(totalCost) {
    const toggle = document.getElementById('autoCostSyncToggle');
    if (!toggle || !toggle.checked) {
        return;
    }

    const numericTotal = Number.isFinite(totalCost) ? Math.round(totalCost) : 0;

    if (currencyFormatters.productCostPrice) {
        currencyFormatters.productCostPrice.set(numericTotal);
    } else {
        const hidden = document.getElementById('productCostPrice');
        const display = document.getElementById('productCostPriceDisplay');
        if (hidden) hidden.value = String(numericTotal);
        if (display) display.value = rupiahFormatter.format(numericTotal);
    }
}

function handleAutoCostToggle() {
    applyAutoCostReadOnlyState();
    const totalCost = calculateBomTotalCost();
    syncCostWithBom(totalCost);
}

// Remove BOM item
function removeBomItem(index) {
    if (confirm('Hapus bahan ini?')) {
        bomItems.splice(index, 1);
        renderBomTable();
        showAlert('Bahan baku dihapus', 'info');
    }
}

// Save all data (product + BOM)
async function saveAllData() {
    if (!currentProductId) {
        showAlert('Simpan info produk terlebih dahulu', 'warning');
        return;
    }

    try {
        const response = await fetch('/api/products/save-bom', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                product_id: currentProductId,
                bom_items: bomItems
            })
        });

        if (response.ok) {
            const result = await response.json();

            if (result.success) {
                showAlert('Semua data berhasil disimpan!', 'success');

                // Close modal after delay
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('manageDataModal')).hide();
                }, 1500);
            } else {
                showAlert('Gagal menyimpan BOM: ' + result.message, 'danger');
            }
        } else {
            showAlert('Terjadi kesalahan saat menyimpan BOM', 'danger');
        }
    } catch (error) {
        console.error('Error saving BOM:', error);
        showAlert('Terjadi kesalahan saat menyimpan BOM', 'danger');
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Add to body
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Sales Transaction Functions
let transactionItemIndex = 0;
let productSearchCache = {};
let customerSearchCache = {};

// Format currency helper function
function formatCurrency(amount) {
    try {
        // Convert to number if it's a string
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;

        // Check if it's a valid number
        if (isNaN(numAmount) || numAmount === null || numAmount === undefined) {
            return '0';
        }

        // Use Indonesian locale formatting
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numAmount);
    } catch (error) {
        console.error('Error formatting currency:', error);
        // Fallback to simple number formatting
        return Math.round(amount || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
}

// Load recent transactions from API
async function loadRecentTransactions(options = {}) {
    try {
        console.log('Loading recent transactions...');
        if (window.currentUploadFilterId) {
            options.data_feed_id = window.currentUploadFilterId;
        }
        const params = new URLSearchParams({
            page: options.page || 1,
            per_page: options.perPage || 10,
            search: options.search || document.getElementById('transactionSearch')?.value || '',
            status: options.status || document.getElementById('statusFilter')?.value || '',
            start_date: options.startDate || document.getElementById('dateStart')?.value || '',
            end_date: options.endDate || document.getElementById('dateEnd')?.value || '',
            sort_by: options.sortBy || 'transaction_date',
            sort_dir: options.sortDir || 'desc',
            data_feed_id: options.data_feed_id || ''
        });

        const response = await fetch('/api/sales-transactions?' + params.toString(), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Transactions response:', result);

        if (result.success && result.data) {
            displayRecentTransactions(result.data.transactions);

            // Update statistics if available
            if (result.data.statistics) {
                updateSalesStatistics(result.data.statistics);
            }

            // Render pagination
            renderTransactionsPagination(result.data.pagination, options);
        } else {
            console.warn('No transaction data available:', result.message);
            showEmptyTransactionsMessage();
        }
    } catch (error) {
        console.error('Error loading recent transactions:', error);
        showEmptyTransactionsMessage();
    }
}

// Display recent transactions in table
function displayRecentTransactions(transactions) {
    const tbody = document.getElementById('recentTransactionsBody');
    if (!tbody) {
        console.error('Recent transactions table body not found');
        return;
    }

    // Clear existing content
    tbody.innerHTML = '';

    if (!transactions || transactions.length === 0) {
        showEmptyTransactionsMessage();
        return;
    }

    // Hide empty message and create rows
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-3">
                <div class="text-sm font-medium">${transaction.transaction_date}</div>
            </td>
            <td class="px-4 py-3">
                <div class="text-sm font-medium">${transaction.customer_name}</div>
            </td>
            <td class="px-4 py-3">
                <div class="text-sm">${transaction.items_summary}</div>
                <div class="text-xs text-muted-foreground">${transaction.items_count} item${transaction.items_count > 1 ? 's' : ''}</div>
            </td>
            <td class="px-4 py-3">
                <div class="text-sm font-medium">${transaction.formatted_total}</div>
            </td>
            <td class="px-4 py-3">
                <select class="form-select form-select-sm bg-light border-secondary transaction-status-select" data-id="${transaction.id}">
                    <option value="completed" ${transaction.status === 'completed' ? 'selected' : ''}>Selesai</option>
                    <option value="pending" ${transaction.status === 'pending' ? 'selected' : ''}>Proses</option>
                    <option value="review" ${transaction.status === 'review' ? 'selected' : ''}>Perlu Ditinjau</option>
                </select>
            </td>
            <td class="px-4 py-3">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" title="Edit" onclick="editTransaction(${transaction.id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="deleteTransaction(${transaction.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    console.log(`Displayed ${transactions.length} transactions`);

    // Attach listeners for status change
    tbody.querySelectorAll('.transaction-status-select').forEach(sel => {
        sel.addEventListener('change', async (e) => {
            const id = e.target.getAttribute('data-id');
            const status = e.target.value;
            try {
                const res = await fetch(`/api/sales-transactions/${id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status })
                });
                const data = await res.json();
                if (!data.success) {
                    showAlert('Gagal memperbarui status', 'danger');
                }
            } catch (err) {
                console.error('Update status error:', err);
                showAlert('Terjadi kesalahan saat memperbarui status', 'danger');
            }
        });
    });
}

// Show empty message when no transactions
function showEmptyTransactionsMessage() {
    const tbody = document.getElementById('recentTransactionsBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr id="emptyTransactionsMessage">
                <td colspan="6" class="px-6 py-8 text-center text-sm text-muted-foreground">
                    <div class="flex flex-col items-center space-y-2">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div>Belum ada transaksi</div>
                        <div class="text-xs">Mulai dengan menambahkan transaksi baru</div>
                    </div>
                </td>
            </tr>
        `;
    }
}

// Update sales statistics (optional)
function updateSalesStatistics(statistics) {
    // Update daily sales
    const dailyElement = document.querySelector('[data-stat="daily-sales"]');
    if (dailyElement) {
        dailyElement.textContent = statistics.formatted_daily;
    }

    // Update weekly sales
    const weeklyElement = document.querySelector('[data-stat="weekly-sales"]');
    if (weeklyElement) {
        weeklyElement.textContent = statistics.formatted_weekly;
    }

    // Update monthly sales
    const monthlyElement = document.querySelector('[data-stat="monthly-sales"]');
    if (monthlyElement) {
        monthlyElement.textContent = statistics.formatted_monthly;
    }

    // Backward-compatible IDs for existing cards
    const todaySales = document.getElementById('todaySales');
    if (todaySales && statistics.formatted_daily) todaySales.textContent = statistics.formatted_daily;
    const weeklySales = document.getElementById('weeklySales');
    if (weeklySales && statistics.formatted_weekly) weeklySales.textContent = statistics.formatted_weekly;
    const monthlySales = document.getElementById('monthlySales');
    if (monthlySales && statistics.formatted_monthly) monthlySales.textContent = statistics.formatted_monthly;
}

// Load compact income overview (stats + latest 5)
async function loadIncomeOverview(options = {}) {
    try {
        const params = new URLSearchParams({
            page: 1,
            per_page: 5,
            search: options.search || document.getElementById('transactionSearch')?.value || '',
            status: options.status || document.getElementById('statusFilter')?.value || '',
            start_date: options.startDate || document.getElementById('dateStart')?.value || '',
            end_date: options.endDate || document.getElementById('dateEnd')?.value || '',
            sort_by: options.sortBy || 'transaction_date',
            sort_dir: options.sortDir || 'desc'
        });

        const response = await fetch('/api/sales-transactions?' + params.toString(), {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const result = await response.json();
        if (result?.data?.statistics) updateSalesStatistics(result.data.statistics);
        renderIncomeOverviewList(result?.data?.transactions || []);
    } catch (err) {
        console.error('Income overview load error:', err);
        renderIncomeOverviewList([]);
    }
}

function renderIncomeOverviewList(transactions) {
    const list = document.getElementById('incomeOverviewList');
    if (!list) return;
    list.innerHTML = '';

    if (!transactions || transactions.length === 0) {
        list.innerHTML = '<li class="text-white-50 small">Belum ada transaksi</li>';
        return;
    }

    transactions.forEach(tx => {
        const dateStr = tx.formatted_date || tx.transaction_date || '-';
        const customer = tx.customer_name || '-';
        const total = typeof tx.total_amount !== 'undefined' ? `Rp ${formatCurrency(tx.total_amount)}` : (tx.formatted_total || 'Rp 0');
        const status = (tx.status || '').toLowerCase();
        const badgeClass = status === 'completed' ? 'success' : (status === 'pending' ? 'warning' : 'secondary');

        const li = document.createElement('li');
        li.className = 'd-flex justify-content-between align-items-center py-2 border-bottom';
        li.style.borderColor = 'rgba(255,255,255,0.07)';
        li.innerHTML = `
            <div class="d-flex flex-column">
                <span class="text-white">${customer}</span>
                <small class="text-white-50">${dateStr}</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-${badgeClass}">${tx.status || '-'}</span>
                <span class="text-white fw-semibold">${total}</span>
            </div>
        `;
        list.appendChild(li);
    });
}

// Placeholder functions for transaction actions
function viewTransactionDetail(transactionId) {
    alert(`Viewing transaction ${transactionId} - Feature will be implemented soon`);
}

function editTransaction(transactionId) {
    openSalesTransactionModal();
    // Mark editing mode
    const form = document.getElementById('salesTransactionForm');
    form.setAttribute('data-edit-id', transactionId);
    loadTransactionIntoForm(transactionId);
}

async function deleteTransaction(transactionId) {
    if (!confirm('Hapus transaksi ini? Tindakan ini tidak dapat dibatalkan.')) return;
    try {
        const res = await fetch(`/api/sales-transactions/${transactionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await res.json();
        if (data.success) {
            showAlert('Transaksi dihapus', 'success');
            loadRecentTransactions();
        } else {
            showAlert(data.message || 'Gagal menghapus transaksi', 'danger');
        }
    } catch (e) {
        console.error('Delete error:', e);
        showAlert('Terjadi kesalahan saat menghapus transaksi', 'danger');
    }
}

// Render pagination controls
function renderTransactionsPagination(pagination, options) {
    // For simplicity, render minimal controls below the table
    const table = document.getElementById('transactionsTable');
    if (!table) return;

    let footer = table.nextElementSibling;
    if (!footer || !footer.classList.contains('transactions-pagination')) {
        footer = document.createElement('div');
        footer.className = 'transactions-pagination d-flex justify-content-between align-items-center p-2 border-top';
        footer.style.borderColor = 'rgba(255,255,255,0.1)';
        table.parentElement.appendChild(footer);
    }

    const { current_page, last_page, total } = pagination;
    footer.innerHTML = `
        <div class="text-white-50 small">Total: ${total}</div>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-light" ${current_page <= 1 ? 'disabled' : ''} id="tx-prev">Prev</button>
            <span class="btn btn-sm btn-dark">${current_page} / ${last_page}</span>
            <button class="btn btn-sm btn-outline-light" ${current_page >= last_page ? 'disabled' : ''} id="tx-next">Next</button>
        </div>`;

    footer.querySelector('#tx-prev')?.addEventListener('click', () => {
        loadRecentTransactions({
            ...options,
            page: Math.max(1, current_page - 1)
        });
    });
    footer.querySelector('#tx-next')?.addEventListener('click', () => {
        loadRecentTransactions({
            ...options,
            page: Math.min(last_page, current_page + 1)
        });
    });
}

// Wire up filters
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('transactionSearch');
    const status = document.getElementById('statusFilter');
    const start = document.getElementById('dateStart');
    const end = document.getElementById('dateEnd');
    const refresh = document.getElementById('refreshTransactions');
    let currentSort = { by: 'transaction_date', dir: 'desc' };

    if (search) {
        let t;
        search.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => loadRecentTransactions({ page: 1 }), 300);
        });
    }
    status?.addEventListener('change', () => { const opts = { page: 1 }; loadRecentTransactions(opts); loadIncomeOverview(opts); });
    start?.addEventListener('change', () => { const opts = { page: 1 }; loadRecentTransactions(opts); loadIncomeOverview(opts); });
    end?.addEventListener('change', () => { const opts = { page: 1 }; loadRecentTransactions(opts); loadIncomeOverview(opts); });
    refresh?.addEventListener('click', (e) => { e.preventDefault(); const opts = {}; loadRecentTransactions(opts); loadIncomeOverview(opts); });

    // Sorting
    document.querySelectorAll('#transactionsTable thead th.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            const by = th.getAttribute('data-sort-by');
            if (currentSort.by === by) {
                currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.by = by;
                currentSort.dir = 'asc';
            }
            const opts = { sortBy: currentSort.by, sortDir: currentSort.dir, page: 1 };
            loadRecentTransactions(opts);
            loadIncomeOverview(opts);
        });
    });
});

// Initialize transaction modal
function openSalesTransactionModal() {
    console.log('openSalesTransactionModal called'); // Debug log

    // Test formatCurrency availability
    if (typeof formatCurrency !== 'function') {
        console.error('formatCurrency is not defined!');
        alert('Error: formatCurrency function is not available');
        return;
    }

    try {
        // Set default transaction date to current time
        const now = new Date();
        const formattedDateTime = now.toISOString().slice(0, 16);
        const dateTimeInput = document.getElementById('transactionDateTime');
        if (dateTimeInput) {
            dateTimeInput.value = formattedDateTime;
        }

        // Reset form
        resetSalesTransactionForm();

        // Show modal
        const modalElement = document.getElementById('salesTransactionModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal shown successfully');
        } else {
            console.error('Modal element not found');
        }
    } catch (error) {
        console.error('Error opening modal:', error);
        alert('Error opening transaction modal: ' + error.message);
    }
}

// Reset sales transaction form
function resetSalesTransactionForm() {
    console.log('Resetting sales transaction form...');

    const form = document.getElementById('salesTransactionForm');
    if (form) form.reset();

    // Clear transaction items container except first item
    const container = document.getElementById('transactionItemsContainer');
    if (container) {
        const firstItem = container.querySelector('.transaction-item');
        if (firstItem) {
            container.innerHTML = '';
            container.appendChild(firstItem);

            // Reset the first item's values
            const firstItemInputs = firstItem.querySelectorAll('input');
            firstItemInputs.forEach(input => {
                if (input.type === 'number') {
                    input.value = input.name.includes('quantity') ? '1' : '0';
                } else if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
        }
    }

    // Reset item index
    transactionItemIndex = 0;

    // Clear totals
    updateTransactionTotals();

    // Set default datetime
    const now = new Date();
    const dateTimeInput = document.getElementById('transactionDateTime');
    if (dateTimeInput) {
        dateTimeInput.value = now.toISOString().slice(0, 16);
    }

    console.log('Sales transaction form reset completed');
}

// Load an existing transaction and fill the form
async function loadTransactionIntoForm(id) {
    try {
        const res = await fetch(`/api/sales-transactions/${id}`, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!res.ok || !json.success) {
            const message = json?.message || 'Gagal memuat data transaksi';
            showAlert(message, 'danger');
            return;
        }

        const data = json.data;

        // Date
        const dt = document.getElementById('transactionDateTime');
        if (dt && data.transaction_date) {
            // Convert to yyyy-MM-ddTHH:mm
            const d = new Date(data.transaction_date);
            const isoLocal = new Date(d.getTime() - d.getTimezoneOffset()*60000).toISOString().slice(0,16);
            dt.value = isoLocal;
        }

        // Customer
        const customerInput = document.getElementById('customerName');
        if (customerInput) {
            customerInput.value = data.customer?.name || '';
            if (data.customer?.id) customerInput.dataset.customerId = data.customer.id;
        }

        // Notes, tax, shipping
        const notes = document.getElementById('transactionNotes');
        if (notes) notes.value = data.notes || '';
        const tax = document.getElementById('transactionTax');
        if (tax) tax.value = data.tax_amount || 0;
        const shipping = document.getElementById('shippingCost');
        if (shipping) shipping.value = data.shipping_cost || 0;

        // Items
        const container = document.getElementById('transactionItemsContainer');
        if (container) container.innerHTML = '';
        transactionItemIndex = 0;
        (data.items || []).forEach((it, idx) => {
            addTransactionItem();
            const itemEl = container.querySelector('[data-item-index="' + transactionItemIndex + '"]');
            if (!itemEl) return;
            itemEl.querySelector('.product-search').value = it.product_name || '';
            if (it.product_id) itemEl.querySelector('.product-id').value = it.product_id;
            itemEl.querySelector('.quantity-input').value = it.quantity || 0;
            itemEl.querySelector('.price-input').value = it.selling_price || 0;
            itemEl.querySelector('.discount-input').value = it.discount || 0;
            calculateItemSubtotal(itemEl);
        });

        updateTransactionTotals();
    } catch (e) {
        console.error('Load transaction error:', e);
        showAlert('Terjadi kesalahan saat memuat data transaksi', 'danger');
    }
}

// Add new transaction item
function addTransactionItem() {
    transactionItemIndex++;
    const container = document.getElementById('transactionItemsContainer');

    if (!container) return;

    const itemHtml = `
        <div class="transaction-item border-bottom pb-3 mb-3" data-item-index="${transactionItemIndex}">
            <div class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="form-label fw-semibold text-white small">Produk</label>
                    <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary product-search"
                           style="border-color: rgba(255,255,255,0.2) !important;"
                           name="items[${transactionItemIndex}][product_name]"
                           placeholder="Cari produk..."
                           autocomplete="off" required>
                    <input type="hidden" name="items[${transactionItemIndex}][product_id]" class="product-id">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label fw-semibold text-white small">Qty</label>
                    <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary quantity-input"
                           style="border-color: rgba(255,255,255,0.2) !important;"
                           name="items[${transactionItemIndex}][quantity]"
                           placeholder="1"
                           min="1" step="0.01" value="1" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label fw-semibold text-white small">Harga</label>
                    <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary price-input"
                           style="border-color: rgba(255,255,255,0.2) !important;"
                           name="items[${transactionItemIndex}][selling_price]"
                           placeholder="0"
                           min="0" step="0.01" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label fw-semibold text-white small">Diskon</label>
                    <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary discount-input"
                           style="border-color: rgba(255,255,255,0.2) !important;"
                           name="items[${transactionItemIndex}][discount]"
                           placeholder="0"
                           min="0" step="0.01" value="0">
                </div>
                <div class="col-md-1 mb-2 text-end">
                    <label class="form-label fw-semibold text-white small">Subtotal</label>
                    <div class="text-success fw-bold item-subtotal">Rp 0</div>
                </div>
                <div class="col-md-1 mb-2 text-end">
                    <label class="form-label fw-semibold text-white small">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTransactionItem(${transactionItemIndex})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);
    setupTransactionItemEvents();
}

// Remove transaction item
function removeTransactionItem(index) {
    const item = document.querySelector(`[data-item-index="${index}"]`);
    if (item) {
        item.remove();
        updateTransactionTotals();
    }
}

// Setup event listeners for transaction items
function setupTransactionItemEvents() {
    // Product search functionality
    document.querySelectorAll('.product-search').forEach(input => {
        input.removeEventListener('input', handleProductSearch);
        input.addEventListener('input', handleProductSearch);
    });

    // Quantity, price, discount change events
    document.querySelectorAll('.quantity-input, .price-input, .discount-input').forEach(input => {
        input.removeEventListener('input', handleItemCalculation);
        input.addEventListener('input', handleItemCalculation);
    });

    // Tax and shipping calculation
    const taxInput = document.getElementById('transactionTax');
    const shippingInput = document.getElementById('shippingCost');

    if (taxInput) {
        taxInput.removeEventListener('input', updateTransactionTotals);
        taxInput.addEventListener('input', updateTransactionTotals);
    }

    if (shippingInput) {
        shippingInput.removeEventListener('input', updateTransactionTotals);
        shippingInput.addEventListener('input', updateTransactionTotals);
    }
}

// Handle product search
function handleProductSearch(event) {
    const input = event.target;
    const query = input.value.trim();

    if (query.length < 2) return;

    // Use cached results if available
    if (productSearchCache[query]) {
        showProductSuggestions(input, productSearchCache[query]);
        return;
    }

    // Fetch product suggestions
    fetch(`/api/products/search?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            productSearchCache[query] = data.products;
            showProductSuggestions(input, data.products);
        }
    })
    .catch(error => console.error('Product search error:', error));
}

// Show product suggestions dropdown
function showProductSuggestions(input, products) {
    // Remove existing dropdown
    const existingDropdown = input.parentNode.querySelector('.product-dropdown');
    if (existingDropdown) {
        existingDropdown.remove();
    }

    if (products.length === 0) return;

    const dropdown = document.createElement('div');
    dropdown.className = 'dropdown-menu show product-dropdown';
    dropdown.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; max-height: 200px; overflow-y: auto; background: rgba(40, 40, 40, 0.95); border: 1px solid rgba(255, 255, 255, 0.2);';

    products.forEach(product => {
        const item = document.createElement('div');
        item.className = 'dropdown-item text-white';
        item.style.cssText = 'cursor: pointer; padding: 0.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.1);';
        item.innerHTML = `
            <div class="fw-semibold">${product.name}</div>
            <small class="text-success">Rp ${formatCurrency(product.selling_price || 0)}</small>
        `;

        item.addEventListener('click', () => {
            selectProduct(input, product);
            dropdown.remove();
        });

        dropdown.appendChild(item);
    });

    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(dropdown);

    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!input.parentNode.contains(e.target)) {
            dropdown.remove();
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// Select product from dropdown
function selectProduct(input, product) {
    const itemContainer = input.closest('.transaction-item');

    // Set product name and ID
    input.value = product.name;
    itemContainer.querySelector('.product-id').value = product.id;

    // Set selling price
    const priceInput = itemContainer.querySelector('.price-input');
    priceInput.value = product.selling_price || 0;

    // Calculate item subtotal
    calculateItemSubtotal(itemContainer);
}

// Handle item calculation (quantity, price, discount changes)
function handleItemCalculation(event) {
    const itemContainer = event.target.closest('.transaction-item');
    calculateItemSubtotal(itemContainer);
}

// Calculate item subtotal
function calculateItemSubtotal(itemContainer) {
    const quantity = parseFloat(itemContainer.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(itemContainer.querySelector('.price-input').value) || 0;
    const discount = parseFloat(itemContainer.querySelector('.discount-input').value) || 0;

    const subtotal = (quantity * price) - discount;
    const subtotalElement = itemContainer.querySelector('.item-subtotal');
    subtotalElement.textContent = `Rp ${formatCurrency(subtotal)}`;

    updateTransactionTotals();
}

// Update transaction totals
function updateTransactionTotals() {
    let itemsTotal = 0;

    // Calculate items subtotal
    document.querySelectorAll('.transaction-item').forEach(item => {
        const quantity = parseFloat(item.querySelector('.quantity-input')?.value) || 0;
        const price = parseFloat(item.querySelector('.price-input')?.value) || 0;
        const discount = parseFloat(item.querySelector('.discount-input')?.value) || 0;

        itemsTotal += (quantity * price) - discount;
    });

    const tax = parseFloat(document.getElementById('transactionTax')?.value) || 0;
    const shipping = parseFloat(document.getElementById('shippingCost')?.value) || 0;
    const finalTotal = itemsTotal + tax + shipping;

    // Update display
    const itemsSubtotalEl = document.getElementById('itemsSubtotal');
    const displayTaxEl = document.getElementById('displayTax');
    const displayShippingEl = document.getElementById('displayShipping');
    const finalTotalEl = document.getElementById('finalTotal');

    if (itemsSubtotalEl) itemsSubtotalEl.textContent = `Rp ${formatCurrency(itemsTotal)}`;
    if (displayTaxEl) displayTaxEl.textContent = `Rp ${formatCurrency(tax)}`;
    if (displayShippingEl) displayShippingEl.textContent = `Rp ${formatCurrency(shipping)}`;
    if (finalTotalEl) finalTotalEl.textContent = `Rp ${formatCurrency(finalTotal)}`;
}

    // Customer search functionality
    function initializeCustomerSearch() {
        const customerInput = document.getElementById('customerName');
        if (!customerInput) return;

        customerInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();

            if (query.length < 2) {
                const suggestionsEl = document.getElementById('customerSuggestions');
                if (suggestionsEl) suggestionsEl.style.display = 'none';
                return;
            }

            // Use cached results if available
            if (customerSearchCache[query]) {
                showCustomerSuggestions(customerSearchCache[query]);
                return;
            }

            // Fetch customer suggestions
            fetch(`/api/customers/search?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    customerSearchCache[query] = data.customers;
                    showCustomerSuggestions(data.customers);
                }
            })
            .catch(error => console.error('Customer search error:', error));
        });
    }

    // Show customer suggestions
    function showCustomerSuggestions(customers) {
        const suggestionsDiv = document.getElementById('customerSuggestions');
        if (!suggestionsDiv) return;

        suggestionsDiv.innerHTML = '';

        if (customers.length === 0) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        customers.forEach(customer => {
            const item = document.createElement('div');
            item.className = 'dropdown-item text-white';
            item.style.cssText = 'cursor: pointer; background: rgba(40, 40, 40, 0.95); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 0.5rem;';
            item.innerHTML = `
                <div class="fw-semibold">${customer.name}</div>
                <small class="text-muted">${customer.phone || 'Tidak ada telepon'}</small>
            `;

            item.addEventListener('click', () => {
                document.getElementById('customerName').value = customer.name;
                document.getElementById('customerName').dataset.customerId = customer.id;
                suggestionsDiv.style.display = 'none';
            });

            suggestionsDiv.appendChild(item);
        });

        suggestionsDiv.style.display = 'block';
    }

    // Add new customer
    function addNewCustomer() {
        const customerName = document.getElementById('customerName').value.trim();
        if (!customerName) {
            showAlert('Masukkan nama pelanggan terlebih dahulu', 'warning');
            return;
        }

        // Simple prompt for now, can be enhanced with proper modal
        const customerPhone = prompt('Nomor telepon pelanggan (opsional):');

        fetch('/api/customers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: customerName,
                phone: customerPhone
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('customerName').dataset.customerId = data.customer.id;
                showAlert('Pelanggan baru berhasil ditambahkan', 'success');
            } else {
                showAlert('Gagal menambahkan pelanggan: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Add customer error:', error);
            showAlert('Terjadi kesalahan saat menambahkan pelanggan', 'danger');
        });
    }    // Save sales transaction
    function saveSalesTransaction() {
        const form = document.getElementById('salesTransactionForm');
        if (!form) return;

        const formData = new FormData(form);

        // Add customer ID if available
        const customerName = document.getElementById('customerName')?.value;
        const customerId = document.getElementById('customerName')?.dataset.customerId;

        if (customerId) {
            formData.append('customer_id', customerId);
        }

        // Validate that we have at least one item with product
        let hasValidItems = false;
        document.querySelectorAll('.transaction-item').forEach(item => {
            const productName = item.querySelector('.product-search')?.value.trim();
            const quantity = parseFloat(item.querySelector('.quantity-input')?.value) || 0;
            const price = parseFloat(item.querySelector('.price-input')?.value) || 0;

            if (productName && quantity > 0 && price > 0) {
                hasValidItems = true;
            }
        });

        if (!hasValidItems) {
            showAlert('Tambahkan minimal satu item dengan data yang valid', 'warning');
            return;
        }

        if (!customerName) {
            showAlert('Nama pelanggan harus diisi', 'warning');
            return;
        }

        // Show loading state
        const saveButton = document.querySelector('#salesTransactionModal .btn-success');
        if (saveButton) {
            const originalText = saveButton.innerHTML;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Menyimpan...';

            // Debug: log form data before sending
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }

            // Decide create vs update
            const editId = form.getAttribute('data-edit-id');
            const url = editId ? `/api/sales-transactions/${editId}` : '/api/sales-transactions';
            const method = editId ? 'PUT' : 'POST';
            // Make API call
            fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showAlert('Transaksi berhasil disimpan', 'success');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('salesTransactionModal'));
                    if (modal) modal.hide();

                    // Reset form
                    resetSalesTransactionForm();
                    form.removeAttribute('data-edit-id');

                    // Reload recent transactions to show the new data
                    loadRecentTransactions();
                } else {
                    showAlert('Gagal menyimpan transaksi: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Save transaction error:', error);
                showAlert('Terjadi kesalahan saat menyimpan transaksi', 'danger');
            })
            .finally(() => {
                // Reset button state
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
            });
        }
    }// Sales Import Functions
function openSalesImportModal() {
    try {
        const modalElement = document.getElementById('salesImportModal');
        if (!modalElement) {
            console.error('Import modal element not found');
            return;
        }

        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();

        const form = document.getElementById('salesImportForm');
        if (form) {
            form.reset();
        }

        resetPreviewUI();
    } catch (error) {
        console.error('Error opening import modal:', error);
        showAlert('Gagal membuka modal import: ' + error.message, 'danger');
    }
}

// Download universal template
function downloadUniversalTemplate(format) {
    console.log('Downloading universal template:', format);
    window.open(`/api/data-feeds/universal-template?format=${format || 'csv'}`, '_blank');
}

// Preview import data
async function previewImportData() {
    const fileInput = document.getElementById('salesImportFile');
    const file = fileInput?.files?.[0];

    if (!file) {
        showAlert('Pilih file untuk diupload terlebih dahulu', 'warning');
        return;
    }

    const previewBtn = document.getElementById('previewBtn');
    const importBtn = document.getElementById('importBtn');

    resetPreviewUI();
    if (importBtn) {
        importBtn.style.display = 'none';
    }

    setButtonLoading(previewBtn, true, '<i class="bi bi-hourglass-split me-2"></i>Mengunggah...');

    try {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/dashboard/data-feeds/preview', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });

        const payload = await response.json();
        console.log('Preview payload received:', payload);

        if (!response.ok) {
            const message = payload?.errors?.file?.[0] || payload?.message || 'Gagal memuat preview data.';
            throw new Error(message);
        }

        console.log('Rows data:', payload.rows);
        console.log('Summary data:', payload.summary);
        renderPreviewData(payload);
    } catch (error) {
        console.error('Preview import error:', error);
        showAlert(error.message || 'Terjadi kesalahan saat memproses preview', 'danger');
    } finally {
        setButtonLoading(previewBtn, false);
    }
}

// Process sales import
async function processSalesImport() {
    if (!dataFeedPreviewState.token) {
        showAlert('Lakukan preview dan pastikan data valid sebelum memproses import.', 'warning');
        return;
    }

    const importBtn = document.getElementById('importBtn');
    const autoCreateToggle = document.getElementById('autoCreateProductsToggle');
    const autoCreate = autoCreateToggle ? !!autoCreateToggle.checked : !!dataFeedPreviewState.autoCreateProducts;

    dataFeedPreviewState.autoCreateProducts = autoCreate;

    setButtonLoading(importBtn, true, '<i class="bi bi-cloud-upload me-2"></i>Memproses...');

    try {
        const response = await fetch('/dashboard/data-feeds/commit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                upload_token: dataFeedPreviewState.token,
                auto_create_products: autoCreate
            })
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.success === false) {
            handleCommitErrorContext(payload.context || payload.errors || {});
            const message = payload?.message || 'Gagal memproses import data.';
            throw new Error(message);
        }

    showAlert(payload.message || 'Data feed berhasil diantrikan untuk diproses.', 'success');

    // Refresh dashboard data to reflect the newly imported sales
    loadRecentTransactions();
    loadIncomeOverview();
    loadExistingProducts(); // Refresh product cards to show new products
    if (typeof refreshUploadsList === 'function') { refreshUploadsList(); }

        const modalEl = document.getElementById('salesImportModal');
        const modalInstance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        resetPreviewUI();
        if (modalInstance) {
            modalInstance.hide();
        }
    } catch (error) {
        console.error('Commit import error:', error);
        showAlert(error.message || 'Terjadi kesalahan saat memproses commit.', 'danger');
    } finally {
        setButtonLoading(importBtn, false);
    }
}

async function autoCreateMissingProducts() {
    const candidates = dataFeedPreviewState.newProductCandidates || [];
    if (!Array.isArray(candidates) || candidates.length === 0) {
        showAlert('Tidak ada kandidat produk baru untuk dibuat.', 'info');
        return;
    }

    const button = document.getElementById('autoCreateProductsButton');
    setButtonLoading(button, true, '<i class="bi bi-stars me-2"></i>Membuat...');

    try {
        const productsToSend = candidates.map((candidate) => {
            if (typeof candidate === 'string') {
                return { name: candidate };
            }
            return {
                name: candidate.name || candidate,
                category: candidate.category || 'Lainnya',
                unit: candidate.unit || 'Pcs',
                selling_price: candidate.selling_price || 0,
                cost_price: candidate.cost_price || 0
            };
        });

        console.log('Sending products to auto-create:', productsToSend);

        const response = await fetch('/dashboard/data-feeds/auto-create-products', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                products: productsToSend
            })
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.success === false) {
            const message = payload?.message || 'Gagal membuat produk baru.';
            throw new Error(message);
        }

        const created = (payload.results || []).filter(item => item.status === 'created');
        const reused = (payload.results || []).filter(item => item.status === 'existing');
        const productsToRender = (payload.results || [])
            .map(item => item.product)
            .filter(product => product && product.card_id);

        productsToRender.forEach(product => {
            createProductCard(product.card_id, product);
        });

        dataFeedPreviewState.newProductCandidates = [];
        dataFeedPreviewState.autoCreateProducts = true;

        const autoCreateToggle = document.getElementById('autoCreateProductsToggle');
        if (autoCreateToggle) {
            autoCreateToggle.checked = true;
        }

        const previewNewProducts = document.getElementById('previewNewProducts');
        if (previewNewProducts) {
            previewNewProducts.innerHTML = `
                <div class="alert alert-success border-success">
                    <strong>${created.length}</strong> produk dibuat otomatis.${reused.length > 0 ? ` ${reused.length} produk sudah tersedia.` : ''}
                </div>
            `;
        }

        showAlert('Produk baru berhasil dibuat.', 'success');
    } catch (error) {
        console.error('Auto-create product error:', error);
        showAlert(error.message || 'Terjadi kesalahan saat membuat produk.', 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

// Initialize sales functionality on page load
// ================= Upload History Functions =================
function initializeUploadsHistory() {
    const searchInput = document.getElementById('uploadSearch');
    const limitSelect = document.getElementById('uploadLimit');
    if (searchInput) {
        let t;
        searchInput.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => refreshUploadsList(), 350);
        });
    }
    limitSelect?.addEventListener('change', () => refreshUploadsList());
    // initial load
    refreshUploadsList();
}

async function refreshUploadsList() {
    const tbody = document.getElementById('uploadsTableBody');
    const emptyRow = document.getElementById('emptyUploadsMessage');
    if (!tbody) return;

    if (emptyRow) {
        emptyRow.innerHTML = '<td colspan="7" class="text-center text-white-50 py-4">Memuat data upload...</td>';
    }

    const limitSelect = document.getElementById('uploadLimit');
    const searchInput = document.getElementById('uploadSearch');
    const limit = parseInt(limitSelect?.value || '25', 10);
    const searchTerm = (searchInput?.value || '').toLowerCase().trim();

    try {
        const res = await fetch(`/dashboard/data-feeds/uploads?limit=${limit}`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await res.json();
        if (!res.ok || payload.success === false) {
            throw new Error(payload.message || 'Gagal memuat riwayat upload');
        }
        let data = payload.data || [];
        if (searchTerm) {
            data = data.filter(item => (item.original_name || '').toLowerCase().includes(searchTerm));
        }
        renderUploadsList(data);
    } catch (e) {
        console.error('Load uploads error:', e);
        if (emptyRow) {
            emptyRow.innerHTML = `<td colspan="7" class="text-center text-danger py-4">${e.message}</td>`;
        }
    }
}

function renderUploadsList(feeds) {
    const tbody = document.getElementById('uploadsTableBody');
    const emptyRow = document.getElementById('emptyUploadsMessage');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!feeds || feeds.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-white-50 py-4">Belum ada data upload.</td></tr>';
        return;
    }

    feeds.forEach(feed => {
        const tr = document.createElement('tr');
        const statusBadge = mapFeedStatusBadge(feed.status);
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', (e) => {
            // Ignore clicks on delete button
            if (e.target.closest('button')) return;
            applyUploadFilter(feed.id, tr);
        });
        tr.innerHTML = `
            <td class="text-white-50 small">${formatDateTime(feed.created_at)}</td>
            <td class="text-white small">${escapeHtml(feed.original_name || '-')}</td>
            <td class="text-white-50 small">${feed.data_type || '-'}</td>
            <td class="text-end text-white small">${feed.record_count ?? '-'}</td>
            <td>${statusBadge}</td>
            <td class="text-white-50 small" style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(feed.log_message || '')}">${escapeHtml(feed.log_message || '')}</td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-danger" onclick="deleteUpload(${feed.id})" ${feed.status === 'processing' ? 'disabled' : ''}>
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);
    });
}

function applyUploadFilter(feedId, rowEl) {
    window.currentUploadFilterId = feedId;
    highlightSelectedUpload(rowEl);
    const clearBtn = document.getElementById('clearUploadFilterBtn');
    if (clearBtn) clearBtn.classList.remove('d-none');
    loadRecentTransactions({ page: 1 });
    // Optional scroll to transactions section
    const txSection = document.getElementById('transactionsTable');
    if (txSection) {
        txSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function clearUploadFilter() {
    window.currentUploadFilterId = null;
    const clearBtn = document.getElementById('clearUploadFilterBtn');
    if (clearBtn) clearBtn.classList.add('d-none');
    removeUploadHighlights();
    loadRecentTransactions({ page: 1 });
}

function highlightSelectedUpload(rowEl) {
    removeUploadHighlights();
    if (rowEl) {
        rowEl.classList.add('table-active');
        rowEl.style.backgroundColor = 'rgba(255,255,255,0.08)';
    }
}

function removeUploadHighlights() {
    document.querySelectorAll('#uploadsTableBody tr').forEach(tr => {
        tr.classList.remove('table-active');
        tr.style.backgroundColor = '';
    });
}

function mapFeedStatusBadge(status) {
    const map = {
        pending: 'secondary',
        processing: 'info',
        completed: 'success',
        failed: 'danger',
        transforming: 'warning'
    };
    const color = map[status] || 'secondary';
    const label = (status || '').charAt(0).toUpperCase() + (status || '').slice(1);
    return `<span class="badge bg-${color}">${label}</span>`;
}

function formatDateTime(dt) {
    if (!dt) return '-';
    try { return new Date(dt).toLocaleString('id-ID'); } catch { return dt; }
}

function escapeHtml(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>'"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;' }[c]));
}

async function deleteUpload(id) {
    if (!confirm('Hapus data feed ini? Tindakan ini tidak dapat dibatalkan.')) return;
    try {
        const res = await fetch(`/dashboard/data-feeds/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const payload = await res.json();
        if (!res.ok || payload.success === false) {
            throw new Error(payload.message || 'Gagal menghapus data feed');
        }
        showAlert('Data feed berhasil dihapus.', 'success');
        refreshUploadsList();
    } catch (e) {
        console.error('Delete upload error:', e);
        showAlert(e.message || 'Terjadi kesalahan saat menghapus data feed.', 'danger');
    }
}

// Hook into import completion to refresh uploads
const originalProcessSalesImport = typeof processSalesImport === 'function' ? processSalesImport : null;
// If re-defining, we wrap existing behavior. Already defined below, so we cannot wrap here reliably without duplicate.
// Instead, after successful import in processSalesImport, we already call functions; augment by monkey patching after definition below if needed.
// We will rely on explicit call inserted in existing processSalesImport success path if necessary.
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing sales functionality...');

    // Initialize uploads list after other initializations
    try {
        initializeUploadsHistory();
    } catch (e) {
        console.error('Failed to initialize uploads history:', e);
    }

    setupTransactionItemEvents();
    initializeCustomerSearch();

    // Set default datetime
    const now = new Date();
    const dateTimeInput = document.getElementById('transactionDateTime');
    if (dateTimeInput) {
        dateTimeInput.value = now.toISOString().slice(0, 16);
    }

    console.log('Sales functions loaded successfully');

    // Load overviews and recent transactions on page load
    loadIncomeOverview();
    loadRecentTransactions();

    // Make functions globally accessible - ensure they are available on window object
    window.openSalesTransactionModal = openSalesTransactionModal;
    window.openSalesImportModal = openSalesImportModal;
    window.saveSalesTransaction = saveSalesTransaction;
    window.addTransactionItem = addTransactionItem;
    window.removeTransactionItem = removeTransactionItem;
    window.downloadUniversalTemplate = downloadUniversalTemplate;
    window.previewImportData = previewImportData;
    window.processSalesImport = processSalesImport;
    window.autoCreateMissingProducts = autoCreateMissingProducts;
    window.addNewCustomer = addNewCustomer;
    window.formatCurrency = formatCurrency; // Add formatCurrency to global scope
    window.loadRecentTransactions = loadRecentTransactions; // Add data loading function
    window.loadIncomeOverview = loadIncomeOverview; // Expose overview loader
    window.refreshUploadsList = refreshUploadsList; // Expose uploads refresh
    window.deleteUpload = deleteUpload; // Expose delete function

    console.log('All sales functions made globally available');

    // Test formatCurrency function
    console.log('Testing formatCurrency(50000):', formatCurrency(50000));

    // Test that modal elements exist
    const salesModal = document.getElementById('salesTransactionModal');
    const importModal = document.getElementById('salesImportModal');

    console.log('Sales modal exists:', !!salesModal);
    console.log('Import modal exists:', !!importModal);

    // Test Bootstrap modal functionality
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap is loaded and ready');
    } else {
        console.error('Bootstrap is not loaded!');
    }
});
</script>
@endsection
