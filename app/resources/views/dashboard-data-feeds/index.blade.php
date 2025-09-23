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

    <!-- Products Management Card -->
    <div class="row p-3">
        <div class="col-12">
            <div class="card card-liquid-transparent mb-4">
                <div class="card-body">
                    <!-- Add Product Button -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="text-white mb-0">Manajemen Produk</h3>
                        <button onclick="addProductCard()" class="btn btn-liquid-glass btn-add-product">
                            <i class="bi bi-plus-circle me-2"></i>
                            Add Product
                        </button>
                    </div>

                    <!-- Product Cards Container -->
                    <div id="productCardsContainer" class="row">
                        <!-- Dynamic Product Cards will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales & Income Transaction Section -->
    <div class="row p-3">
        <div class="col-12">
            <div class="card card-liquid-transparent mb-4">
                <div class="card-body">
                    <!-- Sales Section Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="text-white mb-0">
                                <i class="bi bi-cash-coin me-2 text-white"></i>
                                Transaksi Income
                            </h3>
                            <p class="text-white mb-0 mt-1">Gateway pemasukan untuk mencatat setiap transaksi penjualan secara detail</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button onclick="openSalesTransactionModal()" class="btn btn-liquid-glass btn-sales">
                                <i class="bi bi-receipt me-2"></i>
                                Tambah Transaksi
                            </button>
                            <button onclick="openSalesImportModal()" class="btn btn-liquid-glass btn-import-sales">
                                <i class="bi bi-file-earmark-excel me-2"></i>
                                Import Data
                            </button>
                        </div>
                    </div>

                    <!-- Recent Transactions Preview -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                                <div class="card-body text-center">
                                    <i class="bi bi-graph-up display-6 text-white mb-2"></i>
                                    <h6 class="text-white mb-1">Hari Ini</h6>
                                    <h4 class="text-white mb-0" id="todaySales">Rp 0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2);">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-week display-6 text-white mb-2"></i>
                                    <h6 class="text-white mb-1">Minggu Ini</h6>
                                    <h4 class="text-white mb-0" id="weeklySales">Rp 0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2);">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-month display-6 text-white mb-2"></i>
                                    <h6 class="text-white mb-1">Bulan Ini</h6>
                                    <h4 class="text-white mb-0" id="monthlySales">Rp 0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compact Overview List -->
                    <div class="card card-liquid-transparent my-3">
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <h6 class="mb-0 text-white-50">Ringkasan 5 Transaksi Terakhir</h6>
                        </div>
                        <ul class="list-unstyled p-3" id="incomeOverviewList">
                            <li class="text-white-50 small">Memuat ringkasan transaksi...</li>
                        </ul>
                    </div>

                    <!-- Recent Transactions Table -->
                    <div class="card" style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <div class="card-header" style="background: rgba(255, 255, 255, 0.2); border-bottom: 1px solid rgba(255, 255, 255, 0.2);">
                            <h6 class="mb-0 text-white">
                                <i class="bi bi-clock-history me-2 text-white"></i>
                                Transaksi Terbaru
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-3 border-bottom" style="border-color: rgba(255,255,255,0.2) !important;">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text" style="background: rgba(255, 255, 255, 0.1); text-white; border-color: rgba(255, 255, 255, 0.2) !important;"><i class="bi bi-search"></i></span>
                                            <input type="text" id="transactionSearch" class="form-control text-white border-secondary" style="background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;" placeholder="Cari pelanggan, catatan...">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="date" id="dateStart" class="form-control form-control-sm text-white border-secondary" style="background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;" />
                                            </div>
                                            <div class="col-6">
                                                <input type="date" id="dateEnd" class="form-control form-control-sm text-white border-secondary" style="background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <select id="statusFilter" class="form-select form-select-sm text-white border-secondary" style="background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;">
                                            <option value="">Semua Status</option>
                                            <option value="completed">Selesai</option>
                                            <option value="pending">Proses</option>
                                            <option value="review">Perlu Ditinjau</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button id="refreshTransactions" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-repeat"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table mb-0" id="transactionsTable" style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(12px); border-radius: 12px; overflow: hidden;">
                                    <thead style="background: rgba(255,255,255,0.15);">
                                        <tr>
                                            <th class="text-white sortable" data-sort-by="transaction_date">Tanggal <i class="bi bi-arrow-down-up ms-1 opacity-50"></i></th>
                                            <th class="text-white">Pelanggan</th>
                                            <th class="text-white">Item</th>
                                            <th class="text-white sortable" data-sort-by="total_amount">Total <i class="bi bi-arrow-down-up ms-1 opacity-50"></i></th>
                                            <th class="text-white">Informasi</th>
                                            <th class="text-white" width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentTransactionsBody">
                                        <tr id="emptyTransactionsMessage">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-receipt display-6 d-block mb-2 text-secondary"></i>
                                                <span class="text-white-50">Belum ada transaksi</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Transaction Modal -->
