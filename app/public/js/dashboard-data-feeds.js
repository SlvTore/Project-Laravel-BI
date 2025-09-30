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
const feedProgressPolls = new Map();

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

        const resultData = payload?.data || {};
        const newFeedId = resultData.data_feed_id || resultData.id || null;
        const initialStage = (resultData.status || 'queued').toLowerCase();

        showAlert(payload.message || 'Data feed berhasil diantrikan untuk diproses.', 'success');

    // Refresh dashboard data to reflect the newly imported sales
    loadRecentTransactions();
    loadIncomeOverview();
    loadExistingProducts(); // Refresh product cards to show new products
    if (typeof refreshUploadsList === 'function') { refreshUploadsList(); }

        if (newFeedId) {
            startFeedPolling(newFeedId, initialStage);
        }

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

    const activeFeedKeys = new Set(feeds.map(feed => feedKey(feed.id)));
    for (const key of Array.from(feedProgressPolls.keys())) {
        if (!activeFeedKeys.has(key)) {
            stopFeedPolling(key);
        }
    }

    feeds.forEach(feed => {
        const tr = document.createElement('tr');
        const statusBadge = mapFeedStatusBadge(feed.status, feed.summary);
        const logContent = formatFeedLog(feed);
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
            <td class="text-white-50 small" style="max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${stripHtml(logContent)}">${logContent}</td>
            <td class="text-end">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-danger" onclick="deleteUpload(${feed.id})" ${feed.status === 'processing' ? 'disabled' : ''}>
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);

        if (window.currentUploadFilterId === feed.id) {
            highlightSelectedUpload(tr);
        }

        manageFeedPollingForFeed(feed);
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

function mapFeedStatusBadge(status, summary = null) {
    const stage = summary?.stage || status;
    const map = {
        processing: 'secondary',
        queued: 'info',
        transforming: 'warning',
        transformed: 'success',
        completed: 'success',
        failed: 'danger',
    };
    const labels = {
        processing: 'Menyalin',
        queued: 'Antri',
        transforming: 'Memproses',
        transformed: 'Selesai',
        completed: 'Selesai',
        failed: 'Gagal',
    };
    const color = map[stage] || 'secondary';
    const label = labels[stage] || ((stage || '').charAt(0).toUpperCase() + (stage || '').slice(1));
    return `<span class="badge bg-${color}">${label}</span>`;
}

function formatFeedLog(feed) {
    const summary = feed?.summary || {};
    const fragments = [];
    const baseLog = feed?.log_message ? escapeHtml(feed.log_message) : '';

    if (summary.error) {
        const errorMessage = typeof summary.error === 'object'
            ? summary.error.message || summary.error.code || baseLog
            : summary.error;
        if (errorMessage) {
            fragments.push(`<span class="text-danger">Error: ${escapeHtml(String(errorMessage))}</span>`);
        }
    }

    const issues = Array.isArray(summary.issues) ? summary.issues.slice(0, 2) : [];
    if (issues.length) {
        const issueTexts = issues.map((issue) => {
            if (!issue) return null;
            if (typeof issue === 'string') return escapeHtml(issue);
            if (typeof issue === 'object') {
                const title = issue.title || issue.message || issue.code || '';
                const detail = issue.detail || issue.description || issue.hint || '';
                const text = [title, detail].filter(Boolean).join(': ');
                return text ? escapeHtml(text) : null;
            }
            return null;
        }).filter(Boolean);

        if (issueTexts.length) {
            fragments.push(`<span class="text-warning">Isu: ${issueTexts.join(', ')}</span>`);
        }
    }

    if (summary.metrics && typeof summary.metrics === 'object') {
        const metrics = summary.metrics;
        const metricsParts = [];
        if (metrics.records != null) metricsParts.push(`${metrics.records} baris`);
        if (metrics.gross_revenue != null) metricsParts.push(`Omzet Rp${formatCurrency(metrics.gross_revenue)}`);
        if (metrics.cogs_amount != null) metricsParts.push(`HPP Rp${formatCurrency(metrics.cogs_amount)}`);
        if (metrics.gross_margin_amount != null) metricsParts.push(`Margin Rp${formatCurrency(metrics.gross_margin_amount)}`);
        if (metrics.gross_margin_percent != null) {
            const pct = Number(metrics.gross_margin_percent) || 0;
            metricsParts.push(`Margin ${pct.toFixed(1)}%`);
        }

        if (metricsParts.length) {
            fragments.push(metricsParts.join(' · '));
        }
    }

    if (baseLog) {
        fragments.push(baseLog);
    }

    if (!fragments.length) {
        return '-';
    }

    return fragments.join('<br>');
}

function feedKey(feedId) {
    return String(feedId);
}

function getFeedStage(feed) {
    return ((feed?.summary?.stage || feed?.status || '') + '').toLowerCase();
}

function shouldPollStage(stage) {
    return ['processing', 'queued', 'transforming'].includes((stage || '').toLowerCase());
}

function manageFeedPollingForFeed(feed) {
    const stage = getFeedStage(feed);
    if (shouldPollStage(stage)) {
        startFeedPolling(feed.id, stage);
    } else {
        stopFeedPolling(feed.id);
    }
}

function startFeedPolling(feedId, initialStage = null) {
    if (!feedId) return;
    const key = feedKey(feedId);
    const existing = feedProgressPolls.get(key);
    if (existing) {
        existing.lastStage = initialStage || existing.lastStage;
        if (!existing.timeoutId) {
            pollFeedStatus(feedId, existing);
        }
        return;
    }

    const state = {
        timeoutId: null,
        lastStage: initialStage || null,
    };

    feedProgressPolls.set(key, state);
    pollFeedStatus(feedId, state);
}

async function pollFeedStatus(feedId, state) {
    if (!state) return;

    try {
        const res = await fetch(`/dashboard/data-feeds/${feedId}/transform-status`, {
            headers: { 'Accept': 'application/json' }
        });
        const payload = await res.json().catch(() => ({}));

        if (!res.ok || payload.success === false) {
            throw new Error(payload.message || `Gagal memuat status data feed #${feedId}`);
        }

        const summary = payload.summary || {};
        const status = ((summary.stage || payload.status || '') + '').toLowerCase();

        if (status && status !== state.lastStage) {
            state.lastStage = status;
            if (shouldPollStage(status)) {
                refreshUploadsList();
            }
        }

        if (!status || shouldPollStage(status)) {
            state.timeoutId = window.setTimeout(() => pollFeedStatus(feedId, state), 5000);
            return;
        }

        stopFeedPolling(feedId);
        handleFeedTerminalStatus(feedId, status, payload);
    } catch (error) {
        console.error(`Feed status polling error for ${feedId}:`, error);
        state.timeoutId = window.setTimeout(() => pollFeedStatus(feedId, state), 7000);
    }
}

function stopFeedPolling(feedIdOrKey) {
    const key = typeof feedIdOrKey === 'string' ? feedIdOrKey : feedKey(feedIdOrKey);
    const state = feedProgressPolls.get(key);
    if (state?.timeoutId) {
        clearTimeout(state.timeoutId);
    }
    feedProgressPolls.delete(key);
}

function handleFeedTerminalStatus(feedId, stage, payload) {
    refreshUploadsList();

    if (['transformed', 'completed'].includes(stage)) {
        showAlert(`Data feed #${feedId} berhasil diproses ke warehouse.`, 'success');
        if (typeof loadRecentTransactions === 'function') {
            loadRecentTransactions();
        }
        if (typeof loadIncomeOverview === 'function') {
            loadIncomeOverview();
        }
        if (typeof loadExistingProducts === 'function') {
            loadExistingProducts();
        }
        if (typeof window.loadAllMetricWidgets === 'function') {
            window.loadAllMetricWidgets();
        }
    } else if (stage === 'failed') {
        const summary = payload.summary || {};
        const errorMessage = summary.error?.message || payload.message || 'Proses ETL gagal.';
        showAlert(`Data feed #${feedId} gagal diproses: ${errorMessage}`, 'danger');
    }
}

function formatDateTime(dt) {
    if (!dt) return '-';
    try { return new Date(dt).toLocaleString('id-ID'); } catch { return dt; }
}

function escapeHtml(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>'"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;' }[c]));
}

