@extends('layouts.app')

@section('title', 'Setting Document Dashboard')

@section('styles')
<style>
:root {
    --primary-forest: #1b4332;
    --secondary-forest: #2d5016;
    --success-forest: #40916c;
    --danger-forest: #d62828;
    --warning-forest: #f77f00;
    --light-bg: #f1f8e9;
    --border-forest: #95d5b2;
}

body {
    background: var(--light-bg);
    font-family: 'Inter', sans-serif;
}

.dashboard-header {
    background: linear-gradient(135deg, var(--primary-forest), var(--secondary-forest));
    color: white;
    padding: 2rem 0;
    box-shadow: 0 4px 15px rgba(27, 67, 50, 0.2);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.nav-tabs-forest {
    background: white;
    border-bottom: 3px solid var(--primary-forest);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.nav-tabs-forest .nav-link {
    color: var(--primary-forest);
    font-weight: 600;
    padding: 1rem 2rem;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.nav-tabs-forest .nav-link:hover {
    background: var(--light-bg);
    border-bottom-color: var(--success-forest);
}

.nav-tabs-forest .nav-link.active {
    color: white;
    background: var(--primary-forest);
    border-bottom-color: var(--success-forest);
}

.customer-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
}

.customer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.customer-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-forest);
}

.customer-name {
    font-weight: 700;
    color: var(--primary-forest);
    font-size: 1.1rem;
}

.customer-actions {
    display: flex;
    gap: 0.5rem;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.document-checkbox {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background 0.2s ease;
}

.document-checkbox:hover {
    background: var(--light-bg);
}

.document-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-right: 0.5rem;
    cursor: pointer;
}

.btn-icon {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    border: none;
    transition: all 0.3s ease;
}

.btn-delete-buyer {
    background: var(--danger-forest);
    color: white;
}

.btn-delete-buyer:hover {
    background: #b71c1c;
    transform: scale(1.05);
}

.save-button {
    background: linear-gradient(135deg, var(--success-forest), var(--primary-forest));
    border: none;
    color: white;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.save-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(64, 145, 108, 0.4);
}

.action-buttons-top {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.btn-add {
    background: linear-gradient(135deg, var(--success-forest), var(--primary-forest));
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(64, 145, 108, 0.4);
}

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-forest thead {
    background: var(--primary-forest);
    color: white;
}

.table-forest thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    padding: 1rem;
    border: none;
}

.table-forest tbody tr:hover {
    background: var(--light-bg);
}

.badge-primary-email {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #654321;
    font-weight: 600;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
}

