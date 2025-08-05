<!-- Jumlah Pelanggan Baru Form -->
<div id="pelanggan-baru-form" class="metric-specific-form" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="new_customer_count" class="form-label text-white">
                    <i class="fas fa-user-plus me-1"></i>Jumlah Pelanggan Baru <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-lg modal-input"
                       id="new_customer_count" name="new_customer_count" min="0" required>
                <div class="form-text text-light opacity-75">Jumlah pelanggan baru hari ini</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="customer_source" class="form-label text-white">
                    <i class="fas fa-users me-1"></i>Sumber Pelanggan <span class="text-muted">(Optional)</span>
                </label>
                <select class="form-select modal-input" id="customer_source" name="customer_source">
                    <option value="">Pilih sumber...</option>
                    <option value="online">Online (Website/E-commerce)</option>
                    <option value="social_media">Media Sosial</option>
                    <option value="referral">Referral/Word of Mouth</option>
                    <option value="advertising">Iklan</option>
                    <option value="walk_in">Walk-in</option>
                    <option value="other">Lainnya</option>
                </select>
                <div class="form-text text-light opacity-75">Dari mana pelanggan baru didapat</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="customer_acquisition_cost" class="form-label text-white">
                    <i class="fas fa-money-bill me-1"></i>Biaya Akuisisi per Pelanggan <span class="text-muted">(Optional)</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text modal-input-addon">Rp</span>
                    <input type="number" class="form-control modal-input"
                           id="customer_acquisition_cost" name="customer_acquisition_cost" step="0.01" min="0">
                </div>
                <div class="form-text text-light opacity-75">Rata-rata biaya untuk mendapat 1 pelanggan baru</div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>Jumlah Pelanggan Baru = Σ (Pelanggan Baru dalam Periode)</strong></p>
        <small class="text-white">Berkontribusi pada perhitungan Jumlah Pelanggan Setia</small>
    </div>
</div>
