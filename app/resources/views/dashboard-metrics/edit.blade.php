@extends('layouts.dashboard')

@section('title', 'Edit Records - ' . $businessMetric->metric_name)

@section('content')
<div class="container-fluid ms-4 p-5" >
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">{{ $businessMetric->metric_name }} - Complete Records Management</h2>
            <p class="text-white mb-0">{{ $businessMetric->business->business_name ?? 'Business' }} - {{ $businessMetric->description }}</p>
        </div>
        <div >
            <a href="{{ route('dashboard.metrics') }}"
               class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 stats-row">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Total Records</div>
                <div class="stats-value" id="totalRecords">{{ $statistics['total_records'] ?? 0 }}</div>
                <div class="stats-change">Data points collected</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="card-title">Average Value</div>
                <div class="stats-value" id="avgValue">{{ number_format($statistics['avg_value'] ?? 0) }}</div>
                <div class="stats-change">Overall average</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-area"></i>
                </div>
                <div class="card-title">Growth Rate</div>
                <div class="stats-value {{ ($statistics['growth_rate'] ?? 0) >= 0 ? 'growth-positive' : 'growth-negative' }}" id="growthRate">
                    {{ number_format($statistics['growth_rate'] ?? 0, 1) }}%
                </div>
                <div class="stats-change">Month over month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-title">Last Update</div>
                <div class="stats-value" style="font-size: 1.2rem;" id="lastUpdate">
                    {{ ($statistics['last_update'] ?? null) ? \Carbon\Carbon::parse($statistics['last_update'])->format('d M Y') : 'N/A' }}
                </div>
                <div class="stats-change">Most recent entry</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4 charts-row">
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-white">Trend Analysis</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-light btn-sm chart-period" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-outline-light btn-sm chart-period active" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-outline-light btn-sm chart-period" data-period="90">90 Days</button>
                    </div>
                </div>
                <div id="trendChart" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="mb-3 text-white">Performance Summary</h5>
                <div id="summaryChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Warehouse Data Section -->
    @include('dashboard-metrics.partials.warehouse-data-section')

    <!-- Data Records section removed as per request -->

    <!-- Sidebar Information -->
    <div class="row mt-4">
        <div class="col-md-6">
            <!-- Metric Info Card -->
            <div class="content-card mb-4 p-3">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3 text-white">Metric Information</h6>

                    <div class="d-flex align-items-center mb-3">
                        <div class="metric-icon me-3 text-white">
                            <i class="bi {{ $businessMetric->icon ?? 'bi-graph-up' }}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white">{{ $businessMetric->metric_name }}</h6>
                            <small class="text-white">{{ $businessMetric->category ?? 'General' }} Metric</small>
                        </div>
                    </div>

                    <div class="metric-stats text-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Current Value:</span>
                            <span class="fw-bold text-white">{{ number_format($businessMetric->current_value ?? 0, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Previous Value:</span>
                            <span class="fw-bold text-white">{{ number_format($businessMetric->previous_value ?? 0, 0) }}</span>
                        </div>
                        @php $growth = $statistics['growth_rate'] ?? ($businessMetric->change_percentage ?? 0); @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Change:</span>
                            <span class="fw-bold {{ ($growth ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-arrow-{{ ($growth ?? 0) >= 0 ? 'up' : 'down' }}-right me-1"></i>
                                {{ number_format(($growth ?? 0),1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Activity Timeline -->
            <div class="content-card">
                <div class="card-body p-3">
                    <h6 class="card-title fw-bold mb-3 text-white">Recent Activity</h6>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <small class="text-white">{{ $businessMetric->updated_at->diffForHumans() }}</small>
                                <p class="mb-1 text-white">Metric was last updated</p>
                                <small class="text-white">Value: {{ number_format($businessMetric->current_value ?? 0, 0) }}</small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <small class="text-white">{{ $businessMetric->created_at->diffForHumans() }}</small>
                                <p class="mb-1 text-white">Metric was created</p>
                                <small class="text-white">Added to dashboard</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Record Modal removed per request -->

    <!-- AI Business Assistant Card -->
    <div class="datatable-container mt-2 ms-5"
         data-metric-id="{{ $businessMetric->id }}"
         data-metric-name="{{ $businessMetric->metric_name }}"
         data-business-name="{{ $businessMetric->business->business_name ?? 'Business' }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-white">
                <i class="fas fa-robot me-2"></i>AI Business Assistant
            </h5>
            <div class="ai-status">
                <span class="badge bg-success" id="aiStatus">
                    <i class="fas fa-circle me-1"></i>Online
                </span>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="ai-chat-container">
            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="message ai-message">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <strong>AI Assistant</strong>
                            <small class="text-muted">{{ now()->format('H:i') }}</small>
                        </div>
                        <div class="message-text">
                            Halo! Saya adalah asisten AI untuk analisis bisnis Anda. Saya dapat membantu menganalisis data {{ $businessMetric->metric_name }} dan memberikan insights untuk pengambilan keputusan.
                            <br>
                            Beberapa contoh pertanyaan yang bisa Anda ajukan:
                            <ul class="mt-1 mb-0">
                                <li>Bagaimana tren performa metric ini dalam 30 hari terakhir?</li>
                                <li>Apa rekomendasi untuk meningkatkan {{ strtolower($businessMetric->metric_name) }}?</li>
                                <li>Analisis pola data dan berikan strategi bisnis</li>
                                <li>Identifikasi potensi risiko dari data saat ini</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="quick-actions mb-3">
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Analisis tren performa dalam 30 hari terakhir dan berikan rekomendasi">
                    <i class="fas fa-chart-line me-1"></i>Analisis Tren
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Berikan 3 strategi untuk meningkatkan metrik ini berdasarkan data yang ada">
                    <i class="fas fa-lightbulb me-1"></i>Strategi Peningkatan
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Identifikasi potensi risiko atau masalah dari pola data saat ini">
                    <i class="fas fa-exclamation-triangle me-1"></i>Analisis Risiko
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Bandingkan performa saat ini dengan rata-rata industri dan berikan benchmark">
                    <i class="fas fa-balance-scale me-1"></i>Benchmark Industri
                </button>
            </div>

            <!-- Chat Input -->
            <div class="chat-input-container">
                <form id="aiChatForm" class="d-flex gap-2">
                    <div class="flex-grow-1">
                        <textarea class="form-control chat-input" id="aiQuestion"
                                  placeholder="Tanyakan tentang data metrics, tren, atau minta saran strategi bisnis..."
                                  rows="2" maxlength="500"></textarea>
                        <div class="chat-input-footer">
                            <small class="text-white">
                                <span id="charCount">0</span>/500 karakter
                            </small>
                            <small class="text-white">
                                Press Ctrl+Enter to send
                            </small>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button type="submit" class="btn btn-primary chat-send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearChatBtn" title="Clear Chat">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .stats-card {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .stats-card .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .stats-card .stats-value {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
        transition: all 0.3s ease;
    }

    .stats-card .stats-value.updated {
        transform: scale(1.1);
        color: #28a745;
        text-shadow: 0 0 15px rgba(40, 167, 69, 0.6);
        animation: statsUpdate 1.5s ease-in-out;
    }

    @keyframes statsUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: #28a745; text-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
        100% { transform: scale(1); }
    }

    .stats-card .stats-change {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .chart-container {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        position: relative;
        z-index: 1;
    }

    /* Prevent section overlap and ensure stacking context */
    .stats-row, .charts-row {
        position: relative;
        z-index: 0;
    }
    .charts-row { margin-top: 0.5rem; }

    .datatable-container {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .btn-excel {
        background: linear-gradient(45deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 600;
    }

    .btn-excel:hover {
        background: linear-gradient(45deg, #20c997, #28a745);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .table-responsive { border-radius: 10px; overflow: hidden; }

    .growth-positive {
        color: #28a745;
    }

    .growth-negative {
        color: #dc3545;
    }

    .metric-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        opacity: 0.8;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(255, 255, 255, 0.2);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-marker {
        position: absolute;
        left: -25px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .timeline-content {
        background: rgba(255, 255, 255, 0.05);
        padding: 12px;
        border-radius: 8px;
        border-left: 3px solid var(--primary-color);
    }

    .metric-stats {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .content-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        backdrop-filter: blur(20px);
    }

    /* Inline editing styles */
    .editable-cell {
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
        min-height: 20px;
        display: inline-block;
        width: 100%;
    }

    .editable-cell:hover {
        background-color: rgba(102, 126, 234, 0.1);
        border: 1px dashed rgba(102, 126, 234, 0.3);
    }

    .editable-cell.editing {
        background-color: rgba(40, 167, 69, 0.1);
        border: 2px solid #28a745;
    }

    .inline-edit-input {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        color: inherit !important;
        padding: 2px 4px !important;
        width: 100% !important;
        font-size: inherit !important;
    }

    .inline-edit-input:focus {
        background: rgba(255, 255, 255, 0.9) !important;
        color: #333 !important;
        outline: none !important;
    }

    .new-record {
        background-color: rgba(40, 167, 69, 0.05) !important;
        border-left: 4px solid #28a745 !important;
    }

    .new-record td {
        animation: highlightNew 2s ease-out;
    }

    @keyframes highlightNew {
        0% { background-color: rgba(40, 167, 69, 0.3); }
        100% { background-color: transparent; }
    }

    /* Records table styles removed */

    /* Tooltip for editable cells */
    .editable-cell::before {
        content: "Click to edit";
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 1000;
    }

    .editable-cell:hover::before {
        opacity: 1;
    }

    /* Modal Styling */
    .modal-glass {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 15px !important;
    }

    .modal-glass .modal-header {
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        border-radius: 15px 15px 0 0 !important;
        padding: 20px 25px;
    }

    .modal-glass .modal-body {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 0;
    }

    .modal-glass .modal-footer {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0 0 15px 15px !important;
        padding: 20px 25px;
    }

    .modal-input {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 10px !important;
        color: white !important;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .modal-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: #7cb947 !important;
        box-shadow: 0 0 0 0.2rem rgba(124, 185, 103,
        color: white !important;
    }

    .modal-input::placeholder {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .modal-input-addon {
        background: rgba(102, 126, 234, 0.3) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border-radius: 10px !important;
    }

    .btn-excel-gradient {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border: none !important;
        border-radius: 10px !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 10px 20px !important;
        transition: all 0.3s ease !important;
    }

    .btn-excel-gradient:hover {
        background: linear-gradient(45deg, #20c997, #28a745) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4) !important;
        color: white !important;
    }

    .preview-section {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 10px;
        backdrop-filter: blur(10px);
    }

    .preview-item {
        text-align: center;
        padding: 10px;
    }

    .preview-value {
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 5px;
        min-height: 24px;
    }

    /* Custom scrollbar for modal */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.6);
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.8);
    }

    /* Form validation styling */
    .was-validated .form-control:invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .was-validated .form-control:valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    /* Loading state for form */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }

    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: button-loading-spinner 1s ease infinite;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }

    @keyframes button-loading-spinner {
        from {
            transform: rotate(0turn);
        }
        to {
            transform: rotate(1turn);
        }
    }

    /* Metric-specific form styling */
    .metric-specific-form {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }

    .formula-display {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        backdrop-filter: blur(5px);
    }

    .calculation-preview {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 15px;
    }

    .calculation-preview .bg-dark {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Dynamic form visibility */
    .metric-specific-form.active {
        display: block !important;
        animation: fadeInUp 0.3s ease-out;
    }

    /* Statistics value update animation */
    .stats-value-update {
        animation: statValuePulse 0.8s ease-in-out;
        color: #4ade80 !important;
    }

    @keyframes statValuePulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
            text-shadow: 0 0 20px rgba(74, 222, 128, 0.6);
        }
    }

    /* Warehouse data enhanced styling */
    .warehouse-section .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .warehouse-section .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.15) !important;
        cursor: pointer;
    }

    /* Ensure chart inner spinners don’t overflow layout */
    #trendChart, #summaryChart { position: relative; overflow: hidden; }


    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Auto-calculation fields */
    .auto-calculate {
        background: rgba(40, 167, 69, 0.1) !important;
        border-color: rgba(40, 167, 69, 0.3) !important;
    }

    .auto-calculate:focus {
        background: rgba(40, 167, 69, 0.15) !important;
        border-color: rgba(40, 167, 69, 0.5) !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    /* AI Chat Styles - Modern Chat Platform Design */
    .ai-chat-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        min-height: 600px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .ai-chat-container .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-height: 450px;
        background: linear-gradient(180deg, rgba(30, 30, 50, 0.3) 0%, rgba(20, 20, 40, 0.5) 100%);
        scroll-behavior: smooth;
    }

    .ai-chat-container .message {
        display: flex;
        gap: 12px;
        animation: messageSlideIn 0.3s ease-out, bubblePop 0.28s ease-out;
        max-width: 85%;
        align-items: flex-start;
    }

    .ai-chat-container .ai-message {
        align-self: flex-start;
    }

    .ai-chat-container .user-message {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .ai-chat-container .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
    }

    .ai-chat-container .ai-message .message-avatar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .ai-chat-container .user-message .message-avatar {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .ai-chat-container .message-content {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 18px 18px 18px 4px;
        padding: 14px 18px;
        max-width: 100%;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    /* Bubble tails */
    .ai-chat-container .ai-message .message-content::after {
        content: '';
        position: absolute;
        left: -8px;
        top: 14px;
        width: 0; height: 0;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
        border-right: 8px solid rgba(255, 255, 255, 0.1);
        filter: drop-shadow(0 0 2px rgba(0,0,0,0.15));
    }

    .ai-chat-container .user-message .message-content {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.25) 0%, rgba(118, 75, 162, 0.25) 100%);
        border-color: rgba(102, 126, 234, 0.3);
        border-radius: 18px 18px 4px 18px;
    }

    .ai-chat-container .user-message .message-content::after {
        content: '';
        position: absolute;
        right: -8px;
        top: 14px;
        width: 0; height: 0;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
        border-left: 8px solid rgba(102, 126, 234, 0.25);
        filter: drop-shadow(0 0 2px rgba(0,0,0,0.15));
    }

    .ai-chat-container .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .ai-chat-container .message-header strong {
        color: rgba(255, 255, 255, 0.95);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .ai-chat-container .message-header small {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.75rem;
    }

    .ai-chat-container .message-text {
        color: rgba(255, 255, 255, 0.95) !important;
        line-height: 1.7;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 0.95rem;
    }

    .ai-chat-container .message-text p {
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.95) !important;
    }

    .ai-chat-container .message-text p:last-child {
        margin-bottom: 0;
    }

    .ai-chat-container .message-text ul {
        margin: 10px 0;
        padding-left: 24px;
        color: rgba(255, 255, 255, 0.95) !important;
    }

    .ai-chat-container .message-text li {
        margin-bottom: 6px;
        color: rgba(255, 255, 255, 0.95) !important;
    }

    .ai-chat-container .message-text strong {
        color: rgba(255, 255, 255, 1) !important;
        font-weight: 600;
    }

    .ai-chat-container .message-text code {
        background: rgba(0, 0, 0, 0.4);
        padding: 2px 8px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        color: #7cb947;
    }

    .ai-chat-container .quick-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 16px 24px;
        background: rgba(255, 255, 255, 0.03);
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .ai-chat-container .quick-question {
        background: rgba(102, 126, 234, 0.15);
        border: 1px solid rgba(102, 126, 234, 0.3);
        color: rgba(255, 255, 255, 0.95);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    .ai-chat-container .quick-question:hover {
        background: rgba(102, 126, 234, 0.35);
        border-color: rgba(102, 126, 234, 0.6);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .ai-chat-container .chat-input-container {
        padding: 20px 24px;
        background: rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .ai-chat-container .chat-input {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 25px !important;
        color: white !important;
        resize: none;
        backdrop-filter: blur(10px);
        padding: 14px 20px !important;
        font-size: 0.95rem;
        line-height: 1.5;
        transition: all 0.2s ease;
    }

    .ai-chat-container .chat-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: rgba(102, 126, 234, 0.6) !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
        color: white !important;
        outline: none;
    }

    .ai-chat-container .chat-input::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .ai-chat-container .chat-input-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        padding: 0 8px;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.4);
    }

    .ai-chat-container .chat-send-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
        font-size: 20px;
    }

    .ai-chat-container .chat-send-btn:hover:not(:disabled) {
        transform: scale(1.1) rotate(15deg);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
    }

    .ai-chat-container .chat-send-btn:disabled {
        opacity: 0.5;
        transform: none;
        cursor: not-allowed;
    }

    .ai-chat-container .ai-status .badge {
        animation: pulse 2s infinite;
        font-size: 0.85rem;
        padding: 6px 12px;
    }

    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes bubblePop {
        0% { transform: scale(0.98); }
        100% { transform: scale(1); }
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }

    .ai-chat-container .typing-indicator {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 18px 18px 18px 4px;
        max-width: 140px;
        align-self: flex-start;
        opacity: 0;
        animation: typingFadeIn 0.25s ease-out forwards;
    }

    .ai-chat-container .typing-dots {
        display: flex;
        gap: 4px;
    }

    .ai-chat-container .typing-dots span {
        width: 8px;
        height: 8px;
        background: rgba(102, 126, 234, 0.9);
        border-radius: 50%;
        animation: typingAnimation 1.4s infinite ease-in-out;
    }

    .ai-chat-container .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .ai-chat-container .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
    .ai-chat-container .typing-dots span:nth-child(3) { animation-delay: 0s; }

    @keyframes typingAnimation {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
        30% { transform: translateY(-10px); opacity: 1; }
    }

    @keyframes typingFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Scrollbar Styling */
    .ai-chat-container .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .ai-chat-container .chat-messages::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
    }

    .ai-chat-container .chat-messages::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.5);
        border-radius: 10px;
    }

    .ai-chat-container .chat-messages::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.7);
    }

    /* Warehouse Data Styling */
    .warehouse-data-content {
        animation: fadeInUp 0.6s ease-out;
    }

    .warehouse-row:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: translateX(5px);
        transition: all 0.3s ease;
    }

    .warehouse-product-row:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: scale(1.02);
        transition: all 0.3s ease;
    }

    .warehouse-summary {
        border: 1px solid rgba(40, 167, 69, 0.3);
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(23, 162, 184, 0.1));
    }

    .warehouse-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 10px;
        padding: 30px;
        min-width: 200px;
    }

    #warehouseDailyTable tbody tr td:nth-child(2),
    #warehouseProductsTable tbody tr td:nth-child(3) {
        font-weight: 600;
        background: linear-gradient(45deg, transparent, rgba(40, 167, 69, 0.1));
    }

    /* Warehouse table hover styling consistency */
    #warehouseDailyTable tbody tr:hover,
    #warehouseProductsTable tbody tr:hover,
    #newCustomersTable tbody tr:hover,
    #returningCustomersTable tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.08) !important;
        cursor: pointer;
    }

    #warehouseDailyTable tbody tr:hover td,
    #warehouseProductsTable tbody tr:hover td,
    #newCustomersTable tbody tr:hover td,
    #returningCustomersTable tbody tr:hover td {
        background-color: inherit !important;
    }

    /* Maintain special column styling on hover */
    #warehouseDailyTable tbody tr:hover td:nth-child(2),
    #warehouseProductsTable tbody tr:hover td:nth-child(3) {
        background: linear-gradient(45deg, rgba(40, 167, 69, 0.08), rgba(40, 167, 69, 0.15)) !important;
        font-weight: 600;
    }

    /* Customer table specific styling */
    #newCustomersTable, #returningCustomersTable {
        font-size: 0.875rem;
    }

    #newCustomersTable tbody tr, #returningCustomersTable tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .fa-crown { color: #ffd700 !important; }
    .fa-medal { color: #c0c0c0 !important; }
    .fa-award { color: #cd7f32 !important; }
</style>
<!-- Load AI Chat CSS as a separate file to ensure precedence and easier caching control -->
<link rel="stylesheet" href="{{ asset('css/dashboard/ai-chat.css') }}?v={{ @filemtime(public_path('css/dashboard/ai-chat.css')) ?: time() }}">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<!-- ApexCharts robust loader with fallbacks and timeout -->
<script>
// Guard to avoid duplicate loads
window.__apexLoading = window.__apexLoading || false;
window.__apexReady = window.__apexReady || false;
window.__apexReadyQueue = window.__apexReadyQueue || [];
window.__apexFailed = false;

function onApexReady(cb){
    if (window.__apexReady) return cb();
    if (window.__apexFailed) {
        console.warn('ApexCharts failed to load, cannot execute callback');
        showChartsError();
        return;
    }
    window.__apexReadyQueue.push(cb);
}

function markApexReady(){
    console.log('🎉 ApexCharts marked as ready!');
    window.__apexReady = true;
    const q = window.__apexReadyQueue || [];
    console.log('📋 Processing', q.length, 'queued callbacks');
    while (q.length) {
        try {
            q.shift()();
        } catch(e){
            console.error('❌ Error in ApexCharts ready callback:', e);
        }
    }
}

function markApexFailed(){
    console.error('❌ ApexCharts failed to load from all sources');
    window.__apexFailed = true;
    window.__apexLoading = false;
    showChartsError();
}

function showChartsError(){
    const containers = ['#trendChart', '#summaryChart'];
    containers.forEach(selector => {
        const el = document.querySelector(selector);
        if (el && !el.innerHTML.trim()) {
            el.innerHTML = `
                <div class="d-flex h-100 w-100 align-items-center justify-content-center text-center text-white-50" style="flex-direction: column; gap: 10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                    <div>
                        <strong>Charts Unavailable</strong><br>
                        <small>Unable to load ApexCharts library.<br>Please check your internet connection or disable ad blockers.</small>
                    </div>
                </div>
            `;
        }
    });
}

function loadApexWithFallbacks(){
    if (typeof ApexCharts !== 'undefined') {
        console.log('✅ ApexCharts already loaded!');
        markApexReady();
        return;
    }
    if (window.__apexLoading) {
        console.log('⏳ ApexCharts already loading...');
        return;
    }

    console.log('🔄 Starting ApexCharts CDN loader...');
    window.__apexLoading = true;

    const cdns = [
        'https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js',
        'https://unpkg.com/apexcharts@3.45.2/dist/apexcharts.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.45.2/apexcharts.min.js'
    ];

    let idx = 0;
    let loadTimeout;

    function tryNext(){
        if (typeof ApexCharts !== 'undefined') {
            clearTimeout(loadTimeout);
            markApexReady();
            return;
        }

        if (idx >= cdns.length) {
            clearTimeout(loadTimeout);
            markApexFailed();
            return;
        }

        const src = cdns[idx++];
        console.log(`🌐 Trying CDN ${idx}/${cdns.length}:`, src);

        const s = document.createElement('script');
        s.src = src;
        s.async = false; // Changed to false for sequential loading

        // Set timeout for each CDN attempt
        loadTimeout = setTimeout(() => {
            console.warn('⏱️ Timeout loading from', src);
            s.onerror = null;
            s.onload = null;
            tryNext();
        }, 10000); // 10 second timeout per CDN

        s.onload = function(){
            clearTimeout(loadTimeout);
            // Wait a bit for global to be available
            setTimeout(() => {
                if (typeof ApexCharts !== 'undefined') {
                    console.log('✅ ApexCharts loaded successfully from', src);
                    markApexReady();
                } else {
                    console.warn('⚠️ Script loaded but ApexCharts global not found, trying next...');
                    tryNext();
                }
            }, 100);
        };

        s.onerror = function(e){
            clearTimeout(loadTimeout);
            console.warn('❌ Failed to load from', src, e);
            tryNext();
        };

        document.head.appendChild(s);
    }

    tryNext();

    // Global timeout - if nothing works after 30 seconds, give up
    setTimeout(() => {
        if (!window.__apexReady && !window.__apexFailed) {
            console.error('⏱️ Global timeout: ApexCharts failed to load after 30 seconds');
            markApexFailed();
        }
    }, 30000);
}

// Kick off loading early
console.log('🚀 Initializing ApexCharts loader...');
loadApexWithFallbacks();
</script>
<script src="{{ asset('js/ai-chat.js') }}"></script>
<script>
let trendChart, summaryChart;
const businessMetricId = {{ $businessMetric->id }};
// Make businessMetricId globally available for tests
window.businessMetricId = businessMetricId;

$(document).ready(function() {
    console.log('🚀 Document ready, checking dependencies...');
    console.log('✓ jQuery version:', $.fn.jquery);
    console.log('✓ ApexCharts loaded:', typeof ApexCharts !== 'undefined');
    console.log('✓ toastr loaded:', typeof toastr !== 'undefined');
    console.log('✓ businessMetricId:', businessMetricId);

    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Ensure initialize only once when Apex is ready
    if (!window.__chartsInitializedOnce) {
        window.__chartsInitializedOnce = true;
        console.log('📋 Registering chart initialization callback...');
        onApexReady(() => {
            console.log('✅ ApexCharts ready callback executing, initializing charts...');
            initializeCharts();
        });
        console.log('✅ Callback registered. Queue length:', window.__apexReadyQueue.length);
    } else {
        console.log('⚠️ Charts already initialized once, skipping...');
    }

    // Initialize statistics cards update (if warehouse data section exists)
    if (typeof updateStatisticsCards === 'function') {
        // Initial update after short delay to ensure DOM is ready
        setTimeout(updateStatisticsCards, 1000);
    }

    // Bind chart period buttons (keep charts interactive)
    $('.chart-period').on('click', function() {
        $('.chart-period').removeClass('active btn-primary').addClass('btn-outline-light');
        $(this).removeClass('btn-outline-light').addClass('active btn-primary');
        const period = $(this).data('period');
        updateChart(period);
    });
    // Records table and CRUD disabled per request
});

function initializeCharts() {
    console.log('🎯 Initializing charts...');

    // Double-check ApexCharts is available
    if (typeof ApexCharts === 'undefined') {
        console.error('❌ ApexCharts is not defined! Cannot initialize charts.');
        showChartsError();
        return;
    }

    try {
        // Get initial chart data or use defaults
        const chartData = {
            values: @json($chartData['values'] ?? []),
            dates: @json($chartData['dates'] ?? []),
            labels: @json($chartData['labels'] ?? [])
        };

        console.log('📊 Chart data:', chartData);

        // Verify chart elements exist
        const trendElement = document.querySelector("#trendChart");
        const summaryElement = document.querySelector("#summaryChart");

        if (!trendElement) {
            console.error('❌ Trend chart element (#trendChart) not found!');
            return;
        }

        if (!summaryElement) {
            console.error('❌ Summary chart element (#summaryChart) not found!');
            return;
        }

        console.log('✅ Chart elements found');

    // Get warehouse data for enhanced charts (if available)
    const warehouseData = @json($warehouseData ?? []);
    const hasWarehouseData = warehouseData && warehouseData.available;

    console.log('📦 Warehouse data available:', hasWarehouseData);
    console.log('📦 Full warehouse data:', warehouseData);

    // Prepare chart data - prioritize warehouse OLAP data
    let trendSeriesData = chartData.values;
    let trendLabels = chartData.labels;

    if (hasWarehouseData && warehouseData.daily && warehouseData.daily.length > 0) {
        console.log('✅ Using warehouse daily data for trend chart');
        // Use actual warehouse data from OLAP views
        const daily = warehouseData.daily;

        // Extract values based on metric type
        switch (warehouseData.type) {
            case 'sales':
                trendSeriesData = daily.map(d => parseFloat(d.gross_revenue || d.total_revenue || 0));
                break;
            case 'cogs':
                trendSeriesData = daily.map(d => parseFloat(d.total_cogs || 0));
                break;
            case 'margin':
                trendSeriesData = daily.map(d => parseFloat(d.total_margin || 0));
                break;
            case 'new_customers':
                trendSeriesData = daily.map(d => parseInt(d.new_customers || 0));
                break;
            case 'returning_customers':
                trendSeriesData = daily.map(d => parseInt(d.returning_customers || 0));
                break;
            default:
                trendSeriesData = daily.map(d => parseFloat(d.value || 0));
        }

        // Format dates for labels
        trendLabels = daily.map(d => {
            const date = new Date(d.date || d.sales_date);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        });

        console.log('📊 Warehouse data points:', trendSeriesData.length);
        console.log('📊 Sample values:', trendSeriesData.slice(0, 5));
    } else {
        console.log('⚠️ No warehouse data, using fallback chart data');
    }

    // Trend Chart
    const trendOptions = {
        series: [{
            name: '{{ $businessMetric->metric_name }}',
            data: trendSeriesData
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#667eea'],
        xaxis: {
            categories: trendLabels,
            title: {
                text: 'Tanggal',
                style: { color: '#fff' }
            },
            labels: {
                style: { colors: '#fff' }
            }
        },
        yaxis: {
            title: {
                text: '{{ $businessMetric->unit ?? "Value" }}',
                style: { color: '#fff' }
            },
            labels: {
                style: { colors: '#fff' },
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.1)',
            strokeDashArray: 3
        }
    };

    console.log('📈 Creating trend chart...');

    // Clear any existing chart content
    trendElement.innerHTML = '';

    try {
        trendChart = new ApexCharts(trendElement, trendOptions);
        trendChart.render().then(() => {
            console.log('✅ Trend chart rendered successfully');
        }).catch(err => {
            console.error('❌ Error rendering trend chart:', err);
            trendElement.innerHTML = '<div class="text-center text-white-50 mt-5"><i class="fas fa-exclamation-triangle"></i><br>Error rendering chart</div>';
        });
    } catch (err) {
        console.error('❌ Error creating trend chart:', err);
        trendElement.innerHTML = '<div class="text-center text-white-50 mt-5"><i class="fas fa-exclamation-triangle"></i><br>Error creating chart</div>';
    }

    // Summary Chart (Enhanced with warehouse data)
    console.log('🍩 Preparing summary chart...');
    let summaryData, summaryLabels, summaryColors;

    if (hasWarehouseData && warehouseData.monthly) {
        // Use warehouse monthly data
        const monthlyRevenue = warehouseData.monthly.total_revenue || 0;
        const transactionCount = warehouseData.monthly.transaction_count || 0;
        const avgPerTransaction = transactionCount > 0 ? monthlyRevenue / transactionCount : 0;

        summaryData = [monthlyRevenue, avgPerTransaction, transactionCount];
        summaryLabels = ['Total Revenue Bulan Ini', 'Rata-rata per Transaksi', 'Jumlah Transaksi'];
        summaryColors = ['#28a745', '#17a2b8', '#ffc107'];
        console.log('📦 Using warehouse data for summary chart:', {monthlyRevenue, transactionCount, avgPerTransaction});
    } else if (hasWarehouseData && warehouseData.type === 'cogs' && warehouseData.monthly) {
        // COGS specific summary
        const totalCogs = warehouseData.monthly.total_cogs || 0;
        summaryData = [totalCogs];
        summaryLabels = ['Total COGS Bulan Ini'];
        summaryColors = ['#dc3545'];
        console.log('📦 Using COGS data for summary chart:', {totalCogs});
    } else if (hasWarehouseData && warehouseData.type === 'margin' && warehouseData.monthly) {
        // Margin specific summary
        const totalMargin = warehouseData.monthly.total_margin || 0;
        const avgMarginPercent = warehouseData.monthly.avg_margin_percentage || 0;
        summaryData = [totalMargin, avgMarginPercent * 1000]; // Scale percentage for visibility
        summaryLabels = ['Total Margin', 'Avg Margin % (x1000)'];
        summaryColors = ['#28a745', '#17a2b8'];
        console.log('📦 Using margin data for summary chart:', {totalMargin, avgMarginPercent});
    } else {
        // Fallback to traditional data
        summaryData = [
            {{ $statistics['this_month'] ?? 50 }},
            {{ $statistics['last_month'] ?? 30 }},
            {{ ($statistics['avg_value'] ?? 100) - ($statistics['this_month'] ?? 50) }}
        ];
        summaryLabels = ['Bulan Ini', 'Bulan Lalu', 'Lainnya'];
        summaryColors = ['#28a745', '#ffc107', '#6c757d'];
        console.log('📊 Using fallback data for summary chart');
    }

    const summaryOptions = {
        series: summaryData,
        chart: {
            type: 'donut',
            height: 300,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        labels: summaryLabels,
        colors: summaryColors,
        legend: {
            position: 'bottom',
            labels: {
                colors: '#fff'
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total',
                            fontSize: '16px',
                            fontWeight: 600,
                            color: '#fff',
                            formatter: function (w) {
                                return formatValue(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                            }
                        },
                        value: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 400,
                            color: '#fff',
                            formatter: function (val) {
                                return formatValue(val);
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    console.log('📊 Summary chart data:', {summaryData, summaryLabels, summaryColors});

    // Clear any existing chart content
    summaryElement.innerHTML = '';

    try {
        summaryChart = new ApexCharts(summaryElement, summaryOptions);
        summaryChart.render().then(() => {
            console.log('✅ Summary chart rendered successfully');
        }).catch(err => {
            console.error('❌ Error rendering summary chart:', err);
            summaryElement.innerHTML = '<div class="text-center text-white-50 mt-5"><i class="fas fa-exclamation-triangle"></i><br>Error rendering chart</div>';
        });
    } catch (err) {
        console.error('❌ Error creating summary chart:', err);
        summaryElement.innerHTML = '<div class="text-center text-white-50 mt-5"><i class="fas fa-exclamation-triangle"></i><br>Error creating chart</div>';
    }

    // Update statistics cards with warehouse data
    if (hasWarehouseData) {
        console.log('📦 Updating statistics with warehouse data');
        updateStatisticsCardsWithWarehouse(warehouseData);
    }

    console.log('✅ Charts initialization complete!');

    } catch (error) {
        console.error('❌ Fatal error in initializeCharts:', error);
        toastr.error('Failed to initialize charts. Check console for details.');
        showChartsError();
    }
}

// Records DataTable removed per request

// bindEvents related to DataTable/CRUD removed per request

// addNewRecord removed

// editRecord removed

// deleteRecord removed

// deleteSelected removed

// refreshTable removed

// exportToExcel removed

function updateChart(days) {
    $.ajax({
        url: `/dashboard/metrics/${businessMetricId}/chart-data`,
        method: 'GET',
        data: { period: days },
        success: function(response) {
            if (response && response.success && response.data) {
                const data = response.data;
                if (trendChart) {
                    trendChart.updateSeries([{ name: '{{ $businessMetric->metric_name }}', data: data.values }]);
                    trendChart.updateOptions({ xaxis: { categories: data.labels } });
                }
                animateStatisticsUpdate();
            } else if (response && response.chartData) {
                // Back-compat with older response shape
                if (trendChart) {
                    trendChart.updateSeries([{ name: '{{ $businessMetric->metric_name }}', data: response.chartData.values }]);
                    trendChart.updateOptions({ xaxis: { categories: response.chartData.labels } });
                }
                animateStatisticsUpdate();
            } else {
                toastr.warning('No chart data available for the selected period');
            }
        },
        error: function(xhr) {
            console.error('Chart update failed', xhr?.responseText || xhr);
            toastr.error('Failed to update chart');
        }
    });
}

function updateStatisticsCardsWithWarehouse(warehouseData) {
    if (!warehouseData || !warehouseData.available) return;

    // Update Total Records with transaction count
    if (warehouseData.monthly && warehouseData.monthly.transaction_count) {
        const totalRecords = document.getElementById('totalRecords');
        if (totalRecords) {
            animateValueUpdate(totalRecords, warehouseData.monthly.transaction_count);
        }
    }

    // Update Average Value with average per transaction
    if (warehouseData.monthly) {
        const avgValue = document.getElementById('avgValue');
        if (avgValue && warehouseData.monthly.transaction_count > 0) {
            const avgPerTransaction = warehouseData.monthly.total_revenue / warehouseData.monthly.transaction_count;
            animateValueUpdate(avgValue, Math.round(avgPerTransaction), true);
        }
    }

    // Update Growth Rate (already calculated from backend)
    // Last Update from warehouse data
    if (warehouseData.daily_sales && warehouseData.daily_sales.length > 0) {
        const lastUpdate = document.getElementById('lastUpdate');
        if (lastUpdate) {
            const latestDate = warehouseData.daily_sales[warehouseData.daily_sales.length - 1].sales_date;
            const formattedDate = moment(latestDate).format('DD MMM YYYY');
            lastUpdate.textContent = formattedDate;
        }
    }
}

function animateValueUpdate(element, newValue, isCurrency = false) {
    const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
    const increment = (newValue - currentValue) / 20;
    let current = currentValue;

    element.classList.add('updated');

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= newValue) || (increment < 0 && current <= newValue)) {
            current = newValue;
            clearInterval(timer);
            setTimeout(() => element.classList.remove('updated'), 1000);
        }

        if (isCurrency) {
            element.textContent = formatCurrency(Math.round(current));
        } else {
            element.textContent = Math.round(current).toLocaleString();
        }
    }, 50);
}

function animateStatisticsUpdate() {
    // Add pulse animation to all stats cards
    $('.stats-card .stats-value').addClass('updated');
    setTimeout(() => {
        $('.stats-card .stats-value').removeClass('updated');
    }, 1500);
}

function refreshWarehouseDataButton() {
    const refreshBtn = document.getElementById('refreshWarehouseBtn');
    const warehouseContent = document.getElementById('warehouseDataContent');
    const loading = document.getElementById('warehouseLoading');

    // Show loading
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';

    if (warehouseContent && loading) {
        warehouseContent.style.opacity = '0.5';
        loading.classList.remove('d-none');
    }

    // Refresh page to get updated warehouse data
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// ===== WAREHOUSE DATA MANAGEMENT FUNCTIONS =====

function updateWarehouseData() {
    const dateRange = document.getElementById('dateRangeSelector')?.value || '30';
    const groupBy = document.getElementById('groupBySelector')?.value || 'daily';
    const perPage = document.getElementById('perPageSelector')?.value || '50';

    let params = {
        days: dateRange,
        group_by: groupBy,
        w_per_page: perPage,
        w_page: 1
    };

    // Handle custom date range
    if (dateRange === 'custom') {
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;

        if (startDate && endDate) {
            params.start_date = startDate;
            params.end_date = endDate;
            delete params.days;
        } else {
            toastr.warning('Pilih tanggal mulai dan tanggal akhir');
            return;
        }
    }

    refreshWarehouseDataWithParams(params);
}

function changePage(page) {
    const dateRange = document.getElementById('dateRangeSelector')?.value || '30';
    const groupBy = document.getElementById('groupBySelector')?.value || 'daily';
    const perPage = document.getElementById('perPageSelector')?.value || '50';

    let params = {
        days: dateRange,
        group_by: groupBy,
        w_per_page: perPage,
        w_page: page
    };

    refreshWarehouseDataWithParams(params);
}

function refreshWarehouseData() {
    updateWarehouseData();
}

function refreshWarehouseDataWithParams(params) {
    const loading = document.getElementById('warehouseLoading');
    if (loading) {
        loading.classList.remove('d-none');
        loading.classList.add('d-flex');
    }

    // Build query string
    const queryString = new URLSearchParams(params).toString();
    const currentUrl = window.location.pathname;

    // Reload page with new parameters
    window.location.href = `${currentUrl}?${queryString}`;
}

// Show/hide custom date inputs
document.addEventListener('DOMContentLoaded', function() {
    const dateRangeSelector = document.getElementById('dateRangeSelector');
    if (dateRangeSelector) {
        dateRangeSelector.addEventListener('change', function() {
            const customInputs1 = document.getElementById('customDateInputs');
            const customInputs2 = document.getElementById('customDateInputs2');

            if (this.value === 'custom') {
                if (customInputs1) customInputs1.classList.remove('d-none');
                if (customInputs2) customInputs2.classList.remove('d-none');
            } else {
                if (customInputs1) customInputs1.classList.add('d-none');
                if (customInputs2) customInputs2.classList.add('d-none');
            }
        });
    }
});

// Export functions
function exportToExcel() {
    toastr.info('Preparing Excel export...');
    const metricId = businessMetricId;
    window.location.href = `/dashboard/metrics/${metricId}/export?format=excel`;
}

function exportToPDF() {
    toastr.info('Preparing PDF export...');
    const metricId = businessMetricId;
    window.location.href = `/dashboard/metrics/${metricId}/export?format=pdf`;
}

function exportToCSV() {
    toastr.info('Preparing CSV export...');
    const metricId = businessMetricId;
    window.location.href = `/dashboard/metrics/${metricId}/export?format=csv`;
}

// Real-time statistics update
function updateStatisticsCards() {
    const metricId = businessMetricId;

    $.ajax({
        url: `/dashboard/metrics/${metricId}/statistics`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;

                // Update each stat with animation
                updateStatValue('stat-revenue', stats.revenue, true);
                updateStatValue('stat-transactions', stats.transactions, false);
                updateStatValue('stat-quantity', stats.quantity, false);
                updateStatValue('stat-cogs', stats.cogs, true);
                updateStatValue('stat-margin', stats.margin, true);
                updateStatValue('stat-new-customers', stats.new_customers, false);
                updateStatValue('stat-returning-customers', stats.returning_customers, false);

                // Update top-level stats cards
                animateStatisticsUpdate();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to update statistics:', error);
        }
    });
}

function updateStatValue(elementId, newValue, isCurrency) {
    const element = document.getElementById(elementId);
    if (!element || newValue === undefined) return;

    const formattedValue = isCurrency
        ? 'Rp ' + new Intl.NumberFormat('id-ID').format(newValue)
        : new Intl.NumberFormat('id-ID').format(newValue);

    // Add animation class
    element.classList.add('stats-value-update');
    element.textContent = formattedValue;

    setTimeout(() => {
        element.classList.remove('stats-value-update');
    }, 1000);
}

// Auto-refresh statistics every 30 seconds
setInterval(function() {
    if (document.getElementById('stat-revenue') || document.getElementById('stat-cogs')) {
        updateStatisticsCards();
    }
}, 30000);

function triggerBackfill() {
    const backfillBtn = document.getElementById('backfillBtn');
    if (!backfillBtn) return;

    if (confirm('Jalankan backfill untuk mengisi data warehouse dari transaksi yang ada?')) {
        backfillBtn.disabled = true;
        backfillBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';

        $.ajax({
            url: '{{ route("dashboard.data-feeds.backfill-facts") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('Backfill berhasil! Data warehouse telah diperbarui.');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                toastr.error('Gagal menjalankan backfill: ' + error);
                backfillBtn.disabled = false;
                backfillBtn.innerHTML = '<i class="fas fa-database me-1"></i> Jalankan Backfill';
            }
        });
    }
}

// Inline editing functions
let editingData = {};

function bindInlineEditEvents() {
    $('.editable-cell').off('click').on('click', function() {
        const $cell = $(this);
        const field = $cell.data('field');
        const id = $cell.data('id');
        const type = $cell.data('type');
        const currentValue = $cell.text().trim();

        // Don't edit if already editing
        if ($cell.hasClass('editing')) return;

        // Store original value
        editingData[id] = editingData[id] || {};
        editingData[id][field] = currentValue;

        $cell.addClass('editing');

        let input;
        let inputValue = currentValue;

        if (type === 'date') {
            // Parse the current date format (DD/MM/YYYY) to YYYY-MM-DD
            if (currentValue && currentValue !== '-') {
                inputValue = moment(currentValue, 'DD/MM/YYYY').format('YYYY-MM-DD');
            } else {
                inputValue = moment().format('YYYY-MM-DD'); // Default to today
            }
            input = `<input type="date" class="form-control form-control-sm inline-edit-input" value="${inputValue}" data-field="${field}" data-id="${id}">`;
        } else if (type === 'number') {
            // Remove formatting for editing
            const numValue = currentValue.replace(/[^\d.-]/g, '');
            inputValue = numValue || '0';
            input = `<input type="number" class="form-control form-control-sm inline-edit-input" value="${inputValue}" step="0.01" data-field="${field}" data-id="${id}">`;
        } else {
            // For text fields, get the full text if available
            const textValue = $cell.data('full-text') || currentValue;
            inputValue = textValue === '-' ? '' : textValue;
            input = `<input type="text" class="form-control form-control-sm inline-edit-input" value="${inputValue}" data-field="${field}" data-id="${id}">`;
        }

        $cell.html(input);
        $cell.find('input').focus().select();

        // Show save/cancel buttons
        $(`#save-${id}, #cancel-${id}`).show();

        // Handle Enter key
        $cell.find('input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                saveInlineEdit(id);
            }
        });

        // Handle Escape key
        $cell.find('input').on('keydown', function(e) {
            if (e.which === 27) {
                e.preventDefault();
                cancelInlineEdit(id);
            }
        });

        // Handle Tab key to move to next editable cell
        $cell.find('input').on('keydown', function(e) {
            if (e.which === 9) { // Tab key
                e.preventDefault();
                const $row = $cell.closest('tr');
                const $nextCell = $row.find('.editable-cell').eq($row.find('.editable-cell').index($cell) + 1);
                if ($nextCell.length) {
                    saveInlineEdit(id, function() {
                        $nextCell.click();
                    });
                } else {
                    saveInlineEdit(id);
                }
            }
        });

        // Auto-save on blur for better UX
        $cell.find('input').on('blur', function() {
            // Small delay to allow clicking save/cancel buttons
            setTimeout(() => {
                if ($cell.hasClass('editing')) {
                    saveInlineEdit(id);
                }
            }, 200);
        });
    });
}

// addInlineRecord removed

function updateStatistics() {
    // Fetch updated statistics from server
    $.ajax({
        url: `{{ route('dashboard.metrics.overview', $businessMetric->id) }}`,
        method: 'GET',
        success: function(response) {
            const stats = response.statistics;

            // Update all statistics cards
            $('#totalRecords').text(stats.total_records || 0);
            $('#avgValue').text(formatNumber(stats.avg_value || 0));

            const growthRate = stats.growth_rate || 0;
            $('#growthRate').text(growthRate.toFixed(1) + '%')
                .removeClass('growth-positive growth-negative')
                .addClass(growthRate >= 0 ? 'growth-positive' : 'growth-negative');

            $('#lastUpdate').text(stats.last_update ?
                moment(stats.last_update).format('DD MMM YYYY') : 'N/A');

            // Animate the updated values
            $('.stats-value').each(function() {
                $(this).addClass('updated');
                setTimeout(() => {
                    $(this).removeClass('updated');
                }, 1000);
            });
        },
        error: function(xhr) {
            console.error('Failed to update statistics:', xhr.responseText);
        }
    });
}

// saveInlineEdit function removed (inline editing disabled per request)

function cancelInlineEdit(id) {
    $(`.inline-edit-input[data-id="${id}"]`).each(function() {
        const $input = $(this);
        const $cell = $input.closest('.editable-cell');
        const field = $input.data('field');

        // Restore original value
        let originalValue = '';
        if (editingData[id] && editingData[id][field]) {
            originalValue = editingData[id][field];
        }

        $cell.removeClass('editing').html(originalValue);
    });

    // Hide save/cancel buttons
    $(`#save-${id}, #cancel-${id}`).hide();

    // Clear editing data
    if (editingData[id]) {
        delete editingData[id];
    }
}

function formatValue(value) {
    try {
        @if($businessMetric->unit === 'currency')
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        @elseif($businessMetric->unit === 'percentage')
            return parseFloat(value).toFixed(1) + '%';
        @else
            return new Intl.NumberFormat('id-ID').format(value);
        @endif
    } catch (error) {
        console.error('Error formatting value:', error);
        return value.toString();
    }
}

// Test helpers removed

// Modal Functions
function initializeModal() {
    // Set default date to today
    $('#record_date').val(moment().format('YYYY-MM-DD'));

    // Initialize metric-specific form
    initializeMetricSpecificForm();

    // Bind preview update events
    $('#record_date, #record_value, #record_notes').on('input change', updatePreview);

    // Handle form submission
    $('#addRecordForm').on('submit', handleFormSubmit);

    // Reset form when modal is hidden
    $('#addRecordModal').on('hidden.bs.modal', resetForm);

    // Focus on date field when modal is shown
    $('#addRecordModal').on('shown.bs.modal', function() {
        $('#record_date').focus();
        updatePreview();
    });

    // Keyboard shortcuts
    $('#addRecordModal').on('keydown', function(e) {
        // Save with Ctrl+Enter
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#addRecordForm').submit();
        }
        // Close with Escape (already handled by Bootstrap, but added for consistency)
        if (e.which === 27) {
            $(this).modal('hide');
        }
    });

    // Auto-format number input
    $('#record_value').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
            updatePreview();
        }
    });

    // Update preview on modal show
    updatePreview();
}

