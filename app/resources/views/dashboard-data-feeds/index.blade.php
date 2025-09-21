@extends('layouts.dashboard')

@section('content')
<div class="container-fluid ms-4" id="data-feeds-content">
    <!-- Clean Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 fw-bold text-white mb-0">Data Feeds Management</h1>
                    <p class="text-white">Kelola data produk dengan interface yang bersih</p>
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
    <div class="row">
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
                                    <label class="form-label fw-semibold text-white">Nama Produk</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productName" name="name" placeholder="Masukkan nama produk" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-white">Kategori</label>
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
                                    <label class="form-label fw-semibold text-white">Harga Jual (Rp)</label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productSellingPrice" name="selling_price" placeholder="0" min="0" step="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold text-white">Harga Pokok (Rp)</label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productCostPrice" name="cost_price" placeholder="0" min="0" step="0.01">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold text-white">Unit</label>
                                    <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="productUnit" name="unit" required>
                                        <option value="Pcs">Pcs</option>
                                        <option value="Kg">Kg</option>
                                        <option value="Liter">Liter</option>
                                        <option value="Meter">Meter</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-white">Deskripsi</label>
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
                                            <input type="text" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomMaterialName" name="material_name" placeholder="Nama Bahan" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomQuantity" name="quantity" placeholder="Qty" required min="0">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <select class="form-select bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomUnit" name="unit" required>
                                                <option value="kg">Kg</option>
                                                <option value="gram">Gram</option>
                                                <option value="liter">Liter</option>
                                                <option value="ml">ML</option>
                                                <option value="pcs">Pcs</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" style="border-color: rgba(255,255,255,0.2) !important;" id="bomCostPerUnit" name="cost_per_unit" placeholder="Harga/Unit" required min="0">
                                        </div>
                                        <div class="col-md-2 mb-2">
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
});

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
        });

        modal.addEventListener('hidden.bs.modal', function () {
            resetForms();
        });
    }
}

// Show import modal (placeholder)
function showImportModal() {
    alert('Import CSV feature will be implemented later');
}

// Add new product card
function addProductCard() {
    const cardId = 'product-card-' + Date.now();
    createProductCard(cardId);
}

// Create product card HTML
function createProductCard(cardId) {
    const container = document.getElementById('productCardsContainer');

    const cardHTML = `
        <div class="col-md-6 col-lg-4 mb-4" id="${cardId}">
            <div class="card card-liquid-glass">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title editable-title" onclick="makeEditable(this, '${cardId}')">
                            Produk Baru
                        </h5>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeCard('${cardId}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="card-info">
                        <p class="card-text text-muted mb-2">
                            <small>Kategori: <span class="category-text">-</span></small>
                        </p>
                        <p class="card-text text-muted mb-2">
                            <small>Harga: <span class="price-text">-</span></small>
                        </p>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="openManageDataModal('${cardId}')">
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
</script>
@endsection
