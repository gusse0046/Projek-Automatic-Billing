@extends('layouts.dashboard')

@section('title', 'Admin Finance Dashboard - Document Management')

@section('styles')
<style>
/* ========================================
   CSS VARIABLES & FOUNDATION
   ======================================== */
:root {
    --finance-primary: #1b5e20;
    --finance-secondary: #2e7d32;
    --finance-accent: #388e3c;
    --finance-light: #81c784;
    --finance-bg: #f1f8e9;
    --finance-shadow: 0 4px 12px rgba(27, 94, 32, 0.08);
    --location-surabaya: #2563eb;
    --location-semarang: #7c3aed;
    --location-unknown: #6b7280;
}

/* ========================================
   MAIN CONTAINER & HEADER
   ======================================== */
.main-container {
    background: linear-gradient(135deg, #f1f8e9 0%, #dcedc8 100%);
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
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: var(--finance-shadow);
    border: 1px solid #e5e7eb;
}

.page-title {
    color: var(--finance-primary);
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 0;
}

/* ========================================
   STATISTICS CARDS - SIMPLE
   ======================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--finance-shadow);
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(27, 94, 32, 0.12);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--finance-primary);
    margin-bottom: 5px;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* ========================================
   SEARCH & FILTER - COLLAPSED
   ======================================== */
.search-filter-container {
    background: white;
    border-radius: 12px;
    box-shadow: var(--finance-shadow);
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
    overflow: hidden;
}

.search-filter-header {
    background: var(--finance-primary);
    padding: 15px 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.search-filter-header:hover {
    background: var(--finance-secondary);
}

.filter-title {
    font-size: 1rem;
    font-weight: 600;
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
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-filter-toggle:hover, .btn-filter-clear:hover {
    background: rgba(255, 255, 255, 0.3);
}

.search-toggle-icon {
    font-size: 1rem;
    transition: transform 0.2s ease;
}

.search-toggle-icon.rotated {
    transform: rotate(180deg);
}

.search-filter-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.search-filter-content.expanded {
    max-height: 1000px !important;
}

.search-filter-inner {
    padding: 20px;
}

.search-input-group {
    position: relative;
    margin-bottom: 15px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--finance-primary);
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 45px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
}

.search-input:focus {
    outline: none;
    border-color: var(--finance-primary);
    box-shadow: 0 0 0 3px rgba(27, 94, 32, 0.1);
}

.search-clear-btn {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
}

.search-results-counter {
    color: var(--finance-primary);
    font-weight: 600;
    font-size: 0.875rem;
}

.advanced-filters {
    border-top: 1px solid #f3f4f6;
    padding-top: 15px;
    margin-top: 15px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.filter-input, .filter-select {
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.875rem;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--finance-primary);
    box-shadow: 0 0 0 2px rgba(27, 94, 32, 0.1);
}

/* ========================================
   TABLE LAYOUT - MAIN
   ======================================== */
.delivery-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: var(--finance-shadow);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.delivery-table-header {
    background: var(--finance-primary);
    padding: 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.delivery-table-title {
    font-size: 1.1rem;
    font-weight: 600;
}

.delivery-table {
    width: 100%;
    border-collapse: collapse;
}

.delivery-table thead {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.delivery-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.8rem;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.delivery-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.15s ease;
}

.delivery-table tbody tr:hover {
    background: rgba(27, 94, 32, 0.02);
}

.delivery-table td {
    padding: 16px;
    font-size: 0.875rem;
    color: #374151;
    vertical-align: top;
}

/* Column Widths */
.col-info { width: 30%; }
.col-documents { width: 30%; }
.col-progress { width: 20%; }
.col-actions { width: 20%; }

/* ========================================
   TABLE CELL COMPONENTS
   ======================================== */

/* Combined Info Cell */
.combined-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.info-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
    min-width: 80px;
}

.info-value {
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
}

.info-value.delivery-number {
    font-weight: 600;
    color: #111827;
}

.info-value.customer-name {
    font-weight: 600;
    color: #111827;
}

.info-value.billing-doc {
    font-weight: 600;
    color: #374151;
}

.info-value.billing-value {
    color: #059669;
    font-weight: 700;
    font-family: monospace;
}

.info-separator {
    color: #d1d5db;
    margin: 0 4px;
}

.location-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-left: 4px;
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

/* Documents Cell */
.documents-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.doc-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.doc-type {
    font-size: 0.7rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
}

.doc-status {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
}

.doc-status.uploaded {
    color: #059669;
}

.doc-status.missing {
    color: #dc2626;
}

.doc-status i {
    font-size: 0.7rem;
}

.doc-file-name {
    font-size: 0.7rem;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.doc-actions-mini {
    display: flex;
    gap: 4px;
}

.doc-btn-mini {
    padding: 2px 6px;
    border: none;
    border-radius: 4px;
    font-size: 0.65rem;
    cursor: pointer;
}

.doc-btn-mini.view {
    background: #e0f2fe;
    color: #0277bd;
}

.doc-btn-mini.download {
    background: #e8f5e9;
    color: #2e7d32;
}

/* Progress Cell */
.progress-cell {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.progress-bar-wrapper {
    width: 100%;
}

.progress-bar-container {
    background: #f3f4f6;
    border-radius: 8px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-bar {
    height: 100%;
    border-radius: 8px;
    transition: width 0.3s ease;
}

.progress-bar.low {
    background: #ef4444;
}

.progress-bar.medium {
    background: #f59e0b;
}

.progress-bar.high {
    background: #10b981;
}

.progress-text {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
}

.progress-docs {
    display: flex;
    gap: 8px;
    font-size: 0.7rem;
}

.progress-doc-count {
    display: flex;
    align-items: center;
    gap: 4px;
}

.progress-doc-count.finance {
    color: #059669;
}

.progress-doc-count.exim {
    color: #0277bd;
}

/* Actions Cell */
.actions-cell {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn-action {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-send {
    background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
    color: white;
}

.btn-send:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(27, 94, 32, 0.3);
}

.btn-send:disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.btn-send.sent {
    background: #059669;
}

.action-hint {
    font-size: 0.7rem;
    color: #6b7280;
    text-align: center;
}

/* ========================================
   âœ… NEW: GLOBAL AUTO-UPLOAD BUTTON STYLES
   ======================================== */
.btn.btn-success {
    background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
}

.btn.btn-success:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn.btn-success i {
    font-size: 1rem;
}

.btn.btn-outline-info {
    background: transparent;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.btn.btn-outline-info:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

/* ========================================
   âœ… NEW: EXIM DOCUMENTS DROPDOWN STYLES
   ======================================== */
.exim-dropdown-container {
    margin-top: 12px;
    border-top: 1px solid #e5e7eb;
    padding-top: 8px;
}

.exim-dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
}

.exim-dropdown-toggle:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.exim-toggle-label {
    display: flex;
    align-items: center;
    gap: 6px;
}

.exim-toggle-icon {
    transition: transform 0.2s ease;
    font-size: 0.7rem;
}

.exim-toggle-icon.expanded {
    transform: rotate(180deg);
}

.exim-docs-badge {
    background: #dbeafe;
    color: #0277bd;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
}

.exim-dropdown-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.exim-dropdown-content.expanded {
    max-height: 500px;
    overflow-y: auto;
}

.exim-docs-list {
    padding: 8px 0;
}

.exim-doc-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    margin: 4px 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.exim-doc-item:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.exim-doc-info {
    flex: 1;
    min-width: 0;
}

.exim-doc-type-label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #0277bd;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.exim-doc-filename {
    font-size: 0.7rem;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.exim-doc-actions {
    display: flex;
    gap: 4px;
    margin-left: 8px;
}

.exim-doc-btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    font-size: 0.65rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 3px;
}

.exim-doc-btn.view {
    background: #e0f2fe;
    color: #0277bd;
}

.exim-doc-btn.view:hover {
    background: #bae6fd;
}

.exim-doc-btn.download {
    background: #e8f5e9;
    color: #2e7d32;
}

.exim-doc-btn.download:hover {
    background: #c8e6c9;
}

.no-exim-docs {
    padding: 12px;
    text-align: center;
    color: #9ca3af;
    font-size: 0.7rem;
    font-style: italic;
}

/* ========================================
   ALERTS & NOTIFICATIONS
   ======================================== */
.alert {
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 12px;
    border: none;
    font-size: 0.875rem;
}

.alert-success {
    background: rgba(56, 142, 60, 0.1);
    color: #2e7d32;
    border-left: 3px solid #388e3c;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border-left: 3px solid #f59e0b;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-left: 3px solid #ef4444;
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border-left: 3px solid #3b82f6;
}

/* ========================================
   MODAL STYLES
   ======================================== */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: var(--finance-primary);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 20px;
}

.modal-title {
    font-weight: 600;
}

.modal-body {
    padding: 20px;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.form-control {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px 12px;
}

.form-control:focus {
    border-color: var(--finance-primary);
    box-shadow: 0 0 0 3px rgba(27, 94, 32, 0.1);
}

.form-check-input:checked {
    background-color: var(--finance-primary);
    border-color: var(--finance-primary);
}

/* ========================================
   TOAST NOTIFICATIONS
   ======================================== */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1055;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 1px solid #e5e7eb;
    min-width: 300px;
    max-width: 400px;
}

.toast-header {
    background: var(--finance-primary);
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 8px 8px 0 0;
}

.toast-body {
    padding: 15px;
    font-size: 0.85rem;
}

/* ========================================
   REFRESH FAB
   ======================================== */
.refresh-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--finance-primary);
    color: white;
    border: none;
    box-shadow: 0 8px 25px rgba(27, 94, 32, 0.4);
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.refresh-fab:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 30px rgba(27, 94, 32, 0.5);
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */
@media (max-width: 1200px) {
    .documents-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 968px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .delivery-table {
        font-size: 0.8rem;
    }
    
    .delivery-table th,
    .delivery-table td {
        padding: 12px;
    }
}

@media (max-width: 768px) {
    .main-container {
        padding: 15px 0;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    /* Mobile: Stack table cells */
    .delivery-table thead {
        display: none;
    }
    
    .delivery-table tbody tr {
        display: block;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .delivery-table td {
        display: block;
        text-align: right;
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .delivery-table td:last-child {
        border-bottom: none;
    }
    
    .delivery-table td:before {
        content: attr(data-label);
        float: left;
        font-weight: 600;
        color: #6b7280;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
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
</style>
@endsection

@section('content')
<div class="main-container">
    <div class="container-fluid">
{{-- CONDITION 1: No location selected - SHOW OVERVIEW --}}
@if(!isset($locationFilter) || $locationFilter === 'all')
    
    @if(isset($showOverview) && $showOverview && isset($buyerFinancialSummary))
        
        {{-- ✅ OVERVIEW HEADER --}}
        <div class="page-header">
            <h2 class="page-title">
                
                Overview Dashboard - Masih dalam penyesuaian data dengan SAP
            </h2>
            <p class="text-muted">TIDAK AKAN MENGGANGGU FUNGSI SYSTEM PENAGIHAN</p>
        </div>
        
        {{-- ✅ HITUNG TOTAL PER CURRENCY --}}
        @php
            $currencyTotals = [
                'surabaya' => [],
                'semarang' => []
            ];
            
            foreach($buyerFinancialSummary as $buyer) {
                // Surabaya totals
                foreach($buyer['surabaya_totals'] as $curr => $amount) {
                    if (!isset($currencyTotals['surabaya'][$curr])) {
                        $currencyTotals['surabaya'][$curr] = 0;
                    }
                    $currencyTotals['surabaya'][$curr] += $amount;
                }
                
                // Semarang totals
                foreach($buyer['semarang_totals'] as $curr => $amount) {
                    if (!isset($currencyTotals['semarang'][$curr])) {
                        $currencyTotals['semarang'][$curr] = 0;
                    }
                    $currencyTotals['semarang'][$curr] += $amount;
                }
            }
            
            // Grand totals per currency
            $grandTotals = [];
            foreach(array_merge(array_keys($currencyTotals['surabaya']), array_keys($currencyTotals['semarang'])) as $curr) {
                if (!isset($grandTotals[$curr])) {
                    $grandTotals[$curr] = 0;
                }
                $grandTotals[$curr] += ($currencyTotals['surabaya'][$curr] ?? 0) + ($currencyTotals['semarang'][$curr] ?? 0);
            }
        @endphp
        
        {{-- ✅ SUMMARY CARDS - FIXED VERSION --}}
        <div class="stats-grid">
            {{-- Surabaya Total Card --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white;">
                <div class="stat-number">
                    @foreach($currencyTotals['surabaya'] as $curr => $amount)
                        <div style="font-size: 1.3rem; margin: 5px 0;">
                            {{ $curr }} {{ number_format($amount, 2) }}
                        </div>
                    @endforeach
                </div>
                <div class="stat-label" style="color: rgba(255,255,255,0.9);">Surabaya Total</div>
            </div>
            
            {{-- Semarang Total Card --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white;">
                <div class="stat-number">
                    @foreach($currencyTotals['semarang'] as $curr => $amount)
                        <div style="font-size: 1.3rem; margin: 5px 0;">
                            {{ $curr }} {{ number_format($amount, 2) }}
                        </div>
                    @endforeach
                </div>
                <div class="stat-label" style="color: rgba(255,255,255,0.9);">Semarang Total</div>
            </div>
            
            {{-- Grand Total Card --}}
            <div class="stat-card" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white;">
                <div class="stat-number">
                    @foreach($grandTotals as $curr => $amount)
                        <div style="font-size: 1.3rem; margin: 5px 0;">
                            {{ $curr }} {{ number_format($amount, 2) }}
                        </div>
                    @endforeach
                </div>
                <div class="stat-label" style="color: rgba(255,255,255,0.9);">Grand Total</div>
            </div>
        </div>
        
        {{-- ✅ BUYER BREAKDOWN TABLE --}}
        <div class="delivery-table-container" style="margin-top: 25px;">
            <div class="delivery-table-header">
                <div class="delivery-table-title">
                    
                    Amount Net Value per Buyer
                </div>
                <span class="badge bg-light text-success">{{ count($buyerFinancialSummary) }} Buyers</span>
            </div>
            
            <table class="delivery-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Buyer Name</th>
                        <th style="width: 20%; text-align: right;">Surabaya Total</th>
                        <th style="width: 20%; text-align: right;">Semarang Total</th>
                        <th style="width: 20%; text-align: right;">Grand Total</th>
                        <th style="width: 10%; text-align: center;">Deliveries</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($buyerFinancialSummary as $buyer)
                        <tr>
                            {{-- Buyer Name --}}
                            <td>
                                <div style="font-weight: 600; color: #111827;">
                                    {{ $buyer['name'] }}
                                </div>
                            </td>
                            
                            {{-- Surabaya Total --}}
                            <td style="text-align: right;">
                                @foreach($buyer['surabaya_totals'] as $curr => $amount)
                                    <div style="font-family: monospace; font-weight: 600; color: #2563eb;">
                                        {{ $curr }} {{ number_format($amount, 2) }}
                                    </div>
                                @endforeach
                                @if(empty($buyer['surabaya_totals']))
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            {{-- Semarang Total --}}
                            <td style="text-align: right;">
                                @foreach($buyer['semarang_totals'] as $curr => $amount)
                                    <div style="font-family: monospace; font-weight: 600; color: #7c3aed;">
                                        {{ $curr }} {{ number_format($amount, 2) }}
                                    </div>
                                @endforeach
                                @if(empty($buyer['semarang_totals']))
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            {{-- Grand Total --}}
                            <td style="text-align: right;">
                                @php
                                    $buyerGrandTotals = [];
                                    foreach($buyer['surabaya_totals'] as $curr => $amount) {
                                        if (!isset($buyerGrandTotals[$curr])) $buyerGrandTotals[$curr] = 0;
                                        $buyerGrandTotals[$curr] += $amount;
                                    }
                                    foreach($buyer['semarang_totals'] as $curr => $amount) {
                                        if (!isset($buyerGrandTotals[$curr])) $buyerGrandTotals[$curr] = 0;
                                        $buyerGrandTotals[$curr] += $amount;
                                    }
                                @endphp
                                @foreach($buyerGrandTotals as $curr => $amount)
                                    <div style="font-family: monospace; font-weight: 700; color: #059669; font-size: 1.05rem;">
                                        {{ $curr }} {{ number_format($amount, 2) }}
                                    </div>
                                @endforeach
                            </td>
                            
                            {{-- Delivery Count --}}
                            <td style="text-align: center;">
                                <span class="badge bg-primary">{{ $buyer['delivery_count'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                
                {{-- Table Footer with Totals --}}
                <tfoot>
                    <tr style="background: #f9fafb; font-weight: 700; border-top: 2px solid #1b5e20;">
                        <td style="text-align: right; padding-right: 20px;">
                            <strong>TOTAL:</strong>
                        </td>
                        <td style="text-align: right;">
                            @foreach($currencyTotals['surabaya'] as $curr => $amount)
                                <div style="font-family: monospace; color: #2563eb; font-size: 1.1rem;">
                                    {{ $curr }} {{ number_format($amount, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: right;">
                            @foreach($currencyTotals['semarang'] as $curr => $amount)
                                <div style="font-family: monospace; color: #7c3aed; font-size: 1.1rem;">
                                    {{ $curr }} {{ number_format($amount, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: right;">
                            @foreach($grandTotals as $curr => $amount)
                                <div style="font-family: monospace; color: #059669; font-size: 1.2rem;">
                                    {{ $curr }} {{ number_format($amount, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            <span class="badge bg-success">{{ array_sum(array_column($buyerFinancialSummary, 'delivery_count')) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
    @else
        {{-- No data fallback --}}
        <div style="text-align: center; padding: 100px 20px;">
            <i class="fas fa-database" style="font-size: 4rem; color: #9ca3af; margin-bottom: 20px;"></i>
            <h3 style="color: #1b5e20; margin-bottom: 15px;">No Data Available</h3>
            <p class="text-muted" style="font-size: 1.1rem;">Unable to load overview data</p>
            <button class="btn btn-success mt-3" onclick="window.location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    @endif

        {{-- CONDITION 2: Location selected, no buyer --}}
        @elseif(!isset($selectedBuyer) || empty($selectedBuyer))
            <div style="text-align: center; padding: 100px 20px;">
                <i class="fas fa-building" style="font-size: 4rem; color: #1b5e20; margin-bottom: 20px;"></i>
                <h3 style="color: #1b5e20; margin-bottom: 15px;">{{ ucfirst($locationFilter) }} - Finance Dashboard</h3>
                <p class="text-muted" style="font-size: 1.1rem;">Silakan pilih buyer dari sidebar untuk melihat detail finance</p>
            </div>

        {{-- CONDITION 3: Buyer selected - show full dashboard --}}
        @else
            {{-- Back button --}}
            <div class="mb-3">
                <a href="{{ route('dashboard.admin-finance') }}?location={{ $locationFilter }}" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke {{ ucfirst($locationFilter) }}
                </a>
            </div>

            {{-- ✅ UPDATED: Statistics Cards dengan logika baru --}}
            @if(isset($groupedData) && count($groupedData) > 0)
                @php
                    // ✅ HITUNG STATUS BERDASARKAN PROGRESS
                    $totalOutstanding = 0;
                    $totalProgress = 0;
                    $totalCompleted = 0;
                    $totalSent = 0;

                    foreach ($groupedData as $key => $group) {
                        // Get progress data
                        $progress = $progressData[$key] ?? null;
                        
                        if ($progress) {
                            $overallProgress = $progress['overall_progress_percentage'] ?? 0;
                            $status = $progress['status'] ?? 'outstanding';
                            
                            // ✅ STATUS LOGIC BARU
                            if ($status === 'sent') {
                                $totalSent++;
                            } elseif ($overallProgress >= 100) {
                                $totalCompleted++;
                            } elseif ($overallProgress > 0 && $overallProgress < 100) {
                                $totalProgress++;
                            } else {
                                $totalOutstanding++;
                            }
                        } else {
                            $totalOutstanding++;
                        }
                    }
                @endphp

                <div class="stats-grid">
                    <div class="stat-card" onclick="filterByStatus('outstanding')">
                        <div class="stat-number">{{ $totalOutstanding }}</div>
                        <div class="stat-label">Outstanding</div>
                    </div>

                    <div class="stat-card" onclick="filterByStatus('progress')">
                        <div class="stat-number">{{ $totalProgress }}</div>
                        <div class="stat-label">In Progress</div>
                    </div>

                    <div class="stat-card" onclick="filterByStatus('completed')">
                        <div class="stat-number">{{ $totalCompleted }}</div>
                        <div class="stat-label">Completed</div>
                    </div>

                    <div class="stat-card" onclick="filterByStatus('sent')">
                        <div class="stat-number">{{ $totalSent }}</div>
                        <div class="stat-label">Billed</div>
                    </div>
                </div>

                {{-- Search & Filter Section --}}
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
                    
                    <div class="search-filter-content" id="searchFilterContent" style="max-height: 0;">
                        <div class="search-filter-inner">
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
                                            <i class="fas fa-tasks"></i>
                                            Status
                                        </label>
                                        <select id="statusFilter" class="filter-select" onchange="applyAdvancedFilters()">
                                            <option value="">All Statuses</option>
                                            <option value="outstanding">Outstanding</option>
                                            <option value="progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="sent">Billed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="filter-group">
                                        <label class="filter-label">
                                            <i class="fas fa-file-alt"></i>
                                            Document Status
                                        </label>
                                        <select id="documentStatusFilter" class="filter-select" onchange="applyAdvancedFilters()">
                                            <option value="">All Document Status</option>
                                            <option value="complete">Finance Complete</option>
                                            <option value="incomplete">Finance Incomplete</option>
                                            <option value="has-exim">Has EXIM Docs</option>
                                            <option value="no-documents">No Documents</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Delivery Table --}}
                <div class="delivery-table-container">
                    <div class="delivery-table-header">
                        <div class="delivery-table-title">
                            Delivery Orders & Finance Documents
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            {{-- Global Auto-Upload Button --}}
                            <button class="btn btn-success btn-sm" 
                                    id="batchAutoUploadBtn"
                                    onclick="batchAutoUploadAllDeliveries()" 
                                    title="Auto-upload documents from Z:\sd for all deliveries in this buyer"
                                    style="font-weight: 600;">
                                <span>Auto</span>
                            </button>
                            
                            <span class="badge bg-light text-success">{{ count($groupedData) }} Deliveries</span>
                        </div>
                    </div>

                    <table class="delivery-table">
                        <thead>
                            <tr>
                                <th class="col-info">Delivery Information</th>
                                <th class="col-documents">Finance Documents</th>
                                <th class="col-progress">Upload Progress</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                      <tbody>
    @foreach($groupedData as $key => $group)
        @if($group['delivery'] == '2020003362')
            @php
                Log::info("BLADE RENDERING 2020003362", [
                    'key' => $key,
                    'has_progress' => isset($progressData[$key]),
                    'delivery' => $group['delivery'],
                    'billing_doc' => $group['billing_document'] ?? 'N/A'
                ]);
            @endphp
        @endif
        
        
                                @php
                                    // ✅ GET PROGRESS DATA
                                    $progress = $progressData[$key] ?? null;
                                    $overallProgress = $progress['overall_progress_percentage'] ?? 0;
                                    $currentStatus = $progress['status'] ?? 'outstanding';
                                    
                                    // ✅ DETERMINE STATUS BERDASARKAN PROGRESS
                                    if ($currentStatus === 'sent') {
                                        $displayStatus = 'sent';
                                    } elseif ($overallProgress >= 100) {
                                        $displayStatus = 'completed';
                                    } elseif ($overallProgress > 0) {
                                        $displayStatus = 'progress';
                                    } else {
                                        $displayStatus = 'outstanding';
                                    }
                                @endphp
                                
                                <tr class="delivery-row" 
    data-delivery="{{ $group['delivery'] }}" 
    data-customer="{{ $group['customer_name'] }}" 
    data-status="{{ $displayStatus }}"
    data-progress="{{ $overallProgress }}">
                                    
                                    {{-- Combined Delivery Info --}}
                                    <td class="col-info" data-label="Delivery Info">
                                        <div class="combined-info">
                                            <div class="info-row">
                                                <span class="info-label">Delivery:</span>
                                        <span class="info-value delivery-number">{{ $group['delivery'] }}
                                               </span>
                                            </div>
                                            
                                         <div class="info-row">
    <span class="info-label">Billing Doc:</span>
    <span class="info-value billing-doc">
        {{ $group['billing_document'] ?? $group['delivery'] }}
    </span>
</div>
                                            <div class="info-row">
                                                <span class="info-label">Net Value:</span>
                                                <span class="info-value billing-value">${{ number_format($group['total_net_value'], 2) }}</span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Date:</span>
                                                <span class="info-value">{{ $group['billing_date_display'] ?? '-' }}</span>
                                            </div>
                                            
                                           <div class="info-row">
    <span class="info-label">Shipping Ins:</span>
    <span class="info-value booking-number">{{ $group['booking_number'] ?? '-' }}</span>
</div>
                                        </div>
                                    </td>

                                    {{-- Finance Documents --}}
                                    <td class="col-documents" data-label="Documents">
                                        <div class="documents-grid">
                                            {{-- Invoice --}}
                                            <div class="doc-item">
                                                <div class="doc-type">Invoice</div>
                                                @if(isset($allTeamDocuments[$key]['finance_documents']['INVOICE']))
                                                    @php $doc = $allTeamDocuments[$key]['finance_documents']['INVOICE'] @endphp
                                                    <div class="doc-status uploaded">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>Uploaded</span>
                                                    </div>
                                                    <div class="doc-file-name" title="{{ $doc['file_name'] }}">
                                                        {{ \Illuminate\Support\Str::limit($doc['file_name'], 15) }}
                                                    </div>
                                                    <div class="doc-actions-mini">
                                                        <button class="doc-btn-mini view" onclick="previewDocument({{ $doc['id'] }})" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="doc-btn-mini download" onclick="downloadDocument({{ $doc['id'] }})" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="doc-status missing">
                                                        <i class="fas fa-times-circle"></i>
                                                        <span>Missing</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Packing List --}}
                                            <div class="doc-item">
                                                <div class="doc-type">PL</div>
                                                @if(isset($allTeamDocuments[$key]['finance_documents']['PACKING_LIST']))
                                                    @php $doc = $allTeamDocuments[$key]['finance_documents']['PACKING_LIST'] @endphp
                                                    <div class="doc-status uploaded">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>Uploaded</span>
                                                    </div>
                                                    <div class="doc-file-name" title="{{ $doc['file_name'] }}">
                                                        {{ \Illuminate\Support\Str::limit($doc['file_name'], 15) }}
                                                    </div>
                                                    <div class="doc-actions-mini">
                                                        <button class="doc-btn-mini view" onclick="previewDocument({{ $doc['id'] }})" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="doc-btn-mini download" onclick="downloadDocument({{ $doc['id'] }})" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="doc-status missing">
                                                        <i class="fas fa-times-circle"></i>
                                                        <span>Missing</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Payment Instruction --}}
                                            <div class="doc-item">
                                                <div class="doc-type">PI</div>
                                                @if(isset($allTeamDocuments[$key]['finance_documents']['PAYMENT_INTRUCTION']))
                                                    @php $doc = $allTeamDocuments[$key]['finance_documents']['PAYMENT_INTRUCTION'] @endphp
                                                    <div class="doc-status uploaded">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span>Uploaded</span>
                                                    </div>
                                                    <div class="doc-file-name" title="{{ $doc['file_name'] }}">
                                                        {{ \Illuminate\Support\Str::limit($doc['file_name'], 15) }}
                                                    </div>
                                                    <div class="doc-actions-mini">
                                                        <button class="doc-btn-mini view" onclick="previewDocument({{ $doc['id'] }})" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="doc-btn-mini download" onclick="downloadDocument({{ $doc['id'] }})" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="doc-status missing">
                                                        <i class="fas fa-times-circle"></i>
                                                        <span>Missing</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- EXIM Documents Dropdown --}}
                                        @if(isset($allTeamDocuments[$key]['exim_documents']) && count($allTeamDocuments[$key]['exim_documents']) > 0)
                                            <div class="exim-dropdown-container">
                                                <div class="exim-dropdown-toggle" onclick="toggleEximDropdown('{{ $key }}')">
                                                    <div class="exim-toggle-label">
                                                        <i class="fas fa-file-export"></i>
                                                        <span>EXIM Documents</span>
                                                        <span class="exim-docs-badge">
                                                            {{ array_sum(array_map('count', $allTeamDocuments[$key]['exim_documents'])) }}
                                                        </span>
                                                    </div>
                                                    <i class="fas fa-chevron-down exim-toggle-icon" id="exim-icon-{{ $key }}"></i>
                                                </div>
                                                
                                                <div class="exim-dropdown-content" id="exim-content-{{ $key }}">
                                                    <div class="exim-docs-list">
                                                        @foreach($allTeamDocuments[$key]['exim_documents'] as $docType => $docs)
                                                            @foreach($docs as $doc)
                                                                <div class="exim-doc-item">
                                                                    <div class="exim-doc-info">
                                                                        <div class="exim-doc-type-label">{{ $docType }}</div>
                                                                        <div class="exim-doc-filename" title="{{ $doc['file_name'] }}">
                                                                            {{ $doc['file_name'] }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="exim-doc-actions">
                                                                        <button class="exim-doc-btn view" 
                                                                                onclick="previewDocument({{ $doc['id'] }})" 
                                                                                title="View">
                                                                            <i class="fas fa-eye"></i>
                                                                            <span>View</span>
                                                                        </button>
                                                                        <button class="exim-doc-btn download" 
                                                                                onclick="downloadDocument({{ $doc['id'] }})" 
                                                                                title="Download">
                                                                            <i class="fas fa-download"></i>
                                                                            <span>Download</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Upload Progress --}}
                                    <td class="col-progress" data-label="Progress">
                                        <div class="progress-cell">
                                            <div class="progress-bar-wrapper">
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar {{ $overallProgress >= 100 ? 'high' : ($overallProgress >= 50 ? 'medium' : 'low') }}" 
                                                         id="progress-bar-{{ $key }}"
                                                         style="width: {{ $overallProgress }}%"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span id="progress-percentage-{{ $key }}">{{ $overallProgress }}</span>% Complete
                                                </div>
                                            </div>
                                            <div class="progress-docs">
                                                <div class="progress-doc-count finance">
                                                    <i class="fas fa-calculator"></i>
                                                    <span id="finance-uploaded-{{ $key }}">{{ $progress['finance_uploaded'] ?? 0 }}</span>/<span id="finance-total-{{ $key }}">{{ $progress['finance_total'] ?? 3 }}</span>
                                                </div>
                                                <div class="progress-doc-count exim">
                                                    <i class="fas fa-file-export"></i>
                                                    <span id="exim-uploaded-{{ $key }}">{{ $progress['exim_uploaded'] ?? 0 }}</span>/<span id="exim-total-{{ $key }}">{{ $progress['exim_total'] ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Actions Cell --}}
                                    <td class="col-actions" data-label="Actions">
                                        <div class="actions-cell">
                                            @if($displayStatus !== 'sent')
                                                <button class="btn-action btn-send {{ $overallProgress >= 100 ? 'sent' : '' }}" 
                                                        id="send-btn-{{ $key }}"
                                                        data-delivery="{{ $key }}"
                                                        data-customer="{{ $group['customer_name'] }}"
                                                        onclick="openSendToBuyerModal('{{ $key }}', '{{ $group['customer_name'] }}')"
                                                        {{ $overallProgress < 100 ? 'disabled' : '' }}>
                                                    <i class="fas fa-paper-plane"></i>
                                                    {{ $overallProgress >= 100 ? 'Ready to Send' : 'Send to Buyer' }}
                                                </button>
                                                <div class="action-hint" id="send-btn-hint-{{ $key }}">
                                                    @if($overallProgress >= 100)
                                                        <i class="fas fa-check-circle"></i> All documents ready
                                                    @else
                                                        {{ $progress['missing_documents'] ? count($progress['missing_documents']) : 0 }} docs missing
                                                    @endif
                                                </div>
                                            @else
                                                <div class="alert alert-success" style="margin: 0; padding: 8px 12px;">
                                                    <i class="fas fa-check-circle"></i>
                                                    Sent
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                {{-- ✅ ELSE untuk NO DATA --}}
                <div class="delivery-table-container">
                    <div class="delivery-table-header">
                        <div class="delivery-table-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            No Data Available
                        </div>
                    </div>
                    <div style="text-align: center; padding: 80px 20px;">
                        <i class="fas fa-database" style="font-size: 3rem; color: #9ca3af; margin-bottom: 15px;"></i>
                        <h4>No delivery orders found</h4>
                        <p>Please check your connection or refresh the data.</p>
                        <button class="btn-action btn-send" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh Now
                        </button>
                    </div>
                </div>
            @endif
            {{-- ✅ TUTUP @endif untuk if(isset($groupedData)) --}}
            
        @endif
        {{-- ✅ TUTUP @endif untuk elseif selectedBuyer --}}
    </div>
</div>

{{-- Toast Container --}}
<div class="toast-container" id="toast-container"></div>

{{-- Refresh FAB --}}
<button class="refresh-fab" onclick="refreshDashboard()">
    <i class="fas fa-sync-alt"></i>
</button>

{{-- Send to Buyer Modal --}}
<div class="modal fade" id="sendToBuyerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane me-2"></i>
                    Send Documents to Buyer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendToBuyerForm">
                    <input type="hidden" id="modal_delivery_order" name="delivery_order">
                    <input type="hidden" id="modal_customer_name" name="customer_name">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Buyer Emails</label>
                        <div id="buyer_emails_container">
                            <div class="text-muted">Loading buyer emails...</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Message (Optional)</label>
                        <textarea class="form-control" name="email_message" rows="3" placeholder="Add custom message for buyer..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Internal)</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Internal notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="sendToBuyer()">
                    <i class="fas fa-paper-plane me-1"></i>
                    Send Documents
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
// ========================================
// GLOBAL VARIABLES
// ========================================
let originalDeliveryRows = [];
let isAdvancedFilterOpen = false;
let currentLocation = '{{ $locationFilter ?? "all" }}';
let autoMonitoringInterval = null;
let monitoringInterval = null;
let isMonitoringActive = false;
let isSearchFilterExpanded = false;

let notificationUpdateInterval = null;

async function updateSidebarNotifications() {
    try {
        const currentLocation = '{{ $locationFilter ?? "all" }}';
        
        if (currentLocation === 'all') {
            console.log('Skip notification update - no location selected');
            return;
        }
        
        console.log('🔔 Updating sidebar notifications for:', currentLocation);
        
        const response = await fetch('/api/buyer-notifications?location=' + currentLocation, {
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
            updateNotificationBadges(result.notifications);
            console.log('✅ Notifications updated:', result.notifications);
        }
        
    } catch (error) {
        console.error('❌ Failed to update notifications:', error);
    }
}

function updateNotificationBadges(notifications) {
    console.log('=== UPDATING NOTIFICATION BADGES ===', notifications);
    
    // Loop through all buyer items in sidebar (targeting parent document)
    const buyerItems = parent.document.querySelectorAll('.buyer-item-sidebar');
    
    buyerItems.forEach(function(buyerItem) {
        const buyerName = buyerItem.getAttribute('data-buyer-name');
        
        if (!buyerName) return;
        
        const notifData = notifications[buyerName];
        
        if (!notifData) return;
        
        const completedCount = notifData.completed_count || 0;
        const billedCount = notifData.billed_count || 0;
        const totalCount = notifData.total_deliveries || 0;
        
        console.log('📊 Notification data for', buyerName, {
            total: totalCount,
            completed: completedCount,
            billed: billedCount
        });
        
        // Update data attributes
        buyerItem.setAttribute('data-completed-count', completedCount);
        buyerItem.setAttribute('data-billed-count', billedCount);
        buyerItem.setAttribute('data-delivery-count', totalCount);
        
        // Remove old notifications
        const existingNotifs = buyerItem.querySelector('.buyer-notifications-row');
        if (existingNotifs) {
            existingNotifs.remove();
        }
        
        // Add new notifications
        if (totalCount > 0 || completedCount > 0 || billedCount > 0) {
            const buyerNameDiv = buyerItem.querySelector('.buyer-name');
            
            if (!buyerNameDiv) return;
            
            let notifHTML = '<div class="buyer-notifications-row">';
            
            // 1. Total Badge (Blue)
            if (totalCount > 0) {
                notifHTML += `
                    <span class="notification-badge badge-total" 
                          title="${totalCount} total delivery order(s)">
                        <i class="fas fa-box"></i>
                        <span class="badge-count">${totalCount}</span>
                    </span>
                `;
            }
            
            // 2. Completed Badge (Orange/Yellow - Bell) - READY TO SEND
            if (completedCount > 0) {
                notifHTML += `
                    <span class="notification-badge badge-completed" 
                          title="${completedCount} completed - ready to send">
                        <i class="fas fa-bell"></i>
                        <span class="badge-count">${completedCount}</span>
                    </span>
                `;
            }
            
            // 3. Billed Badge (Green - Check) - ALREADY SENT
            if (billedCount > 0) {
                notifHTML += `
                    <span class="notification-badge badge-billed" 
                          title="${billedCount} already sent to buyer">
                        <i class="fas fa-check-circle"></i>
                        <span class="badge-count">${billedCount}</span>
                    </span>
                `;
            }
            
            notifHTML += '</div>';
            
            buyerNameDiv.insertAdjacentHTML('beforeend', notifHTML);
            
            console.log('✅ Badges updated for:', buyerName);
        }
    });
}

function startNotificationAutoUpdate() {
    console.log('🚀 Starting notification auto-update (every 30s)...');
    
    // Update immediately
    updateSidebarNotifications();
    
    // Then update every 5 minutes (was 30 seconds - too heavy)
    notificationUpdateInterval = setInterval(function() {
        updateSidebarNotifications();
    }, 300000);
}

function stopNotificationAutoUpdate() {
    if (notificationUpdateInterval) {
        clearInterval(notificationUpdateInterval);
        notificationUpdateInterval = null;
        console.log('⏸️ Notification auto-update stopped');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const locationFilter = '{{ $locationFilter ?? "all" }}';
    const selectedBuyer = '{{ $selectedBuyer ?? "" }}';
    
    // Only start if location selected and buyer selected
    if (locationFilter !== 'all' && selectedBuyer) {
        console.log('📍 Location & Buyer selected - starting notification updates');
        startNotificationAutoUpdate();
    }
});

// Pause when page hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopNotificationAutoUpdate();
    } else {
        const locationFilter = '{{ $locationFilter ?? "all" }}';
        const selectedBuyer = '{{ $selectedBuyer ?? "" }}';
        
        if (locationFilter !== 'all' && selectedBuyer) {
            startNotificationAutoUpdate();
        }
    }
});

// Update after sending email
window.addEventListener('email-sent-success', function() {
    console.log('📧 Email sent - updating notifications...');
    setTimeout(function() {
        updateSidebarNotifications();
    }, 2000);
});

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== ADMIN FINANCE TABLE DASHBOARD INITIALIZED ===');
    
    originalDeliveryRows = Array.from(document.querySelectorAll('.delivery-row'));
    console.log('Found delivery rows:', originalDeliveryRows.length);
    
    const searchFilterContent = document.getElementById('searchFilterContent');
    if (searchFilterContent) {
        searchFilterContent.style.maxHeight = '0';
        isSearchFilterExpanded = false;
    }
    
    initializeFinanceDashboard();
    //initializeRealTimeMonitoring();
    //startAutoMonitoring();
    
    console.log('✅ Admin Finance Dashboard initialized successfully');
});

// ========================================
// ✅ NEW: TOGGLE EXIM DROPDOWN
// ========================================
function toggleEximDropdown(deliveryOrder) {
    const content = document.getElementById(`exim-content-${deliveryOrder}`);
    const icon = document.getElementById(`exim-icon-${deliveryOrder}`);
    
    if (content && icon) {
        const isExpanded = content.classList.contains('expanded');
        
        if (isExpanded) {
            content.classList.remove('expanded');
            icon.classList.remove('expanded');
            content.style.maxHeight = '0';
        } else {
            content.classList.add('expanded');
            icon.classList.add('expanded');
            content.style.maxHeight = content.scrollHeight + 'px';
        }
        
        console.log(`EXIM dropdown for ${deliveryOrder}: ${isExpanded ? 'COLLAPSED' : 'EXPANDED'}`);
    }
}

// ========================================
// PAGE VISIBILITY HANDLING
// ========================================
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (autoMonitoringInterval) {
            clearInterval(autoMonitoringInterval);
            console.log('Auto-monitoring paused (page hidden)');
        }
        pauseBackgroundMonitoring();
    } else {
        startAutoMonitoring();
        resumeBackgroundMonitoring();
        console.log('Auto-monitoring resumed');
    }
});

// ========================================
// AUTO-MONITORING SYSTEM
// ========================================
function startAutoMonitoring() {
    console.log('Starting automatic Z:\\sd monitoring...');
    
    // Auto-monitoring dari browser dinonaktifkan
    // Gunakan Kernel.php schedule (jam 07:00 dan 08:30) untuk scan Z:\sd
    console.log('Auto-monitoring disabled - using server-side schedule instead');
}

async function checkForNewFilesQuietly() {
    try {
        console.log('Checking Z:\\sd for new files...');
        
        const response = await fetch('/smartform/monitor-auto-upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            console.warn('Monitor check failed:', response.statusText);
            return;
        }

        const result = await response.json();
        
        if (result.success && result.results) {
            const uploaded = result.results.summary.uploaded || 0;
            
            if (uploaded > 0) {
                console.log('Auto-uploaded ' + uploaded + ' files from Z:\\sd');
                
                setTimeout(function() {
                    console.log('Refreshing page to show new uploads...');
                    window.location.reload();
                }, 3000);
            } else {
                console.log('No new files to upload');
            }
        }
        
    } catch (error) {
        console.error('Auto-monitoring error:', error);
    }
}

// ========================================
// ✅ NEW: BATCH AUTO-UPLOAD - ALL DELIVERIES
// ========================================
async function batchAutoUploadAllDeliveries() {
    const button = document.getElementById('batchAutoUploadBtn');
    const originalText = button.innerHTML;
    
    try {
        console.log('=== BATCH AUTO-UPLOAD ALL DELIVERIES ===');
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
        
        const deliveryRows = document.querySelectorAll('.delivery-row');
        const deliveriesData = [];
        
        deliveryRows.forEach(function(row) {
            const delivery = row.dataset.delivery;
            const customer = row.dataset.customer;
            
            const billingDocElement = row.querySelector('.info-value.billing-doc');
            const billingDoc = billingDocElement ? billingDocElement.textContent.trim() : delivery;
            
            if (delivery && customer) {
                deliveriesData.push({
                    delivery_order: delivery,
                    customer_name: customer,
                    billing_document: billingDoc
                });
            }
        });
        
        if (deliveriesData.length === 0) {
            showToast('Error', 'No deliveries found to process', 'error');
            return;
        }
        
        console.log('Processing ' + deliveriesData.length + ' deliveries:', deliveriesData);
        
        showToast('Processing', 
            'Scanning Z:\\sd for ' + deliveriesData.length + ' deliveries. Please wait...', 
            'info');
        
        const response = await fetch('/smartform/batch-upload-for-buyer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                deliveries: deliveriesData
            })
        });
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        
        const result = await response.json();
        console.log('=== BATCH UPLOAD RESPONSE ===', result);
        
        if (result.success) {
            const totalUploaded = result.total_uploaded || 0;
            const totalProcessed = result.total_processed || 0;
            
            if (totalUploaded > 0) {
                showToast('Success', 
                    'Uploaded ' + totalUploaded + ' files from ' + totalProcessed + ' deliveries. Refreshing...', 
                    'success');
                
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                showToast('Info', 
                    'No new files found in Z:\\sd for these deliveries', 
                    'warning');
            }
        } else {
            throw new Error(result.message || 'Batch upload failed');
        }
        
    } catch (error) {
        console.error('BATCH AUTO-UPLOAD ERROR:', error);
        showToast('Upload Failed', 
            'Error: ' + error.message + '. Check console for details.', 
            'error');
        
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
}

// ========================================
// REAL-TIME MONITORING SYSTEM
// ========================================
function initializeRealTimeMonitoring() {
    console.log('Initializing real-time Z:\\sd monitoring system...');
    startBackgroundMonitoring();
}

function startBackgroundMonitoring() {
    if (isMonitoringActive) return;
    
    console.log('Starting background monitoring every 2 minutes...');
    isMonitoringActive = true;
    
    // Background monitoring dari browser dinonaktifkan
    // Scan Z:\sd dilakukan via server schedule (Kernel.php)
    console.log('Background monitoring disabled - using server-side schedule');
}

function pauseBackgroundMonitoring() {
    if (!isMonitoringActive) return;
    
    console.log('Pausing background monitoring...');
    isMonitoringActive = false;
    
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
        monitoringInterval = null;
    }
}

function resumeBackgroundMonitoring() {
    if (isMonitoringActive) return;
    
    console.log('Resuming background monitoring...');
    startBackgroundMonitoring();
}

async function performBackgroundMonitoring() {
    try {
        console.log('Performing background Z:\\sd check...', new Date().toLocaleTimeString());
        
        const response = await fetch('/smartform/monitor-auto-upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }

        const result = await response.json();
        
        if (result.success) {
            if (result.results && result.results.summary) {
                const summary = result.results.summary;
                
                console.log('Background monitoring results:', {
                    uploaded: summary.uploaded,
                    failed: summary.failed,
                    ignored: summary.ignored,
                    timestamp: result.timestamp
                });
                
                if (summary.uploaded > 0) {
                    showToast('Auto-Upload Success', 
                        summary.uploaded + ' new documents uploaded. Page will refresh...', 
                        'success');
                    
                    setTimeout(function() {
                        console.log('Refreshing page due to new uploads...');
                        window.location.reload();
                    }, 3000);
                }
            }
        } else {
            console.warn('Background monitoring returned error:', result.message);
        }
        
    } catch (error) {
        console.error('Background monitoring error:', error);
    }
}

// ========================================
// TOGGLE SEARCH FILTER
// ========================================
function toggleSearchFilter() {
    const content = document.getElementById('searchFilterContent');
    const icon = document.getElementById('searchToggleIcon');
    
    isSearchFilterExpanded = !isSearchFilterExpanded;
    
    if (isSearchFilterExpanded) {
        content.style.maxHeight = content.scrollHeight + 'px';
        content.classList.add('expanded');
        icon.classList.add('rotated');
        
        console.log('Search filter EXPANDED');
    } else {
        content.style.maxHeight = '0';
        content.classList.remove('expanded');
        icon.classList.remove('rotated');
        
        console.log('Search filter COLLAPSED');
    }
}

function toggleAdvancedFilter() {
    const advancedFilters = document.getElementById('advancedFilters');
    isAdvancedFilterOpen = !isAdvancedFilterOpen;
    
    if (isAdvancedFilterOpen) {
        advancedFilters.style.display = 'block';
    } else {
        advancedFilters.style.display = 'none';
    }
}

function autoExpandSearchFilter() {
    if (!isSearchFilterExpanded) {
        toggleSearchFilter();
    }
}

// ========================================
// SEARCH FUNCTIONS
// ========================================
function performQuickSearch(searchTerm) {
    const searchClearBtn = document.querySelector('.search-clear-btn');
    const resultsCounter = document.getElementById('searchResultsCounter');
    const resultsCount = document.getElementById('resultsCount');
    
    if (searchTerm.length > 0 && !isSearchFilterExpanded) {
        autoExpandSearchFilter();
    }
    
    console.log('Searching for:', searchTerm);
    
    if (searchTerm.length > 0) {
        searchClearBtn.style.display = 'block';
    } else {
        searchClearBtn.style.display = 'none';
    }
    
    if (!searchTerm.trim()) {
        originalDeliveryRows.forEach(function(row) {
            row.style.display = 'table-row';
        });
        resultsCounter.style.display = 'none';
        return;
    }
    
    const searchTermLower = searchTerm.toLowerCase().trim();
    let visibleCount = 0;
    
    originalDeliveryRows.forEach(function(row) {
        const deliveryOrder = row.dataset.delivery || '';
        const customerName = row.dataset.customer || '';
        
        const billingDoc = row.querySelector('.billing-doc')?.textContent.trim() || '';
        const bookingNumber = row.querySelector('.delivery-meta div:nth-child(2)')?.textContent.trim() || '';
        
        const searchableTexts = [
            deliveryOrder,
            customerName,
            billingDoc,
            bookingNumber
        ];
        
        const isMatch = searchableTexts.some(function(text) {
            return text && text.toLowerCase().includes(searchTermLower);
        });
        
        if (isMatch) {
            row.style.display = 'table-row';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    console.log('Search results:', visibleCount, 'out of', originalDeliveryRows.length);
    
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
    
    originalDeliveryRows.forEach(function(row) {
        row.style.display = 'table-row';
    });
    
    console.log('Search cleared, showing all', originalDeliveryRows.length, 'rows');
}

function clearAllFilters() {
    console.log('Clearing all filters');
    
    clearSearch();
    
    const filterInputs = [
        'billingDocumentFilter',
        'statusFilter',
        'documentStatusFilter'
    ];
    
    filterInputs.forEach(function(inputId) {
        const element = document.getElementById(inputId);
        if (element) element.value = '';
    });
    
    originalDeliveryRows.forEach(function(row) {
        row.style.display = 'table-row';
    });
}

function applyAdvancedFilters() {
    const billingDoc = document.getElementById('billingDocumentFilter')?.value.toLowerCase() || '';
    const status = document.getElementById('statusFilter')?.value.toLowerCase() || '';
    const docStatus = document.getElementById('documentStatusFilter')?.value.toLowerCase() || '';
    
    originalDeliveryRows.forEach(function(row) {
        let showRow = true;
        
        if (billingDoc) {
            const rowBillingDoc = row.querySelector('.billing-doc')?.textContent.toLowerCase() || '';
            if (!rowBillingDoc.includes(billingDoc)) {
                showRow = false;
            }
        }
        
        if (status) {
            const rowStatus = row.dataset.status?.toLowerCase() || '';
            if (rowStatus !== status) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? 'table-row' : 'none';
    });
}

// ========================================
// ✅ UPDATED: FILTER BY STATUS - DENGAN LOGIKA BARU
// ========================================
// ✅ UPDATED: FILTER BY STATUS - DENGAN LOGIKA BARU
function filterByStatus(status) {
    console.log('Filtering by status:', status);
    
    clearAllFilters();
    
    originalDeliveryRows.forEach(function(row) {
        const rowStatus = row.dataset.status?.toLowerCase() || '';
        const rowProgress = parseFloat(row.dataset.progress || 0);
        
        let shouldShow = false;
        
        // ✅ FILTER LOGIC SESUAI UPDATE
        if (status === 'outstanding') {
            // Outstanding: progress = 0%
            shouldShow = (rowProgress === 0 && rowStatus !== 'sent');
        } else if (status === 'progress') {
            // In Progress: 1-99%
            shouldShow = (rowProgress > 0 && rowProgress < 100 && rowStatus !== 'sent');
        } else if (status === 'completed') {
            // Completed: 100% tapi belum sent
            shouldShow = (rowProgress >= 100 && rowStatus !== 'sent');
        } else if (status === 'sent') {
            // Sent: sudah dikirim ke buyer
            shouldShow = (rowStatus === 'sent');
        }
        
        row.style.display = shouldShow ? 'table-row' : 'none';
    });
    
    // Auto-clear filter setelah 10 detik
    setTimeout(function() {
        clearAllFilters();
    }, 10000);
}

// ========================================
// DASHBOARD INITIALIZATION
// ========================================
function initializeFinanceDashboard() {
    console.log('=== FINANCE DASHBOARD INITIALIZED ===');
    
    // Interval check dokumen baru dinonaktifkan dari browser
    // Dilakukan via server schedule
}

function checkForNewDocuments() {
    console.log('Checking for new documents...');
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

// ========================================
// DOCUMENT PREVIEW & DOWNLOAD
// ========================================
function previewDocument(documentId) {
    try {
        console.log('Preview document:', documentId);
        
        if (!documentId || documentId <= 0) {
            showToast('Preview Error', 'Invalid document ID', 'error');
            return;
        }
        
        const previewUrl = '/documents/preview/' + documentId;
        
        fetch(previewUrl, {
            method: 'HEAD',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (response.ok) {
                const newTab = window.open(previewUrl, '_blank');
                
                if (!newTab) {
                    showToast('Popup Blocked', 
                        'Please allow popups for this site, or try download instead.', 
                        'error'
                    );
                }
            } else {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
        })
        .catch(function(error) {
            console.error('Preview check failed:', error);
            showToast('Preview Error', 
                'Cannot load document preview. File may not exist.', 
                'error'
            );
        });
        
    } catch (error) {
        console.error('Preview error:', error);
        showToast('Preview Error', error.message, 'error');
    }
}

function downloadDocument(documentId) {
    try {
        console.log('Starting document download:', documentId);
        
        const downloadUrl = '/documents/download/' + documentId;
        
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = downloadUrl;
        
        iframe.onload = function() {
            console.log('Download initiated successfully');
        };
        
        iframe.onerror = function() {
            console.error('Download failed');
            showToast('Download Error', 
                'Failed to download document', 
                'error'
            );
        };
        
        document.body.appendChild(iframe);
        
        setTimeout(function() {
            if (iframe && iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 10000);
        
    } catch (error) {
        console.error('Download error:', error);
        showToast('Download Error', 
            'Failed to start download: ' + error.message, 
            'error'
        );
        
        const downloadUrl = '/documents/download/' + documentId;
        window.open(downloadUrl, '_blank');
    }
}

// ========================================
// SEND TO BUYER FUNCTIONS
// ========================================
function openSendToBuyerModal(deliveryOrder, customerName) {
    console.log('=== OPENING SEND TO BUYER MODAL ===');
    console.log('Delivery Order:', deliveryOrder);
    console.log('Customer Name:', customerName);
    
    document.getElementById('modal_delivery_order').value = deliveryOrder;
    document.getElementById('modal_customer_name').value = customerName;
    
    const deliveryRow = document.querySelector(`tr.delivery-row[data-delivery="${deliveryOrder}"][data-customer="${customerName}"]`);
    
    delete window.modalBillingDocument;
    delete window.modalBookingNumber;
    
    if (deliveryRow) {
        console.log('✅ Delivery row found!');
        
        const billingDocElement = deliveryRow.querySelector('.info-value.billing-doc');
        let billingDoc = billingDocElement ? billingDocElement.textContent.trim() : '';
        
        console.log('📄 Billing Document Element:', billingDocElement);
        console.log('📄 Billing Document Value:', billingDoc);
        
       // ✅ IMPROVED: Get booking number dengan multiple selector strategies
let bookingNumber = '';

// Strategy 1: Direct class selector (preferred)
const bookingElement = deliveryRow.querySelector('.info-value.booking-number');
if (bookingElement) {
    bookingNumber = bookingElement.textContent.trim();
    console.log('✅ FOUND Booking Number (direct selector):', bookingNumber);
}

// Strategy 2: Fallback - loop through info-rows
if (!bookingNumber || bookingNumber === '-') {
    const infoRows = deliveryRow.querySelectorAll('.info-row');
    console.log('🔍 Found info-rows:', infoRows.length);
    
    infoRows.forEach(function(infoRow, index) {
        const labelElement = infoRow.querySelector('.info-label');
        const valueElement = infoRow.querySelector('.info-value');
        
        if (labelElement && labelElement.textContent.trim().includes('Booking')) {
            if (valueElement) {
                bookingNumber = valueElement.textContent.trim();
                console.log('✅ FOUND Booking Number (loop):', bookingNumber);
            }
        }
    });
}

console.log('📋 Final Booking Number:', bookingNumber);
        
        if (billingDoc && billingDoc !== deliveryOrder && billingDoc !== '-') {
            window.modalBillingDocument = billingDoc;
            console.log('✅ SET window.modalBillingDocument:', window.modalBillingDocument);
        } else {
            window.modalBillingDocument = deliveryOrder;
            console.log('⚠️ Using delivery order as billing doc:', window.modalBillingDocument);
        }
        
        if (bookingNumber && bookingNumber !== '-') {
            window.modalBookingNumber = bookingNumber;
            console.log('✅ SET window.modalBookingNumber:', window.modalBookingNumber);
        } else {
            window.modalBookingNumber = '';
            console.warn('❌ Booking number is empty or "-"');
        }
        
        console.log('📊 FINAL MODAL DATA:', {
            delivery_order: deliveryOrder,
            billing_document: window.modalBillingDocument || 'N/A',
            booking_number: window.modalBookingNumber || 'N/A',
            customer_name: customerName
        });
        
    } else {
        console.error('❌ Delivery row NOT FOUND');
        window.modalBillingDocument = deliveryOrder;
        window.modalBookingNumber = '';
    }
    
    loadBuyerEmails(customerName);
    
    const modal = new bootstrap.Modal(document.getElementById('sendToBuyerModal'));
    modal.show();
}

async function loadBuyerEmails(customerName) {
    try {
        const container = document.getElementById('buyer_emails_container');
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading buyer emails...</div>';
        
        const url = '/api/buyer-emails/' + encodeURIComponent(customerName);
        console.log('Fetching buyer emails from:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('Non-JSON response received:', textResponse.substring(0, 500));
            throw new Error('Server returned HTML instead of JSON. Check Laravel logs for buyer emails API.');
        }
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error('HTTP ' + response.status + ': ' + (errorData.message || response.statusText));
        }
        
        const result = await response.json();
        console.log('Buyer emails API response:', result);
        
        if (result.success && result.emails && result.emails.length > 0) {
            let emailsHtml = '<div class="buyer-emails-list">';
            
            result.emails.forEach(function(email) {
                const checkedAttr = 'checked';
                const primaryBadge = email.is_primary ? '<span class="badge bg-primary ms-2">Primary</span>' : '';
                
                emailsHtml += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" 
                               id="email_${email.id}" 
                               value="${email.id}" 
                               ${checkedAttr}>
                        <label class="form-check-label" for="email_${email.id}">
                            <strong>${email.email}</strong>
                            ${email.contact_name ? '(' + email.contact_name + ')' : ''}
                            ${primaryBadge}
                            <br><small class="text-muted">${email.email_type}</small>
                        </label>
                    </div>
                `;
            });
            
            emailsHtml += '</div>';
            
            if (window.modalBookingNumber && window.modalBookingNumber !== '-') {
                emailsHtml = `
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Email Subject will include:</strong><br>
                        <small>Doc ${window.modalBillingDocument || 'N/A'} - Booking: ${window.modalBookingNumber}</small>
                    </div>
                ` + emailsHtml;
            }
            
            container.innerHTML = emailsHtml;
            
            console.log('Auto-checked ' + result.emails.length + ' buyer emails for ' + customerName);
        } else {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No email addresses found for this buyer.
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error loading buyer emails:', error);
        const container = document.getElementById('buyer_emails_container');
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle me-2"></i>
                <strong>Error loading buyer emails:</strong><br>
                ${error.message}
            </div>
        `;
    }
}

async function sendToBuyer() {
    try {
        console.log('=== SEND TO BUYER INITIATED ===');
        
        const form = document.getElementById('sendToBuyerForm');
        const formData = new FormData(form);
        
        const selectedEmails = [];
        document.querySelectorAll('#buyer_emails_container input[type="checkbox"]:checked').forEach(function(checkbox) {
            selectedEmails.push(parseInt(checkbox.value));
        });
        
        if (selectedEmails.length === 0) {
            showToast('Error', 'Please select at least one email address', 'error');
            return;
        }
        
        // ✅ FIX: Gunakan const dan variable name berbeda
        const rawDeliveryOrder = formData.get('delivery_order');
        const customerName = formData.get('customer_name');
        const rawBillingDocument = window.modalBillingDocument || rawDeliveryOrder;
        
        // ✅ Parse composite keys - HANYA SEKALI!
        function parseDeliveryOrder(compositeKey) {
            if (!compositeKey) return null;
            if (!compositeKey.includes('_')) return compositeKey;
            return compositeKey.split('_')[0];
        }
        
        function parseBillingDocument(compositeKey) {
            if (!compositeKey) return null;
            if (!compositeKey.includes('_')) return compositeKey;
            let parts = compositeKey.split('_');
            return parts.length > 1 ? parts[1] : parts[0];
        }
        
        // ✅ Parse values dengan variable name yang jelas
        const deliveryOrder = parseDeliveryOrder(rawDeliveryOrder);
        const billingDocument = parseBillingDocument(rawBillingDocument);
        
        console.log('🔧 PARSED VALUES:', {
            raw_delivery: rawDeliveryOrder,
            raw_billing: rawBillingDocument,
            parsed_delivery: deliveryOrder,
            parsed_billing: billingDocument
        });
        
        // ✅ Booking number
        let bookingNumber = window.modalBookingNumber || '';
        if (bookingNumber && bookingNumber !== '-') {
            bookingNumber = bookingNumber.toString().trim();
        } else {
            bookingNumber = '';
        }
        
        // ✅ Send data - HANYA SEKALI!
        const sendData = {
            delivery_order: deliveryOrder,
            customer_name: customerName,
            billing_document: billingDocument,
            booking_number: bookingNumber,
            selected_emails: selectedEmails,
            email_message: formData.get('email_message') || '',
            notes: formData.get('notes') || ''
        };
        
        console.log('=== SEND DATA PAYLOAD ===', sendData);
        
        const sendBtn = document.querySelector('button[onclick="sendToBuyer()"]');
        const originalText = sendBtn.innerHTML;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
        
        const response = await fetch('/api/buyer-emails/send-multiple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(sendData)
        });
        
        console.log('Response status:', response.status);
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('❌ Non-JSON response received:', textResponse.substring(0, 500));
            throw new Error('Server returned HTML instead of JSON. Check Laravel error logs at storage/logs/laravel.log');
        }
        
        const result = await response.json();
        console.log('=== RESPONSE RESULT ===', result);
        
        if (result.success) {
            showToast('Success', result.message, 'success');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('sendToBuyerModal'));
            modal.hide();

            // ✅ TRIGGER NOTIFICATION UPDATE
            window.dispatchEvent(new Event('email-sent-success'));
            
            delete window.modalBillingDocument;
            delete window.modalBookingNumber;
            
            setTimeout(function() {
                window.location.reload();
            }, 2000);
            
        } else {
            console.error('❌ Send failed:', result.message);
            showToast('Error', result.message || 'Failed to send documents', 'error');
        }
        
    } catch (error) {
        console.error('❌ SEND TO BUYER ERROR:', error);
        console.error('Error stack:', error.stack);
        
        showToast('Error', 
            'Network error: ' + error.message + ' - Check browser console (F12) for details', 
            'error'
        );
        
    } finally {
        const sendBtn = document.querySelector('button[onclick="sendToBuyer()"]');
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Send Documents';
        }
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function refreshDashboard() {
    console.log('Refreshing dashboard...');
    window.location.reload();
}

function showToast(title, message, type) {
    console.log('Toast ' + type.toUpperCase() + ': ' + title + ' - ' + message);
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    
    const iconMap = {
        'success': 'check-circle',
        'error': 'times-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    const toastElement = document.createElement('div');
    toastElement.id = toastId;
    toastElement.className = 'toast';
    toastElement.setAttribute('role', 'alert');
    toastElement.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toastElement);
    
    if (typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: type === 'error' ? 8000 : (type === 'success' ? 5000 : 4000)
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    } else {
        toastElement.style.display = 'block';
        setTimeout(function() {
            toastElement.remove();
        }, type === 'error' ? 8000 : 4000);
    }
}



// ========================================
// EXPORT FUNCTIONS
// ========================================

window.updateSidebarNotifications = updateSidebarNotifications;
window.startNotificationAutoUpdate = startNotificationAutoUpdate;
window.stopNotificationAutoUpdate = stopNotificationAutoUpdate;
window.toggleEximDropdown = toggleEximDropdown;
window.batchAutoUploadAllDeliveries = batchAutoUploadAllDeliveries;
window.previewDocument = previewDocument;
window.downloadDocument = downloadDocument;
window.openSendToBuyerModal = openSendToBuyerModal;
window.sendToBuyer = sendToBuyer;
window.refreshDashboard = refreshDashboard;
window.startRealTimeMonitoring = initializeRealTimeMonitoring;
window.pauseBackgroundMonitoring = pauseBackgroundMonitoring;
window.resumeBackgroundMonitoring = resumeBackgroundMonitoring;
window.toggleSearchFilter = toggleSearchFilter;
window.toggleAdvancedFilter = toggleAdvancedFilter;
window.clearSearch = clearSearch;
window.clearAllFilters = clearAllFilters;
window.filterByStatus = filterByStatus;
window.performQuickSearch = performQuickSearch;
window.applyAdvancedFilters = applyAdvancedFilters;

console.log('✅ Admin Finance Table Dashboard initialized successfully');
console.log('✅ Global Auto-Upload button ready');
console.log('✅ Real-time Z:\\sd monitoring system active');
console.log('✅ EXIM Documents dropdown ready');
</script>
@endsection