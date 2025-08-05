# Traction Tracker - Laravel Business Intelligence Platform

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version">
    <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
    <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
    <img src="https://img.shields.io/badge/AI%20Powered-Gemini-purple.svg" alt="AI Powered">
</p>

## About Traction Tracker

Traction Tracker adalah platform Business Intelligence berbasis web yang dirancang untuk membantu bisnis melacak, menganalisis, dan mengoptimalkan performa mereka. Platform ini mengintegrasikan AI Assistant untuk memberikan insights dan rekomendasi strategis berdasarkan data bisnis real-time.

### ✨ Key Features

- **📊 Multi-Metric Tracking**: 6 jenis metrics utama dengan form dinamis
- **🤖 AI Business Assistant**: Integrasi Google Gemini untuk analysis mendalam
- **📈 Real-time Analytics**: Dashboard interaktif dengan visualisasi data
- **🎯 Smart Calculations**: Auto-computed metrics dengan cross-data relationships
- **👥 Multi-User Support**: Role-based access dengan collaboration features
- **📱 Responsive Design**: Akses optimal di semua device

---

## 🚀 Alur Penggunaan Traction Tracker

### 1. Registrasi & Setup Awal

**Langkah Pertama:**
- Akses halaman utama di `resources/views/landing/welcome.blade.php`
- Klik "Mulai Gratis" untuk registrasi
- Setelah registrasi, otomatis diarahkan ke wizard setup

**Setup Wizard (`resources/views/wizard.blade.php`):**

1. **Step 1: Pilih Role**
   - Business Owner, Manager, Mentor/Advisor, atau Data Investigator
   - Setiap role memiliki permission berbeda (lihat `database/migrations/2025_07_25_105943_create_roles_table.php`)

2. **Step 2: Informasi Bisnis**
   - Nama bisnis, industri, deskripsi
   - Website, pendapatan awal, jumlah pelanggan awal

3. **Step 3: Target & Goals**
   - Target revenue, customer, growth rate
   - Key metrics yang ingin dipantau

### 2. Dashboard Utama

**Akses Dashboard:**
- Route utama: `/dashboard` (`routes/web.php`)
- Menampilkan overview metrics dan quick actions

### 3. Mengelola Metrics

**Membuat Metrics Baru:**
1. Akses `dashboard/metrics/create`
2. Pilih dari 6 jenis metrics utama:
   - **Total Penjualan** (Revenue tracking)
   - **Cost of Goods Sold** (COGS)
   - **Margin Keuntungan** (Auto-calculated)
   - **Jumlah Pelanggan Baru**
   - **Jumlah Pelanggan Setia**
   - **Penjualan Produk Terlaris**

**Form Dinamis per Metric (`METRICS_IMPLEMENTATION.md`):**
- Setiap metric memiliki form khusus dengan field yang relevan
- Ada formula display dan real-time calculation
- Data disimpan di tabel yang sesuai dengan relasi antar metrics

### 4. Input Data Harian

**Proses Input Data:**
1. Buka metrics yang sudah dibuat
2. Klik "Add New Row" di halaman edit metrics
3. Form modal akan muncul sesuai jenis metric
4. Isi data sesuai periode (harian/mingguan/bulanan)
5. Preview real-time menampilkan kalkulasi otomatis

**Contoh Input Data:**
```php
// Total Penjualan
- Total Pendapatan: Rp 5,000,000
- Jumlah Transaksi: 50

// Pelanggan Baru  
- Jumlah Pelanggan Baru: 15
- Sumber: Social Media
- Biaya Akuisisi: Rp 50,000/pelanggan
```

---

## 🤖 Analisis Data dengan AI Assistant

### Akses AI Chat
- Tersedia di setiap halaman edit metrics (`resources/views/dashboard-metrics/edit.blade.php`)
- Fitur real-time chat dengan AI business assistant

### Cara Menggunakan AI

**Quick Actions (Tombol Cepat):**
- **📈 Analisis Tren**: "Analisis tren performa dalam 30 hari terakhir dan berikan rekomendasi"
- **💡 Strategi Peningkatan**: "Berikan 3 strategi untuk meningkatkan metrik ini berdasarkan data yang ada"
- **⚠️ Analisis Risiko**: "Identifikasi potensi risiko atau masalah dari pola data saat ini"
- **⚖️ Benchmark Industri**: "Bandingkan performa saat ini dengan rata-rata industri"

**Custom Questions:**
```javascript
// Contoh pertanyaan yang bisa diajukan:
"Bagaimana tren penjualan dalam 3 bulan terakhir?"
"Apa strategi untuk meningkatkan customer retention?"
"Analisis margin keuntungan dan berikan rekomendasi optimasi"
"Prediksi revenue bulan depan berdasarkan data historis"
```

