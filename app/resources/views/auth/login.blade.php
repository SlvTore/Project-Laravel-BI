<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Traction Tracker</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="row h-100 g-0">
            <!-- Form Section - Left Side (4 columns) -->
            <div class="col-lg-4 col-md-6">
                <div class="form-section">
                    <div class="form-wrapper">
                        <!-- Logo and Brand -->
                        <div class="brand-section text-center mb-4">
                            <img src="{{ asset('images/ttLogo.png') }}" alt="Traction Tracker" class="brand-logo mb-3">
                            <h2 class="brand-title">Welcome Back!</h2>
                            <p class="brand-subtitle">Sign in to continue your data journey</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Login Form -->
                        <form method="POST" action="{{ route('login') }}" class="auth-form">
                            @csrf

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
                                    autofocus
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
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                                <label for="password">Password</label>
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                    <label class="form-check-label" for="remember_me">
                                        Remember me
                                    </label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-password-link">
                                        Forgot password?
                                    </a>
                                @endif
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-auth w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Sign In
                            </button>

                            <!-- Register Link -->
                            <div class="text-center">
                                <p class="register-link">
                                    Don't have an account?
                                    <a href="{{ route('register') }}" class="text-decoration-none">
                                        Create one here
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Video Section - Right Side (8 columns) -->
            <div class="col-lg-8 col-md-6">
                <div class="video-section">
                    <video autoplay muted loop class="auth-video">
                        <source src="{{ asset('videos/video1.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Video Overlay with Quote -->
                    <div class="video-overlay">
                        <div class="video-content">
                            <div class="quote-section">
                                <blockquote class="quote">
                                    <i class="bi bi-quote quote-icon"></i>
                                    <p class="quote-text">
                                        "Transform your business data into actionable insights.
                                        Make every decision count with Traction Tracker."
                                    </p>
                                    <footer class="quote-author">
                                        — Traction Tracker Team
                                    </footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
