<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KMI Finance - Billing System')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --primary-color: #1b4332;
        --secondary-color: #2d5016;
        --success-color: #40916c;
        --danger-color: #d62828;
        --warning-color: #f77f00;
        --info-color: #277da1;
        --light-bg: #f1f8e9;
        --dark-bg: #081c15;
        --sidebar-bg: #1b4332;
        --sidebar-hover: #2d5016;
        --text-primary: #081c15;
        --text-secondary: #52796f;
        --border-color: #95d5b2;
        --shadow: 0 4px 6px -1px rgba(27, 67, 50, 0.1), 0 2px 4px -1px rgba(27, 67, 50, 0.06);
        --sidebar-width: 340px; /* ✅ UPDATED: Lebih lebar untuk 3 badge */
        --accent-green: #40916c;
        --light-green: #95d5b2;
        --forest-gradient: linear-gradient(135deg, #1b4332 0%, #2d5016 50%, #40916c 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--light-bg);
        color: var(--text-primary);
        line-height: 1.6;
    }

    /* ========================================
       SIDEBAR STYLES
       ======================================== */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--forest-gradient);
        z-index: 1000;
        transition: all 0.3s ease;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--light-green) var(--sidebar-bg);
        box-shadow: 4px 0 15px rgba(27, 67, 50, 0.2);
    }

    body.sidebar-open {
        overflow: hidden;
        position: fixed;
        width: 100%;
        height: 100%;
    }

    .sidebar::-webkit-scrollbar {
        width: 8px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: var(--sidebar-bg);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: var(--light-green);
        border-radius: 4px;
    }

    .sidebar.collapsed {
        width: 85px;
    }

    .sidebar-header {
        padding: 30px 25px;
        border-bottom: 2px solid rgba(149, 213, 178, 0.2);
        background: rgba(8, 28, 21, 0.3);
        backdrop-filter: blur(10px);
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.3rem;
        transition: all 0.3s ease;
    }

    .sidebar-brand img {
        width: 32px;
        height: 32px;
        margin-right: 15px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid rgba(149, 213, 178, 0.3);
        background-color: white;
        padding: 2px;
    }

    .sidebar.collapsed .sidebar-brand span {
        display: none;
    }

    .sidebar-nav {
        padding: 25px 0;
    }

    /* ========================================
       MOBILE RESPONSIVE
       ======================================== */
    @media (max-width: 768px) {
        :root {
            --sidebar-width: 280px; /* ✅ Lebih kecil di mobile */
        }

        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-wrapper {
            margin-left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(27, 67, 50, 0.8);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        body.sidebar-open {
            overflow: hidden;
        }
    }

    @media (min-width: 769px) and (max-width: 992px) {
        :root {
            --sidebar-width: 320px; /* ✅ Medium size untuk tablet */
        }
    }

    /* ========================================
       NAV SECTIONS
       ======================================== */
    .nav-section {
        margin-bottom: 35px;
    }

    .nav-section-title {
        color: rgba(149, 213, 178, 0.8);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 25px 15px;
        transition: all 0.3s ease;
    }

    .sidebar.collapsed .nav-section-title {
        opacity: 0;
        padding: 0 25px 8px;
    }

    .nav-item {
        margin: 3px 15px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 15px 25px;
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-weight: 500;
        position: relative;
        font-size: 0.95rem;
    }

    .nav-link:hover {
        background: rgba(149, 213, 178, 0.15);
        color: white;
        transform: translateX(8px);
        box-shadow: 0 4px 15px rgba(149, 213, 178, 0.2);
    }

    .nav-link.active {
        background: linear-gradient(135deg, var(--accent-green) 0%, var(--light-green) 100%);
        color: var(--dark-bg);
        box-shadow: 0 6px 20px rgba(64, 145, 108, 0.4);
        font-weight: 600;
    }

    .nav-link i {
        font-size: 1.2rem;
        margin-right: 15px;
        min-width: 24px;
        text-align: center;
    }

    .sidebar.collapsed .nav-link {
        justify-content: center;
        padding: 15px 0;
    }

    .sidebar.collapsed .nav-link span {
        display: none;
    }

    .nav-badge {
        margin-left: auto;
        background: var(--danger-color);
        color: white;
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 15px;
        min-width: 24px;
        text-align: center;
        font-weight: 600;
    }

    .sidebar.collapsed .nav-badge {
        display: none;
    }

    /* ========================================
       BUYER LIST IN SIDEBAR
       ======================================== */
    .buyer-list-section {
        margin-top: 20px;
    }

    .buyer-item-sidebar {
        margin: 2px 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

   .buyer-link {
    display: grid;
    grid-template-columns: 32px 1fr auto; /* icon | nama (flexible) | badges (fixed) */
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.9rem;
}

.buyer-name {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0; /* Penting untuk ellipsis bekerja di grid */
}

.buyer-notifications-row {
    display: flex;
    align-items: center;
    gap: 5px;
    justify-self: end; /* Align ke kanan */
    flex-shrink: 0; /* Jangan mengecil */
    margin-left: 8px;
}

    .buyer-link:hover {
        background: rgba(149, 213, 178, 0.1);
        color: white;
        transform: translateX(5px);
    }

    .buyer-link.active {
        background: rgba(64, 145, 108, 0.8);
        color: white;
        box-shadow: 0 4px 12px rgba(64, 145, 108, 0.3);
    }

    .buyer-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--light-green);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--dark-bg);
        font-size: 0.8rem;
        font-weight: 600;
        margin-right: 12px;
        min-width: 32px;
        flex-shrink: 0; /* ✅ Prevent icon from shrinking */
    }

    .buyer-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px; /* ✅ UPDATED: Gap antara nama dan badges */
        min-width: 0; /* ✅ Allow text to shrink properly */
    }

    .buyer-count {
        background: rgba(149, 213, 178, 0.3);
        color: white;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
    }

    .sidebar.collapsed .buyer-link {
        justify-content: center;
        padding: 12px 0;
    }

    .sidebar.collapsed .buyer-name,
    .sidebar.collapsed .buyer-count {
        display: none;
    }

    /* ========================================
       ✅ NEW: BUYER NOTIFICATIONS - 3 BADGES HORIZONTAL
       ======================================== */
