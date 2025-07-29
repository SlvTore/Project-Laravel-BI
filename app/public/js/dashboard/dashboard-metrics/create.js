// Dashboard Metrics Create JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const metricsContainer = document.getElementById('metricsContainer');
    const importBtn = document.getElementById('importBtn');
    const selectedSummary = document.getElementById('selectedSummary');
    const selectedMetricsList = document.getElementById('selectedMetricsList');
    const selectedCount = document.getElementById('selectedCount');
    const metricsForm = document.getElementById('metricsForm');

    let selectedMetrics = [];

    // Initialize
    updateImportButton();

    // Add event listeners to all metric checkboxes
    const checkboxes = document.querySelectorAll('input[name="selected_metrics[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedMetrics();
            updateSelectedSummary();
            updateImportButton();
        });
    });

    // Toggle metric selection when card is clicked
    window.toggleMetric = function(checkboxId) {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox && !checkbox.disabled) {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        }
    };

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
                Select Metrics to Import
            `;
        }
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
                alert('Please select at least one metric to import.');
                return;
            }

            // Show loading state
            importBtn.disabled = true;
            importBtn.innerHTML = `
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Importing Metrics...
            `;
        });
    }

    // Search and filter functionality
    const searchInput = document.getElementById('metricsSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterMetrics(searchTerm);
        });
    }

    function filterMetrics(searchTerm) {
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const description = card.querySelector('.card-text').textContent.toLowerCase();

            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                card.closest('.col').style.display = 'block';
            } else {
                card.closest('.col').style.display = 'none';
            }
        });
    }

    // Category filter
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function(e) {
            const selectedCategory = e.target.value;
            filterByCategory(selectedCategory);
        });
    }

    function filterByCategory(category) {
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            const cardCategory = checkbox ? checkbox.dataset.category : '';

            if (!category || cardCategory.toLowerCase() === category.toLowerCase()) {
                card.closest('.col').style.display = 'block';
            } else {
                card.closest('.col').style.display = 'none';
            }
        });
    }
});

// Global function for onclick events
function toggleMetric(metricId) {
    const checkbox = document.getElementById(metricId);
    const card = checkbox.closest('.metric-card');

    // Don't allow interaction with disabled cards
    if (card.classList.contains('disabled') || checkbox.disabled) {
        return;
    }

    checkbox.checked = !checkbox.checked;

    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }

    // Trigger change event
    checkbox.dispatchEvent(new Event('change'));
}