.badge-email-type {
    padding: 0.3rem 0.7rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-to { background: var(--success-forest); color: white; }
.badge-cc { background: var(--warning-forest); color: white; }
.badge-bcc { background: var(--danger-forest); color: white; }

.btn-forest-primary {
    background: linear-gradient(135deg, var(--success-forest), var(--primary-forest));
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.7rem 1.5rem;
    border-radius: 8px;
}

.btn-action {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.btn-edit { background: var(--warning-forest); color: white; }
.btn-delete { background: var(--danger-forest); color: white; }

.modal-header-forest {
    background: linear-gradient(135deg, var(--primary-forest), var(--secondary-forest));
    color: white;
    border-radius: 12px 12px 0 0;
}

.form-control-forest, .form-select-forest {
    border: 2px solid var(--border-forest);
    border-radius: 8px;
    padding: 0.7rem 1rem;
}

.form-control-forest:focus, .form-select-forest:focus {
    border-color: var(--success-forest);
    box-shadow: 0 0 0 0.2rem rgba(64, 145, 108, 0.25);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--success-forest);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
}

.toast-message {
    min-width: 300px;
    padding: 1rem 1.5rem;
    margin-bottom: 10px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
}

.toast-success {
    background: linear-gradient(135deg, var(--success-forest), var(--primary-forest));
    color: white;
}

.toast-error {
    background: linear-gradient(135deg, var(--danger-forest), #b71c1c);
    color: white;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* ✅ NEW: Styling untuk tampilan buyer cards */
.buyer-email-card {
    background: white;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.buyer-email-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.buyer-card-header {
    background: linear-gradient(135deg, var(--primary-forest), var(--secondary-forest));
    color: white;
    padding: 1.2rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.buyer-info h4 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.buyer-info p {
    margin: 0.3rem 0 0 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.buyer-card-body {
    padding: 1.5rem;
}

.email-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.8rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--success-forest);
    transition: all 0.2s ease;
}

.email-list-item:hover {
    background: var(--light-bg);
    transform: translateX(5px);
}

.email-list-item:last-child {
    margin-bottom: 0;
}

.email-info {
    flex: 1;
}

.email-address {
    font-weight: 600;
    color: var(--primary-forest);
    margin-bottom: 0.3rem;
}

.email-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
    font-size: 0.85rem;
}

.email-contact {
    color: #666;
}

.email-actions {
    display: flex;
    gap: 0.5rem;
}

.no-data-message {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.no-data-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ccc;
}

/* ✅ NEW: Filter section styling */
.filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    font-weight: 600;
    color: var(--primary-forest);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.filter-input {
    width: 100%;
    border: 2px solid var(--border-forest);
    border-radius: 8px;
    padding: 0.6rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-input:focus {
    outline: none;
    border-color: var(--success-forest);
    box-shadow: 0 0 0 0.2rem rgba(64, 145, 108, 0.15);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-filter {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-filter-apply {
    background: linear-gradient(135deg, var(--success-forest), var(--primary-forest));
    color: white;
}

.btn-filter-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(64, 145, 108, 0.4);
}

.btn-filter-reset {
    background: #6c757d;
    color: white;
}

.btn-filter-reset:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.filter-stats {
    margin-top: 1rem;
    padding: 0.8rem 1rem;
    background: var(--light-bg);
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.filter-stats-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-stats-label {
    color: #666;
}

.filter-stats-value {
    font-weight: 700;
    color: var(--primary-forest);
}
</style>
@endsection

@section('content')
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner"></div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="dashboard-header">
    <div class="container">
        <div class="header-content">
            <h1 class="header-title">
                <i class="fas fa-cog me-2"></i>Setting Document Dashboard
            </h1>
            <div>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-user me-1"></i>{{ session('setting_user') }}
                </span>
                <form action="{{ route('setting-document.logout') }}" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-light">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <ul class="nav nav-tabs nav-tabs-forest" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#document-settings">
                <i class="fas fa-file-alt me-2"></i>Document Settings
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#buyer-email" id="buyer-email-tab">
                <i class="fas fa-envelope me-2"></i>Buyer Email Management
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: Document Settings (EXISTING - TIDAK DIUBAH) -->
        <div class="tab-pane fade show active" id="document-settings">
            <div class="action-buttons-top">
                <button class="btn btn-add" onclick="showAddDocumentModal()">
                    <i class="fas fa-plus me-2"></i>Add New Document Type
                </button>
                <button class="btn btn-add" onclick="showAddBuyerFromListModal()" style="margin-left: 10px;">
                    <i class="fas fa-user-plus me-2"></i>Add Existing Buyer
                </button>
            </div>

            <!-- ✅ NEW: Filter Section for Document Settings -->
            <div class="filter-section" style="margin-bottom: 1.5rem;">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1;">
                        <label for="filterDocumentBuyer">
                            <i class="fas fa-search me-1"></i>Search Buyer
                        </label>
                        <input type="text" 
                               id="filterDocumentBuyer" 
                               class="filter-input" 
                               placeholder="Search by buyer name..."
                               onkeyup="filterDocumentSettings()"
                               style="width: 100%;">
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn-filter btn-filter-reset" onclick="resetDocumentFilter()">
                            <i class="fas fa-redo"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <form id="settingsForm" method="POST" action="{{ route('setting-document.update-settings') }}">
                @csrf
                <div id="customerCards">
                    @foreach($customerNames as $customer)
                    <div class="customer-card">
                        <div class="customer-card-header">
                            <span class="customer-name">{{ $customer }}</span>
                            <div class="customer-actions">
                                <button type="button" class="btn btn-icon btn-delete-buyer" 
                                        onclick="deleteBuyer('{{ $customer }}')">
                                    <i class="fas fa-trash"></i> Delete Buyer
                                </button>
                            </div>
                        </div>
                        
                        <div class="documents-grid">
                            @foreach($availableDocuments as $doc)
                            <label class="document-checkbox">
                                <input type="checkbox" 
                                       name="settings[{{ $customer }}][]" 
                                       value="{{ $doc }}"
                                       data-customer="{{ $customer }}"
                                       onchange="autoSaveDocumentSetting(this)"
                                       {{ isset($documentSettings[$customer]) && in_array($doc, $documentSettings[$customer]) ? 'checked' : '' }}>
                                <span>{{ $doc }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </form>
        </div>

        <!-- ✅ TAB 2: Buyer Email Management (DIPERBAIKI) -->
        <div class="tab-pane fade" id="buyer-email">
            <div class="action-buttons-top">
                <button class="btn btn-add" onclick="showAddEmailModal()">
                    <i class="fas fa-plus me-2"></i>Add New Buyer/Email
                </button>
            </div>

            <!-- ✅ NEW: Filter Section -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filterBuyerCode">
                            <i class="fas fa-barcode me-1"></i>Buyer Code
                        </label>
                        <input type="text" 
                               id="filterBuyerCode" 
                               class="filter-input" 
                               placeholder="Search by buyer code..."
                               onkeyup="handleFilterKeyPress(event)">
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterBuyerName">
                            <i class="fas fa-building me-1"></i>Buyer Name
                        </label>
                        <input type="text" 
                               id="filterBuyerName" 
                               class="filter-input" 
                               placeholder="Search by buyer name..."
                               onkeyup="handleFilterKeyPress(event)">
                    </div>
                    
                    <div class="filter-actions">
                        <button class="btn-filter btn-filter-apply" onclick="applyFilter()">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                        <button class="btn-filter btn-filter-reset" onclick="resetFilter()">
                            <i class="fas fa-redo"></i>
                            Reset
                        </button>
                    </div>
                </div>
                
                <!-- Filter Stats -->
                <div class="filter-stats" id="filterStats" style="display: none;">
                    <div class="filter-stats-item">
                        <span class="filter-stats-label">Showing:</span>
                        <span class="filter-stats-value" id="filterStatsShowing">0</span>
                        <span class="filter-stats-label">of</span>
                        <span class="filter-stats-value" id="filterStatsTotal">0</span>
                        <span class="filter-stats-label">buyers</span>
                    </div>
                    <div class="filter-stats-item">
                        <span class="filter-stats-label">Total Emails:</span>
                        <span class="filter-stats-value" id="filterStatsTotalEmails">0</span>
                    </div>
                </div>
            </div>

            <!-- ✅ Container untuk buyer cards -->
            <div id="buyerEmailCards">
                <!-- Akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Document Type (EXISTING - TIDAK DIUBAH) -->
<div class="modal fade" id="addDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-forest">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Document Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Document Type</label>
                    <input type="text" class="form-control form-control-forest" id="newDocumentType" 
                           placeholder="Enter document type (e.g., CERTIFICATE_OF_ORIGIN)">
                    <small class="text-muted">Will be automatically converted to uppercase</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-forest-primary" onclick="addDocument()">
                    <i class="fas fa-save me-2"></i>Add Document
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ✅ NEW: Modal Add Buyer from List -->
<div class="modal fade" id="addBuyerFromListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-forest">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add Buyer from List</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Buyer <span class="text-danger">*</span></label>
                    <select class="form-select form-select-forest" id="selectBuyerFromList">
                        <option value="">Loading buyers...</option>
                    </select>
                    <small class="text-muted">Choose a buyer from Buyer Email Management</small>
                </div>
                <div id="buyerPreview" style="display: none;" class="alert alert-info">
                    <strong>Selected:</strong> <span id="selectedBuyerName"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-forest-primary" onclick="addBuyerFromList()">
                    <i class="fas fa-save me-2"></i>Add Buyer
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ✅ Modal: Buyer Email Form (EXISTING MODAL STRUCTURE - TIDAK DIUBAH) -->
<div class="modal fade" id="buyerEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-forest">
                <h5 class="modal-title" id="emailModalTitle">
                    <i class="fas fa-envelope me-2"></i>Buyer Email Form
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="buyerEmailForm">
                    <input type="hidden" id="emailFormMode" value="create">
                    <input type="hidden" id="emailId" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Buyer Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-forest" id="buyerCode" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Buyer Name</label>
                            <input type="text" class="form-control form-control-forest" id="buyerName">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-forest" id="buyerEmailAddress" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Name</label>
                            <input type="text" class="form-control form-control-forest" id="contactName">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-forest" id="emailType" required>
                                <option value="To">To</option>
                                <option value="CC">CC</option>
                                <option value="BCC">BCC</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="isPrimary">
                        <label class="form-check-label" for="isPrimary">
                            <i class="fas fa-star text-warning"></i> Set as Primary Email
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-forest-primary" onclick="saveBuyerEmail()">
                    <i class="fas fa-save me-2"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete Confirmation (GENERIC - TIDAK DIUBAH) -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-forest">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
                <div id="deleteInfo" class="alert alert-warning"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ========================================
// UTILITY FUNCTIONS (EXISTING - TIDAK DIUBAH)
// ========================================

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-message toast-${type}`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: 1rem;">
                ×
            </button>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// EXISTING FUNCTIONS - TIDAK DIUBAH
// ========================================

let deleteModalGeneric, addDocumentModal, addBuyerFromListModal;

document.addEventListener('DOMContentLoaded', function() {
    deleteModalGeneric = new bootstrap.Modal(document.getElementById('deleteModal'));
    addDocumentModal = new bootstrap.Modal(document.getElementById('addDocumentModal'));
    addBuyerFromListModal = new bootstrap.Modal(document.getElementById('addBuyerFromListModal'));
});

function showAddDocumentModal() {
    document.getElementById('newDocumentType').value = '';
    addDocumentModal.show();
}

function deleteBuyer(buyerName) {
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this buyer?';
    document.getElementById('deleteInfo').innerHTML = `<strong>Buyer:</strong> ${escapeHtml(buyerName)}<br><small class="text-danger">Warning: All document settings for this buyer will be removed!</small>`;
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        try {
            showLoading();
            
            const response = await fetch('/setting-document/delete-buyer', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ buyer_name: buyerName })
            });
            
            const result = await response.json();
            
            if (result.success) {
                deleteModalGeneric.hide();
                showToast(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'Failed to delete buyer', 'error');
            }
            
        } catch (error) {
            console.error('Delete buyer error:', error);
            showToast('Error: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    };
    
    deleteModalGeneric.show();
}

// ========================================
// ✅ NEW: Add Buyer from List Functions
// ========================================

async function showAddBuyerFromListModal() {
    // Show modal
    addBuyerFromListModal.show();
    
    // Load buyers list
    await loadBuyersList();
}

async function loadBuyersList() {
    const selectElement = document.getElementById('selectBuyerFromList');
    
    try {
        selectElement.innerHTML = '<option value="">Loading buyers...</option>';
        
        const response = await fetch('/setting-document/get-buyers-list', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.buyers.length > 0) {
            selectElement.innerHTML = '<option value="">-- Select a buyer --</option>';
            
            result.buyers.forEach(buyer => {
                const option = document.createElement('option');
                option.value = buyer.buyer_name;
                option.textContent = `${buyer.buyer_name} (${buyer.buyer_code})`;
                selectElement.appendChild(option);
            });
            
            // Add change event listener
            selectElement.onchange = function() {
                const buyerPreview = document.getElementById('buyerPreview');
                const selectedBuyerName = document.getElementById('selectedBuyerName');
                
                if (this.value) {
                    selectedBuyerName.textContent = this.value;
                    buyerPreview.style.display = 'block';
                } else {
                    buyerPreview.style.display = 'none';
                }
            };
            
        } else {
            selectElement.innerHTML = '<option value="">No buyers available</option>';
            showToast('No buyers available to add', 'info');
        }
        
    } catch (error) {
        console.error('Load buyers error:', error);
        selectElement.innerHTML = '<option value="">Error loading buyers</option>';
        showToast('Failed to load buyers list: ' + error.message, 'error');
    }
}

async function addBuyerFromList() {
    const selectElement = document.getElementById('selectBuyerFromList');
    const buyerName = selectElement.value;
    
    if (!buyerName) {
        showToast('Please select a buyer', 'error');
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch('/setting-document/add-buyer', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ buyer_name: buyerName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            addBuyerFromListModal.hide();
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to add buyer', 'error');
        }
        
    } catch (error) {
        console.error('Add buyer error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

async function addDocument() {
    const documentType = document.getElementById('newDocumentType').value.trim().toUpperCase();
    
    if (!documentType) {
        showToast('Please enter document type', 'error');
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch('/setting-document/add-document', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ document_type: documentType })
        });
        
        const result = await response.json();
        
        if (result.success) {
            addDocumentModal.hide();
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to add document', 'error');
        }
        
    } catch (error) {
        console.error('Add document error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function deleteDocument(documentType) {
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this document type?';
    document.getElementById('deleteInfo').innerHTML = `<strong>Document:</strong> ${escapeHtml(documentType)}<br><small class="text-danger">Warning: This will remove this document from all buyers!</small>`;
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        try {
            showLoading();
            
            const response = await fetch('/setting-document/delete-document', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ document_type: documentType })
            });
            
            const result = await response.json();
            
            if (result.success) {
                deleteModalGeneric.hide();
                showToast(result.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'Failed to delete document', 'error');
            }
            
        } catch (error) {
            console.error('Delete document error:', error);
            showToast('Error: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    };
    
    deleteModalGeneric.show();
}

// ========================================
// ✅ AUTO-SAVE DOCUMENT SETTING
// ========================================
async function autoSaveDocumentSetting(checkbox) {
    const customerName = checkbox.dataset.customer;
    const documentType = checkbox.value;
    const isChecked = checkbox.checked;
    
    try {
        // Get all checked documents for this customer
        const customerCard = checkbox.closest('.customer-card');
        const checkboxes = customerCard.querySelectorAll('input[type="checkbox"]');
        const allowedDocuments = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        // Show loading indicator on checkbox
        checkbox.disabled = true;
        const label = checkbox.closest('.document-checkbox');
        label.style.opacity = '0.5';
        
        // Send update request
        const response = await fetch('/setting-document/update-settings', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                customer_name: customerName,
                allowed_documents: allowedDocuments
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success feedback
            label.style.opacity = '1';
            label.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                label.style.backgroundColor = '';
            }, 500);
            
            showToast(
                isChecked 
                    ? `${documentType} added to ${customerName}` 
                    : `${documentType} removed from ${customerName}`,
                'success'
            );
        } else {
            // Revert checkbox on error
            checkbox.checked = !isChecked;
            showToast(result.message || 'Failed to save', 'error');
        }
        
    } catch (error) {
        console.error('Auto-save error:', error);
        // Revert checkbox on error
        checkbox.checked = !isChecked;
        showToast('Error saving: ' + error.message, 'error');
    } finally {
        // Re-enable checkbox
        checkbox.disabled = false;
        checkbox.closest('.document-checkbox').style.opacity = '1';
    }
}

// ========================================
// ✅ BUYER EMAIL MANAGEMENT (UPDATED)
// ========================================
let buyerEmails = [];
let filteredBuyerEmails = []; // ✅ NEW: For filtered data
let deleteTargetId = null;
let emailModal, deleteModal;

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOM CONTENT LOADED ===');
    
    emailModal = new bootstrap.Modal(document.getElementById('buyerEmailModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    const buyerEmailTab = document.getElementById('buyer-email-tab');
    
    if (buyerEmailTab) {
        // Load when tab is clicked
        buyerEmailTab.addEventListener('click', function() {
            console.log('Buyer Email tab clicked');
            loadBuyerEmails();
        });
        
        // Check if tab is already active on page load
        if (buyerEmailTab.classList.contains('active') || 
            document.getElementById('buyer-email').classList.contains('active')) {
            console.log('Buyer Email tab is active, loading data...');
            loadBuyerEmails();
        }
    }
    
    console.log('Buyer Email Management initialized');
});

async function loadBuyerEmails() {
    console.log('=== LOAD BUYER EMAILS CALLED ===');
    
    try {
        showLoading();
        const response = await fetch('/setting-document/buyer-email', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            buyerEmails = result.data;
            filteredBuyerEmails = result.data;
            
            console.log('Buyer emails loaded successfully');
            console.log('Total buyers:', buyerEmails.length);
            console.log('Sample data:', buyerEmails.slice(0, 2));
            
            renderBuyerEmailCards(result.data);
            updateFilterStats(result.data);
        } else {
            console.error('Load failed:', result.message);
            showToast('Failed to load buyer emails', 'error');
        }
    } catch (error) {
        console.error('Load error:', error);
        showToast('Error loading data: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

// ✅ NEW FUNCTION: Filter functions
function applyFilter() {
    console.log('=== APPLY FILTER CALLED ===');
    
    const filterCodeInput = document.getElementById('filterBuyerCode');
    const filterNameInput = document.getElementById('filterBuyerName');
    
    if (!filterCodeInput || !filterNameInput) {
        console.error('Filter inputs not found!');
        showToast('Error: Filter elements not found', 'error');
        return;
    }
    
    const filterCode = filterCodeInput.value.trim().toLowerCase();
    const filterName = filterNameInput.value.trim().toLowerCase();
    
    console.log('==========================================');
    console.log('FILTER DEBUG INFO:');
    console.log('Filter Code:', filterCode);
    console.log('Filter Name:', filterName);
    console.log('Total Buyers:', buyerEmails.length);
    if (buyerEmails.length > 0) {
        console.log('Sample buyer:', {
            buyer_code: buyerEmails[0].buyer_code,
            buyer_name: buyerEmails[0].buyer_name
        });
    }
    console.log('==========================================');
    
    if (!buyerEmails || buyerEmails.length === 0) {
        console.error('No buyer data loaded!');
        showToast('No data available. Please wait for data to load.', 'warning');
        return;
    }
    
    if (!filterCode && !filterName) {
        // No filter, show all
        console.log('No filter criteria, showing all');
        filteredBuyerEmails = buyerEmails;
        renderBuyerEmailCards(buyerEmails);
        updateFilterStats(buyerEmails);
        document.getElementById('filterStats').style.display = 'none';
        showToast('Showing all buyers', 'success');
        return;
    }
    
    // Apply filter
    console.log('Starting filter process...');
    filteredBuyerEmails = buyerEmails.filter(buyer => {
        const buyerCodeLower = String(buyer.buyer_code || '').toLowerCase();
        const buyerNameLower = buyer.buyer_name ? buyer.buyer_name.toLowerCase() : '';
        
        const matchCode = !filterCode || buyerCodeLower.includes(filterCode);
        const matchName = !filterName || buyerNameLower.includes(filterName);
        const match = matchCode && matchName;
        
        console.log(`Checking buyer: ${buyer.buyer_code} | ${buyer.buyer_name}`);
        console.log(`  - matchCode: ${matchCode} (looking for: "${filterCode}" in "${buyerCodeLower}")`);
        console.log(`  - matchName: ${matchName} (looking for: "${filterName}" in "${buyerNameLower}")`);
        console.log(`  - RESULT: ${match ? 'MATCH ✅' : 'NO MATCH ❌'}`);
        
        return match;
    });
    
    console.log('==========================================');
    console.log('FILTER RESULTS:');
    console.log('Filtered count:', filteredBuyerEmails.length);
    console.log('==========================================');
    
    renderBuyerEmailCards(filteredBuyerEmails);
    updateFilterStats(filteredBuyerEmails);
    
    const statsDiv = document.getElementById('filterStats');
    if (statsDiv) {
        statsDiv.style.display = 'flex';
    }
    
    if (filteredBuyerEmails.length === 0) {
        showToast('No buyers found matching your filter criteria', 'warning');
    } else {
        showToast(`Found ${filteredBuyerEmails.length} buyer(s)`, 'success');
    }
}

function resetFilter() {
    console.log('=== RESET FILTER CALLED ===');
    
    const filterCodeInput = document.getElementById('filterBuyerCode');
    const filterNameInput = document.getElementById('filterBuyerName');
    
    if (filterCodeInput) filterCodeInput.value = '';
    if (filterNameInput) filterNameInput.value = '';
    
    filteredBuyerEmails = buyerEmails;
    renderBuyerEmailCards(buyerEmails);
    updateFilterStats(buyerEmails);
    
    const statsDiv = document.getElementById('filterStats');
    if (statsDiv) {
        statsDiv.style.display = 'none';
    }
    
    showToast('Filter reset - showing all buyers', 'success');
    console.log('Reset complete. Showing', buyerEmails.length, 'buyers');
}

function handleFilterKeyPress(event) {
    if (event.key === 'Enter') {
        applyFilter();
    }
}

function updateFilterStats(data) {
    const totalBuyers = buyerEmails.length;
    const showingBuyers = data.length;
    const totalEmails = data.reduce((sum, buyer) => sum + buyer.emails.length, 0);
    
    document.getElementById('filterStatsTotal').textContent = totalBuyers;
    document.getElementById('filterStatsShowing').textContent = showingBuyers;
    document.getElementById('filterStatsTotalEmails').textContent = totalEmails;
}

// ✅ NEW FUNCTION: Render buyer email cards
function renderBuyerEmailCards(data) {
    const container = document.getElementById('buyerEmailCards');
    
    if (!data || data.length === 0) {
        container.innerHTML = `
            <div class="no-data-message">
                <div class="no-data-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h5>No Buyer Emails Found</h5>
                <p>Click "Add New Buyer/Email" to get started</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    // Loop through each buyer
    data.forEach(buyer => {
        html += `
        <div class="buyer-email-card">
            <div class="buyer-card-header">
                <div class="buyer-info">
                    <h4><i class="fas fa-building me-2"></i>${escapeHtml(buyer.buyer_code)}</h4>
                    <p><i class="fas fa-tag me-2"></i>${escapeHtml(buyer.buyer_name || 'No name specified')}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-envelope me-1"></i>${buyer.emails.length} email${buyer.emails.length > 1 ? 's' : ''}
                    </span>
                    <button class="btn btn-sm btn-danger" onclick="showDeleteBuyerModal('${escapeHtml(buyer.buyer_code).replace(/'/g, "\\'")}', '${escapeHtml(buyer.buyer_name || buyer.buyer_code).replace(/'/g, "\\'")}', ${buyer.emails.length})" title="Delete Buyer & All Emails">
                        <i class="fas fa-trash-alt me-1"></i>Delete Buyer
                    </button>
                </div>
            </div>
            
            <div class="buyer-card-body">
        `;
        
        // Loop through emails for this buyer
        buyer.emails.forEach(email => {
            html += `
                <div class="email-list-item">
                    <div class="email-info">
                        <div class="email-address">
                            <i class="fas fa-at me-2"></i>${escapeHtml(email.email)}
                        </div>
                        <div class="email-meta">
                            ${email.contact_name ? `<span class="email-contact"><i class="fas fa-user me-1"></i>${escapeHtml(email.contact_name)}</span>` : ''}
                            <span class="badge-email-type badge-${email.email_type.toLowerCase()}">${email.email_type}</span>
                            ${email.is_primary ? '<span class="badge-primary-email"><i class="fas fa-star"></i> Primary</span>' : ''}
                        </div>
                    </div>
                    <div class="email-actions">
                        <button class="btn btn-action btn-edit" onclick='editEmail(${email.id})' title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-action btn-delete" onclick="showDeleteEmailModal(${email.id}, '${escapeHtml(email.email).replace(/'/g, "\\'")}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
        </div>
        `;
    });
    
    container.innerHTML = html;
}

function showAddEmailModal() {
    document.getElementById('emailModalTitle').textContent = 'Add New Buyer/Email';
    document.getElementById('buyerEmailForm').reset();
    document.getElementById('emailFormMode').value = 'create';
    document.getElementById('emailId').value = '';
    emailModal.show();
}

// ✅ FIXED: Edit function dengan proper data handling
function editEmail(emailId) {
    // Find email in the loaded data
    let emailData = null;
    
    for (const buyer of buyerEmails) {
        const found = buyer.emails.find(e => e.id === emailId);
        if (found) {
            // Combine buyer info with email info
            emailData = {
                id: found.id,
                buyer_code: buyer.buyer_code,
                buyer_name: buyer.buyer_name,
                email: found.email,
                contact_name: found.contact_name,
                email_type: found.email_type,
                is_primary: found.is_primary
            };
            break;
        }
    }
    
    if (!emailData) {
        showToast('Email data not found', 'error');
        return;
    }
    
    // Populate form
    document.getElementById('emailModalTitle').textContent = 'Edit Buyer/Email';
    document.getElementById('emailFormMode').value = 'edit';
    document.getElementById('emailId').value = emailData.id;
    document.getElementById('buyerCode').value = emailData.buyer_code || '';
    document.getElementById('buyerName').value = emailData.buyer_name || '';
    document.getElementById('buyerEmailAddress').value = emailData.email || '';
    document.getElementById('contactName').value = emailData.contact_name || '';
    document.getElementById('emailType').value = emailData.email_type || 'To';
    document.getElementById('isPrimary').checked = emailData.is_primary || false;
    
    emailModal.show();
}

async function saveBuyerEmail() {
    try {
        const formMode = document.getElementById('emailFormMode').value;
        const emailId = document.getElementById('emailId').value;
        
        const formData = {
            buyer_code: document.getElementById('buyerCode').value.trim(),
            buyer_name: document.getElementById('buyerName').value.trim(),
            email: document.getElementById('buyerEmailAddress').value.trim(),
            contact_name: document.getElementById('contactName').value.trim(),
            email_type: document.getElementById('emailType').value,
            is_primary: document.getElementById('isPrimary').checked
        };
        
        showLoading();
        
        const url = formMode === 'edit' ? `/setting-document/buyer-email/${emailId}` : '/setting-document/buyer-email';
        const method = formMode === 'edit' ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            emailModal.hide();
            showToast(result.message, 'success');
            loadBuyerEmails();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (error) {
        console.error('Save error:', error);
        showToast('Error: ' + error.message, 'error');
    } finally {
        hideLoading();
    }
}

function showDeleteEmailModal(id, email) {
    deleteTargetId = id;
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this email?';
    document.getElementById('deleteInfo').innerHTML = `<strong>Email:</strong> ${escapeHtml(email)}`;
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        if (!deleteTargetId) return;
        
        try {
            showLoading();
            const response = await fetch(`/setting-document/buyer-email/${deleteTargetId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                deleteModalGeneric.hide();
                showToast(result.message, 'success');
                loadBuyerEmails();
            } else {
                showToast(result.message || 'Failed to delete', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('Error: ' + error.message, 'error');
        } finally {
            hideLoading();
            deleteTargetId = null;
        }
    };
    
    deleteModalGeneric.show();
}

// ✅ NEW FUNCTION: Delete buyer beserta semua emailnya
function showDeleteBuyerModal(buyerCode, buyerName, emailCount) {
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this buyer and ALL their emails?';
    document.getElementById('deleteInfo').innerHTML = `
        <strong>Buyer Code:</strong> ${escapeHtml(buyerCode)}<br>
        <strong>Buyer Name:</strong> ${escapeHtml(buyerName)}<br>
        <strong>Total Emails:</strong> ${emailCount}<br>
        <small class="text-danger mt-2 d-block">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>Warning:</strong> This action cannot be undone! All ${emailCount} email(s) for this buyer will be permanently deleted.
        </small>
    `;
    
    document.getElementById('confirmDeleteBtn').onclick = async function() {
        try {
            showLoading();
            
            // ✅ FIX: Send buyer_code in request body
            const response = await fetch(`/setting-document/buyer-email/buyer/${encodeURIComponent(buyerCode)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    buyer_code: buyerCode
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                deleteModalGeneric.hide();
                showToast(result.message, 'success');
                loadBuyerEmails();
            } else {
                showToast(result.message || 'Failed to delete buyer', 'error');
            }
        } catch (error) {
            console.error('Delete buyer error:', error);
            showToast('Error: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    };
    
    deleteModalGeneric.show();
}

// ========================================
// ✅ NEW: DOCUMENT SETTINGS FILTER
// ========================================
function filterDocumentSettings() {
    const searchValue = document.getElementById('filterDocumentBuyer').value.toLowerCase().trim();
    const customerCards = document.querySelectorAll('#customerCards .customer-card');
    
    let visibleCount = 0;
    
    customerCards.forEach(card => {
        const customerName = card.querySelector('.customer-name').textContent.toLowerCase();
        
        if (searchValue === '' || customerName.includes(searchValue)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    console.log(`Document Settings Filter: Showing ${visibleCount} of ${customerCards.length} buyers`);
    
    // Show message if no results
    if (visibleCount === 0 && searchValue !== '') {
        showToast('No buyers found matching your search', 'warning');
    }
}

function resetDocumentFilter() {
    document.getElementById('filterDocumentBuyer').value = '';
    filterDocumentSettings();
    showToast('Filter reset - showing all buyers', 'success');
}
</script>
@endsection