<div class="modal fade" id="salesTransactionModal" tabindex="-1" aria-labelledby="salesTransactionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; color: white;">
            <div class="modal-header border-0" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;">
                <h5 class="modal-title fw-bold text-white" id="salesTransactionModalLabel">
                    <i class="bi bi-receipt-cutoff me-2 text-success"></i>
                    Input Transaksi Penjualan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="salesTransactionForm">
                    @csrf

                    <!-- Transaction Header Information -->
                    <div class="card mb-4" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="card-body">
                            <h6 class="card-title text-success">
                                <i class="bi bi-info-circle me-2"></i>
                                Informasi Transaksi
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Tanggal & Waktu Transaksi
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Waktu Transaksi"
                                           data-bs-content="Tentukan kapan transaksi ini terjadi. Default adalah waktu saat ini, tapi bisa diubah untuk input transaksi yang sudah lewat"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <input type="datetime-local" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="transactionDateTime" name="transaction_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Pelanggan
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Data Pelanggan"
                                           data-bs-content="Pilih pelanggan yang melakukan pembelian. Jika pelanggan baru, akan otomatis ditambahkan ke database untuk analisis customer retention"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="customerName" name="customer_name" placeholder="Nama Pelanggan" autocomplete="off">
                                        <button class="btn btn-outline-success" type="button" onclick="addNewCustomer()">
                                            <i class="bi bi-person-plus"></i>
                                        </button>
                                    </div>
                                    <div id="customerSuggestions" class="dropdown-menu" style="width: 100%; max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Items -->
                    <div class="card mb-4" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-title text-primary mb-0">
                                    <i class="bi bi-basket me-2"></i>
                                    Item yang Dibeli
                                </h6>
                                <button type="button" class="btn btn-success btn-sm" onclick="addTransactionItem()">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Tambah Item
                                </button>
                            </div>

                            <!-- Transaction Items Container -->
                            <div id="transactionItemsContainer">
                                <div class="transaction-item border-bottom pb-3 mb-3" data-item-index="0">
                                    <div class="row align-items-end">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Produk
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-title="Pilih Produk"
                                                   data-bs-content="Ketik nama produk untuk mencari. Harga akan otomatis terisi dari master produk tapi bisa diubah sesuai kebutuhan"
                                                   data-bs-html="true"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary product-search"
                                                   style="border-color: rgba(255,255,255,0.2) !important;"
                                                   name="items[0][product_name]"
                                                   placeholder="Cari produk..."
                                                   autocomplete="off" required>
                                            <input type="hidden" name="items[0][product_id]" class="product-id">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">Qty</label>
                                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary quantity-input"
                                                   style="border-color: rgba(255,255,255,0.2) !important;"
                                                   name="items[0][quantity]"
                                                   placeholder="1"
                                                   min="1" step="0.01" value="1" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">Harga</label>
                                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary price-input"
                                                   style="border-color: rgba(255,255,255,0.2) !important;"
                                                   name="items[0][selling_price]"
                                                   placeholder="0"
                                                   min="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">Diskon</label>
                                            <input type="number" class="form-control form-control-sm bg-dark text-white border-secondary discount-input"
                                                   style="border-color: rgba(255,255,255,0.2) !important;"
                                                   name="items[0][discount]"
                                                   placeholder="0"
                                                   min="0" step="0.01" value="0">
                                        </div>
                                        <div class="col-md-2 mb-2 text-end">
                                            <label class="form-label fw-semibold text-white small">Subtotal</label>
                                            <div class="text-success fw-bold item-subtotal">Rp 0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Summary -->
                    <div class="card mb-4" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                        <div class="card-body">
                            <h6 class="card-title text-purple">
                                <i class="bi bi-calculator me-2"></i>
                                Ringkasan Transaksi
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Pajak (Rp)
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-content="Tambahan pajak yang dikenakan pada transaksi ini (opsional)"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="transactionTax" name="tax_amount" placeholder="0" min="0" step="0.01" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Biaya Pengiriman (Rp)
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-content="Biaya pengiriman atau ongkir untuk transaksi ini (opsional)"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="shippingCost" name="shipping_cost" placeholder="0" min="0" step="0.01" value="0">
                                </div>
                            </div>

                            <!-- Transaction Total -->
                            <div class="border-top pt-3 mt-3" style="border-color: rgba(255, 255, 255, 0.2) !important;">
                                <div class="row">
                                    <div class="col-md-8"></div>
                                    <div class="col-md-4">
                                        <table class="table table-sm text-white">
                                            <tbody>
                                                <tr>
                                                    <td>Subtotal Items:</td>
                                                    <td class="text-end" id="itemsSubtotal">Rp 0</td>
                                                </tr>
                                                <tr>
                                                    <td>Pajak:</td>
                                                    <td class="text-end" id="displayTax">Rp 0</td>
                                                </tr>
                                                <tr>
                                                    <td>Pengiriman:</td>
                                                    <td class="text-end" id="displayShipping">Rp 0</td>
                                                </tr>
                                                <tr class="border-top" style="border-color: rgba(255, 255, 255, 0.3) !important;">
                                                    <th>Total Akhir:</th>
                                                    <th class="text-end text-success" id="finalTotal">Rp 0</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); background: rgba(30,30,30,0.9);">
                <button type="button" class="btn btn-secondary bg-dark text-white" style="border-color: rgba(255,255,255,0.2);" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-success" onclick="saveSalesTransaction()">
                    <i class="bi bi-check-lg me-2"></i>
                    Simpan Transaksi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sales Import Modal -->