---

## 🗃️ Data Flow & Integration

### Struktur Data (`METRICS_IMPLEMENTATION.md`)

```sql
-- Data utama disimpan di sales_data
sales_data:
- total_revenue (dari Total Penjualan)
- total_cogs (dari COGS input)
- new_customer_count (dari Pelanggan Baru)
- total_customer_count (untuk Pelanggan Setia)

-- Product data di product_sales
product_sales:
- product_name, quantity_sold, unit_price
- revenue_generated (auto-calculated)

-- Metric records untuk tracking
metric_records:
- business_metric_id, record_date, value
- metadata (JSON untuk data tambahan)
```

### Cross-Metric Calculations
- **Margin Keuntungan** = ((Revenue - COGS) / Revenue) × 100%
- **Pelanggan Setia** = ((Total - Baru) / Total) × 100%
- Semua kalkulasi real-time dan saling terintegrasi

### AI Context & Capabilities

**Data yang Tersedia untuk AI (`app/Http/Controllers/Dashboard/MetricRecordsController.php`):**

```php
// AI memiliki akses ke:
- Recent metric data (10 data terakhir)
- Statistical analysis (avg, trend, growth)
- Business context (industry, targets, goals)
- Cross-metric relationships
- Historical patterns
```

**AI Response Features:**
- Markdown formatting support
- Numbered lists dan bullet points
- Real-time typing indicator
- Error handling untuk rate limits
- Export chat history

---

## 📋 Best Practices Penggunaan

### Input Data Konsisten
1. Input data secara rutin (harian/mingguan)
2. Pastikan data akurat untuk analisis yang tepat
3. Gunakan notes field untuk context tambahan

### Interaksi dengan AI
1. Mulai dengan pertanyaan spesifik tentang metric tertentu
2. Gunakan quick actions untuk analisis standar
3. Ajukan follow-up questions berdasarkan response AI
4. Manfaatkan export chat untuk dokumentasi insights

### Monitoring & Review
1. Review trends secara berkala
2. Bandingkan actual vs target
3. Gunakan AI recommendations untuk action items
4. Track improvement berdasarkan implementasi saran AI

---

## 🔧 Installation & Setup

### Requirements
- PHP 8.2+
- Laravel 11.x
- MySQL 8.0+
- Node.js 18+
- Composer
- Google Gemini API Key

### Installation Steps

1. **Clone Repository**
```bash
git clone https://github.com/SlvTore/Project-Laravel-BI.git
cd Project-Laravel-BI/app
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Configuration**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_bi
DB_USERNAME=root
DB_PASSWORD=

# AI Configuration
GEMINI_API_KEY=your_gemini_api_key_here
```

5. **Run Migrations**
```bash
php artisan migrate --seed
```

6. **Build Assets**
```bash
npm run build
# or for development
npm run dev
```

7. **Start Server**
```bash
php artisan serve
```

---

## 🚀 Advanced Features

### Multi-User Collaboration
- Role-based permissions
- Team access dengan different privilege levels
- Shared insights dan discussions

### Export & Reporting
- Export data ke PDF/Excel
- Scheduled reports
- Custom dashboard views

### Mobile Access
- Responsive design untuk semua device
- Mobile app features (planned)

---

## 📁 Project Structure

```
app/
├── app/
│   ├── Http/Controllers/Dashboard/    # Controllers untuk dashboard
│   ├── Models/                        # Eloquent models
│   ├── Services/                      # Service classes (GeminiAI)
│   └── ...
├── database/
│   ├── migrations/                    # Database schema
│   └── seeders/                       # Data seeders
├── resources/
│   ├── views/dashboard-metrics/       # Metric management views
│   └── js/                           # Frontend assets
└── routes/
    └── web.php                       # Application routes
```

---

## 🤝 Contributing

Kontribusi sangat diterima! Silakan:

1. Fork repository ini
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📄 License

Project ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).

---

## 📞 Support

Untuk bantuan dan pertanyaan:
- 📧 Email: support@tractiontracker.com
- 💬 Discord: [Join our community](https://discord.gg/tractiontracker)
- 📚 Documentation: [docs.tractiontracker.com](https://docs.tractiontracker.com)

---

*Aplikasi ini dirancang untuk memberikan business intelligence yang comprehensive dengan AI assistance untuk decision making yang lebih baik. Data flow yang terintegrasi memungkinkan analisis mendalam dan insights actionable untuk pertumbuhan bisnis.*
