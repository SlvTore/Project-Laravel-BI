<!-- Penjualan Produk Terlaris Form -->
<div id="produk-terlaris-form" class="metric-specific-form" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="product_name" class="form-label text-white">
                    <i class="fas fa-box me-1"></i>Nama Produk <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-lg modal-input"
                       id="product_name" name="product_name" required
                       placeholder="Contoh: Sepatu Running Nike Air Max">
                <div class="form-text text-light opacity-75">Nama lengkap produk</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="product_sku" class="form-label text-white">
                    <i class="fas fa-barcode me-1"></i>SKU/Kode Produk <span class="text-muted">(Optional)</span>
                </label>
                <input type="text" class="form-control form-control-lg modal-input"
                       id="product_sku" name="product_sku"
                       placeholder="Contoh: NKE-ARM-001">
                <div class="form-text text-light opacity-75">SKU atau kode internal produk</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="quantity_sold" class="form-label text-white">
                    <i class="fas fa-cubes me-1"></i>Jumlah Terjual <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-lg modal-input"
                       id="quantity_sold" name="quantity_sold" min="0" required>
                <div class="form-text text-light opacity-75">Unit yang terjual hari ini</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="unit_price" class="form-label text-white">
                    <i class="fas fa-tag me-1"></i>Harga Satuan <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text modal-input-addon">Rp</span>
                    <input type="number" class="form-control form-control-lg modal-input"
                           id="unit_price" name="unit_price" step="0.01" min="0" required>
                </div>
                <div class="form-text text-light opacity-75">Harga jual per unit</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="cost_per_unit" class="form-label text-white">
                    <i class="fas fa-calculator me-1"></i>Biaya per Unit <span class="text-muted">(Optional)</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text modal-input-addon">Rp</span>
                    <input type="number" class="form-control modal-input"
                           id="cost_per_unit" name="cost_per_unit" step="0.01" min="0">
                </div>
                <div class="form-text text-light opacity-75">Biaya produksi per unit</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="product_category" class="form-label text-white">
                    <i class="fas fa-tags me-1"></i>Kategori Produk <span class="text-muted">(Optional)</span>
                </label>
                <select class="form-select modal-input" id="product_category" name="product_category">
                    <option value="">Pilih kategori...</option>
                    <option value="electronics">Elektronik</option>
                    <option value="fashion">Fashion</option>
                    <option value="food_beverage">Makanan & Minuman</option>
                    <option value="health_beauty">Kesehatan & Kecantikan</option>
                    <option value="home_garden">Rumah & Taman</option>
                    <option value="sports">Olahraga</option>
                    <option value="books">Buku</option>
                    <option value="toys">Mainan</option>
                    <option value="automotive">Otomotif</option>
                    <option value="other">Lainnya</option>
                </select>
                <div class="form-text text-light opacity-75">Kategori untuk grouping produk</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="calculation-preview">
                <h6 class="text-white mb-3">Revenue Generated</h6>
                <div class="bg-dark p-3 rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Quantity × Price:</span>
                        <span class="text-success" id="calculated_revenue">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-white">Profit per Unit:</span>
                        <span class="text-info" id="calculated_profit_per_unit">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>Penjualan Produk X = (Penjualan Produk X / Total Penjualan Seluruh Produk) × 100%</strong></p>
        <p class="text-info mb-1"><strong>Revenue Generated = Quantity Sold × Unit Price</strong></p>
        <small class="text-white">Memberikan rincian detail dari metrik Total Penjualan</small>
    </div>
</div>