<div class="modal fade" id="salesImportModal" tabindex="-1" aria-labelledby="salesImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; color: white;">
            <div class="modal-header border-0" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;">
                <h5 class="modal-title fw-bold text-white" id="salesImportModalLabel">
                    <i class="bi bi-file-earmark-excel me-2 text-success"></i>
                    Import Data Penjualan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Download Template -->
                <div class="card mb-4" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-download me-2"></i>
                            Langkah 1: Download Template
                        </h6>
                        <p class="text-white-50 mb-3">Download template Excel/CSV untuk memastikan format data sesuai dengan sistem</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadSalesTemplate('excel')">
                                <i class="bi bi-file-earmark-excel me-1"></i>
                                Template Excel
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="downloadSalesTemplate('csv')">
                                <i class="bi bi-filetype-csv me-1"></i>
                                Template CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Upload File -->
                <div class="card mb-4" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-upload me-2"></i>
                            Langkah 2: Upload File Data
                        </h6>
                        <form id="salesImportForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" class="form-control bg-dark text-white border-secondary"
                                       style="border-color: rgba(255,255,255,0.2) !important;"
                                       id="salesImportFile"
                                       name="sales_file"
                                       accept=".xlsx,.xls,.csv" required>
                                <div class="form-text text-white-50">
                                    Supported formats: .xlsx, .xls, .csv (Max: 10MB)
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Import Preview -->
                <div id="importPreviewSection" class="card" style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(255, 255, 255, 0.1); display: none;">
                    <div class="card-body">
                        <h6 class="card-title text-purple">
                            <i class="bi bi-eye me-2"></i>
                            Preview Data
                        </h6>
                        <div id="importPreviewContent">
                            <!-- Preview content will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1); background: rgba(30,30,30,0.9);">
                <button type="button" class="btn btn-secondary bg-dark text-white" style="border-color: rgba(255,255,255,0.2);" data-bs-dismiss="modal">
                    Tutup
                </button>
                <button type="button" class="btn btn-warning" onclick="previewImportData()" id="previewBtn">
                    <i class="bi bi-eye me-2"></i>
                    Preview Data
                </button>
                <button type="button" class="btn btn-success" onclick="processSalesImport()" id="importBtn" style="display: none;">
                    <i class="bi bi-check-lg me-2"></i>
                    Proses Import
                </button>
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
                        <form id="productInfoForm">
                            @csrf
                            <input type="hidden" id="productId" name="product_id">
                            <input type="hidden" id="cardId" name="card_id">

                            <div class="row">
                                <div class="col-md-6 mb-3">
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
                                <div class="col-md-6 mb-3">
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
                                        <option value="Elektronik">Elektronik</option>
                                        <option value="Pakaian">Pakaian</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
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
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productSellingPrice" name="selling_price" placeholder="0" min="0" step="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Harga Pokok (Rp)
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-title="Menghitung Harga Pokok"
                                           data-bs-content="Komponen Harga Pokok:<br>• <b>Bahan baku</b> (gunakan BOM tab)<br>• <b>Tenaga kerja</b><br>• <b>Overhead</b> (listrik, sewa)<br>• <b>Packaging</b><br><br>💡 <i>Akan otomatis terhitung jika menggunakan Bill of Materials</i>"
                                           data-bs-html="true"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productCostPrice" name="cost_price" placeholder="0" min="0" step="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold text-white">
                                        Unit
                                        <i class="bi bi-info-circle text-info ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="top"
                                           data-bs-trigger="hover focus"
                                           data-bs-content="Satuan penjualan produk. Pilih satuan yang paling umum digunakan untuk produk ini"
                                           style="cursor: pointer; font-size: 0.9em;"></i>
                                    </label>
                                    <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productUnit" name="unit" required>
                                        <option value="Pcs">Pcs</option>
                                        <option value="Kg">Kg</option>
                                        <option value="Liter">Liter</option>
                                        <option value="Meter">Meter</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-white">
                                    Deskripsi
                                    <i class="bi bi-info-circle text-info ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="top"
                                       data-bs-trigger="hover focus"
                                       data-bs-content="Informasi tambahan tentang produk seperti bahan, ukuran, rasa, atau keunggulan khusus produk"
                                       style="cursor: pointer; font-size: 0.9em;"></i>
                                </label>
                                <textarea class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" rows="3" id="productDescription" name="description" placeholder="Deskripsi produk (opsional)"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>
                                    Simpan Info Produk
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bill of Material Tab -->
                    <div class="tab-pane fade" id="bom" role="tabpanel">
                        <!-- Add BOM Item Form -->
                        <div class="card mb-4" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <div class="card-body">
                                <h6 class="card-title text-warning">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Tambah Bahan Baku
                                </h6>
                                <form id="bomForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Nama Bahan
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-title="Bill of Materials (BOM)"
                                                   data-bs-content="BOM adalah daftar bahan yang dibutuhkan:<br>• <b>Tepung terigu</b> - bahan utama<br>• <b>Telur ayam</b> - protein<br>• <b>Kemasan plastik</b> - packaging<br>• <b>Label sticker</b> - branding<br><br>💡 <i>Semakin detail, semakin akurat cost calculation</i>"
                                                   data-bs-html="true"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomMaterialName" name="material_name" placeholder="Nama Bahan" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Qty
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-title="Quantity Planning"
                                                   data-bs-content="Berapa banyak bahan yang diperlukan untuk <b>1 unit produk</b>:<br><br>Contoh untuk Kue Brownies:<br>• Tepung: <b>0.2</b> Kg<br>• Telur: <b>2</b> Pcs<br>• Coklat: <b>0.1</b> Kg<br>• Kemasan: <b>1</b> Pcs<br><br>⚠️ <i>Hitung dengan tepat untuk akurasi cost</i>"
                                                   data-bs-html="true"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomQuantity" name="quantity" placeholder="Qty" required min="0">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Unit
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-content="Satuan ukuran untuk bahan ini. Pilih yang sesuai dengan cara pembelian bahan"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomUnit" name="unit" required>
                                                <option value="kg">Kg</option>
                                                <option value="gram">Gram</option>
                                                <option value="liter">Liter</option>
                                                <option value="ml">ML</option>
                                                <option value="pcs">Pcs</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Harga/Unit
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-title="Cost Per Unit Calculation"
                                                   data-bs-content="Harga bahan per satuan unit:<br><br><b>Cara menghitung:</b><br>• Beli tepung 25kg = Rp 250.000<br>• Harga per Kg = Rp 10.000<br>• Input: <b>10000</b><br><br><b>Tips:</b> Gunakan harga rata-rata dari beberapa supplier untuk akurasi yang lebih baik"
                                                   data-bs-html="true"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomCostPerUnit" name="cost_per_unit" placeholder="Harga/Unit" required min="0">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label fw-semibold text-white small">
                                                Tambah
                                                <i class="bi bi-info-circle text-info ms-1"
                                                   data-bs-toggle="popover"
                                                   data-bs-placement="top"
                                                   data-bs-trigger="hover focus"
                                                   data-bs-content="Klik untuk menambahkan bahan ke daftar BOM"
                                                   style="cursor: pointer; font-size: 0.8em;"></i>
                                            </label>
                                            <button type="submit" class="btn btn-warning btn-sm w-100">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

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

// DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Data Feeds App initialized');
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
    try {
        const response = await fetch('/api/products/all');
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.products) {
                result.products.forEach(product => {
                    if (product.card_id) {
                        createProductCard(product.card_id, product);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error loading existing products:', error);
    }
}

// Show import modal (placeholder)
function showImportModal() {
    alert('Import CSV feature will be implemented later');
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
    const productName = product?.name || 'Produk Baru';
    const category = product?.category || '-';
    const sellingPrice = product?.selling_price ?
        new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(product.selling_price) : '-';

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
        document.getElementById(cardId).remove();

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
    document.getElementById('productSellingPrice').value = product.selling_price || '';
    document.getElementById('productCostPrice').value = product.cost_price || '';
    document.getElementById('productUnit').value = product.unit || 'Pcs';
    document.getElementById('productDescription').value = product.description || '';

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
    document.getElementById('productInfoForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('cardId').value = currentCardId;
    currentProductId = null;
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
    document.getElementById('bomForm').reset();
}

// Render BOM table
function renderBomTable() {
    const tbody = document.getElementById('bomTableBody');
    const footer = document.getElementById('bomTotalFooter');
    const emptyMessage = document.getElementById('emptyBomMessage');

    // Clear existing rows except empty message
    tbody.querySelectorAll('tr:not(#emptyBomMessage)').forEach(row => row.remove());

    if (bomItems.length === 0) {
        emptyMessage.style.display = '';
        footer.style.display = 'none';
    } else {
        emptyMessage.style.display = 'none';
        footer.style.display = '';

        let totalCost = 0;

        bomItems.forEach((item, index) => {
            const itemTotal = item.quantity * item.cost_per_unit;
            totalCost += itemTotal;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-white">${item.material_name}</td>
                <td class="text-white">${item.quantity}</td>
                <td class="text-white">${item.unit}</td>
                <td class="text-white">Rp ${new Intl.NumberFormat('id-ID').format(item.cost_per_unit)}</td>
                <td class="fw-bold text-success">Rp ${new Intl.NumberFormat('id-ID').format(itemTotal)}</td>
                <td>
                    <button onclick="removeBomItem(${index})" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
        });

        // Update total
        document.getElementById('totalBomCost').textContent =
            'Rp ' + new Intl.NumberFormat('id-ID').format(totalCost);
    }
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
        const params = new URLSearchParams({
            page: options.page || 1,
            per_page: options.perPage || 10,
            search: options.search || document.getElementById('transactionSearch')?.value || '',
            status: options.status || document.getElementById('statusFilter')?.value || '',
            start_date: options.startDate || document.getElementById('dateStart')?.value || '',
            end_date: options.endDate || document.getElementById('dateEnd')?.value || '',
            sort_by: options.sortBy || 'transaction_date',
            sort_dir: options.sortDir || 'desc',
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
        if (!json.success) {
            showAlert('Gagal memuat data transaksi', 'danger');
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
    console.log('openSalesImportModal called'); // Debug log

    try {
        const modalElement = document.getElementById('salesImportModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Reset import form
            const form = document.getElementById('salesImportForm');
            if (form) form.reset();

            const previewSection = document.getElementById('importPreviewSection');
            if (previewSection) previewSection.style.display = 'none';

            const previewBtn = document.getElementById('previewBtn');
            const importBtn = document.getElementById('importBtn');

            if (previewBtn) previewBtn.style.display = 'inline-block';
            if (importBtn) importBtn.style.display = 'none';

            console.log('Import modal shown successfully');
        } else {
            console.error('Import modal element not found');
        }
    } catch (error) {
        console.error('Error opening import modal:', error);
        alert('Error opening import modal: ' + error.message);
    }
}

// Download sales template
function downloadSalesTemplate(format) {
    console.log('Downloading template:', format);
    window.open(`/api/sales-transactions/template?format=${format}`, '_blank');
}

// Preview import data
function previewImportData() {
    const fileInput = document.getElementById('salesImportFile');
    const file = fileInput?.files[0];

    if (!file) {
        showAlert('Pilih file untuk diupload terlebih dahulu', 'warning');
        return;
    }

    // Placeholder implementation
    showAlert('Preview data akan ditampilkan', 'info');

    // Show preview section
    const previewSection = document.getElementById('importPreviewSection');
    const previewBtn = document.getElementById('previewBtn');
    const importBtn = document.getElementById('importBtn');

    if (previewSection) previewSection.style.display = 'block';
    if (previewBtn) previewBtn.style.display = 'none';
    if (importBtn) importBtn.style.display = 'inline-block';

    // Sample preview content
    const previewContent = document.getElementById('importPreviewContent');
    if (previewContent) {
        previewContent.innerHTML = `
            <div class="mb-3">
                <span class="badge bg-info me-2">10 baris data</span>
                <span class="badge bg-success me-2">8 valid</span>
                <span class="badge bg-danger">2 invalid</span>
            </div>
            <div class="alert alert-info">
                Preview akan menampilkan contoh data yang akan diimport
            </div>
        `;
    }
}

// Process sales import
function processSalesImport() {
    const fileInput = document.getElementById('salesImportFile');
    const file = fileInput?.files[0];

    if (!file) {
        showAlert('Pilih file untuk diupload terlebih dahulu', 'warning');
        return;
    }

    const importBtn = document.getElementById('importBtn');
    if (importBtn) {
        const originalText = importBtn.innerHTML;
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';

        // Placeholder for API call
        setTimeout(() => {
            showAlert('Import data berhasil', 'success');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('salesImportModal'));
            if (modal) modal.hide();

            // Reset button state
            importBtn.disabled = false;
            importBtn.innerHTML = originalText;
        }, 2000);
    }
}

// Initialize sales functionality on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing sales functionality...');

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
    window.downloadSalesTemplate = downloadSalesTemplate;
    window.previewImportData = previewImportData;
    window.processSalesImport = processSalesImport;
    window.addNewCustomer = addNewCustomer;
    window.formatCurrency = formatCurrency; // Add formatCurrency to global scope
    window.loadRecentTransactions = loadRecentTransactions; // Add data loading function
    window.loadIncomeOverview = loadIncomeOverview; // Expose overview loader

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
