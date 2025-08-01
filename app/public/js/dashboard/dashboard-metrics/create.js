// Dashboard Metrics Create JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const metricsContainer = document.getElementById('metricsContainer');
    const importBtn = document.getElementById('importBtn');
    const selectedSummary = document.getElementById('selectedSummary');
    const selectedMetricsList = document.getElementById('selectedMetricsList');
    const selectedCount = document.getElementById('selectedCount');
    const metricsForm = document.getElementById('metricsForm');
    const metricsSearch = document.getElementById('metricsSearch');
    const categoryFilter = document.getElementById('categoryFilter');

    let selectedMetrics = [];

    // Initialize
    updateImportButton();
    initializeCardClickHandlers();

    // Initialize card click handlers for toggling selection
    function initializeCardClickHandlers() {
        const metricCards = document.querySelectorAll('.metric-card:not(.disabled)');
        metricCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                toggleCardSelection(card);
            });

            // Add pointer cursor
            card.style.cursor = 'pointer';
        });
    }

    // Toggle card selection
    function toggleCardSelection(card) {
        const checkbox = card.querySelector('input[type="checkbox"]');
        const metricName = card.getAttribute('data-metric-name');

        if (!checkbox || checkbox.disabled) return;

        // Toggle checkbox state
        checkbox.checked = !checkbox.checked;

        // Update visual state
        card.classList.toggle('selected', checkbox.checked);

        // Update selected metrics array
        if (checkbox.checked) {
            if (!selectedMetrics.includes(metricName)) {
                selectedMetrics.push(metricName);
            }
        } else {
            selectedMetrics = selectedMetrics.filter(name => name !== metricName);
        }

        updateSelectedSummary();
        updateImportButton();
    }

    // Update selected metrics summary
    function updateSelectedSummary() {
        if (selectedMetrics.length > 0) {
            selectedSummary.style.display = 'block';
            selectedCount.textContent = selectedMetrics.length;

            // Group metrics by category
            const categoryCount = {};
            selectedMetrics.forEach(metricName => {
                const card = document.querySelector(`[data-metric-name="${metricName}"]`);
                const category = card.getAttribute('data-category');
                categoryCount[category] = (categoryCount[category] || 0) + 1;
            });

            // Generate summary HTML
            let summaryHTML = '<div class="row g-2">';
            Object.keys(categoryCount).forEach(category => {
                const count = categoryCount[category];
                summaryHTML += `
                    <div class="col-auto">
                        <span class="badge bg-primary me-1">${category}</span>
                        <small class="text-muted">${count} metric${count > 1 ? 's' : ''}</small>
                    </div>
                `;
            });
            summaryHTML += '</div>';
            selectedMetricsList.innerHTML = summaryHTML;

        } else {
            selectedSummary.style.display = 'none';
        }
    }
    function updateCardStyle(checkbox) {
        const card = checkbox.closest('.metric-card');
        if (card) {
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }
    }

    function updateSelectedMetrics() {
        selectedMetrics = [];
        checkboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                selectedMetrics.push({
                    name: checkbox.value,
                    category: checkbox.dataset.category
                });
            }
        });
    }

    function updateSelectedSummary() {
        if (selectedMetrics.length > 0) {
            selectedSummary.style.display = 'block';

            // Group metrics by category
            const groupedMetrics = selectedMetrics.reduce((acc, metric) => {
                if (!acc[metric.category]) {
                    acc[metric.category] = [];
                }
                acc[metric.category].push(metric.name);
                return acc;
            }, {});

            // Create summary HTML
            let summaryHTML = '';
            Object.keys(groupedMetrics).forEach(category => {
                summaryHTML += `<div class="mb-2">
                    <strong class="text-primary">${category}:</strong>
                    <span class="ms-2">${groupedMetrics[category].join(', ')}</span>
                </div>`;
            });

            selectedMetricsList.innerHTML = summaryHTML;
            selectedCount.textContent = selectedMetrics.length;
        } else {
            selectedSummary.style.display = 'none';
        }
    }

    function updateImportButton() {
        if (importBtn) {
            if (selectedMetrics.length > 0) {
                importBtn.disabled = false;
                importBtn.innerHTML = `
                    <i class="bi bi-plus-circle me-2"></i>
                    Import ${selectedMetrics.length} Metric${selectedMetrics.length > 1 ? 's' : ''}
                `;
            } else {
                importBtn.disabled = true;
                importBtn.innerHTML = `
                    <i class="bi bi-plus-circle me-2"></i>
                    Pilih Metrics untuk Import
                `;
            }
        }
    }

    // Search functionality
    if (metricsSearch) {
        metricsSearch.addEventListener('input', function() {
            filterMetrics();
        });
    }

    // Category filter functionality
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterMetrics();
        });
    }

    function filterMetrics() {
        const searchTerm = metricsSearch ? metricsSearch.value.toLowerCase() : '';
        const selectedCategory = categoryFilter ? categoryFilter.value : '';
        const metricItems = document.querySelectorAll('.metric-item');

        metricItems.forEach(item => {
            const metricCard = item.querySelector('.metric-card');
            const metricTitle = metricCard.querySelector('.card-title').textContent.toLowerCase();
            const metricCategory = item.dataset.category;
            const metricDescription = metricCard.querySelector('.card-text').textContent.toLowerCase();

            const matchesSearch = metricTitle.includes(searchTerm) || metricDescription.includes(searchTerm);
            const matchesCategory = !selectedCategory || metricCategory === selectedCategory;

            if (matchesSearch && matchesCategory) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Add visual feedback for card selection
    document.querySelectorAll('.metric-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on checkbox or label
            if (e.target.type === 'checkbox' || e.target.tagName === 'LABEL') {
                return;
            }

            const checkbox = this.querySelector('input[type="checkbox"]');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));

                // Update card visual state
                if (checkbox.checked) {
                    this.classList.add('selected');
                } else {
                    this.classList.remove('selected');
                }
            }
        });

        // Initial state check
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
            card.classList.add('selected');
        }
    });

    // Form submission with loading state
    if (metricsForm) {
        metricsForm.addEventListener('submit', function(e) {
            if (selectedMetrics.length === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal satu metric untuk diimport.');
                return;
            }

            // Show loading state
            if (importBtn) {
                importBtn.disabled = true;
                importBtn.innerHTML = `
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Mengimport Metrics...
                `;
            }
        });
    }
});
