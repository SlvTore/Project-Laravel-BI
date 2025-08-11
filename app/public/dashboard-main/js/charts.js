// Dashboard Main - Charts Management
document.addEventListener('DOMContentLoaded', function() {
    // Render Combined Metrics Chart
    if (document.getElementById('combinedMetricsChart')) {
        if (combinedChartData.labels && combinedChartData.labels.length > 0) {
            const combinedOptions = {
                series: combinedChartData.datasets.map(dataset => ({
                    name: dataset.label,
                    data: dataset.data
                })),
                chart: {
                    type: 'line',
                    height: 200,
                    background: 'transparent',
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#20c997'],
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    categories: combinedChartData.labels,
                    labels: {
                        style: {
                            colors: '#ffffff'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#ffffff'
                        }
                    }
                },
                grid: {
                    borderColor: 'rgba(255, 255, 255, 0.1)'
                },
                legend: {
                    labels: {
                        colors: '#ffffff'
                    }
                },
                theme: {
                    mode: 'dark'
                }
            };

            const combinedChart = new ApexCharts(document.querySelector("#combinedMetricsChart"), combinedOptions);
            combinedChart.render();
        } else {
            // Show empty chart with X/Y axes only
            const emptyOptions = {
                series: [],
                chart: {
                    type: 'line',
                    height: 200,
                    background: 'transparent',
                    toolbar: {
                        show: false
                    }
                },
                xaxis: {
                    categories: ['Day 1', 'Day 2', 'Day 3'],
                    labels: {
                        style: {
                            colors: '#ffffff'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#ffffff'
                        }
                    }
                },
                grid: {
                    borderColor: 'rgba(255, 255, 255, 0.1)'
                },
                theme: {
                    mode: 'dark'
                },
                noData: {
                    text: 'No data available',
                    style: {
                        color: '#ffffff'
                    }
                }
            };

            const emptyChart = new ApexCharts(document.querySelector("#combinedMetricsChart"), emptyOptions);
            emptyChart.render();
        }
    }

    // Render Mini Charts for Each Metric Card
    if (typeof metricsChartData !== 'undefined') {
        metricsChartData.forEach(function(metric, index) {
            const chartId = '#metricChart' + metric.id;
            const chartElement = document.querySelector(chartId);

            if (chartElement) {
                if (metric.chart_data && metric.chart_data.length > 0) {
                    const miniOptions = {
                        series: [{
                            name: metric.metric_name,
                            data: metric.chart_data
                        }],
                        chart: {
                            type: 'line',
                            height: 60,
                            background: 'transparent',
                            sparkline: {
                                enabled: true
                            }
                        },
                        colors: ['#ffffff'],
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.7,
                                opacityTo: 0.1,
                                colorStops: [{
                                    offset: 0,
                                    color: '#ffffff',
                                    opacity: 0.4
                                }, {
                                    offset: 100,
                                    color: '#ffffff',
                                    opacity: 0.1
                                }]
                            }
                        },
                        xaxis: {
                            categories: metric.chart_labels || []
                        }
                    };

                    const miniChart = new ApexCharts(chartElement, miniOptions);
                    miniChart.render();
                } else {
                    // Show empty mini chart
                    const emptyMiniOptions = {
                        series: [],
                        chart: {
                            type: 'line',
                            height: 60,
                            background: 'transparent',
                            sparkline: {
                                enabled: true
                            }
                        },
                        colors: ['#ffffff'],
                        stroke: {
                            curve: 'smooth',
                            width: 1
                        },
                        grid: {
                            show: true,
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        },
                        noData: {
                            text: 'No data',
                            style: {
                                color: '#ffffff',
                                fontSize: '10px'
                            }
                        }
                    };

                    const emptyMiniChart = new ApexCharts(chartElement, emptyMiniOptions);
                    emptyMiniChart.render();
                }
            }
        });
    }
});