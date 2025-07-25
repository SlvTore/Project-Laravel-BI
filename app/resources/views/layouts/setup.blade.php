<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Traction Tracker - Dashboard')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .main-content {
            min-height: 100vh;
        }
        .wizard-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .role-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .role-card.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .role-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 15px;
        }
    </style>

    @stack('styles')
</head>
<body>
    @auth
        @if(!auth()->user()->setup_completed)
            {{-- Layout untuk Setup Wizard --}}
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-4 col-lg-3 sidebar text-white d-flex flex-column">
                        <div class="p-4">
                            <h4 class="fw-bold mb-0">
                                <i class="bi bi-bar-chart-line-fill me-2"></i>
                                Traction Tracker
                            </h4>
                            <small class="opacity-75">Setup Akun Anda</small>
                        </div>

                        <div class="flex-grow-1 d-flex flex-column justify-content-center p-4">
                            <div class="text-center">
                                <i class="bi bi-person-gear fs-1 mb-3 opacity-75"></i>
                                <h5 class="fw-semibold">Selamat Datang!</h5>
                                <p class="opacity-75 small">
                                    Mari selesaikan setup akun Anda untuk mendapatkan akses penuh ke dashboard
                                </p>
                            </div>
                        </div>

                        <div class="p-4 border-top border-light border-opacity-25">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-white bg-opacity-25 rounded-circle p-2">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-semibold small">{{ auth()->user()->name }}</div>
                                    <div class="opacity-75" style="font-size: 0.75rem;">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 col-lg-9 main-content">
                        <main class="p-4">
                            @yield('content')
                        </main>
                    </div>
                </div>
            </div>
        @else
            {{-- Layout untuk Dashboard (nanti) --}}
            <div class="container-fluid">
                <main class="p-4">
                    @yield('content')
                </main>
            </div>
        @endif
    @else
        {{-- Redirect ke login jika tidak authenticated --}}
        <script>window.location.href = "{{ route('login') }}";</script>
    @endauth

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
