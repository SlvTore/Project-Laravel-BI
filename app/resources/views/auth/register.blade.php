<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - Traction Tracker</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="row h-100 g-0">
            <!-- Video Section - Left Side (8 columns) -->
            <div class="col-lg-8 col-md-6">
                <div class="video-section">
                    <video autoplay muted loop class="auth-video">
                        <source src="{{ asset('videos/video2.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Video Overlay with Quote -->
                    <div class="video-overlay">
                        <div class="video-content">
                            <div class="quote-section">
                                <blockquote class="quote">
                                    <i class="bi bi-quote quote-icon"></i>
                                    <p class="quote-text">
                                        "Start your journey to data-driven success.
                                        Join thousands of businesses growing with smart analytics."
                                    </p>
                                    <footer class="quote-author">
                                        — Traction Tracker Community
                                    </footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Section - Right Side (4 columns) -->
            <div class="col-lg-4 col-md-6">
                <div class="form-section">
                    <div class="form-wrapper">
                        <!-- Logo and Brand -->
                        <div class="brand-section text-center mb-3">
                            <img src="{{ asset('images/ttLogo.png') }}" alt="Traction Tracker" class="brand-logo mb-2">
                            <h2 class="brand-title">Join Traction Tracker</h2>
                            <p class="brand-subtitle">Create your account and start tracking</p>
                        </div>

                        <!-- Invitation Notice -->
                        @if($invitationActive ?? false)
                        <div class="alert alert-info mb-3" role="alert">
                            <i class="bi bi-envelope-check me-2"></i>
                            <strong>You're invited!</strong><br>
                            @if($inviterName ?? false)
                                {{ $inviterName }} invited you to join
                            @else
                                You've been invited to join
                            @endif
                            <strong>{{ $businessName ?? 'a business' }}</strong>
                        </div>
                        @endif

                        <!-- Register Form -->
                        <form method="POST" action="{{ route('register') }}" class="auth-form">
                            @csrf

                            <!-- Name -->
                            <div class="form-floating mb-3">
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="Enter your full name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                >
                                <label for="name">Full Name</label>
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Email Address -->
                            <div class="form-floating mb-3">
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="Enter your email"
                                    required
                                    autocomplete="username"
                                >
                                <label for="email">Email Address</label>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="form-floating mb-3">
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Create a password"
                                    required
                                    autocomplete="new-password"
                                >
                                <label for="password">Password</label>
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-floating mb-3">
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    placeholder="Confirm your password"
                                    required
                                    autocomplete="new-password"
                                >
                                <label for="password_confirmation">Confirm Password</label>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-auth w-100 mb-2">
                                <i class="bi bi-person-plus me-2"></i>
                                Create Account
                            </button>

                            <!-- Login Link -->
                            <div class="text-center">
                                <p class="login-link">
                                    Already have an account?
                                    <a href="{{ route('login') }}" class="text-decoration-none">
                                        Sign in here
                                    </a>
                                </p>
                            </div>

                            <!-- Terms and Privacy -->
                            <div class="text-center mt-2">
                                <p class="terms-text">
                                    By creating an account, you agree to our
                                    <a href="#" class="text-decoration-none">Terms of Service</a>
                                    and
                                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
