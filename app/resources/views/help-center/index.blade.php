@extends('layouts.dashboard')

@section('title', 'Help Center - Traction Tracker')

@section('content')
<div class="dashboard-content ms-4">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Help Center</h1>
                <p class="content-subtitle">Find answers, learn features, and get support</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <div class="content-body">
        <!-- App Information Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle me-2"></i>
                            About {{ $appInfo['name'] }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <p class="mb-3">{{ $appInfo['description'] }}</p>
                                <h6 class="fw-semibold text-primary mb-3">Key Features:</h6>
                                <div class="row">
                                    @foreach($appInfo['features'] as $feature)
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                <span>{{ $feature }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="app-info-stats bg-light p-3 rounded">
                                    <h6 class="fw-semibold mb-3">Application Details</h6>
                                    <div class="mb-2">
                                        <small class="text-muted">Version</small>
                                        <div class="fw-semibold">{{ $appInfo['version'] }}</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Platform</small>
                                        <div class="fw-semibold">Web Application</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Technology</small>
                                        <div class="fw-semibold">Laravel + Bootstrap</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Use Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-compass me-2"></i>
                            How to Use Traction Tracker
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="usage-step text-center">
                                    <div class="step-icon mx-auto mb-3">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                    <h6 class="fw-semibold">1. Setup Your Account</h6>
                                    <p class="text-muted small">Create your business profile and invite team members to start collaborating.</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="usage-step text-center">
                                    <div class="step-icon mx-auto mb-3">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <h6 class="fw-semibold">2. Add Your Metrics</h6>
                                    <p class="text-muted small">Define and track the key performance indicators that matter to your business.</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="usage-step text-center">
                                    <div class="step-icon mx-auto mb-3">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <h6 class="fw-semibold">3. Monitor Progress</h6>
                                    <p class="text-muted small">Use the dashboard to visualize your data and track performance over time.</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="usage-step text-center">
                                    <div class="step-icon mx-auto mb-3">
                                        <i class="bi bi-trophy"></i>
                                    </div>
                                    <h6 class="fw-semibold">4. Achieve Goals</h6>
                                    <p class="text-muted small">Make data-driven decisions to improve your business performance.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-question-circle me-2"></i>
                            Frequently Asked Questions
                        </h5>
                        <p class="card-subtitle">Find quick answers to common questions</p>
                    </div>
                    <div class="card-body">
                        @foreach($faqs as $categoryIndex => $category)
                            <div class="faq-category mb-4">
                                <h6 class="fw-semibold text-white mb-3">
                                    <i class="bi bi-folder me-2"></i>
                                    {{ $category['category'] }}
                                </h6>
                                <div class="accordion" id="faqAccordion{{ $categoryIndex }}">
                                    @foreach($category['questions'] as $questionIndex => $faq)
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading{{ $categoryIndex }}{{ $questionIndex }}">
                                                <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $categoryIndex }}{{ $questionIndex }}"
                                                        aria-expanded="false"
                                                        aria-controls="collapse{{ $categoryIndex }}{{ $questionIndex }}">
                                                    {{ $faq['question'] }}
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $categoryIndex }}{{ $questionIndex }}"
                                                 class="accordion-collapse collapse"
                                                 aria-labelledby="heading{{ $categoryIndex }}{{ $questionIndex }}"
                                                 data-bs-parent="#faqAccordion{{ $categoryIndex }}">
                                                <div class="accordion-body">
                                                    {{ $faq['answer'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-headset me-2"></i>
                            Need More Help?
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-4">Can't find what you're looking for? Our support team is here to help!</p>
                        <div class="row justify-content-center">
                            <div class="col-md-4 mb-3">
                                <div class="support-option">
                                    <i class="bi bi-whatsapp display-6 text-success mb-2"></i>
                                    <h6 class="fw-semibold">WhatsApp Support</h6>
                                    <p class="text-muted small">Get instant help via WhatsApp</p>
                                    <button class="btn btn-success btn-sm" id="whatsappSupport">
                                        <i class="bi bi-whatsapp me-1"></i>
                                        Chat Now
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="support-option">
                                    <i class="bi bi-envelope display-6 text-primary mb-2"></i>
                                    <h6 class="fw-semibold">Email Support</h6>
                                    <p class="text-muted small">Send us a detailed message</p>
                                    <a href="mailto:support@tractiontracker.com" class="btn btn-primary btn-sm">
                                        <i class="bi bi-envelope me-1"></i>
                                        Send Email
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="support-option">
                                    <i class="bi bi-book display-6 text-info mb-2"></i>
                                    <h6 class="fw-semibold">Documentation</h6>
                                    <p class="text-muted small">Browse our comprehensive guides</p>
                                    <a href="#" class="btn btn-info btn-sm">
                                        <i class="bi bi-book me-1"></i>
                                        View Docs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Chat Bubble -->
<div class="whatsapp-bubble" id="whatsappBubble">
    <div class="bubble-content" id="bubbleContent">
        <i class="bi bi-whatsapp"></i>
    </div>
    <div class="bubble-tooltip" id="bubbleTooltip">
        Need help? Chat with us!
    </div>
</div>

<style>
/* Help Center Styles */
.usage-step {
    height: 100%;
    padding: 1rem;
    transition: transform 0.3s ease;
}

.usage-step:hover {
    transform: translateY(-5px);
}

.step-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.faq-category {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 1.5rem;
}

.faq-category:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.accordion-button {
    background-color: #f8f9fa;
    border: none;
    color: #333;
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background-color: #e3f2fd;
    border-color: #007bff;
    color: #007bff;
}

.accordion-button:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

.support-option {
    padding: 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.support-option:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
}

/* WhatsApp Bubble Styles */
.whatsapp-bubble {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    cursor: pointer;
}

.bubble-content {
    width: 60px;
    height: 60px;
    background: #25d366;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8rem;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.bubble-content:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
}

.bubble-tooltip {
    position: absolute;
    bottom: 70px;
    right: 0;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    white-space: nowrap;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.bubble-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 20px;
    border: 6px solid transparent;
    border-top-color: #333;
}

.whatsapp-bubble:hover .bubble-tooltip {
    opacity: 1;
    transform: translateY(0);
}

@keyframes pulse {
    0% {
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    }
    50% {
        box-shadow: 0 4px 20px rgba(37, 211, 102, 0.8);
    }
    100% {
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    }
}

.app-info-stats {
    border: 1px solid #e9ecef;
}

.card-subtitle {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0;
    margin-top: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // WhatsApp support functionality
    const whatsappBubble = document.getElementById('whatsappBubble');
    const whatsappSupport = document.getElementById('whatsappSupport');

    const openWhatsApp = function() {
        // Replace with your actual WhatsApp business number
        const phoneNumber = '1234567890'; // Your WhatsApp business number
        const message = encodeURIComponent('Hello! I need help with Traction Tracker. Can you assist me?');
        const whatsappUrl = `https://wa.me/${phoneNumber}?text=${message}`;
        window.open(whatsappUrl, '_blank');
    };

    if (whatsappBubble) {
        whatsappBubble.addEventListener('click', openWhatsApp);
    }

    if (whatsappSupport) {
        whatsappSupport.addEventListener('click', openWhatsApp);
    }

    // Smooth scrolling for FAQ navigation
    const faqButtons = document.querySelectorAll('.accordion-button');
    faqButtons.forEach(button => {
        button.addEventListener('click', function() {
            setTimeout(() => {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target && !target.classList.contains('show')) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        });
    });
});
</script>
@endsection
