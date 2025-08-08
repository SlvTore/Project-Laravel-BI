<!-- Side Navigation with Collapse and Hover Effect -->
<nav id="sidebar" class="sidebar">
    <div class="sidebar-container">
        <!-- Logo Section -->
        <div class="sidebar-brand">
            <div class="brand-content">
                <img src="{{ asset('images/ttLogo.png') }}" alt="TT" class="brand-logo">
                <span class="brand-text">Traction Tracker</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="sidebar-nav">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                    <i class="bi bi-grid-fill nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Metrics (accessible to Business Owner, Administrator, Staff) -->
            @if(auth()->user()->isBusinessOwner() || auth()->user()->isAdministrator() || auth()->user()->isStaff())
            <li class="nav-item">
                <a href="{{ route('dashboard.metrics') }}" class="nav-link {{ request()->routeIs('dashboard.metrics*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow nav-icon"></i>
                    <span class="nav-text">Metrics</span>
                </a>
            </li>
            @endif

            <!-- Data Feeds (accessible to Business Owner, Administrator, Staff) -->
            @if(auth()->user()->isBusinessOwner() || auth()->user()->isAdministrator() || auth()->user()->isStaff())
            <li class="nav-item">
                <a href="{{ route('dashboard.feeds') }}" class="nav-link {{ request()->routeIs('dashboard.feeds*') ? 'active' : '' }}">
                    <i class="bi bi-rss nav-icon"></i>
                    <span class="nav-text">Data Feeds</span>
                </a>
            </li>
            @endif

            <!-- Users (only accessible to Business Owner and Administrator) -->
            @if(auth()->user()->canManageUsers())
            <li class="nav-item">
                <a href="{{ route('dashboard.users') }}" class="nav-link {{ request()->routeIs('dashboard.users*') ? 'active' : '' }}">
                    <i class="bi bi-people nav-icon"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>
            @endif

            <!-- Notifications -->
            <li class="nav-item">
                <a href="{{ route('dashboard.notifications') }}" class="nav-link {{ request()->routeIs('dashboard.notifications*') ? 'active' : '' }}">
                    <i class="bi bi-bell nav-icon"></i>
                    <span class="nav-text">Notifications</span>
                </a>
            </li>

            <!-- Settings -->
            <li class="nav-item">
                <a href="{{ route('dashboard.settings') }}" class="nav-link {{ request()->routeIs('dashboard.settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear nav-icon"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>

            <!-- Divider -->
            <li class="nav-divider">
                <hr class="divider-line">
            </li>

            <!-- Help Center -->
            <li class="nav-item">
                <a href="{{ route('dashboard.help') }}" class="nav-link {{ request()->routeIs('dashboard.help*') ? 'active' : '' }}">
                    <i class="bi bi-question-circle nav-icon"></i>
                    <span class="nav-text">Help Center</span>
                </a>
            </li>
        </ul>

        <!-- User Profile Section -->
        <div class="sidebar-user">
            <div class="user-dropdown dropdown dropup">
                <button class="user-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <span class="user-role">{{ Auth::user()->userRole->display_name ?? 'User' }}</span>
                    </div>
                    <i class="bi bi-chevron-up dropdown-icon"></i>
                </button>

                <ul class="dropdown-menu user-dropdown-menu" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person-circle me-2"></i>
                            Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Collapse Toggle Button -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<!-- CSS Styles -->
<style>
/* Sidebar Base Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 70px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    z-index: 1000;
    overflow: hidden;
}

.sidebar:hover,
.sidebar.expanded {
    width: 260px;
}

.sidebar-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 20px 0;
}

/* Brand Section */
.sidebar-brand {
    padding: 0 20px 30px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.brand-content {
    display: flex;
    align-items: center;
    gap: 12px;
    white-space: nowrap;
}

.brand-logo {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    object-fit: contain;
}

.brand-text {
    font-size: 1.1rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar:hover .brand-text,
.sidebar.expanded .brand-text {
    opacity: 1;
}

/* Navigation Styles */
.sidebar-nav {
    flex: 1;
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 0 25px 25px 0;
    margin-right: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-link:hover {
    background: rgba(124, 185, 71, 0.2);
    color: rgba(255, 255, 255, 1);
    transform: translateX(5px);
}

.nav-link.active {
    background: linear-gradient(135deg, rgba(124, 185, 71, 0.3) 0%, rgba(30, 60, 128, 0.3) 100%);
    color: rgba(255, 255, 255, 1);
    box-shadow: 0 4px 15px rgba(124, 185, 71, 0.2);
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
}

.nav-icon {
    font-size: 1.2rem;
    min-width: 30px;
    text-align: center;
}

.nav-text {
    margin-left: 12px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar:hover .nav-text,
.sidebar.expanded .nav-text {
    opacity: 1;
}

/* Divider */
.nav-divider {
    margin: 20px 0;
    padding: 0 20px;
}

.divider-line {
    border: none;
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 0;
}

/* User Section */
.sidebar-user {
    padding: 0 20px;
    margin-top: auto;
}

.user-btn {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    cursor: pointer;
}

.user-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 1);
}

.user-avatar {
    font-size: 1.8rem;
    min-width: 30px;
}

.user-info {
    margin-left: 12px;
    text-align: left;
    flex: 1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar:hover .user-info,
.sidebar.expanded .user-info {
    opacity: 1;
}

.user-name {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1.2;
}

.user-role {
    display: block;
    font-size: 0.75rem;
    opacity: 0.7;
    line-height: 1.2;
}

.dropdown-icon {
    margin-left: auto;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar:hover .dropdown-icon,
.sidebar.expanded .dropdown-icon {
    opacity: 1;
}

/* User Dropdown Menu */
.user-dropdown-menu {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    margin-bottom: 8px;
}

.user-dropdown-menu .dropdown-item {
    padding: 10px 16px;
    color: #333;
    transition: all 0.3s ease;
}

.user-dropdown-menu .dropdown-item:hover {
    background: rgba(124, 185, 71, 0.1);
    color: #7cb947;
}

/* Sidebar Toggle Button */
.sidebar-toggle {
    position: absolute;
    top: 20px;
    right: -15px;
    width: 30px;
    height: 30px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    color: #333;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1001;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar-toggle:hover {
    background: #7cb947;
    color: white;
    transform: scale(1.1);
}

/* Collapsed State */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-text,
.sidebar.collapsed .user-info,
.sidebar.collapsed .dropdown-icon {
    opacity: 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        transform: translateX(-100%);
    }

    .sidebar:hover,
    .sidebar.expanded {
        width: 260px;
        transform: translateX(0);
    }

    .sidebar-toggle {
        right: -40px;
        background: #7cb947;
        color: white;
    }
}

/* Animation for smooth transitions */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar:hover .nav-text,
.sidebar:hover .brand-text,
.sidebar:hover .user-info {
    animation: slideIn 0.3s ease forwards;
}
</style>

<!-- JavaScript for Sidebar Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');

    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('expanded');
        sidebar.classList.toggle('collapsed');
    });

    // Auto collapse when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && sidebar.classList.contains('expanded')) {
                sidebar.classList.remove('expanded');
                sidebar.classList.add('collapsed');
            }
        }
    });
});
</script>
