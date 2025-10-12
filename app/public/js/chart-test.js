// Test ApexCharts functionality independently
console.log('🔧 Testing chart functionality...');

// Check if ApexCharts is loaded
if (typeof ApexCharts === 'undefined') {
    console.error('❌ ApexCharts not loaded');
} else {
    console.log('✅ ApexCharts available');
}

// Test the chart data endpoint
function testChartEndpoint() {
    const businessMetricId = window.businessMetricId || 1; // fallback for testing
    console.log('🔍 Testing chart endpoint for metric:', businessMetricId);

    fetch(`/dashboard/metrics/${businessMetricId}/chart-data?period=30`)
        .then(response => {
            console.log('📡 Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Chart data endpoint working:', data);

            if (data.success && data.data) {
                console.log('📊 Chart data structure:', {
                    dates: data.data.dates?.length || 0,
                    labels: data.data.labels?.length || 0,
                    values: data.data.values?.length || 0
                });
            }
            return data;
        })
        .catch(error => {
            console.error('❌ Chart data endpoint failed:', error);
        });
}

// Simple chart initialization test
function testChartInit() {
    const chartElement = document.querySelector('#trendChart');
    if (!chartElement) {
        console.error('❌ Chart element #trendChart not found');
        return;
    }

    console.log('✅ Chart element found');

    if (typeof ApexCharts !== 'undefined') {
        const options = {
            series: [{
                name: 'Test Data',
                data: [10, 20, 30, 40, 50]
            }],
            chart: {
                type: 'line',
                height: 300
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May']
            }
        };

        const chart = new ApexCharts(chartElement, options);
        chart.render().then(() => {
            console.log('✅ Test chart rendered successfully');
        }).catch(err => {
            console.error('❌ Test chart render failed:', err);
        });
    }
}

// Run tests when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Chart test script loaded');
    testChartEndpoint();

    // Wait a bit for ApexCharts to load, then test
    setTimeout(testChartInit, 2000);
});
