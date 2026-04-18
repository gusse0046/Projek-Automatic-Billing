@extends('layouts.dashboard')

@section('title', 'EXIM Dashboard - Document Upload Management')

@section('page-title')
@if(request()->has('buyer'))
    {{ ucwords(str_replace('-', ' ', request()->get('buyer'))) }} - Document Upload
@else
    EXIM Dashboard
@endif
@endsection

@section('page-subtitle')
@if(request()->has('buyer'))
    Document upload management for selected buyer
@else
    Export-Import Document Management System
@endif
@endsection

@section('styles')

@php
function sanitizeDeliveryForClass($delivery) {
    return str_replace([',', ' ', '.'], '_', trim($delivery));
}

// ✅ NEW: Generate unique ID menggunakan delivery + billing
function generateUniqueId($delivery, $billingDocument = null) {
    $base = str_replace([',', ' ', '.'], '_', trim($delivery));
    if (!empty($billingDocument) && $billingDocument !== $delivery) {
        $billing = str_replace([',', ' ', '.'], '_', trim($billingDocument));
        return $base . '_' . $billing;
    }
    return $base;
}
@endphp

<style>
:root {
    --forest-primary: #1b4332;
    --forest-secondary: #2d5016;
    --forest-accent: #40916c;
    --forest-light: #95d5b2;
    --forest-bg: #f1f8e9;
    --forest-dark: #081c15;
    --forest-shadow: 0 8px 32px rgba(27, 67, 50, 0.1);
    
    --location-surabaya: #2563eb;
    --location-semarang: #7c3aed;
    --location-unknown: #6b7280;
}

