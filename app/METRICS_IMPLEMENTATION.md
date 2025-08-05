# Implementasi Form Khusus untuk Setiap Jenis Metrics

## Ringkasan Implementasi

Saya telah mengimplementasikan struktur form yang berbeda-beda untuk setiap jenis metrics sesuai dengan kebutuhan kalkulasi dan formula yang spesifik. Berikut adalah detail implementasinya:

## 1. Total Penjualan

### Struktur Form:
- **Total Pendapatan** (required): Input utama untuk revenue harian
- **Jumlah Transaksi** (optional): Untuk tracking volume transaksi
- **Formula Display**: Menampilkan formula `Total Penjualan = Σ (Penjualan dalam periode)`

### Data Storage:
- Disimpan di tabel `sales_data` dengan field:
  - `total_revenue`: Nilai pendapatan
  - `transaction_count`: Jumlah transaksi
  - `sales_date`: Tanggal data

### Kalkulasi:
- SUM(total_revenue) untuk periode yang dipilih
- Menjadi basis untuk perhitungan Revenue Growth dan Margin Keuntungan

## 2. Cost of Goods Sold (COGS)

### Struktur Form:
- **Total Biaya Pokok Penjualan** (required): Input COGS harian
- **Catatan Biaya** (optional): Detail komponen biaya produksi
- **Formula Display**: `COGS = Σ (Total Biaya Produksi dan Material dalam periode)`

### Data Storage:
- Disimpan di tabel `sales_data` dengan field:
  - `total_cogs`: Nilai COGS
  - `notes`: Catatan detail biaya

### Kalkulasi:
- SUM(total_cogs) untuk periode yang dipilih
- Komponen penting untuk Margin Keuntungan dan analisis profitabilitas

## 3. Margin Keuntungan

### Struktur Form:
- **Data Referensi**: Preview otomatis dari Total Penjualan dan COGS
- **Periode Kalkulasi**: Dropdown (Harian/Mingguan/Bulanan/Tahunan)
- **Target Margin** (optional): Target margin yang diinginkan
- **Kalkulasi Real-time**: Menampilkan margin berdasarkan data existing

### Data Storage:
- Tidak memerlukan input manual, dihitung otomatis
- Metadata disimpan untuk period dan target

### Kalkulasi:
- Formula: `((Pendapatan - COGS) / Pendapatan) × 100%`
- Menggunakan data dari tabel `sales_data`

## 4. Jumlah Pelanggan Baru

### Struktur Form:
- **Jumlah Pelanggan Baru** (required): Count pelanggan baru harian
- **Sumber Pelanggan** (optional): Dropdown sumber acquisition
- **Biaya Akuisisi per Pelanggan** (optional): CAC calculation

### Data Storage:
- Disimpan di tabel `sales_data`:
  - `new_customer_count`: Jumlah pelanggan baru
- Metadata untuk sumber dan biaya akuisisi

### Kalkulasi:
- SUM(new_customer_count) untuk periode
- Berkontribusi pada perhitungan Customer Loyalty

## 5. Jumlah Pelanggan Setia

### Struktur Form:
- **Total Pelanggan Bertransaksi** (required): Total customer count harian
- **Definisi Pelanggan Setia**: Dropdown kriteria loyalty
- **Member Program Loyalitas** (optional): Count member program
- **Frekuensi Pembelian Rata-rata** (optional): Purchase frequency
- **Kalkulasi Real-time**: Preview % pelanggan setia

### Data Storage:
- Disimpan di tabel `sales_data`:
  - `total_customer_count`: Total pelanggan
  - `new_customer_count`: Diambil dari input sebelumnya
- Metadata untuk definisi loyalty dan program data

### Kalkulasi:
- Formula: `((Total Pelanggan - Pelanggan Baru) / Total Pelanggan) × 100%`
- Real-time calculation dengan preview

## 6. Penjualan Produk Terlaris

### Struktur Form:
- **Nama Produk** (required): Product name
- **SKU/Kode Produk** (optional): Product identifier
- **Jumlah Terjual** (required): Quantity sold
- **Harga Satuan** (required): Unit price
- **Biaya per Unit** (optional): Cost calculation
- **Kategori Produk** (optional): Product categorization
- **Revenue Generated**: Auto-calculated preview

### Data Storage:
- Disimpan di tabel `product_sales`:
  - `product_name`, `product_sku`, `quantity_sold`
  - `unit_price`, `cost_per_unit`, `category`
  - `revenue_generated`: Auto-calculated (quantity × price)

### Kalkulasi:
- Revenue Generated = Quantity Sold × Unit Price
- Contribution to total sales percentage
- Product performance ranking

## Fitur Teknis Implementasi

### 1. Dynamic Form Loading
- Form yang berbeda dimuat berdasarkan jenis metric
- CSS classes untuk styling khusus
- JavaScript handlers untuk setiap form type

### 2. Real-time Calculations
- Preview values yang update secara real-time
- Validasi input dengan feedback visual
- Auto-calculation untuk computed metrics

### 3. Data Integration
- Automatic relationship between metrics
- Cross-metric data usage (contoh: Pelanggan Baru → Pelanggan Setia)
- Centralized data storage dengan referential integrity

### 4. API Endpoints
- `/dashboard/metrics/{id}/calculation-data`: Untuk data kalkulasi
- `/dashboard/business/{id}/daily-data`: Untuk data harian existing
- Enhanced store method dengan metric-specific validation

### 5. Enhanced UI/UX
- Formula display untuk transparency
- Preview sections untuk validation
- Contextual help dan tooltips
- Responsive design untuk semua form types

## Database Schema Updates

```sql
-- Sales Data table sudah di-update dengan field tambahan:
ALTER TABLE sales_data ADD COLUMN new_customer_count INT DEFAULT 0;
ALTER TABLE sales_data ADD COLUMN total_customer_count INT DEFAULT 0;

-- Product Sales table sudah ada dengan struktur lengkap
-- MetricRecord table menggunakan metadata JSON untuk data tambahan
```

## Cara Penggunaan

1. **Pilih metric** di halaman create metrics
2. **Buka edit records** untuk metric yang dipilih
3. **Klik "Add New Row"** untuk membuka modal form
4. **Form yang muncul** akan disesuaikan dengan jenis metric
5. **Isi data** sesuai dengan field yang tersedia
6. **Preview real-time** akan menampilkan hasil kalkulasi
7. **Submit** untuk menyimpan data ke tabel yang sesuai

## Benefits

1. **User Experience**: Form yang intuitive dan relevan untuk setiap metric
2. **Data Accuracy**: Validasi dan kalkulasi otomatis mengurangi error
3. **Business Intelligence**: Relationship antar metric memberikan insight komprehensif
4. **Scalability**: Mudah menambah metric baru dengan pattern yang sama
5. **Performance**: Efficient data storage dan retrieval

Implementasi ini memungkinkan tracking yang lebih akurat dan meaningful untuk setiap jenis business metric dengan struktur data yang terorganisir dan kalkulasi yang reliable.
