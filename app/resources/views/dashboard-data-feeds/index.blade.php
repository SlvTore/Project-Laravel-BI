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
                    <button onclick="showCleanWarehouseModal()" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash3 me-2"></i>
                        Clean Warehouse
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

<!-- Clean Warehouse Confirmation Modal -->
<div class="modal fade" id="cleanWarehouseModal" tabindex="-1" aria-labelledby="cleanWarehouseModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-warning" id="cleanWarehouseModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Konfirmasi Pembersihan Warehouse
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-shield-exclamation me-2"></i>Peringatan!</h6>
                    <p class="mb-2">Tindakan ini akan <strong>menghapus seluruh data warehouse</strong> termasuk:</p>
                    <ul class="mb-2">
                        <li>Semua data fact_sales</li>
                        <li>Data staging (sales items & costs)</li>
                        <li>Data dimensi (customers & products)</li>
                        <li>Riwayat metrik dan analisis</li>
                    </ul>
                    <p class="mb-0"><strong>Data yang dihapus tidak dapat dikembalikan!</strong></p>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Untuk melanjutkan, ketik: <strong class="text-warning">HAPUS SEMUA DATA</strong></label>
                        <input type="text" id="cleanWarehouseConfirmText" class="form-control bg-dark text-white border-secondary"
                               placeholder="Ketik konfirmasi disini...">
                    </div>
                </div>

                <div id="cleanWarehouseStatus" class="mt-3 d-none">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-warning me-3" role="status"></div>
                        <span>Membersihkan data warehouse...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmCleanWarehouseBtn" onclick="executeCleanWarehouse()" disabled>
                    <i class="bi bi-trash3 me-2"></i>
                    Ya, Hapus Semua Data
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard-data-feeds.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/dashboard-data-feeds.js') }}" defer></script>
@endpush

@endsection
