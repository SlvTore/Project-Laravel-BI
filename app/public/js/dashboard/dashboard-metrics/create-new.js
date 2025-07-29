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

    // Update import button state
    function updateImportButton() {
        if (!importBtn) return;

        if (selectedMetrics.length > 0) {
            importBtn.disabled = false;
            importBtn.classList.add('animate-bounce');
        } else {
            importBtn.disabled = true;
            importBtn.classList.remove('animate-bounce');
        }
    }

    // Search functionality
    if (metricsSearch) {
        metricsSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterMetrics(searchTerm, categoryFilter ? categoryFilter.value : '');
        });
    }

    // Category filter functionality
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const searchTerm = metricsSearch ? metricsSearch.value.toLowerCase() : '';
            filterMetrics(searchTerm, this.value);
        });
    }

    // Filter metrics based on search and category
    function filterMetrics(searchTerm, selectedCategory) {
        const metricItems = document.querySelectorAll('.metric-item');

        metricItems.forEach(item => {
            const card = item.querySelector('.metric-card');
            const metricName = card.getAttribute('data-metric-name');
            const category = card.getAttribute('data-category');

            // Check if item matches search term
            const matchesSearch = !searchTerm ||
                metricName.toLowerCase().includes(searchTerm) ||
                category.toLowerCase().includes(searchTerm);

            // Check if item matches category filter
            const matchesCategory = !selectedCategory || category === selectedCategory;

            // Show/hide item based on filters
            if (matchesSearch && matchesCategory) {
                item.style.display = 'block';
                item.style.opacity = '1';
            } else {
                item.style.display = 'none';
                item.style.opacity = '0';
            }
        });
    }

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
                importBtn.innerHTML = `
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Mengimport Metrics...
                `;
                importBtn.disabled = true;
            }
        });
    }

    // Add animation class
    const style = document.createElement('style');
    style.textContent = `
        .animate-bounce {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    `;
    document.head.appendChild(style);
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
