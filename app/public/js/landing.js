document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll behavior
    initNavbarScrollBehavior();

    // Navbar scroll behavior
    function initNavbarScrollBehavior() {
        const navbar = document.getElementById('mainNavbar');
        if (!navbar) return;

        function updateNavbar() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > 50) {
                navbar.classList.add('navbar-scrolled');
                navbar.classList.remove('navbar-top');
            } else {
                navbar.classList.remove('navbar-scrolled');
                navbar.classList.add('navbar-top');
            }
        }

        // Initial check
        updateNavbar();

        // Add scroll listener
        let ticking = false;
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateNavbar);
                ticking = true;
            }
        }

        window.addEventListener('scroll', () => {
            requestTick();
            ticking = false;
        }, { passive: true });
    }

    // Initialize parallax backgrounds
    initParallaxBackgrounds();

    // Parallax scroll effect
    let ticking = false;

    function updateParallax() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.parallax-overlay-green, .parallax-overlay-blue');

        parallaxElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const elementTop = rect.top + scrolled;
            const windowHeight = window.innerHeight;

            // Only apply parallax if element is in viewport
            if (rect.bottom >= 0 && rect.top <= windowHeight) {
                const yPos = -(scrolled - elementTop) * 0.5;
                element.style.backgroundPosition = `center ${yPos}px`;
            }
        });

        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }

    // Add scroll listener for parallax
    window.addEventListener('scroll', requestTick, { passive: true });

    // Initialize parallax backgrounds from data attributes
    function initParallaxBackgrounds() {
        const parallaxElements = document.querySelectorAll('[data-bg-image]');

        parallaxElements.forEach(element => {
            const bgImage = element.getAttribute('data-bg-image');
            if (bgImage) {
                element.style.backgroundImage = `url(${bgImage})`;
            }
        });
    }

    // Counter Animation with Intersection Observer
    let countersAnimated = false;

    function animateCounters() {
        if (countersAnimated) return;

        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2500;
            const increment = target / (duration / 16);
            let current = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }

                // Add comma formatting for large numbers
                const displayValue = Math.floor(current);
                counter.textContent = displayValue.toLocaleString();
            }, 16);
        });

        countersAnimated = true;
    }

    // Enhanced Testimonial Slider
    let currentTestimonial = 0;
    const testimonials = document.querySelectorAll('.testimonial-card');
    const totalTestimonials = testimonials.length;
    let autoSlideInterval;

    function showTestimonial(index) {
        testimonials.forEach((card, i) => {
            card.classList.remove('active');
            if (i === index) {
                card.classList.add('active');
            }
        });
    }

    function nextTestimonial() {
        currentTestimonial = (currentTestimonial + 1) % totalTestimonials;
        showTestimonial(currentTestimonial);
    }

    function prevTestimonial() {
        currentTestimonial = (currentTestimonial - 1 + totalTestimonials) % totalTestimonials;
        showTestimonial(currentTestimonial);
    }

    function startAutoSlide() {
        autoSlideInterval = setInterval(nextTestimonial, 5000);
    }

    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
    }

    // Initialize testimonial slider
    if (testimonials.length > 0) {
        showTestimonial(0);
        startAutoSlide();

        // Navigation buttons
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                stopAutoSlide();
                prevTestimonial();
                startAutoSlide();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                stopAutoSlide();
                nextTestimonial();
                startAutoSlide();
            });
        }

        // Pause auto-slide on hover
        const testimonialSection = document.querySelector('.testimonial-section');
        if (testimonialSection) {
            testimonialSection.addEventListener('mouseenter', stopAutoSlide);
            testimonialSection.addEventListener('mouseleave', startAutoSlide);
        }
    }

    // Enhanced Scroll Animations with Intersection Observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');

                // Trigger counter animation when achievement section is visible
                if (entry.target.classList.contains('achievement-item')) {
                    animateCounters();
                }
            }
        });
    }, observerOptions);

    // Observe elements for scroll animations
    document.querySelectorAll('.feature-card, .product-card, .achievement-item, .testimonial-card, .about-text, .about-image-placeholder').forEach(el => {
        el.classList.add('scroll-animate');
        observer.observe(el);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Contact form submission
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Mengirim...';
            submitBtn.disabled = true;

            // Simulate form submission
            setTimeout(() => {
                alert('Terima kasih! Pesan Anda telah terkirim. Tim kami akan segera menghubungi Anda.');
                this.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }

    // Optimize scroll performance
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Re-initialize parallax on resize
            initParallaxBackgrounds();
        }, 250);
    });

    // Preload critical images - will be set by blade template
    if (window.criticalImages) {
        window.criticalImages.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }

     // Inisialisasi Testimonial Swiper
    const testimonialSwiper = new Swiper('.testimonial-swiper', {
        // Optional parameters
        effect: 'coverflow',
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: 'auto',
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        coverflowEffect: {
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: true,
        },

        // If we need pagination
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },

        // Navigation arrows
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
});