function initializeMetricSpecificForm() {
    const metricName = '{{ $businessMetric->metric_name }}';

    // Hide generic value field for specific metrics that have their own input fields
    const metricsWithSpecificForms = [
        'Total Penjualan',
        'Biaya Pokok Penjualan (COGS)',
        'Margin Keuntungan (Profit Margin)',
        'Jumlah Pelanggan Baru',
        'Jumlah Pelanggan Setia',
        'Penjualan Produk Terlaris'
    ];

    if (metricsWithSpecificForms.includes(metricName)) {
        $('#generic-value-field').hide();

        // Show specific form
        $('.metric-specific-form').hide();
        const formId = getFormIdByMetricName(metricName);
        $(`#${formId}`).addClass('active').show();

        // Initialize specific form handlers
        initializeSpecificFormHandlers(metricName);
    }
}

function getFormIdByMetricName(metricName) {
    const formMap = {
        'Total Penjualan': 'total-penjualan-form',
        'Biaya Pokok Penjualan (COGS)': 'cogs-form',
        'Margin Keuntungan (Profit Margin)': 'margin-keuntungan-form',
        'Jumlah Pelanggan Baru': 'pelanggan-baru-form',
        'Jumlah Pelanggan Setia': 'pelanggan-setia-form',
        'Penjualan Produk Terlaris': 'produk-terlaris-form'
    };
    return formMap[metricName] || '';
}