function stripHtml(html) {
    if (!html) return '';
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
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
        stopFeedPolling(id);
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

    // Setup clean warehouse modal confirmation text validation
    const cleanWarehouseConfirmText = document.getElementById('cleanWarehouseConfirmText');
    const confirmCleanWarehouseBtn = document.getElementById('confirmCleanWarehouseBtn');

    if (cleanWarehouseConfirmText && confirmCleanWarehouseBtn) {
        cleanWarehouseConfirmText.addEventListener('input', function() {
            const requiredText = 'HAPUS SEMUA DATA';
            confirmCleanWarehouseBtn.disabled = this.value.trim() !== requiredText;
        });
    }
});

/**
 * Show clean warehouse confirmation modal
 */
function showCleanWarehouseModal() {
    const modal = new bootstrap.Modal(document.getElementById('cleanWarehouseModal'));

    // Reset form
    document.getElementById('cleanWarehouseConfirmText').value = '';
    document.getElementById('confirmCleanWarehouseBtn').disabled = true;
    document.getElementById('cleanWarehouseStatus').classList.add('d-none');

    modal.show();
}

/**
 * Execute warehouse cleanup
 */
async function executeCleanWarehouse() {
    const statusDiv = document.getElementById('cleanWarehouseStatus');
    const confirmBtn = document.getElementById('confirmCleanWarehouseBtn');

    try {
        // Show loading state
        statusDiv.classList.remove('d-none');
        confirmBtn.disabled = true;

        const response = await fetch('/dashboard/data-feeds/clean-warehouse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                business_id: 1 // Default business ID, you can get this from user context
            })
        });

        const result = await response.json();

        if (result.success) {
            // Success
            statusDiv.innerHTML = `
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    ${result.message}
                </div>
            `;

            // Auto close modal after 2 seconds and refresh page
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('cleanWarehouseModal')).hide();
                location.reload(); // Refresh to show updated data
            }, 2000);

        } else {
            // Error
            statusDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${result.message}
                </div>
            `;
            confirmBtn.disabled = false;
        }

    } catch (error) {
        console.error('Clean warehouse error:', error);
        statusDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Terjadi kesalahan saat membersihkan data warehouse.
            </div>
        `;
        confirmBtn.disabled = false;
    }
}
