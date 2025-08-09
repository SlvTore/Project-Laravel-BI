<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Traction Tracker'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Enhanced UI CSS -->
    <link rel="stylesheet" href="{{ asset('css/enhanced-ui.css') }}">
    <!-- Custom Dashboard CSS -->
    <style>
        :root {
            --primary-color: #7cb947;
            --secondary-color: #1e3c80;
            --dark-bg: rgba(30, 60, 128, 0.95);
            --card-bg: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.05);
        }

        body {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-content {
            flex: 1;
            padding: 2rem;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .dashboard-content.sidebar-open {
            margin-left: 280px;
        }

        .content-header {
            margin-bottom: 2rem;
        }

        .content-title {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .dashboard-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
        }

        .dashboard-card .card-body {
            padding: 1.5rem;
        }

        .card-title {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(124, 185, 71, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #6ca63a;
            border-color: #6ca63a;
        }

        .btn-outline-secondary {
            color: rgba(255, 255, 255, 0.8);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
        }

        .alert {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(124, 185, 71, 0.2);
            border-color: rgba(124, 185, 71, 0.3);
        }

        .badge {
            font-size: 0.75rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
                margin-left: 0 !important;
            }

            .content-title {
                font-size: 1.5rem;
            }

            .dashboard-card .card-header,
            .dashboard-card .card-body {
                padding: 1rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="@if(Auth::check() && Auth::user()->isStaff()) staff-mode @endif">
    <div class="dashboard-wrapper
        @if(Auth::check())
            role-{{ strtolower(str_replace([' ', '-'], '', Auth::user()->userRole->name ?? 'user')) }}
        @endif">
        <!-- Include Sidebar Navigation - Fixed Path -->
        @include('layouts.partials.dashboardNav')

        <!-- Main Content -->
        <div class="dashboard-content" id="dashboardContent">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Dashboard JavaScript -->
    <script>
        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('dashboardContent');
            const toggleBtn = document.getElementById('sidebarToggle');

            if (sidebar && content) {
                sidebar.classList.toggle('active');
                content.classList.toggle('sidebar-open');

                // Store sidebar state in localStorage
                const isOpen = sidebar.classList.contains('active');
                localStorage.setItem('sidebarOpen', isOpen);

                // Update toggle button icon
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.className = isOpen ? 'bi bi-x' : 'bi bi-list';
                    }
                }
            }
        }

        // Initialize sidebar state from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarOpen = localStorage.getItem('sidebarOpen');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('dashboardContent');

            if (sidebarOpen === 'true' && sidebar && content) {
                sidebar.classList.add('active');
                content.classList.add('sidebar-open');

                const toggleBtn = document.getElementById('sidebarToggle');
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.className = 'bi bi-x';
                    }
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