function initializeSpecificFormHandlers(metricName) {
    switch(metricName) {
        case 'Total Penjualan':
            initializeTotalPenjualanForm();
            break;
        case 'Biaya Pokok Penjualan (COGS)':
            initializeCOGSForm();
            break;
        case 'Margin Keuntungan (Profit Margin)':
            initializeMarginKeuntunganForm();
            break;
        case 'Jumlah Pelanggan Baru':
            initializePelangganBaruForm();
            break;
        case 'Jumlah Pelanggan Setia':
            initializePelangganSetiaForm();
            break;
        case 'Penjualan Produk Terlaris':
            initializeProdukTerlarisForm();
            break;
    }
}

function initializeTotalPenjualanForm() {
    // Update record_value when total_revenue changes
    $('#total_revenue').on('input', function() {
        const value = parseFloat($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });

    // Validation
    $('#total_revenue').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value) && value > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
}

function initializeCOGSForm() {
    // Update record_value when total_cogs changes
    $('#total_cogs').on('input', function() {
        const value = parseFloat($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });
}

function initializeMarginKeuntunganForm() {
    // Load existing data and calculate margin
    loadMarginCalculationData();

    // Recalculate when period changes
    $('#margin_period').on('change', loadMarginCalculationData);
}

function initializePelangganBaruForm() {
    // Update record_value when new_customer_count changes
    $('#new_customer_count').on('input', function() {
        const value = parseInt($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });

    // Calculate acquisition cost per customer
    $('#customer_acquisition_cost, #new_customer_count').on('input', function() {
        calculateAcquisitionMetrics();
    });
}

function initializePelangganSetiaForm() {
    // Real-time calculation
    $('#total_customer_count, #new_customer_count').on('input', function() {
        calculateLoyaltyMetrics();
    });

    // Load new customer count from previous input if available
    loadNewCustomerCount();
}

function initializeProdukTerlarisForm() {
    // Calculate revenue generated
    $('#quantity_sold, #unit_price, #cost_per_unit').on('input', function() {
        calculateProductMetrics();
    });

    // Auto-uppercase SKU
    $('#product_sku').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
}

function loadMarginCalculationData() {
    const period = $('#margin_period').val();

    // Fetch data via AJAX
    $.ajax({
        url: '{{ route("dashboard.metrics.calculation.data", $businessMetric->id) }}',
        method: 'GET',
        data: { period: period },
        success: function(response) {
            $('#ref_total_revenue').text(formatCurrency(response.total_revenue || 0));
            $('#ref_total_cogs').text(formatCurrency(response.total_cogs || 0));

            const margin = calculateMarginPercentage(response.total_revenue || 0, response.total_cogs || 0);
            $('#calculated_margin').text(margin + '%');
            $('#record_value').val(margin);
            updatePreview();
        },
        error: function() {
            console.error('Failed to load calculation data');
        }
    });
}

function calculateLoyaltyMetrics() {
    const totalCustomers = parseInt($('#total_customer_count').val()) || 0;
    const newCustomers = parseInt($('#new_customer_count').val()) || 0;
    const returningCustomers = Math.max(0, totalCustomers - newCustomers);
    const loyaltyPercentage = totalCustomers > 0 ? ((returningCustomers / totalCustomers) * 100).toFixed(1) : 0;

    $('#display_total_customers').text(totalCustomers);
    $('#display_new_customers').text(newCustomers);
    $('#display_returning_customers').text(returningCustomers);
    $('#display_loyalty_percentage').text(loyaltyPercentage + '%');

    // Update main record value
    $('#record_value').val(loyaltyPercentage);
    updatePreview();
}

function calculateProductMetrics() {
    const quantity = parseInt($('#quantity_sold').val()) || 0;
    const price = parseFloat($('#unit_price').val()) || 0;
    const cost = parseFloat($('#cost_per_unit').val()) || 0;

    const revenue = quantity * price;
    const profitPerUnit = price - cost;

    $('#calculated_revenue').text(formatCurrency(revenue));
    $('#calculated_profit_per_unit').text(formatCurrency(profitPerUnit));

    // Update main record value with revenue
    $('#record_value').val(revenue);
    updatePreview();
}

function calculateAcquisitionMetrics() {
    const newCustomers = parseInt($('#new_customer_count').val()) || 0;
    const acquisitionCost = parseFloat($('#customer_acquisition_cost').val()) || 0;

    if (newCustomers > 0 && acquisitionCost > 0) {
        const costPerCustomer = acquisitionCost / newCustomers;
        // You can display this somewhere if needed
        console.log('Cost per customer acquisition:', formatCurrency(costPerCustomer));
    }
}

function loadNewCustomerCount() {
    // Try to get new customer count from today's data
    const today = moment().format('YYYY-MM-DD');
    $.ajax({
        url: '{{ route("dashboard.metrics.daily.data", $businessMetric->business_id) }}',
        method: 'GET',
        data: { date: today },
        success: function(response) {
            if (response.new_customer_count) {
                $('#new_customer_count').val(response.new_customer_count);
                calculateLoyaltyMetrics();
            }
        },
        error: function() {
            // Ignore error, it's optional data
        }
    });
}

function calculateMarginPercentage(revenue, cogs) {
    if (revenue <= 0) return 0;
    return (((revenue - cogs) / revenue) * 100).toFixed(1);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function updatePreview() {
    const date = $('#record_date').val();
    let value = parseFloat($('#record_value').val()) || 0;
    const notes = $('#record_notes').val();
    const metricName = '{{ $businessMetric->metric_name }}';

    // Get value from metric-specific forms if available
    if (!value || value === 0) {
        value = getValueFromSpecificForm(metricName);
    }

    // Update date preview with animation
    if (date) {
        const formattedDate = moment(date).format('DD MMM YYYY');
        animateTextChange('#preview_date', formattedDate);

        // Validate date (not in future)
        const selectedDate = moment(date);
        const today = moment();
        if (selectedDate.isAfter(today)) {
            $('#record_date').addClass('is-invalid');
            showFieldError('#record_date', 'Date cannot be in the future');
        } else {
            $('#record_date').removeClass('is-invalid');
            clearFieldError('#record_date');
        }
    } else {
        animateTextChange('#preview_date', '-');
    }

    // Update value preview with animation
    if (value > 0) {
        animateTextChange('#preview_value', new Intl.NumberFormat('id-ID').format(value));
        animateTextChange('#preview_formatted', formatValue(value));

        // Validate value (reasonable range)
        if (value > 999999999999) { // 1 trillion
            showFieldError('#record_value', 'Value seems too large. Please verify.');
        } else {
            clearFieldError('#record_value');
        }
    } else {
        animateTextChange('#preview_value', '-');
        animateTextChange('#preview_formatted', '-');
    }

    // Update notes preview
    if (notes) {
        const truncatedNotes = notes.length > 50 ? notes.substring(0, 50) + '...' : notes;
        animateTextChange('#preview_notes', truncatedNotes);

        if (notes.length > 100) {
            $('#record_notes').addClass('is-invalid');
            showFieldError('#record_notes', `Notes are quite long (${notes.length} characters). Consider shortening.`);
        } else {
            $('#record_notes').removeClass('is-invalid');
            clearFieldError('#record_notes');
        }
    } else {
        animateTextChange('#preview_notes', 'No notes');
        $('#record_notes').removeClass('is-invalid');
        clearFieldError('#record_notes');
    }
}

function getValueFromSpecificForm(metricName) {
    switch(metricName) {
        case 'Total Penjualan':
            return parseFloat($('#total_revenue').val()) || 0;
        case 'Biaya Pokok Penjualan (COGS)':
            return parseFloat($('#total_cogs').val()) || 0;
        case 'Jumlah Pelanggan Baru':
            return parseInt($('#new_customer_count').val()) || 0;
        case 'Jumlah Pelanggan Setia':
            const totalCustomers = parseInt($('#total_customer_count').val()) || 0;
            const newCustomers = parseInt($('#new_customer_count').val()) || 0;
            if (totalCustomers > 0) {
                return parseFloat((((totalCustomers - newCustomers) / totalCustomers) * 100).toFixed(1));
            }
            return 0;
        case 'Penjualan Produk Terlaris':
            const quantity = parseInt($('#quantity_sold').val()) || 0;
            const price = parseFloat($('#unit_price').val()) || 0;
            return quantity * price;
        case 'Margin Keuntungan (Profit Margin)':
            return parseFloat($('#calculated_margin').text().replace('%', '')) || 0;
        default:
            return parseFloat($('#record_value').val()) || 0;
    }
}

function animateTextChange(selector, newText) {
    const $element = $(selector);
    if ($element.text() !== newText) {
        $element.fadeOut(150, function() {
            $(this).text(newText).fadeIn(150);
        });
    }
}

function showFieldError(selector, message) {
    const $field = $(selector);
    const $group = $field.closest('.mb-3');

    // Remove existing error
    $group.find('.invalid-feedback').remove();

    // Add new error
    $group.append(`<div class="invalid-feedback d-block">${message}</div>`);
}

function clearFieldError(selector) {
    const $field = $(selector);
    const $group = $field.closest('.mb-3');
    $group.find('.invalid-feedback').remove();
}

function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Add CSRF token
    formData.append('_token', '{{ csrf_token() }}');

    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    // Submit form
    $.ajax({
        url: '{{ route("dashboard.metrics.records.store", $businessMetric->id) }}',
        method: 'POST',
        data: Object.fromEntries(formData),
        success: function(response) {
            toastr.success(response.message || 'Record added successfully!');

            // Hide modal
            $('#addRecordModal').modal('hide');

            // Table disabled

            // Update charts
            updateChart($('.chart-period.active').data('period') || 30);

            // Update statistics
            setTimeout(updateStatistics, 500);

            // Reset form
            resetForm();
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Please check the following errors:<br>';
                Object.keys(errors).forEach(field => {
                    errorMessage += `• ${errors[field].join(', ')}<br>`;

                    // Highlight error fields
                    const fieldElement = $(`[name="${field}"]`);
                    fieldElement.addClass('is-invalid');
                    fieldElement.closest('.mb-3').append(
                        `<div class="invalid-feedback d-block">${errors[field].join(', ')}</div>`
                    );
                });
                toastr.error(errorMessage);
            } else {
                toastr.error('Failed to add record: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        },
        complete: function() {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
        }
    });
}

function resetForm() {
    const form = document.getElementById('addRecordForm');
    form.reset();
    form.classList.remove('was-validated');

    // Clear validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Reset date to today
    $('#record_date').val(moment().format('YYYY-MM-DD'));

    // Reset preview
    updatePreview();
}

// Note: AI Chat functionality is loaded from external file: public/js/ai-chat.js
// All AI chat functions (initializeAIChat, sendAIMessage, etc.) are defined there
</script>
@endpush
