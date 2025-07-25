
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('images/ttLogo.png') }}" alt="Traction Tracker logo featuring the company brand identity, displayed prominently as the website header. The logo represents the business intelligence tracking platform." class="d-inline-block align-text-top">
            <span class="ms-2 brand-text"><span class="brand-primary">Traction</span> Tracker</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @if(Auth::check())
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link border-0 bg-transparent p-0">
                                Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/login') }}">Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="{{ url('/register') }}">Daftar Gratis</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>





















{{-- <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('images/ttLogo.png') }}" alt="Traction Tracker logo featuring the company brand identity, displayed prominently as the website header. The logo represents the business intelligence tracking platform." class="d-inline-block align-text-top">
            <span class="ms-2 brand-text"><span class="brand-primary">Traction</span> Tracker</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link border-0 bg-transparent p-0">
                                Keluar
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                    </li>
                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="{{ route('register') }}">Daftar Gratis</a>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>
    </div>
</nav> --}}