.buyer-notifications-row {
    display: inline-flex;
    align-items: center;
    gap: 5px; /* ✅ Dari 4px jadi 5px (lebih lega) */
    margin-left: 4px;
    vertical-align: middle;
    flex-wrap: nowrap;
    flex-shrink: 0;
}

    .notification-badge {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        padding: 3px 7px;
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 700;
        transition: all 0.2s ease;
        white-space: nowrap;
        min-width: 28px;
        flex-shrink: 0; /* ✅ Prevent individual badges from shrinking */
    }

    /* 2. Completed Badge (Orange/Yellow - Bell) */
    .notification-badge.badge-completed {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        animation: pulse-completed 2s ease-in-out infinite;
    }

    .notification-badge.badge-completed:hover {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.6);
        animation: none;
    }

    /* 3. Billed Badge (Green - Check) */
    .notification-badge.badge-billed {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }

    .notification-badge.badge-billed:hover {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.6);
    }

    /* Badge Icons */
    .notification-badge i {
        font-size: 0.7rem;
    }

    /* Badge Count */
    .notification-badge .badge-count {
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1;
    }

    /* Pulse Animation for Completed */
    @keyframes pulse-completed {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.95;
        }
    }

    /* Active Buyer Link - Stop Animation */
    .buyer-link.active .notification-badge {
        animation: none !important;
    }

    /* Hover Effect untuk Keseluruhan Buyer Link */
    .buyer-link:hover .notification-badge {
        transform: translateY(-1px);
    }

    /* Collapsed Sidebar - Hide Notifications */
    .sidebar.collapsed .buyer-notifications-row {
        display: none;
    }

    /* Mobile Responsive for Notifications */
    @media (max-width: 768px) {
        .buyer-name {
            font-size: 0.85rem;
            max-width: 150px;
        }
        
        .buyer-notifications-row {
            margin-left: 3px;
            gap: 3px;
        }
        
        .notification-badge {
            padding: 2px 5px;
            font-size: 0.6rem;
            gap: 2px;
            min-width: 24px;
        }
        
        .notification-badge i {
            font-size: 0.65rem;
        }
        
        .notification-badge .badge-count {
            font-size: 0.6rem;
        }
    }

    /* ========================================
       MAIN CONTENT WRAPPER
       ======================================== */
    .main-wrapper {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #f1f8e9 0%, #d8f3dc 100%);
    }

    .sidebar.collapsed + .main-wrapper {
        margin-left: 85px;
    }

    @media (max-width: 768px) {
        .main-wrapper {
            margin-left: 0;
        }
    }

    /* ========================================
       TOPBAR STYLES
       ======================================== */
    .topbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border-bottom: 2px solid var(--light-green);
        padding: 20px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 999;
    }

    .sidebar-toggle {
        background: none;
        border: none;
        font-size: 1.3rem;
        color: var(--primary-color);
        cursor: pointer;
        padding: 10px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .sidebar-toggle:hover {
        background: var(--light-bg);
        color: var(--accent-green);
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 30px;
        transition: all 0.2s ease;
    }

    .user-menu:hover {
        background: var(--light-bg);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--forest-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        border: 2px solid var(--light-green);
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .user-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.95rem;
    }

    .user-role {
        color: var(--text-secondary);
        font-size: 0.8rem;
    }

    .main-content {
        padding: 0;
        min-height: calc(100vh - 80px);
    }

    /* ========================================
       DROPDOWN MENU
       ======================================== */
    .dropdown-menu {
        border: none;
        box-shadow: 0 15px 50px rgba(27, 67, 50, 0.15);
        border-radius: 12px;
        padding: 15px;
        border: 1px solid var(--light-green);
    }

    .dropdown-item {
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: var(--light-bg);
        color: var(--primary-color);
    }

    .dropdown-divider {
        margin: 15px 0;
        border-color: var(--light-green);
    }

    /* ========================================
       CUSTOM SCROLLBAR
       ======================================== */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: var(--light-green) transparent;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: var(--light-green);
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: var(--accent-green);
    }

    /* ========================================
       SEARCH BOX
       ======================================== */
    .buyer-search {
        margin: 15px;
        position: relative;
    }

    .buyer-search input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid rgba(149, 213, 178, 0.3);
        border-radius: 25px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    .buyer-search input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .buyer-search input:focus {
        outline: none;
        border-color: var(--light-green);
        background: rgba(255, 255, 255, 0.15);
        box-shadow: 0 0 0 3px rgba(149, 213, 178, 0.2);
    }

    .buyer-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.6);
    }

    .sidebar.collapsed .buyer-search {
        display: none;
    }

    .btn-check:checked + .btn-outline-light {
        background-color: rgba(149, 213, 178, 0.3);
        border-color: var(--light-green);
        color: white;
    }

    .btn-outline-light {
        color: rgba(255, 255, 255, 0.8);
        border-color: rgba(149, 213, 178, 0.3);
    }

    .btn-outline-light:hover {
        background-color: rgba(149, 213, 178, 0.2);
        border-color: var(--light-green);
        color: white;
    }

    /* ========================================
       UTILITY CLASSES
       ======================================== */
    .text-muted {
        color: #6b7280;
    }

    .fw-bold {
        font-weight: 700;
    }

    .d-none {
        display: none;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }

    /* ========================================
   ✅ EXIM NOTIFICATION BADGES
   ======================================== */