.main-container {
    background: linear-gradient(135deg, #f1f8e9 0%, #d8f3dc 100%);
    min-height: 100vh;
    padding: 25px 0;
}

@media (min-width: 769px) {
    .sidebar.collapsed {
        width: 0;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        transform: translateX(-100%);
    }
    
    .main-wrapper.sidebar-collapsed {
        margin-left: 0 !important;
    }
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 35px;
    box-shadow: var(--forest-shadow);
    border: 2px solid var(--forest-light);
}

.page-title {
    color: var(--forest-primary);
    font-weight: 800;
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title i {
    background: var(--forest-gradient);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.location-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.location-surabaya {
    background: #dbeafe;
    color: var(--location-surabaya);
}

.location-semarang {
    background: #ede9fe;
    color: var(--location-semarang);
}

.location-unknown {
    background: #f3f4f6;
    color: var(--location-unknown);
}

/* ========================================
   ðŸ†• SEARCH & FILTER SECTION - COLLAPSIBLE
   ======================================== */
.search-filter-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    box-shadow: var(--forest-shadow);
    border: 2px solid var(--forest-light);
    margin-bottom: 25px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.search-filter-container.collapsed {
    box-shadow: 0 4px 15px rgba(27, 67, 50, 0.08);
}

.search-filter-header {
    background: var(--forest-gradient);
    padding: 20px 25px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
    user-select: none;
}

.search-filter-header:hover {
    background: linear-gradient(135deg, #1b4332 0%, #2d5016 70%, #40916c 100%);
}

.filter-title {
    font-size: 1.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-filter-toggle, .btn-filter-clear {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-filter-toggle:hover, .btn-filter-clear:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.search-toggle-icon {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
    margin-left: 10px;
}

.search-toggle-icon.rotated {
    transform: rotate(180deg);
}

.search-filter-content {
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.search-filter-content.expanded {
    max-height: 1000px !important;
}

.search-filter-inner {
    padding: 25px;
}

.search-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 15px;
    color: var(--forest-primary);
    z-index: 2;
}

.search-input {
    width: 100%;
    padding: 15px 50px 15px 45px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: var(--forest-primary);
    box-shadow: 0 0 0 3px rgba(27, 67, 50, 0.1);
}

.search-clear-btn {
    position: absolute;
    right: 15px;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.search-clear-btn:hover {
    background: #f3f4f6;
    color: #6b7280;
}

.search-results-counter {
    margin-top: 10px;
    color: var(--forest-primary);
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.advanced-filters {
    border-top: 2px solid #f3f4f6;
    padding-top: 20px;
    margin-top: 20px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-weight: 600;
    color: var(--forest-dark);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-input, .filter-select {
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--forest-primary);
    box-shadow: 0 0 0 2px rgba(27, 67, 50, 0.1);
}

@media (max-width: 768px) {
    .search-filter-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-controls {
        width: 100%;
        justify-content: space-between;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
}

.delivery-section {
    margin-bottom: 30px;
    transition: none;
}

.delivery-summary {
    font-size: 0.85rem;
    background: rgba(241, 248, 233, 0.5);
    padding: 15px;
    border-radius: 8px;
}

.delivery-summary .row {
    line-height: 1.4;
}

.delivery-summary .col-5 {
    color: var(--forest-dark);
    font-weight: 600;
}

.delivery-summary .col-7 {
    color: var(--forest-primary);
    font-weight: 500;
}

.upload-form-section {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.upload-form-section .form-control,
.upload-form-section .form-select {
    font-size: 0.85rem;
    border: 2px solid var(--forest-light);
    border-radius: 8px;
    padding: 8px 12px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.upload-form-section .form-control:focus,
.upload-form-section .form-select:focus {
    border-color: var(--forest-accent);
    box-shadow: 0 0 0 3px rgba(64, 145, 108, 0.1);
    outline: none;
}

.upload-form-section .btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.upload-form-section .btn-primary {
    background: var(--forest-gradient);
    border: none;
}

.upload-form-section .btn-primary:hover {
    transform: none;
    box-shadow: 0 4px 12px rgba(27, 67, 50, 0.2);
}

.progress-section {
    font-size: 0.85rem;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.progress {
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    border-radius: 15px;
    background: rgba(149, 213, 178, 0.2);
}

.progress-bar {
    border-radius: 15px;
    transition: width 0.6s ease;
    background: var(--forest-gradient);
}

.alert {
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    border: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-sm {
    padding: 8px 12px;
    font-size: 0.8rem;
    margin-bottom: 10px;
}

.alert-success {
    background: rgba(72, 187, 120, 0.1);
    color: #38a169;
    border-left: 4px solid #38a169;
}

.alert-warning {
    background: rgba(237, 137, 54, 0.1);
    color: #dd6b20;
    border-left: 4px solid #dd6b20;
}

.alert-danger {
    background: rgba(245, 101, 101, 0.1);
    color: #e53e3e;
    border-left: 4px solid #e53e3e;
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border-left: 4px solid #3b82f6;
}

.spinner-border-sm {
    width: 1.5rem;
    height: 1.5rem;
}

.refresh-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: var(--forest-gradient);
    color: white;
    border: none;
    box-shadow: 0 8px 25px rgba(27, 67, 50, 0.4);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.refresh-fab:hover {
    transform: scale(1.1) rotate(180deg);
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1055;
}

.toast {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 2px solid var(--forest-light);
    margin-bottom: 10px;
    overflow: hidden;
    animation: slideInRight 0.3s ease;
    min-width: 300px;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.toast-header {
    background: var(--forest-gradient);
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
}

.toast-body {
    padding: 15px;
    font-size: 0.85rem;
}

.card {
    transition: box-shadow 0.2s ease;
}

.card:hover {
    transform: none;
    box-shadow: 0 4px 15px rgba(27, 67, 50, 0.1);
}

.btn {
    transition: background-color 0.2s ease, border-color 0.2s ease;
}

.btn:hover {
    transform: none;
}

.doc-action-btn, .other-file-btn {
    transition: background-color 0.2s ease, color 0.2s ease;
}

.doc-action-btn:hover, .other-file-btn:hover {
    transform: none;
}

.finance-doc-card:hover,
.other-doc-item:hover {
    transform: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .main-container {
        padding: 15px 0;
    }
    
    .page-header {
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .page-title {
        font-size: 1.8rem;
        flex-direction: column;
        text-align: center;
    }
    
    .page-title i {
        width: 50px;
        height: 50px;
        font-size: 20px;
        margin-bottom: 10px;
    }
}

.upload-form-section select option[data-uploaded="true"] {
    background-color: #e7f3ff !important;
    color: #0d6efd !important;
    font-weight: 600 !important;
}

.upload-form-section .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

.upload-form-section .form-select:focus {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2340916c' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}
</style>
@endsection

@section('content')
<div class="main-container">
    <div class="container-fluid">

        @if(!isset($locationFilter) || $locationFilter === 'all')
            
            <div style="text-align: center; padding: 100px 20px;">
                <i class="fas fa-map-marker-alt" style="font-size: 4rem; color: #95d5b2; margin-bottom: 20px;"></i>
                <h3 style="color: #1b4332; margin-bottom: 15px;">EXIM Dashboard</h3>
                <p class="text-muted" style="font-size: 1.1rem;">Silakan pilih lokasi dari sidebar untuk memulai</p>
            </div>

        @elseif(!isset($selectedBuyer) || empty($selectedBuyer))
            
            <div style="text-align: center; padding: 100px 20px;">
                <i class="fas fa-users" style="font-size: 4rem; color: #40916c; margin-bottom: 20px;"></i>
                <h3 style="color: #1b4332; margin-bottom: 15px;">{{ ucfirst($locationFilter) }} - EXIM Dashboard</h3>
                <p class="text-muted" style="font-size: 1.1rem;">Silakan pilih buyer dari sidebar untuk melihat detail dokumen EXIM</p>
            </div>

        @else
            
            <div class="mb-3">
                <a href="{{ route('dashboard.exim') }}?location={{ $locationFilter }}" 
                   class="btn btn-outline-success">
                   <i class="fas fa-arrow-left me-2"></i>Kembali ke {{ ucfirst($locationFilter) }}
                </a>
            </div>
            
            @if(isset($groupedData) && count($groupedData) > 0)
                <div class="search-filter-container">
                    <div class="search-filter-header" onclick="toggleSearchFilter()">
                        <div class="filter-title">
                            <i class="fas fa-search"></i>
                            Search & Filter Deliveries
                        </div>
                        <div class="filter-controls">
                            <button class="btn-filter-toggle" onclick="event.stopPropagation(); toggleAdvancedFilter()">
                                <i class="fas fa-sliders-h"></i>
                                Advanced Filter
                            </button>
                            <button class="btn-filter-clear" onclick="event.stopPropagation(); clearAllFilters()">
                                <i class="fas fa-eraser"></i>
                                Clear All
                            </button>
                            <i class="fas fa-chevron-down search-toggle-icon" id="searchToggleIcon"></i>
                        </div>
                    </div>
                    
                    <div class="search-filter-content" id="searchFilterContent" style="max-height: 0; overflow: hidden;">
                        <div class="search-filter-inner">
                            <div class="quick-search-section">
                                <div class="search-input-group">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" id="quickSearch" placeholder="Search by delivery order, billing document, or customer name..." 
                                           class="search-input" onkeyup="performQuickSearch(this.value)">
                                    <button class="search-clear-btn" onclick="clearSearch()" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="search-results-counter" id="searchResultsCounter" style="display: none;">
                                    <i class="fas fa-check-circle"></i>
                                    <span id="resultsCount">0</span> results found
                                </div>
                            </div>
                            
                            <div class="advanced-filters" id="advancedFilters" style="display: none;">
                                <div class="filter-grid">
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-file-invoice"></i>
                                            Billing Document
                                        </label>
                                        <input type="text" id="billingDocumentFilter" placeholder="e.g., 3110004900" 
                                               class="filter-input" onkeyup="applyAdvancedFilters()">
                                    </div>

                                    <div class="filter-group">
                                    <label class="filter-label">
                                    <i class="fas fa-box"></i>
                                        Container Number
                                    </label>
                                    <input type="text" id="containerFilter" placeholder="e.g., TCLU1234567" 
                                    class="filter-input" onkeyup="applyAdvancedFilters()">
                                    </div>
                                    
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-building"></i>
                                            Customer
                                        </label>
                                        <select id="customerFilter" class="filter-select" onchange="applyAdvancedFilters()">
                                            <option value="">All Customers</option>
                                            @if(isset($groupedData))
                                                @php
                                                    $uniqueCustomers = collect($groupedData)->pluck('customer_name')->unique()->sort();
                                                @endphp
                                                @foreach($uniqueCustomers as $customer)
                                                    <option value="{{ $customer }}">{{ Str::limit($customer, 40) }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    
                                    
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-file-alt"></i>
                                            Document Status
                                        </label>
                                        <select id="documentStatusFilter" class="filter-select" onchange="applyAdvancedFilters()">
                                            <option value="">All Document Status</option>
                                            <option value="complete">All Complete</option>
                                            <option value="incomplete">Has Missing Docs</option>
                                            <option value="no-documents">No Documents</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @foreach($groupedData as $key => $delivery)
               @php $uniqueIdSection = generateUniqueId($delivery['delivery'], $delivery['billing_document'] ?? null); @endphp
               <div class="delivery-section delivery-row mb-4"
                     data-delivery="{{ $delivery['delivery'] }}" 
                     data-customer="{{ $delivery['customer_name'] }}"
                     data-billing="{{ $delivery['billing_document'] ?? $delivery['delivery'] }}"
                     data-container="{{ $delivery['container_number'] ?? '' }}"
                     data-unique-id="{{ $uniqueIdSection }}"> 
                    
                    {{-- ✅ MAIN CARD WRAPPER --}}
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    {{ $delivery['delivery'] }} - {{ Str::limit($delivery['customer_name'], 30) }}
                                    
                                    @if(isset($delivery['has_multiple_billing_documents']) && $delivery['has_multiple_billing_documents'])
                                     
                                    @endif
                                </h6>
                                <span class="badge bg-light text-success">
                                    {{ $delivery['location'] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                
                                <div class="col-md-4">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Delivery Info
                                    </h6>
                                    <div class="delivery-summary p-3 bg-light rounded">
                                        <div class="row g-1 small">
                                            <div class="col-5"><strong>DO:</strong></div>
                                            <div class="col-7">{{ $delivery['delivery'] }}</div>
                                            
                                    <div class="col-5"><strong>Billing:</strong></div>
<div class="col-7">
    {{ $delivery['billing_document'] ?? $delivery['delivery'] }}
   
</div>
                                            
                                            <div class="col-5"><strong>Shipping Ins:</strong></div>
                                            <div class="col-7">{{ $delivery['booking_number'] ?? '-' }}</div>

                                            <div class="col-5"><strong>Container:</strong></div>
                                            <div class="col-7">{{ $delivery['container_number'] ?? '-' }}</div>
                                            
                                            
                                            <div class="col-5"><strong>Value:</strong></div>
                                            <div class="col-7">${{ number_format($delivery['total_net_value'], 0) }}</div>
                                            
                                            <div class="col-5"><strong>Status:</strong></div>
                                            <div class="col-7">
                                                <span class="badge bg-{{ $delivery['status'] === 'completed' ? 'success' : ($delivery['status'] === 'progress' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($delivery['status']) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>Document Upload
                                    </h6>
                                    <div class="upload-form-section">
                                        @php $uniqueId = generateUniqueId($delivery['delivery'], $delivery['billing_document'] ?? null); @endphp
                                        <form id="upload-form-{{ $uniqueId }}" 
      data-delivery="{{ $delivery['delivery'] }}" 
      data-customer="{{ $delivery['customer_name'] }}"
      data-billing="{{ $delivery['billing_document'] ?? $delivery['delivery'] }}"
      data-unique-id="{{ $uniqueId }}"
      style="display: none;">
    @csrf
    <input type="hidden" name="delivery_order" value="{{ $delivery['delivery'] }}">
    <input type="hidden" name="customer_name" value="{{ $delivery['customer_name'] }}">
    
    {{-- ✅ CRITICAL: Billing document untuk BL validation --}}
    <input type="hidden" name="billing_document" 
           value="{{ $delivery['billing_document'] ?? $delivery['delivery'] }}">

           {{-- ✅ NEW: Container number untuk BL validation --}}
    <input type="hidden" name="container_number" 
           value="{{ $delivery['container_number'] ?? '' }}">
    
    <div class="mb-2">
        <select name="document_type" class="form-select form-select-sm" required>
            <option value="">Select Document...</option>
        </select>
    </div>
    
    <div class="mb-2">
        <input type="file" name="document_files[]" class="form-control form-control-sm" 
               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls,.xlsm" required multiple>
        <small class="text-muted">PDF, DOC, JPG, PNG, XLS </small>
    </div>
    
    {{-- ✅ Selected files preview --}}
    <div class="selected-files-preview mb-2" style="display: none;">
        <small class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i><span class="file-count">0</span> file(s) selected</small>
        <ul class="list-unstyled small text-muted mb-0 mt-1 ps-3" style="max-height: 60px; overflow-y: auto;"></ul>
    </div>
    
    <button type="submit" class="btn btn-primary btn-sm w-100">
        <i class="fas fa-upload me-1"></i><span class="btn-text">Upload</span>
    </button>
</form>
                                        
                                        <div id="loading-settings-{{ $uniqueId }}" class="text-center">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            <small class="d-block mt-2 text-muted">Loading allowed documents...</small>
                                        </div>
                                        
                                        <div id="no-setting-message-{{ $uniqueId }}" style="display: none;">
                                            <div class="alert alert-warning alert-sm p-2">
                                                <small>
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    No documents configured
                                                </small>
                                            </div>
                                            <div class="text-center">
                                                <a href="{{ route('setting-document.dashboard') }}" class="btn btn-outline-warning btn-sm" target="_blank">
                                                    <i class="fas fa-cog me-1"></i>Configure
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <h6 class="text-info mb-3">
                                        <i class="fas fa-chart-line me-2"></i>Upload Progress
                                    </h6>
                                    <div class="progress-section">
                                        <div class="progress mb-2" style="height: 20px;">
                                           <div class="progress-bar bg-success progress-bar-{{ $uniqueId }}"
                                                 role="progressbar" style="width: 0%">
                                               <span class="progress-text-{{ $uniqueId }} small">0%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between small mb-2">
                                           <span>Req: <span class="badge bg-secondary" id="required-count-{{ $uniqueId }}">0</span></span>
                                       <span>Up: <span class="badge bg-success" id="uploaded-count-{{ $uniqueId }}">0</span></span>
                                           <span>Miss: <span class="badge bg-warning" id="remaining-count-{{ $uniqueId }}">0</span></span>
                                        </div>
                                        
                                       <div class="progress-details-{{ $uniqueId }}">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Progress updates after settings loaded
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- ✅ UPLOADED DOCUMENTS SECTION - INSIDE SAME CARD --}}
                            <hr class="my-3">
                            <div class="uploaded-documents-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-secondary">
                                        <i class="fas fa-folder-open me-2"></i>
                                        Uploaded Documents (EXIM Only)
                                    </h6>
                                    <button onclick="loadExistingDocuments('{{ $delivery['delivery'] }}', '{{ $delivery['customer_name'] }}', '{{ $uniqueId }}')" 
                                            class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-sync-alt me-1"></i>Refresh
                                    </button>
                                </div>
                                <div id="documents-display-{{ $uniqueId }}" class="row">
                                    <div class="col-12 text-center text-muted py-3">
                                        <i class="fas fa-folder-open fa-2x mb-2 opacity-50"></i>
                                        <p class="text-muted mb-0 small">No EXIM documents uploaded yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- ✅ END MAIN CARD --}}
                    
                </div>
            @endforeach
            
        @endif
            
    </div>
</div>

<button class="refresh-fab" onclick="refreshDashboard()">
    <i class="fas fa-sync-alt"></i>
</button>
@endsection

@section('scripts')
<script>

function sanitizeDeliveryForSelector(delivery) {
    if (!delivery) return '';
    return String(delivery).replace(/[,\s.\-]/g, '_');
}

let documentCache = {};
let currentLocation = '{{ $locationFilter ?? "all" }}';
let uploadedDocuments = {};
let originalDeliveryCards = [];
let isAdvancedFilterOpen = false;
let isSearchFilterExpanded = false;


// ✅ NEW: User role/team untuk filtering dokumen
// Detect from URL path instead of auth()->user() karena session-based
const currentPath = window.location.pathname;
const isLogisticDashboard = currentPath.includes('/logistic');
const userRole = '{{ auth()->user()->role ?? "" }}';
const userTeam = '{{ auth()->user()->team ?? "" }}';

console.log('🌐 Current Path:', currentPath);
console.log('👤 User Role:', userRole, '| Team:', userTeam);
console.log('🚛 Is Logistic Dashboard?', isLogisticDashboard);

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== EXIM DASHBOARD INITIALIZED ===');
    console.log('Current location:', currentLocation);
    console.log('🌐 Path:', currentPath);
    console.log('🚛 Is Logistic Dashboard?', isLogisticDashboard);
    
    originalDeliveryCards = Array.from(document.querySelectorAll('.delivery-section'));
    console.log('Filter initialized, found cards:', originalDeliveryCards.length);
    
    const searchFilterContent = document.getElementById('searchFilterContent');
    if (searchFilterContent) {
        searchFilterContent.style.maxHeight = '0';
        isSearchFilterExpanded = false;
    }
    
    initializeEximDashboard();
    
    const quickSearch = document.getElementById('quickSearch');
    if (quickSearch) {
        quickSearch.addEventListener('input', debounce(function() {
            performQuickSearch(this.value);
        }, 300));
    }
});

/**
 * âœ… NEW: Show BL validation error modal dengan detail lengkap
 */
function showBLValidationError(errorData) {
    const modalHTML = `
        <div class="modal fade" id="blValidationErrorModal" tabindex="-1" aria-labelledby="blValidationErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="blValidationErrorModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            BL Entry Number Validation Failed
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-4">
                            <strong><i class="fas fa-times-circle me-2"></i>Validation Error:</strong>
                            <p class="mb-0 mt-2">${errorData.message}</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-check-circle me-2"></i>Expected Billing Document
                                        </h6>
                                        <p class="fs-3 fw-bold text-primary mb-0">
                                            ${errorData.validation_details?.expected_billing_document || 'N/A'}
                                        </p>
                                        <small class="text-muted">This should match Entry Number in BL</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-danger h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-danger mb-3">
                                            <i class="fas fa-times-circle me-2"></i>Found Entry Number
                                        </h6>
                                        <p class="fs-3 fw-bold text-danger mb-0">
                                            ${errorData.validation_details?.found_entry_number || 'Not Found'}
                                        </p>
                                        <small class="text-muted">Entry Number dari PDF BL</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${errorData.validation_details?.all_found_numbers && errorData.validation_details.all_found_numbers.length > 0 ? `
                            <div class="alert alert-info mb-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-info-circle me-2"></i>All Entry Numbers Found in BL:
                                </h6>
                                <ul class="mb-0">
                                    ${errorData.validation_details.all_found_numbers.map(num => 
                                        `<li class="mb-1"><code class="fs-6">${num}</code></li>`
                                    ).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        
                        <div class="alert alert-warning mb-0">
                            <h6 class="mb-3">
                                <i class="fas fa-lightbulb me-2"></i>Troubleshooting Steps:
                            </h6>
                            <ol class="mb-0">
                                <li class="mb-2">
                                    <strong>Verify BL Document:</strong> Pastikan file PDF adalah BL document yang benar dan bukan dokumen lain
                                </li>
                                <li class="mb-2">
                                    <strong>Check Entry Number Location:</strong> Entry Number biasanya terletak di bagian <strong>"2. ENTRY NUMBER"</strong> pada BL document
                                </li>
                                <li class="mb-2">
                                    <strong>Match with Billing Document:</strong> Entry Number di BL harus sama dengan Billing Document: 
                                    <code class="text-danger fs-6">${errorData.validation_details?.expected_billing_document}</code>
                                </li>
                                <li class="mb-2">
                                    <strong>Update BL Document:</strong> Jika BL belum memiliki Entry Number atau salah, mohon update dokumen BL terlebih dahulu
                                </li>
                                <li class="mb-0">
                                    <strong>PDF Quality:</strong> Pastikan PDF tidak ter-corrupt dan bisa dibaca dengan jelas
                                </li>
                            </ol>
                        </div>
                        
                        ${errorData.help_text ? `
                            <div class="alert alert-secondary mt-3 mb-0">
                                <small><i class="fas fa-question-circle me-2"></i>${errorData.help_text}</small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="retryBLUpload()">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('blValidationErrorModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('blValidationErrorModal'));
    modal.show();
    
    // Log error untuk debugging
    console.error('BL Validation Failed:', errorData);
}

/**
 * âœ… NEW: Retry BL upload - close modal dan biarkan user pilih file lagi
 */
function retryBLUpload() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('blValidationErrorModal'));
    if (modal) {
        modal.hide();
    }
    // User dapat retry dengan memilih file lagi dari form
    console.log('User can now retry by selecting a new BL file');
}

// ========================================
// ðŸ†• TOGGLE SEARCH FILTER
// ========================================
function toggleSearchFilter() {
    const content = document.getElementById('searchFilterContent');
    const icon = document.getElementById('searchToggleIcon');
    const container = content.closest('.search-filter-container');
    
    isSearchFilterExpanded = !isSearchFilterExpanded;
    
    if (isSearchFilterExpanded) {
        content.style.maxHeight = content.scrollHeight + 'px';
        content.classList.add('expanded');
        icon.classList.add('rotated');
        container.classList.remove('collapsed');
        console.log('Search filter EXPANDED');
    } else {
        content.style.maxHeight = '0';
        content.classList.remove('expanded');
        icon.classList.remove('rotated');
        container.classList.add('collapsed');
        console.log('Search filter COLLAPSED');
    }
}

function autoExpandSearchFilter() {
    if (!isSearchFilterExpanded) {
        toggleSearchFilter();
    }
}

function toggleAdvancedFilter() {
    const advancedFilters = document.getElementById('advancedFilters');
    isAdvancedFilterOpen = !isAdvancedFilterOpen;
    advancedFilters.style.display = isAdvancedFilterOpen ? 'block' : 'none';
}

// ========================================
// ðŸ†• SEARCH FUNCTIONS - FIXED BILLING DOC SEARCH
// ========================================
function performQuickSearch(searchTerm) {
    const searchClearBtn = document.querySelector('.search-clear-btn');
    const resultsCounter = document.getElementById('searchResultsCounter');
    const resultsCount = document.getElementById('resultsCount');
    
    if (searchTerm.length > 0 && !isSearchFilterExpanded) {
        autoExpandSearchFilter();
    }
    
    console.log('ðŸ” Searching for:', searchTerm);
    
    if (searchTerm.length > 0) {
        searchClearBtn.style.display = 'block';
    } else {
        searchClearBtn.style.display = 'none';
    }
    
    if (!searchTerm.trim()) {
        originalDeliveryCards.forEach(function(card) {
            card.style.display = 'block';
        });
        resultsCounter.style.display = 'none';
        return;
    }
    
    const searchTermLower = searchTerm.toLowerCase().trim();
    let visibleCount = 0;
    
    originalDeliveryCards.forEach(function(card) {
        const deliveryOrder = card.dataset.delivery || '';
        const customerName = card.dataset.customer || '';
        // âœ… FIX: Ambil billing dari data attribute (bukan dari parsing HTML)
        const billingDocument = card.dataset.billing || '';
        
        console.log('Card data:', {
            delivery: deliveryOrder,
            customer: customerName,
            billing: billingDocument
        });
        
        const searchableTexts = [
            deliveryOrder,
            customerName,
            billingDocument,
            card.dataset.container || ''
        ];
        
        const isMatch = searchableTexts.some(function(text) {
            return text && text.toLowerCase().includes(searchTermLower);
        });
        
        if (isMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    console.log('âœ… Search results:', visibleCount, 'out of', originalDeliveryCards.length);
    
    resultsCount.textContent = visibleCount;
    resultsCounter.style.display = 'block';
}

function clearSearch() {
    const quickSearch = document.getElementById('quickSearch');
    const searchClearBtn = document.querySelector('.search-clear-btn');
    const resultsCounter = document.getElementById('searchResultsCounter');
    
    if (quickSearch) quickSearch.value = '';
    if (searchClearBtn) searchClearBtn.style.display = 'none';
    if (resultsCounter) resultsCounter.style.display = 'none';
    
    originalDeliveryCards.forEach(function(card) {
        card.style.display = 'block';
    });
    
    console.log('Search cleared, showing all', originalDeliveryCards.length, 'cards');
}

function clearAllFilters() {
    console.log('Clearing all filters');
    
    clearSearch();
    
    const filterInputs = [
        'billingDocumentFilter',
        'customerFilter',
        'documentStatusFilter'
    ];
    
    filterInputs.forEach(function(inputId) {
        const element = document.getElementById(inputId);
        if (element) element.value = '';
    });
    
    originalDeliveryCards.forEach(function(card) {
        card.style.display = 'block';
    });
}

function applyAdvancedFilters() {
    const billingFilter = document.getElementById('billingDocumentFilter').value.toLowerCase();
    const customerFilter = document.getElementById('customerFilter').value.toLowerCase();
    const containerFilter = document.getElementById('containerFilter')?.value.toLowerCase() || '';
    const docStatusFilter = document.getElementById('documentStatusFilter').value;
    
    let visibleCount = 0;
    
    originalDeliveryCards.forEach(function(card) {
        let shouldShow = true;
        
        // âœ… FIX: Billing document filter menggunakan data attribute
        if (billingFilter) {
            const billingDocument = (card.dataset.billing || '').toLowerCase();
            shouldShow = shouldShow && billingDocument.includes(billingFilter);
        }
        
        if (customerFilter) {
            const customerName = (card.dataset.customer || '').toLowerCase();
            shouldShow = shouldShow && customerName.includes(customerFilter);
        }

        if (containerFilter) {
            const containerNumber = (card.dataset.container || '').toLowerCase();
            shouldShow = shouldShow && containerNumber.includes(containerFilter);
        }
        
        if (docStatusFilter) {
        const sanitized = sanitizeDeliveryForSelector(card.dataset.delivery);
        const uploadedCount = parseInt(card.querySelector(`#uploaded-count-${sanitized}`)?.textContent || '0');
        const requiredCount = parseInt(card.querySelector(`#required-count-${sanitized}`)?.textContent || '0');
            
            switch(docStatusFilter) {
                case 'complete':
                    shouldShow = shouldShow && (uploadedCount >= requiredCount && requiredCount > 0);
                    break;
                case 'incomplete':
                    shouldShow = shouldShow && (uploadedCount < requiredCount && uploadedCount > 0);
                    break;
                case 'no-documents':
                    shouldShow = shouldShow && uploadedCount === 0;
                    break;
            }
        }
        
        card.style.display = shouldShow ? 'block' : 'none';
        if (shouldShow) visibleCount++;
    });
    
    const resultsCounter = document.getElementById('searchResultsCounter');
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) resultsCount.textContent = visibleCount;
    if (resultsCounter) resultsCounter.style.display = 'block';
}

function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// ========================================
// EXISTING EXIM FUNCTIONS
// ========================================
function initializeEximDashboard() {
    setupFormSubmissions();
    loadAllCustomerSettings();
}

function loadAllCustomerSettings() {
    const customerElements = document.querySelectorAll('[data-delivery][data-customer][data-unique-id]');
    console.log('Found customer elements:', customerElements.length);
    
    customerElements.forEach((element, index) => {
        const deliveryOrder = element.dataset.delivery;
        const customerName = element.dataset.customer;
        const uniqueId = element.dataset.uniqueId; // ✅ Get unique ID
        
        if (deliveryOrder && customerName && uniqueId) {
            console.log(`Processing ${index + 1}/${customerElements.length}: ${customerName} (${deliveryOrder}) [${uniqueId}]`);
            
            setTimeout(() => {
                loadAllowedDocuments(customerName, deliveryOrder, uniqueId);
            }, index * 300);
        }
    });
}

async function fetchWithRetry(url, options, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(url, options);
            return response;
        } catch (error) {
            if (i === maxRetries - 1) throw error;
            console.log(`Retry ${i + 1}/${maxRetries} for ${url}`);
            await new Promise(resolve => setTimeout(resolve, 2000));
        }
    }
}

async function loadAllowedDocuments(customerName, deliveryOrder, uniqueId = null) {
    // ✅ Use uniqueId if provided, otherwise fallback to delivery only
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    
    const loadingElement = document.getElementById(`loading-settings-${elementId}`);
    const formElement = document.getElementById(`upload-form-${elementId}`);
    const noSettingMsg = document.getElementById(`no-setting-message-${elementId}`);
    
    console.log('🔍 Looking for elements with ID:', elementId, {
        loading: !!loadingElement,
        form: !!formElement,
        noSetting: !!noSettingMsg
    });
    
    // ✅ HELPER: Pastikan loading SELALU di-hide
    const hideLoading = () => {
        if (loadingElement) {
            loadingElement.style.display = 'none';
            loadingElement.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <small class="d-block mt-2 text-muted">Loading allowed documents...</small>
            `;
        }
    };
    
    try {
        if (loadingElement) loadingElement.style.display = 'block';
        if (formElement) formElement.style.display = 'none';
        if (noSettingMsg) noSettingMsg.style.display = 'none';
        
        const timestamp = new Date().getTime();
        const encodedCustomer = encodeURIComponent(customerName);
        const url = `/setting-document/get-settings/${encodedCustomer}?_t=${timestamp}`;
        
        console.log('📡 Fetching settings:', {
            customer: customerName,
            url: url,
            delivery: deliveryOrder,
            uniqueId: elementId
        });
        
        // ✅ TIMEOUT: 8 detik (increased dari 5)
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
            console.error('⏰ Request timeout after 8 seconds');
        }, 8000);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        console.log('✅ Settings response:', result);
        
        hideLoading(); // ✅ ALWAYS hide loading on success
        
        if (result.success && result.allowed_documents && result.allowed_documents.length > 0) {
            console.log('✅ Found allowed documents:', result.allowed_documents);
            
            let filteredDocs = result.allowed_documents;
            
            if (isLogisticDashboard) {
                const logisticAllowedDocs = [
                    'CONTAINER_LOAD', 
                    'CONTAINER_CHECKLIST',
                    'CONTAINER_CHEKLIST'
                ];
                filteredDocs = result.allowed_documents.filter(doc => logisticAllowedDocs.includes(doc));
                console.log('✅ Filtered for Logistic:', filteredDocs);
            }
            
            updateUploadFormForCustomer(customerName, deliveryOrder, filteredDocs, elementId);
            updateProgressInfo(customerName, deliveryOrder, filteredDocs, elementId);
            const billingDocument = document
    .querySelector(`[data-unique-id="${elementId}"]`)
    ?.dataset.billing;

await loadExistingDocuments(
    deliveryOrder,
    customerName,
    elementId,
    billingDocument // 🔑
);

setTimeout(() => {
    updateProgressAfterUpload(deliveryOrder, customerName, elementId, billingDocument);
}, 500);

        } else {
            console.log('⚠️ No allowed documents found');
            showNoSettingMessage(deliveryOrder, customerName, null, elementId);
        }
        
    } catch (error) {
        console.error('❌ Error loading settings:', error);
        
        hideLoading(); // ✅ ALWAYS hide loading on error
        
        // ✅ RETRY LOGIC: Only retry once on timeout
        const retryKey = `retried_${elementId}`;
        if (error.name === 'AbortError' && !window[retryKey]) {
            window[retryKey] = true;
            console.log(`🔄 Retrying for ${deliveryOrder} [${elementId}]...`);
            
            if (loadingElement) {
                loadingElement.style.display = 'block';
                loadingElement.innerHTML = `
                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    <small class="d-block mt-2 text-warning">Retrying...</small>
                `;
            }
            
            setTimeout(() => {
                loadAllowedDocuments(customerName, deliveryOrder, uniqueId);
            }, 2000);
        } else {
            // ✅ Show error message with retry button
            showNoSettingMessage(deliveryOrder, customerName, error.message, elementId);
            showNoSettingMessage(deliveryOrder, customerName, error.message);
        }
    }
}


function updateDropdownWithCheckmarks(deliveryOrder, customerName, uniqueId = null) {
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    const form = document.getElementById(`upload-form-${elementId}`);
    if (!form) {
        // Fallback ke query selector jika tidak ketemu
        const fallbackForm = document.querySelector(`form[data-delivery="${deliveryOrder}"][data-customer="${customerName}"]`);
        if (!fallbackForm) return;
    }
    
    const targetForm = form || document.querySelector(`form[data-delivery="${deliveryOrder}"][data-customer="${customerName}"]`);
    const select = targetForm.querySelector('select[name="document_type"]');
    if (!select) return;
    
    const uploadKey = `${deliveryOrder}_${customerName}`;
    const uploadedDocs = uploadedDocuments[uploadKey] || [];
    
    Array.from(select.options).forEach(option => {
        if (option.value === '') return;
        
        const docType = option.value;
        const isUploaded = uploadedDocs.includes(docType);
        
        if (isUploaded) {
            if (!option.textContent.includes('✔')) {
                option.textContent = `✔ ${docType}`;
                option.style.color = '#0d6efd';
                option.style.fontWeight = '600';
                option.setAttribute('data-uploaded', 'true');
            }
        } else {
            option.textContent = docType;
            option.style.color = '';
            option.style.fontWeight = '';
            option.removeAttribute('data-uploaded');
        }
    });
    
    console.log('Checkmarks updated:', deliveryOrder, uploadedDocs);
}

function updateUploadFormForCustomer(customerName, deliveryOrder, allowedDocs, uniqueId = null) {
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    console.log('Updating upload form for:', customerName, deliveryOrder, 'ID:', elementId);
    console.log('Allowed docs:', allowedDocs);
    
    // ✅ Use uniqueId to find the correct form
    const form = document.getElementById(`upload-form-${elementId}`);
    if (!form) {
        console.error('Form not found for ID:', elementId);
        return;
    }
    
    const select = form.querySelector('select[name="document_type"]');
    if (select) {
        select.innerHTML = '<option value="">Select Document Type</option>';
        
        // ✅ Jika docs kosong (untuk Logistic tanpa setting), tampilkan message
        if (allowedDocs.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No documents configured for this buyer';
            option.disabled = true;
            select.appendChild(option);
            console.log('⚠️ No documents available');
        } else {
            allowedDocs.forEach(doc => {
                const option = document.createElement('option');
                option.value = doc;
                option.textContent = doc;
                select.appendChild(option);
            });
            console.log('Dropdown updated with', allowedDocs.length, 'documents');
        }
    }
    
    form.style.display = 'block';
    
    const noSettingMsg = document.getElementById(`no-setting-message-${elementId}`);
    if (noSettingMsg) {
        noSettingMsg.style.display = 'none';
    }
}

function updateProgressInfo(customerName, deliveryOrder, allowedDocs, uniqueId = null) {
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    const requiredCountEl = document.getElementById(`required-count-${elementId}`);
    const remainingCountEl = document.getElementById(`remaining-count-${elementId}`);
    
    if (requiredCountEl) {
        requiredCountEl.textContent = allowedDocs.length;
    }
    if (remainingCountEl) {
        remainingCountEl.textContent = allowedDocs.length;
    }
    
  
    const progressDetails = document.querySelector(`.progress-details-${elementId}`);
    if (progressDetails) {
        progressDetails.innerHTML = `
            <small class="text-info">
                <i class="fas fa-check-circle me-1"></i>
                ${allowedDocs.length} document types configured for upload
            </small>
        `;
    }
}

function showNoSettingMessage(deliveryOrder, customerName, errorMessage = null, uniqueId = null) {
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    console.log('Showing no setting message for:', deliveryOrder, customerName, 'ID:', elementId);
    
    // ✅ CRITICAL: Always hide loading element first
    const loadingElement = document.getElementById(`loading-settings-${elementId}`);
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
    
    const form = document.getElementById(`upload-form-${elementId}`);
    if (form) {
        form.style.display = 'none';
    }
    
    const noSettingMsg = document.getElementById(`no-setting-message-${elementId}`);
    if (noSettingMsg) {
        let messageContent = `
            <div class="alert alert-warning alert-sm p-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>No Document Settings Found</strong>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Customer: <strong>${customerName}</strong></small>
                </div>
        `;
        
        if (errorMessage) {
            messageContent += `
                <div class="mb-2">
                    <small class="text-danger">Error: ${errorMessage}</small>
                </div>
            `;
        }
        
        messageContent += `
                <div class="text-center mt-2">
                    <a href="/setting-document/dashboard" class="btn btn-outline-warning btn-sm me-2" target="_blank">
                        <i class="fas fa-cog me-1"></i>Configure Settings
                    </a>
                    <button onclick="retryLoadSettings('${customerName}', '${deliveryOrder}', '${elementId}')" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sync-alt me-1"></i>Retry
                    </button>
                </div>
            </div>
        `;
        
        noSettingMsg.innerHTML = messageContent;
        noSettingMsg.style.display = 'block';
    }
}

function retryLoadSettings(customerName, deliveryOrder, uniqueId = null) {
    console.log('Retrying settings load for:', customerName, deliveryOrder, 'ID:', uniqueId);
    
    // ✅ Reset retry counter agar bisa retry lagi
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    const retryKey = `retried_${elementId}`;
    window[retryKey] = false;
    
    loadAllowedDocuments(customerName, deliveryOrder, uniqueId);
}

async function refreshCustomerSettings(deliveryOrder, customerName) {
    console.log('=== REFRESHING CUSTOMER SETTINGS ===');
    console.log('Delivery:', deliveryOrder);
    console.log('Customer:', customerName);
    
    const refreshBtn = event.target;
    const originalText = refreshBtn.innerHTML;
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        await loadAllowedDocuments(customerName, deliveryOrder);
    } catch (error) {
        showToast('Error', 'Failed to refresh settings', 'error');
    } finally {
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = originalText;
    }
}

function setupFormSubmissions() {
    console.log('Setting up form submissions...');
    
    document.querySelectorAll('form[data-delivery]').forEach(form => {
        form.removeEventListener('submit', handleFormSubmission);
        form.addEventListener('submit', handleFormSubmission);
        
        // ✅ ADD: File input change listener for preview
        const fileInput = form.querySelector('input[name="document_files[]"]');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                updateFilePreview(this);
            });
        }
        
        console.log('Form submission handler attached for:', {
            delivery: form.dataset.delivery,
            customer: form.dataset.customer
        });
    });
}

/**
 * ✅ NEW: Update file preview when files selected
 */
function updateFilePreview(fileInput) {
    const form = fileInput.closest('form');
    const preview = form.querySelector('.selected-files-preview');
    const fileCount = preview?.querySelector('.file-count');
    const fileList = preview?.querySelector('ul');
    
    if (!preview || !fileCount || !fileList) return;
    
    const files = fileInput.files;
    
    if (files.length > 0) {
        preview.style.display = 'block';
        fileCount.textContent = files.length;
        
        // Build file list
        let listHtml = '';
        for (let i = 0; i < Math.min(files.length, 5); i++) {
            const file = files[i];
            const size = (file.size / 1024).toFixed(1);
            listHtml += `<li><i class="fas fa-file me-1"></i>${file.name} <span class="text-muted">(${size} KB)</span></li>`;
        }
        if (files.length > 5) {
            listHtml += `<li class="text-muted">... and ${files.length - 5} more</li>`;
        }
        fileList.innerHTML = listHtml;
        
        // Update button text
        const btnText = form.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = files.length > 1 ? `Upload ${files.length} Files` : 'Upload';
        }
    } else {
        preview.style.display = 'none';
        const btnText = form.querySelector('.btn-text');
        if (btnText) btnText.textContent = 'Upload';
    }
}

async function handleFormSubmission(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const originalBtnText = btnText ? btnText.textContent : 'Upload';
    const originalBtnHtml = submitBtn.innerHTML;
    
    const documentType = form.querySelector('select[name="document_type"]').value;
    const fileInput = form.querySelector('input[name="document_files[]"]');
    const files = fileInput.files;
    const billingDocument = form.querySelector('input[name="billing_document"]').value;
    const deliveryOrder = form.querySelector('input[name="delivery_order"]').value;
    const customerName = form.querySelector('input[name="customer_name"]').value;
    const containerNumber = form.querySelector('input[name="container_number"]')?.value || '';
    
    // ✅ GET UNIQUE ID FROM FORM
    const uniqueId = form.dataset.uniqueId || form.id.replace('upload-form-', '');
    
    // ✅ ENHANCED VALIDATION
    if (!documentType) {
        showToast('Error', 'Please select document type', 'error');
        return;
    }
    
    if (!files || files.length === 0) {
        showToast('Error', 'Please select at least one file', 'error');
        return;
    }
    
    // ✅ BL-specific validation - only for single file
    if (documentType === 'BL' && files.length > 1) {
        showToast('Error', 'BL document can only upload 1 file at a time for validation', 'error');
        return;
    }
    
    if (documentType === 'BL' && !billingDocument) {
        console.error('❌ BL validation requires billing_document');
        showToast('Error', 'Billing document required for BL validation', 'error');
        return;
    }
    
    const totalFiles = files.length;
    console.log(`📤 Multiple files upload: ${totalFiles} file(s)`, {
        delivery: deliveryOrder,
        customer: customerName,
        documentType: documentType,
        fileCount: totalFiles,
        billingDocument: billingDocument,
        uniqueId: uniqueId
    });
    
    submitBtn.disabled = true;
    
    let successCount = 0;
    let failCount = 0;
    let lastResult = null;
    
    // ✅ UPLOAD FILES ONE BY ONE
    for (let i = 0; i < totalFiles; i++) {
        const file = files[i];
        
        // Update button text with progress
        submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>Uploading ${i + 1}/${totalFiles}...`;
        
        try {
            const formData = new FormData();
            formData.append('document_type', documentType);
            formData.append('document_file', file);
            formData.append('delivery_order', deliveryOrder);
            formData.append('customer_name', customerName);
            formData.append('billing_document', billingDocument);
            formData.append('container_number', containerNumber);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            console.log(`📤 Uploading file ${i + 1}/${totalFiles}: ${file.name}`);
            
            const response = await fetch('/documents/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Dashboard': 'exim',
                    'Accept': 'application/json'
                }
            });
            
            const rawText = await response.text();
            let result;
            
            try {
                result = JSON.parse(rawText);
            } catch (parseError) {
                const jsonMatch = rawText.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    result = JSON.parse(jsonMatch[0]);
                } else {
                    throw new Error('Invalid server response');
                }
            }
            
            if (response.ok && result.success) {
                successCount++;
                lastResult = result;
                console.log(`✅ File ${i + 1}/${totalFiles} uploaded: ${file.name}`);
            } else if (result.error_type === 'bl_entry_number_validation_failed') {
                failCount++;
                showBLValidationError(result);
                break; // Stop on BL validation error
            } else {
                failCount++;
                console.error(`❌ File ${i + 1}/${totalFiles} failed: ${file.name}`, result.message);
            }
            
        } catch (error) {
            failCount++;
            console.error(`❌ File ${i + 1}/${totalFiles} error: ${file.name}`, error);
        }
    }
    
    // ✅ RESET FORM AND REFRESH
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnHtml;
    
    if (successCount > 0) {
        form.reset();
        
        // Hide file preview
        const preview = form.querySelector('.selected-files-preview');
        if (preview) preview.style.display = 'none';
        
        // Auto refresh
        await autoRefreshAfterUpload(deliveryOrder, customerName, uniqueId, documentType, lastResult);
        
        // Show appropriate message
        if (failCount === 0) {
            showSuccessToast(`${successCount} file(s) uploaded successfully!`);
        } else {
            showToast('Partial Success', `${successCount} uploaded, ${failCount} failed`, 'warning');
        }
    } else {
        showToast('Error', 'All uploads failed', 'error');
    }
}



/**
 * ✅ NEW: Auto refresh semua data setelah upload berhasil
 */
async function autoRefreshAfterUpload(deliveryOrder, customerName, uniqueId, documentType, result) {
    console.log('🔄 Auto-refreshing after upload...', { deliveryOrder, customerName, uniqueId, documentType });
    
    try {
        // 1. Reload existing documents
        console.log('📄 Reloading documents...');
        const billingDocument = document
    .querySelector(`[data-unique-id="${uniqueId}"]`)
    ?.dataset.billing;

await loadExistingDocuments(
    deliveryOrder,
    customerName,
    uniqueId,
    billingDocument // 🔑 WAJIB
);

setTimeout(() => {
    updateProgressAfterUpload(deliveryOrder, customerName, elementId);
}, 300);

        
        // 2. Update progress bar
        console.log('📊 Updating progress...');
        await updateProgressAfterUpload(
    deliveryOrder,
    customerName,
    uniqueId,
    billingDocument // 🔑 WAJIB
);

        
        // 3. Update dropdown checkmarks
        console.log('✅ Updating checkmarks...');
        updateDropdownWithCheckmarks(deliveryOrder, customerName, uniqueId);
        
        // 4. Show success message
        let successMessage = `${documentType} uploaded successfully!`;
        if (result?.document?.entry_number_validated) {
            successMessage += ' (Entry Number validated ✓)';
        }
        showSuccessToast(successMessage);
        
        console.log('✅ Auto-refresh completed!');
        
    } catch (error) {
        console.error('⚠️ Auto-refresh error (upload still successful):', error);
        // Still show success even if refresh fails
        showSuccessToast(`${documentType} uploaded successfully!`);
    }
}

/**
 * ✅ FIXED: Update progress EXIM ONLY (tidak terpengaruh Finance)
 */
async function updateProgressAfterUpload(
    deliveryOrder,
    customerName,
    uniqueId = null,
    billingDocument = null
) {
    try {
        const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);

        const params = new URLSearchParams();
        if (billingDocument) {
            params.append('billing_document', billingDocument);
        }

        const response = await fetch(
            `/api/documents/progress/${deliveryOrder}/${encodeURIComponent(customerName)}?${params.toString()}`,
            {
                headers: {
                    'X-Dashboard': 'exim',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        const result = await response.json();

        if (result.success && result.progress) {
            const progress = result.progress;
            const eximProgress = progress.exim_progress_percentage || 0;

            const progressBar = document.querySelector(`.progress-bar-${elementId}`);
            const progressText = document.querySelector(`.progress-text-${elementId}`);

            if (progressBar && progressText) {
                progressBar.style.width = eximProgress + '%';
                progressText.textContent = eximProgress + '%';
            }
        }
    } catch (error) {
        console.error('❌ Error updating EXIM progress:', error);
    }
}

async function loadExistingDocuments(deliveryOrder, customerName, uniqueId, billingDocument = null) {
    const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);

    const params = new URLSearchParams();
    if (billingDocument) {
        params.append('billing_document', billingDocument);
    }

    const response = await fetch(
        `/documents/uploads/${deliveryOrder}/${encodeURIComponent(customerName)}?${params.toString()}`,
        {
            headers: {
                'X-Dashboard': 'exim',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }
    );

    const result = await response.json();

    if (result.success && result.uploads) {
        updateDocumentsDisplay(deliveryOrder, result.uploads, elementId, customerName);
    }
}


// ========================================
// ✅ FIXED: UPDATE DOCUMENTS DISPLAY
// ========================================
function updateDocumentsDisplay(deliveryOrder, uploads, uniqueId = null, customerName = null) {
   const elementId = uniqueId || sanitizeDeliveryForSelector(deliveryOrder);
    const container = document.getElementById(`documents-display-${elementId}`);
    if (!container) {
        console.warn('Documents display container not found for:', deliveryOrder);
        return;
    }
    
    // ✅ CRITICAL: Filter out Finance documents
    const financeDocTypes = ['INVOICE', 'PACKING_LIST', 'PAYMENT_INTRUCTION', 'PAYMENT_INSTRUCTION'];
    
    // ✅ Escape strings for onclick
    const escapedDelivery = deliveryOrder ? deliveryOrder.replace(/'/g, "\\'") : '';
    const escapedCustomer = customerName ? customerName.replace(/'/g, "\\'") : '';
    const escapedUniqueId = elementId ? elementId.replace(/'/g, "\\'") : '';
    
    if (uploads && Object.keys(uploads).length > 0) {
        let html = '';
        let validDocCount = 0;
        
        Object.entries(uploads).forEach(([docType, docs]) => {
            // ✅ BLOCKING: Skip Finance documents
            if (financeDocTypes.includes(docType)) {
                console.warn(`🚫 BLOCKED Finance document from display: ${docType}`);
                return;
            }
            
            validDocCount++;
            
            // ✅ TAMBAHAN BARU: Simpan document type yang sudah diupload
            const uploadKey = `${deliveryOrder}_${customerName}`;
            if (!uploadedDocuments[uploadKey]) {
                uploadedDocuments[uploadKey] = [];
            }
            if (!uploadedDocuments[uploadKey].includes(docType)) {
                uploadedDocuments[uploadKey].push(docType);
            }
            
            docs.forEach(doc => {
                html += `
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="document-type-badge badge bg-success">
                                        ${docType}
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" onclick="previewDocument(${doc.id})">
                                                    <i class="fas fa-eye me-2 text-primary"></i>Preview
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="downloadDocument(${doc.id})">
                                                    <i class="fas fa-download me-2 text-success"></i>Download
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger" onclick="deleteDocument(${doc.id}, '${escapedDelivery}', '${escapedCustomer}', '${escapedUniqueId}')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="document-filename mb-2">
                                    <small class="text-muted d-block" style="word-break: break-word;">
                                        <i class="fas fa-file-pdf me-1"></i>
                                        ${doc.file_name}
                                    </small>
                                </div>
                                
                                <div class="document-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        ${doc.uploaded_by || 'System'}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        ${doc.uploaded_at || 'Unknown'}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
        
        // ✅ CHECK: If no EXIM documents found
        if (validDocCount === 0 || html === '') {
            container.innerHTML = `
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                    <h5 class="text-muted">No EXIM documents uploaded yet</h5>
                    <p class="text-muted">Upload documents using the form above</p>
                </div>
            `;
        } else {
            container.innerHTML = html;
        }
        
        console.log(`✅ Documents display updated: ${validDocCount} EXIM docs`);
        
        // ✅ TAMBAHAN BARU: Update dropdown dengan checkmarks
        updateDropdownWithCheckmarks(deliveryOrder, customerName, uniqueId);
        
    } else {
        container.innerHTML = `
            <div class="col-12 text-center text-muted py-4">
                <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                <h5 class="text-muted">No EXIM documents uploaded yet</h5>
                <p class="text-muted">Upload documents using the form above</p>
            </div>
        `;
        
        // ✅ TAMBAHAN BARU: Clear uploadedDocuments jika tidak ada upload
        const uploadKey = `${deliveryOrder}_${customerName}`;
        uploadedDocuments[uploadKey] = [];
        updateDropdownWithCheckmarks(deliveryOrder, customerName, uniqueId);
    }
}

// ========================================
// âœ… DELETE: REMOVE updateProgressAfterLoad() 
// (Tidak diperlukan karena sudah ada updateProgressAfterUpload)
// ========================================

function updateProgressAfterLoad(deliveryOrder, uploadedCount) {
    // Sanitize delivery untuk selector
    const sanitized = sanitizeDeliveryForSelector(deliveryOrder);
    const uploadedCountEl = document.getElementById(`uploaded-count-${sanitized}`);
    const requiredCountEl = document.getElementById(`required-count-${sanitized}`);
    const remainingCountEl = document.getElementById(`remaining-count-${sanitized}`);
    const progressBar = document.querySelector(`.progress-bar-${sanitized}`);
    const progressText = document.querySelector(`.progress-text-${sanitized}`);;
    
    if (uploadedCountEl) {
        uploadedCountEl.textContent = uploadedCount;
    }
    
    const requiredCount = parseInt(requiredCountEl?.textContent || 0);
    const remaining = Math.max(0, requiredCount - uploadedCount);
    
    if (remainingCountEl) {
        remainingCountEl.textContent = remaining;
    }
    
    const percentage = requiredCount > 0 ? Math.round((uploadedCount / requiredCount) * 100) : 0;
    
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
    if (progressText) {
        progressText.textContent = percentage + '%';
    }
}

function previewDocument(documentId) {
    const previewUrl = `/documents/preview/${documentId}`;
    window.open(previewUrl, '_blank', 'width=800,height=600');
}

function downloadDocument(documentId) {
    const downloadUrl = `/documents/download/${documentId}`;
    window.location.href = downloadUrl;
}

async function deleteDocument(documentId, deliveryOrder = null, customerName = null, uniqueId = null) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }
    
    const deleteButton = document.querySelector(`[onclick*="deleteDocument(${documentId}"]`);
    const documentCard = deleteButton?.closest('.card') || deleteButton?.closest('.col-md-6') || deleteButton?.closest('.col-lg-4') || deleteButton?.closest('.col-xl-3');
    
    // ✅ Try to get delivery info from closest delivery-section if not provided
    if (!deliveryOrder || !customerName || !uniqueId) {
        const deliverySection = deleteButton?.closest('.delivery-section');
        if (deliverySection) {
            deliveryOrder = deliveryOrder || deliverySection.dataset.delivery;
            customerName = customerName || deliverySection.dataset.customer;
            uniqueId = uniqueId || deliverySection.dataset.uniqueId;
        }
    }
    
    // ✅ Fallback: Try to get from container ID
    if (!uniqueId) {
        const container = documentCard?.closest('[id^="documents-display-"]');
        if (container) {
            const match = container.id.match(/documents-display-(.+)/);
            if (match) {
                uniqueId = match[1];
            }
        }
    }
    
    console.log('🗑️ Deleting document:', { documentId, deliveryOrder, customerName, uniqueId });
    
    if (documentCard) {
        documentCard.style.opacity = '0.6';
        documentCard.style.pointerEvents = 'none';
    }
    
    try {
        const response = await fetch(`/documents/delete/${documentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            // ✅ Animate card removal
            if (documentCard) {
                documentCard.style.transform = 'scale(0.8)';
                documentCard.style.opacity = '0';
                documentCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    documentCard.remove();
                }, 300);
            }
            
            // ✅ AUTO-REFRESH: Update semua data setelah delete
            setTimeout(async () => {
                await autoRefreshAfterDelete(deliveryOrder, customerName, uniqueId);
            }, 400);
            
            showSuccessToast('Document deleted successfully!');
            
        } else {
            showToast('Error', result.message || 'Delete failed', 'error');
            if (documentCard) {
                documentCard.style.opacity = '1';
                documentCard.style.pointerEvents = 'auto';
            }
        }
    } catch (error) {
        console.error('Delete error:', error);
        showToast('Error', 'Delete failed: ' + error.message, 'error');
        if (documentCard) {
            documentCard.style.opacity = '1';
            documentCard.style.pointerEvents = 'auto';
        }
    }
}

/**
 * ✅ NEW: Auto refresh setelah delete dokumen
 */
async function autoRefreshAfterDelete(deliveryOrder, customerName, uniqueId) {
    console.log('🔄 Auto-refreshing after delete...', { deliveryOrder, customerName, uniqueId });
    
    if (!deliveryOrder || !customerName) {
        console.warn('⚠️ Missing delivery/customer info for refresh');
        return;
    }
    
    try {
        // 1. Reload existing documents
        console.log('📄 Reloading documents...');
      const billingDocument = document
    .querySelector(`[data-unique-id="${uniqueId}"]`)
    ?.dataset.billing;

await loadExistingDocuments(
    deliveryOrder,
    customerName,
    uniqueId,
    billingDocument // 🔑 WAJIB
);

        
        // 2. Update progress bar
        console.log('📊 Updating progress...');
        await updateProgressAfterUpload(
    deliveryOrder,
    customerName,
    uniqueId,
    billingDocument // 🔑 WAJIB
);

        
        // 3. Update dropdown checkmarks
        console.log('✅ Updating checkmarks...');
        updateDropdownWithCheckmarks(deliveryOrder, customerName, uniqueId);
        
        console.log('✅ Auto-refresh after delete completed!');
        
    } catch (error) {
        console.error('⚠️ Auto-refresh after delete error:', error);
    }
}

function showToast(title, message, type = 'info') {
    console.log(`Toast ${type.toUpperCase()}: ${title} - ${message}`);
    
    if (type !== 'error') {
        return;
    }
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastColors = {
        error: 'bg-danger', 
        warning: 'bg-warning'
    };
    
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast';
    toastElement.setAttribute('role', 'alert');
    toastElement.innerHTML = `
        <div class="toast-header ${toastColors[type]} text-white">
            <i class="fas fa-${type === 'error' ? 'times' : 'exclamation-triangle'} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    if (typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 8000
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } else {
        toastElement.style.display = 'block';
        setTimeout(() => {
            toastElement.remove();
        }, 8000);
    }
}

function showSuccessToast(message) {
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast';
    toastElement.innerHTML = `
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Upload Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <i class="fas fa-check text-success me-2"></i>${message}
            <br><small class="text-muted">Dropdown updated with checkmark âœ“</small>
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    if (typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 4000 });
        toast.show();
        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    } else {
        toastElement.style.display = 'block';
        setTimeout(() => toastElement.remove(), 4000);
    }
}

function refreshDashboard() {
    console.log('Refreshing dashboard...');
    window.location.reload();
}

// âœ… ENHANCED: Show BL validation error modal dengan better styling
function showBLValidationError(errorData) {
    console.log('ðŸš¨ Showing BL validation error modal:', errorData);
    
    const modalHTML = `
        <div class="modal fade" id="blValidationErrorModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-danger shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            BL Entry Number Validation Failed
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-4">
                            <strong><i class="fas fa-times-circle me-2"></i>Validation Error:</strong>
                            <p class="mb-0 mt-2">${errorData.message || 'Entry Number mismatch'}</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-check-circle me-2"></i>Expected Billing Document
                                        </h6>
                                        <p class="fs-3 fw-bold text-primary mb-0">
                                            ${errorData.validation_details?.expected_billing_document || 'N/A'}
                                        </p>
                                        <small class="text-muted">This should match Entry Number in BL</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-danger h-100">
                                    <div class="card-body text-center">
                                        <h6 class="text-danger mb-3">
                                            <i class="fas fa-times-circle me-2"></i>Found Entry Number
                                        </h6>
                                        <p class="fs-3 fw-bold text-danger mb-0">
                                            ${errorData.validation_details?.found_entry_number || 'Not Found'}
                                        </p>
                                        <small class="text-muted">Entry Number dari PDF BL</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${errorData.validation_details?.all_found_numbers?.length > 0 ? `
                            <div class="alert alert-info mb-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-info-circle me-2"></i>All Entry Numbers Found in BL:
                                </h6>
                                <ul class="mb-0">
                                    ${errorData.validation_details.all_found_numbers.map(num => 
                                        `<li class="mb-1"><code class="fs-6">${num}</code></li>`
                                    ).join('')}
                                </ul>
                            </div>
                        ` : ''}
                        
                        <div class="alert alert-warning mb-0">
                            <h6 class="mb-3">
                                <i class="fas fa-lightbulb me-2"></i>Troubleshooting Steps:
                            </h6>
                            <ol class="mb-0">
                                <li class="mb-2">
                                    <strong>Verify BL Document:</strong> Pastikan file PDF adalah BL document yang benar
                                </li>
                                <li class="mb-2">
                                    <strong>Check Entry Number:</strong> Entry Number biasanya di bagian <strong>"2. ENTRY NUMBER"</strong>
                                </li>
                                <li class="mb-2">
                                    <strong>Match Required:</strong> Entry Number harus sama dengan: 
                                    <code class="text-danger fs-6">${errorData.validation_details?.expected_billing_document}</code>
                                </li>
                                <li class="mb-2">
                                    <strong>Update Document:</strong> Jika BL salah, mohon update dokumen terlebih dahulu
                                </li>
                                <li class="mb-0">
                                    <strong>PDF Quality:</strong> Pastikan PDF bisa dibaca dengan jelas
                                </li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="retryBLUpload()">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    const existingModal = document.getElementById('blValidationErrorModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add and show modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('blValidationErrorModal'));
    modal.show();
    
    console.error('âŒ BL Validation Details:', errorData);
}

function retryBLUpload() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('blValidationErrorModal'));
    if (modal) {
        modal.hide();
    }
    console.log('ðŸ”„ User can retry by selecting a new BL file');
}

/**
 * âœ… NEW: Retry BL upload - close modal dan biarkan user pilih file lagi
 */
function retryBLUpload() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('blValidationErrorModal'));
    if (modal) {
        modal.hide();
    }
    // User dapat retry dengan memilih file lagi dari form
    console.log('User can now retry by selecting a new BL file');
}

/**
 * ✅ NEW: Initialize progress bars untuk semua delivery orders saat page load
 */
async function initializeAllProgressBars() {
    console.log('📊 Initializing all progress bars...');
    
    // Cari semua form upload yang ada
    const forms = document.querySelectorAll('form[data-delivery][data-customer][data-unique-id]');
    
    console.log(`Found ${forms.length} delivery orders to initialize`);
    
    forms.forEach((form, index) => {
        const deliveryOrder = form.dataset.delivery;
        const customerName = form.dataset.customer;
        const uniqueId = form.dataset.uniqueId;
        const billingDocument = form.dataset.billing;
        
        console.log(`📦 Init progress [${index + 1}/${forms.length}]:`, {
            delivery: deliveryOrder,
            customer: customerName,
            uniqueId: uniqueId
        });
        
        // Delay untuk setiap delivery order
        setTimeout(() => {
            if (deliveryOrder && customerName && uniqueId) {
                updateProgressAfterUpload(deliveryOrder, customerName, uniqueId, billingDocument);
            }
        }, 300 * (index + 1)); // Delay 300ms per item
    });
}

// ✅ AUTO-INITIALIZE saat page load
$(document).ready(function() {
    console.log('🚀 Auto-initializing documents...');
    
    // 1. Load existing documents first
    $('form[data-delivery][data-customer][data-unique-id]').each(function(index, form) {
        const $form = $(form);
        const deliveryOrder = $form.data('delivery');
        const customerName = $form.data('customer');
        const uniqueId = $form.data('unique-id');
        const billingDocument = $form.data('billing');
        
        if (deliveryOrder && customerName && uniqueId) {
            setTimeout(() => {
                loadExistingDocuments(deliveryOrder, customerName, uniqueId, billingDocument);
            }, 100 * index);
        }
    });
    
    // 2. Initialize progress bars after a delay
    setTimeout(() => {
        initializeAllProgressBars();
    }, 1000); // Wait 1 second after page load
});

// ========================================
// ðŸ†• EXPORT FUNCTIONS TO WINDOW
// ========================================
window.loadAllowedDocuments = loadAllowedDocuments;
window.refreshCustomerSettings = refreshCustomerSettings;
window.loadExistingDocuments = loadExistingDocuments;
window.updateDropdownWithCheckmarks = updateDropdownWithCheckmarks;
window.previewDocument = previewDocument;
window.downloadDocument = downloadDocument;
window.deleteDocument = deleteDocument;
window.refreshDashboard = refreshDashboard;
window.retryLoadSettings = retryLoadSettings;

// ðŸ†• Export search functions
window.toggleSearchFilter = toggleSearchFilter;
window.toggleAdvancedFilter = toggleAdvancedFilter;
window.performQuickSearch = performQuickSearch;
window.clearSearch = clearSearch;
window.clearAllFilters = clearAllFilters;
window.applyAdvancedFilters = applyAdvancedFilters;

console.log('âœ… EXIM Dashboard initialized with AUTO-UPLOAD BLOCKING & FIXED SEARCH');
</script>
@endsection