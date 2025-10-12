/**
 * OLAP Location Drill-Down Widget
 * Interactive location-based analytics with drill-down/drill-up functionality
 */

(function() {
    'use strict';

    // State management
    const state = {
        currentLevel: 'provinsi',
        currentParent: null,
        parentLevel: null,
        chart: null,
        data: [],
        breadcrumbs: [],
        startDate: null,
        endDate: null
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        if (document.getElementById('location-drilldown-widget')) {
            initDrillDown();
        }
    }

    function initDrillDown() {
        console.log('🎯 Initializing Location Drill-Down Widget...');

        // Initial load - show provinces
        loadDrillDownData('provinsi', null, null);

        // Level button handlers
        document.querySelectorAll('[data-level]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const level = this.getAttribute('data-level');
                console.log(`📍 Level button clicked: ${level}`);
                resetToLevel(level);
            });
        });

        // Metric selector handler
        const metricSelector = document.getElementById('drill-metric-selector');
        if (metricSelector) {
            metricSelector.addEventListener('change', function() {
                console.log(`📊 Metric changed to: ${this.value}`);
                renderChart(state.data, state.currentLevel);
            });
        }

        // Date filter handlers (if exists)
        const startDateInput = document.getElementById('filter-start-date');
        const endDateInput = document.getElementById('filter-end-date');

        if (startDateInput) {
            startDateInput.addEventListener('change', function() {
                state.startDate = this.value;
                loadDrillDownData(state.currentLevel, state.currentParent, state.parentLevel);
            });
        }

        if (endDateInput) {
            endDateInput.addEventListener('change', function() {
                state.endDate = this.value;
                loadDrillDownData(state.currentLevel, state.currentParent, state.parentLevel);
            });
        }

        console.log('✅ Location Drill-Down Widget initialized');
    }

    function loadDrillDownData(level, parent = null, parentLevel = null) {
        console.log(`🔄 Loading drill-down data: level=${level}, parent=${parent}, parentLevel=${parentLevel}`);

        showLoading(true);

        const params = new URLSearchParams({
            level: level
        });

        if (parent) params.append('parent', parent);
        if (parentLevel) params.append('parent_level', parentLevel);

        // Add date range if exists
        if (state.startDate) params.append('start_date', state.startDate);
        if (state.endDate) params.append('end_date', state.endDate);

        const url = `/dashboard/olap/location/drill-down?${params}`;
        console.log(`📡 Fetching: ${url}`);

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('✅ Data received:', result);

                if (result.success) {
                    state.currentLevel = level;
                    state.currentParent = parent;
                    state.parentLevel = parentLevel;
                    state.data = result.data;

                    renderChart(result.data, level);
                    updateStats(result.data);
                    updateBreadcrumbs();
                    updateLevelButtons();

                    console.log(`📊 Rendered ${result.data.length} locations at ${level} level`);
                } else {
                    console.error('❌ API returned error:', result.message);
                    showError('Gagal memuat data lokasi: ' + result.message);
                }
            })
            .catch(error => {
                console.error('❌ Error loading drill-down data:', error);
                showError('Terjadi kesalahan saat memuat data: ' + error.message);
            })
            .finally(() => {
                showLoading(false);
            });
    }

    function renderChart(data, level) {
        const canvas = document.getElementById('location-drilldown-chart');
        if (!canvas) {
            console.warn('⚠️ Chart canvas not found');
            return;
        }

        const ctx = canvas.getContext('2d');
        const metricSelector = document.getElementById('drill-metric-selector');
        const metric = metricSelector ? metricSelector.value : 'revenue';

        console.log(`🎨 Rendering chart: level=${level}, metric=${metric}, data_count=${data.length}`);

        // Destroy existing chart
        if (state.chart) {
            state.chart.destroy();
        }

        // Handle empty data
        if (!data || data.length === 0) {
            console.warn('⚠️ No data to display');
            showEmptyState();
            return;
        }

        // Prepare chart data
        const labels = data.map(item => item.location || 'Unknown');
        const values = data.map(item => {
            switch(metric) {
                case 'revenue': return parseFloat(item.total_revenue || 0);
                case 'margin': return parseFloat(item.total_margin || 0);
                case 'transactions': return parseInt(item.transaction_count || 0);
                case 'margin_pct': return parseFloat(item.avg_margin_pct || 0);
                default: return parseFloat(item.total_revenue || 0);
            }
        });

        console.log('📊 Chart data prepared:', { labels, values });

        // Create new chart
        state.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: getMetricLabel(metric),
                    data: values,
                    backgroundColor: 'rgba(0, 212, 255, 0.6)',
                    borderColor: 'rgba(0, 212, 255, 1)',
                    borderWidth: 1,
                    hoverBackgroundColor: 'rgba(0, 212, 255, 0.8)',
                    hoverBorderColor: 'rgba(0, 212, 255, 1)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const locationName = labels[index];
                        console.log(`🖱️ Chart clicked: ${locationName} at level ${level}`);
                        drillDown(locationName, level);
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: `Penjualan per ${getLevelLabel(level)}` + (state.currentParent ? ` - ${state.currentParent}` : ''),
                        color: '#fff',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                return getMetricLabel(metric) + ': ' + formatValue(value, metric);
                            },
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const item = data[index];
                                const lines = [];

                                if (metric !== 'revenue') {
                                    lines.push('Revenue: ' + formatCurrency(parseFloat(item.total_revenue || 0)));
                                }
                                if (metric !== 'margin') {
                                    lines.push('Margin: ' + formatCurrency(parseFloat(item.total_margin || 0)));
                                }
                                if (metric !== 'transactions') {
                                    lines.push('Transaksi: ' + formatNumber(parseInt(item.transaction_count || 0)));
                                }

                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#fff',
                            callback: function(value) {
                                return formatValue(value, metric);
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff',
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        console.log('✅ Chart rendered successfully');
    }

    function drillDown(locationName, currentLevel) {
        // Determine next level
        let nextLevel;
        if (currentLevel === 'provinsi') {
            nextLevel = 'kota';
        } else if (currentLevel === 'kota') {
            nextLevel = 'kecamatan';
        } else {
            // Already at finest grain
            console.log('ℹ️ Already at finest level (kecamatan)');
            return;
        }

        console.log(`⬇️ Drilling down: ${currentLevel} "${locationName}" → ${nextLevel}`);

        // Load next level data filtered by parent
        loadDrillDownData(nextLevel, locationName, currentLevel);
    }

    function resetToLevel(level) {
        console.log(`🔄 Resetting to level: ${level}`);
        loadDrillDownData(level, null, null);
    }

    function updateStats(data) {
        const totalRevenue = data.reduce((sum, item) => sum + parseFloat(item.total_revenue || 0), 0);
        const totalMargin = data.reduce((sum, item) => sum + parseFloat(item.total_margin || 0), 0);
        const totalCogs = data.reduce((sum, item) => sum + parseFloat(item.total_cogs || 0), 0);
        const totalTransactions = data.reduce((sum, item) => sum + parseInt(item.transaction_count || 0), 0);
        const avgMarginPct = totalRevenue > 0 ? (totalMargin / totalRevenue) * 100 : 0;

        console.log('📈 Stats updated:', { totalRevenue, totalMargin, totalCogs, totalTransactions, avgMarginPct });

        const statRevenue = document.getElementById('stat-revenue');
        const statMargin = document.getElementById('stat-margin');
        const statTransactions = document.getElementById('stat-transactions');
        const statMarginPct = document.getElementById('stat-margin-pct');

        if (statRevenue) statRevenue.textContent = formatCurrency(totalRevenue);
        if (statMargin) statMargin.textContent = formatCurrency(totalMargin);
        if (statTransactions) statTransactions.textContent = formatNumber(totalTransactions);
        if (statMarginPct) statMarginPct.textContent = avgMarginPct.toFixed(2) + '%';
    }

    function updateBreadcrumbs() {
        const breadcrumb = document.getElementById('drill-breadcrumb');
        if (!breadcrumb) return;

        const ol = breadcrumb.querySelector('ol');

        // Clear existing breadcrumbs except home
        while (ol.children.length > 1) {
            ol.removeChild(ol.lastChild);
        }

        if (state.currentParent) {
            // Show breadcrumb
            breadcrumb.style.display = 'block';

            // Add parent breadcrumb
            const li = document.createElement('li');
            li.className = 'breadcrumb-item';

            const a = document.createElement('a');
            a.href = '#';
            a.className = 'drill-up';
            a.setAttribute('data-level', state.parentLevel);
            a.textContent = state.currentParent;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                console.log(`⬆️ Drilling up to: ${state.parentLevel}`);
                resetToLevel(state.parentLevel);
            });

            li.appendChild(a);
            ol.appendChild(li);

            // Add current level breadcrumb (active)
            const liActive = document.createElement('li');
            liActive.className = 'breadcrumb-item active';
            liActive.setAttribute('aria-current', 'page');
            liActive.textContent = getLevelLabel(state.currentLevel);
            ol.appendChild(liActive);

            console.log('🍞 Breadcrumbs updated:', { parent: state.currentParent, level: state.currentLevel });
        } else {
            breadcrumb.style.display = 'none';
        }
    }

    function updateLevelButtons() {
        document.querySelectorAll('[data-level]').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-level') === state.currentLevel && !state.currentParent) {
                btn.classList.add('active');
            }
        });
    }

    function showLoading(show) {
        const loading = document.getElementById('drill-loading');
        const chartContainer = document.querySelector('.chart-container');
        const statsContainer = document.getElementById('location-stats');

        if (loading) loading.style.display = show ? 'block' : 'none';
        if (chartContainer) chartContainer.style.display = show ? 'none' : 'block';
        if (statsContainer) statsContainer.style.display = show ? 'none' : 'flex';
    }

    function showEmptyState() {
        const canvas = document.getElementById('location-drilldown-chart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        state.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Tidak ada data'],
                datasets: [{
                    label: 'Data tidak tersedia',
                    data: [0],
                    backgroundColor: 'rgba(128, 128, 128, 0.3)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Tidak ada data lokasi untuk periode ini',
                        color: '#fff'
                    }
                }
            }
        });
    }

    function showError(message) {
        alert(message);
    }

    // Utility functions
    function getLevelLabel(level) {
        const labels = {
            'provinsi': 'Provinsi',
            'kota': 'Kota',
            'kecamatan': 'Kecamatan'
        };
        return labels[level] || level;
    }

    function getMetricLabel(metric) {
        const labels = {
            'revenue': 'Revenue',
            'margin': 'Margin',
            'transactions': 'Transaksi',
            'margin_pct': 'Margin %'
        };
        return labels[metric] || metric;
    }

    function formatValue(value, metric) {
        if (metric === 'margin_pct') {
            return value.toFixed(2) + '%';
        } else if (metric === 'transactions') {
            return formatNumber(value);
        } else {
            return formatCurrency(value);
        }
    }

    function formatCurrency(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(value));
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(value);
    }

    // Export for external access if needed
    window.LocationDrillDown = {
        reload: function() {
            loadDrillDownData(state.currentLevel, state.currentParent, state.parentLevel);
        },
        resetToProvinsi: function() {
            resetToLevel('provinsi');
        },
        getState: function() {
            return { ...state };
        }
    };

})();