.notification-badge.badge-exim-complete {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: 2px solid rgba(16, 185, 129, 0.3);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.notification-badge.badge-exim-incomplete {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    border: 2px solid rgba(245, 158, 11, 0.3);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.notification-badge.badge-exim-complete:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.notification-badge.badge-exim-incomplete:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
}
</style>

    @yield('styles')
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="{{ route('dashboard.admin-finance') }}" class="sidebar-brand">
                <img src="{{ asset('image/kmi-logo.png') }}" alt="KMI Finance Logo">
                <span>KMI Finance</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <!-- Overview Button -->
            <div class="nav-section">
                <div class="nav-item">
                    <a href="{{ route('dashboard.admin-finance') }}" 
                       class="nav-link {{ !request()->has('location') || request()->get('location') === 'all' ? 'active' : '' }}">
                        <span>Overview</span>
                    </a>
                </div>
            </div>

            <!-- Location Selection -->
            <div class="nav-section">
                <div class="nav-section-title">Select Location</div>
                
                @php
                    $currentRoute = request()->route()->getName();
                    $isLogistic = str_contains($currentRoute, 'logistic');
                    $isExim = str_contains($currentRoute, 'exim') || $isLogistic;
                    $isFinance = str_contains($currentRoute, 'admin-finance');
                    $currentLocation = request()->get('location', 'all');
                    $currentBuyer = request()->get('buyer');
                    
                    // Determine correct route based on user type
                    $dashboardRoute = $isLogistic ? 'dashboard.logistic' : ($isExim ? 'dashboard.exim' : 'dashboard.admin-finance');
                @endphp
                
                <div class="nav-item">
                    <a href="{{ route($dashboardRoute, ['location' => 'surabaya']) }}" 
                       class="nav-link {{ $currentLocation === 'surabaya' ? 'active' : '' }}">
                      
                        <span>Surabaya</span>
                        @if(isset($locationStats['surabaya']))
                            <span class="nav-badge bg-primary">{{ $locationStats['surabaya'] }}</span>
                        @endif
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="{{ route($dashboardRoute, ['location' => 'semarang']) }}" 
                       class="nav-link {{ $currentLocation === 'semarang' ? 'active' : '' }}">
                       
                        <span>Semarang</span>
                        @if(isset($locationStats['semarang']))
                            <span class="nav-badge bg-info">{{ $locationStats['semarang'] }}</span>
                        @endif
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="{{ route($dashboardRoute, ['location' => 'local']) }}" 
                       class="nav-link {{ $currentLocation === 'local' ? 'active' : '' }}">
                       
                        <span>Local SBY-SMG</span>
                        @if(isset($locationStats['local']))
                            <span class="nav-badge bg-success">{{ $locationStats['local'] }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <!-- Buyer List -->
            @if($currentLocation !== 'all' && isset($filteredBuyersByLocation) && count($filteredBuyersByLocation) > 0)
                <div class="nav-section buyer-list-section">
                    <div class="nav-section-title">
                        {{ ucfirst($currentLocation) }} - Buyers
                        <span class="badge bg-success ms-2">{{ count($filteredBuyersByLocation) }}</span>
                    </div>
                    
                    @if($currentBuyer)
                        <div class="nav-item">
                            <a href="{{ route($dashboardRoute) }}?location={{ $currentLocation }}" 
                               class="nav-link" style="background: rgba(149, 213, 178, 0.1); color: rgba(255, 255, 255, 0.9);">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to {{ ucfirst($currentLocation) }}</span>
                            </a>
                        </div>
                    @endif
                    
                    @if(!$currentBuyer)
                        <div class="buyer-search">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   id="buyer-search-input" 
                                   placeholder="Search by buyer name or billing..." 
                                   oninput="searchBuyersEnhanced()">
                        </div>
                        
                        <div class="mx-3 mb-2">
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <input type="radio" class="btn-check" name="searchType" id="searchName" value="name" checked>
                                <label class="btn btn-outline-light" for="searchName" style="font-size: 0.75rem;">
                                    <i class="fas fa-user"></i> Name
                                </label>
                                
                                <input type="radio" class="btn-check" name="searchType" id="searchBilling" value="billing">
                                <label class="btn btn-outline-light" for="searchBilling" style="font-size: 0.75rem;">
                                    <i class="fas fa-file-invoice"></i> Billing Doc
                                </label>
                                
                                <input type="radio" class="btn-check" name="searchType" id="searchAll" value="all">
                                <label class="btn btn-outline-light" for="searchAll" style="font-size: 0.75rem;">
                                    <i class="fas fa-search-plus"></i> All
                                </label>
                            </div>
                        </div>
                        
                        <div class="mx-3 mb-2" id="search-results-info" style="display: none;">
                            <small class="text-light">
                                <i class="fas fa-filter"></i> 
                                Found <strong id="search-count">0</strong> results
                            </small>
                        </div>
                        
                        <div id="buyer-list-sidebar" class="custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                        @foreach($filteredBuyersByLocation as $buyerData)
    @php
        $buyerSlug = Str::slug($buyerData['name']);
        $isActive = $currentBuyer === $buyerSlug;
        
        $completedCount = 0;
        $billedCount = 0;
        $totalCount = $buyerData['delivery_count'] ?? 0;
        
        if (isset($buyerNotifications[$buyerData['name']])) {
            $completedCount = $buyerNotifications[$buyerData['name']]['completed_count'] ?? 0;
            $billedCount = $buyerNotifications[$buyerData['name']]['billed_count'] ?? 0;
        }
    @endphp
    
    <div class="buyer-item-sidebar" 
         data-buyer="{{ strtolower($buyerData['name']) }}"
         data-buyer-name="{{ $buyerData['name'] }}"
         data-billing="{{ isset($buyerData['billing_documents']) ? strtolower(implode(',', $buyerData['billing_documents'])) : '' }}"
         data-delivery-count="{{ $totalCount }}"
         data-completed-count="{{ $completedCount }}"
         data-billed-count="{{ $billedCount }}">
        
        <a href="{{ route($dashboardRoute) }}?location={{ $currentLocation }}&buyer={{ $buyerSlug }}" 
           class="buyer-link {{ $isActive ? 'active' : '' }}">
           
            {{-- Icon --}}
            <div class="buyer-icon">{{ substr($buyerData['name'], 0, 1) }}</div>
            
            {{-- Nama Buyer (flexible width) --}}
            <span class="buyer-name" title="{{ $buyerData['name'] }}">
                {{ $buyerData['name'] }}
            </span>
            
           {{-- Notifications (fixed width, aligned right) --}}
@php
    $pageType = $userType ?? 'Admin Finance';
    $isEximPage = $pageType === 'Exim';
@endphp

@if($completedCount > 0 || $billedCount > 0 || ($isEximPage && $totalCount > 0))
    <div class="buyer-notifications-row">
        @if($isEximPage)
            {{-- ✅ EXIM BADGES --}}
            @if($completedCount > 0)
                <span class="notification-badge badge-exim-complete" 
                      title="{{ $completedCount }} delivery dengan dokumen lengkap (100%)">
                    <i class="fas fa-check-circle"></i>
                    <span class="badge-count">{{ $completedCount }}</span>
                </span>
            @endif
            
            @php
                $incompleteCount = $totalCount - $completedCount - $billedCount;
            @endphp
            
            @if($incompleteCount > 0)
                <span class="notification-badge badge-exim-incomplete" 
                      title="{{ $incompleteCount }} delivery dengan dokumen belum lengkap">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="badge-count">{{ $incompleteCount }}</span>
                </span>
            @endif
        @else
            {{-- ✅ ADMIN FINANCE BADGES --}}
            @if($completedCount > 0)
                <span class="notification-badge badge-completed" 
                      title="{{ $completedCount }} completed document(s) ready to send">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="badge-count">{{ $completedCount }}</span>
                </span>
            @endif
            
            @if($billedCount > 0)
                <span class="notification-badge badge-billed" 
                      title="{{ $billedCount }} billed document(s) already sent">
                    <i class="fas fa-check-circle"></i>
                    <span class="badge-count">{{ $billedCount }}</span>
                </span>
            @endif
        @endif
    </div>
@endif
        </a>
    </div>
@endforeach
                        </div>
                    @else
                        <div class="nav-item">
                            <div class="buyer-link active" style="cursor: default;">
                                <div class="buyer-icon">{{ substr($currentBuyer, 0, 1) }}</div>
                                <span class="buyer-name">{{ ucwords(str_replace('-', ' ', $currentBuyer)) }}</span>
                                <i class="fas fa-check text-success"></i>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tools & Settings -->
            <div class="nav-section">
                <div class="nav-section-title">Tools & Settings</div>
                
                <div class="nav-item">
                    <a href="{{ route('setting-document.dashboard') }}" 
                       class="nav-link {{ request()->routeIs('setting-document.*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span>Document Settings</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navigation Bar -->
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                    <i class="fas fa-bars" id="hamburger-icon"></i>
                </button>
                <div class="ms-3">
                    <h6 class="mb-0 fw-bold" style="color: var(--primary-color);">@yield('page-title', 'Dashboard')</h6>
                </div>
            </div>
            <div class="topbar-right">
                <!-- User Menu -->
                <div class="dropdown">
                    <div class="user-menu" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            {{ substr($user->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ $user->name ?? 'Unknown User' }}</div>
                            <div class="user-role">{{ $userType ?? 'Admin Finance' }}</div>
                        </div>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </div>
                    
                 <ul class="dropdown-menu dropdown-menu-end">
    <li><hr class="dropdown-divider"></li>
    <li>
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <button type="submit" 
                    class="dropdown-item text-danger w-100 text-start"
                    onclick="return confirm('Logout?');"
                    style="border: none; background: transparent; cursor: pointer; padding: 8px 16px;">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </button>
        </form>
    </li>
</ul>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Enhanced search buyers by name or billing document
        function searchBuyersEnhanced() {
            const searchTerm = document.getElementById('buyer-search-input').value.toLowerCase().trim();
            const searchType = document.querySelector('input[name="searchType"]:checked').value;
            const buyerItems = document.querySelectorAll('.buyer-item-sidebar');
            const searchInfo = document.getElementById('search-results-info');
            const searchCount = document.getElementById('search-count');
            
            let visibleCount = 0;
            
            buyerItems.forEach(item => {
                const buyerName = item.getAttribute('data-buyer');
                const billingDocs = item.getAttribute('data-billing');
                
                let shouldShow = false;
                
                if (searchTerm === '') {
                    shouldShow = true;
                } else {
                    switch(searchType) {
                        case 'name':
                            shouldShow = buyerName.includes(searchTerm);
                            break;
                        case 'billing':
                            shouldShow = billingDocs && billingDocs.includes(searchTerm);
                            break;
                        case 'all':
                            shouldShow = buyerName.includes(searchTerm) || 
                                        (billingDocs && billingDocs.includes(searchTerm));
                            break;
                    }
                }
                
                if (shouldShow) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            if (searchTerm === '') {
                searchInfo.style.display = 'none';
            } else {
                searchInfo.style.display = 'block';
                searchCount.textContent = visibleCount;
            }
        }

        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mainWrapper = document.querySelector('.main-wrapper');
            const hamburgerIcon = document.getElementById('hamburger-icon');
            
            if (window.innerWidth <= 768) {
                const isOpen = sidebar.classList.contains('show');
                
                if (isOpen) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                    document.body.style.overflow = '';
                    
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-times');
                        hamburgerIcon.classList.add('fa-bars');
                    }
                } else {
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                    document.body.classList.add('sidebar-open');
                    document.body.style.overflow = 'hidden';
                    
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-bars');
                        hamburgerIcon.classList.add('fa-times');
                    }
                }
            } else {
                const isCollapsed = sidebar.classList.contains('collapsed');
                
                if (isCollapsed) {
                    sidebar.classList.remove('collapsed');
                    if (mainWrapper) mainWrapper.classList.remove('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', 'false');
                    
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-bars');
                        hamburgerIcon.classList.add('fa-times');
                    }
                } else {
                    sidebar.classList.add('collapsed');
                    if (mainWrapper) mainWrapper.classList.add('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', 'true');
                    
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-times');
                        hamburgerIcon.classList.add('fa-bars');
                    }
                }
            }
        }

        // Notification System
        let notificationRefreshInterval = null;

       async function loadBuyerNotifications() {
    try {
        const currentLocation = '{{ $locationFilter ?? "all" }}';
        
        if (currentLocation === 'all') {
            console.log('Skip notification load - no location selected');
            return;
        }
        
        // ✅ DETEKSI PAGE TYPE (EXIM atau FINANCE)
        const pageType = '{{ $userType ?? "Admin Finance" }}'; // Ambil dari controller
        
        console.log('Loading buyer notifications for:', currentLocation, 'Page type:', pageType);
        
        // ✅ GUNAKAN ENDPOINT BERBEDA UNTUK EXIM
        const apiEndpoint = pageType === 'Exim' 
            ? '/api/buyer-notifications-exim?location=' 
            : '/api/buyer-notifications?location=';
        
        const response = await fetch(apiEndpoint + currentLocation, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        
        const result = await response.json();
        
        if (result.success && result.notifications) {
            updateNotificationUI(result.notifications, pageType); // ✅ PASS pageType
            console.log('Notifications updated:', result.notifications);
        }
        
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// ========================================
// 2. FUNCTION: updateNotificationUI (UPDATED WITH ICON WARNING)
// ========================================
function updateNotificationUI(notifications, pageType) {
    console.log('=== UPDATING NOTIFICATION UI ===', 'Page:', pageType, notifications);
    
    document.querySelectorAll('.buyer-item-sidebar').forEach(function(buyerItem) {
        const buyerName = buyerItem.getAttribute('data-buyer-name');
        
        if (!buyerName) {
            console.warn('Buyer item missing data-buyer-name attribute');
            return;
        }
        
        // ✅ GET NOTIFICATION DATA
        const notifData = notifications[buyerName];
        
        if (!notifData) {
            console.log('No notification data for:', buyerName);
            return;
        }
        
        const completedCount = notifData.completed_count || 0;
        const billedCount = notifData.billed_count || 0;
        const totalCount = notifData.total_deliveries || 0;
        
        console.log(`Processing notifications for ${buyerName}:`, {
            total: totalCount,
            completed: completedCount,
            billed: billedCount,
            pageType: pageType
        });
        
        // ✅ UPDATE DATA ATTRIBUTES
        buyerItem.setAttribute('data-completed-count', completedCount);
        buyerItem.setAttribute('data-billed-count', billedCount);
        buyerItem.setAttribute('data-delivery-count', totalCount);
        
        // ✅ REMOVE OLD NOTIFICATIONS
        const existingNotifs = buyerItem.querySelector('.buyer-notifications-row');
        if (existingNotifs) {
            existingNotifs.remove();
        }
        
        // ✅ ADD NEW NOTIFICATIONS BASED ON PAGE TYPE
        if (totalCount > 0 || completedCount > 0 || billedCount > 0) {
            const buyerNameDiv = buyerItem.querySelector('.buyer-name');
            
            if (!buyerNameDiv) {
                console.warn('buyer-name div not found for:', buyerName);
                return;
            }
            
            let notifHTML = '<div class="buyer-notifications-row">';
            
            if (pageType === 'Exim') {
                // ✅ EXIM BADGES
                // 1. Complete Badge (Green - 100% document)
                if (completedCount > 0) {
                    notifHTML += `
                        <span class="notification-badge badge-exim-complete" 
                              title="${completedCount} delivery dengan dokumen lengkap (100%)">
                            <i class="fas fa-check-circle"></i>
                            <span class="badge-count">${completedCount}</span>
                        </span>
                    `;
                }
                
                // 2. Incomplete Badge (Orange - < 100% document) - ICON WARNING
                const incompleteCount = totalCount - completedCount - billedCount;
                if (incompleteCount > 0) {
                    notifHTML += `
                        <span class="notification-badge badge-exim-incomplete" 
                              title="${incompleteCount} delivery dengan dokumen belum lengkap">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="badge-count">${incompleteCount}</span>
                        </span>
                    `;
                }
                
            } else {
                // ✅ ADMIN FINANCE BADGES
                // 1. Completed Badge (Orange - Warning) - ICON WARNING
                if (completedCount > 0) {
                    notifHTML += `
                        <span class="notification-badge badge-completed" 
                              title="${completedCount} completed document(s) ready to send">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="badge-count">${completedCount}</span>
                        </span>
                    `;
                }
                
                // 2. Billed Badge (Green - Check)
                if (billedCount > 0) {
                    notifHTML += `
                        <span class="notification-badge badge-billed" 
                              title="${billedCount} billed document(s) already sent">
                            <i class="fas fa-check-circle"></i>
                            <span class="badge-count">${billedCount}</span>
                        </span>
                    `;
                }
            }
            
            notifHTML += '</div>';
            
            buyerNameDiv.insertAdjacentHTML('beforeend', notifHTML);
            
            console.log(`✅ ${pageType} notifications updated for:`, buyerName);
        }
    });
    
    console.log('=== NOTIFICATION UI UPDATE COMPLETE ===');
}


        // Toast notifications
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'danger' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        // Export functions
        window.loadBuyerNotifications = loadBuyerNotifications;
        window.startNotificationAutoRefresh = startNotificationAutoRefresh;
        window.stopNotificationAutoRefresh = stopNotificationAutoRefresh;
        window.showToast = showToast;
    </script>

    @yield('scripts')
</body>
</html>