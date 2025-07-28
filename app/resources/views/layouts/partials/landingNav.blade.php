<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            <img src="{{ asset('images/ttLogo.png') }}" alt="Traction Tracker" style="height: 75px;">
            <span  class="ms-2 mt-2" style="color: #1e3c80;">Traction <span style="color: #7cb947;">Tracker</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center me-4 mt-2">
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3" href="{{ url('/') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3" href="#pricing">Pricing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-medium px-3" href="#contact">Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mt-2">
                <a href="{{ route('login') }}" class="btn btn-link nav-login-btn text-decoration-none fw-medium px-3 py-2">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-medium shadow-sm">Get Started</a>
            </div>
        </div>
    </div>
</nav>
