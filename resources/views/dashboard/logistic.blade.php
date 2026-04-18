@extends('layouts.app')

@section('title', 'Logistic Dashboard - Document Management')

@section('styles')
<style>
:root {
    --logistic-primary: #2563eb;
    --logistic-secondary: #1e40af;
    --logistic-accent: #60a5fa;
    --logistic-light: #dbeafe;
    --logistic-bg: #eff6ff;
    --logistic-shadow: 0 8px 32px rgba(37, 99, 235, 0.1);
}

body {
    background: linear-gradient(135deg, var(--logistic-bg) 0%, #e0f2fe 100%);
    min-height: 100vh;
}

.main-container {
    padding: 25px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 35px;
    box-shadow: var(--logistic-shadow);
    border: 2px solid var(--logistic-light);
}

.page-header h1 {
    color: var(--logistic-primary);
    font-weight: 800;
    font-size: 2rem;
    margin-bottom: 20px;
}

/* Location Filter Buttons */
.location-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-location {
    padding: 10px 20px;
    border: 2px solid var(--logistic-light);
    background: white;
    color: var(--logistic-primary);
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-location:hover {
    background: var(--logistic-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}

.btn-location.active {
    background: var(--logistic-primary);
    color: white;
    border-color: var(--logistic-primary);
}

/* Statistics Cards */
.stats-cards {
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: var(--logistic-shadow);
    border: 2px solid var(--logistic-light);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(37, 99, 235, 0.2);
}

.stat-card h3 {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--logistic-primary);
    margin-bottom: 5px;
}

.stat-card p {
    color: #64748b;
    font-weight: 600;
    margin: 0;
}

/* Delivery List Card */
.delivery-list-card {
    background: white;
    border-radius: 15px;
    box-shadow: var(--logistic-shadow);
    border: 2px solid var(--logistic-light);
}

.delivery-list-card .card-header {
    background: linear-gradient(135deg, var(--logistic-primary) 0%, var(--logistic-secondary) 100%);
    color: white;
    border-radius: 13px 13px 0 0;
    padding: 20px;
}

.delivery-list-card .card-header h5 {
    margin: 0;
    font-weight: 700;
}

.delivery-row {
    cursor: pointer;
    transition: all 0.2s ease;
}

.delivery-row:hover {
    background: var(--logistic-light) !important;
}

.delivery-row.active {
    background: var(--logistic-light) !important;
    border-left: 4px solid var(--logistic-primary);
}

/* Progress Bar */
.progress {
    height: 20px;
    border-radius: 10px;
    background: #e2e8f0;
}

.progress-bar {
    background: linear-gradient(90deg, var(--logistic-primary) 0%, var(--logistic-accent) 100%);
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Upload Form Card */
.upload-form-card {
    background: white;
    border-radius: 15px;
    box-shadow: var(--logistic-shadow);
    border: 2px solid var(--logistic-light);
}

.upload-form-card .card-header {
    background: linear-gradient(135deg, var(--logistic-primary) 0%, var(--logistic-secondary) 100%);
    color: white;
    border-radius: 13px 13px 0 0;
    padding: 20px;
}

.upload-form-card .card-header h5 {
    margin: 0;
    font-weight: 700;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--logistic-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.form-control:read-only {
    background: #f1f5f9;
    cursor: not-allowed;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, var(--logistic-primary) 0%, var(--logistic-secondary) 100%);
    border: none;
    padding: 12px 25px;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.btn-block {
    width: 100%;
    display: block;
}

/* Uploaded Documents Panel */
.doc-group {
    margin-bottom: 25px;
}

.doc-group h6 {
    color: var(--logistic-primary);
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--logistic-light);
}

.doc-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 10px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.doc-item:hover {
    background: white;
    border-color: var(--logistic-light);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.doc-item i {
    color: var(--logistic-primary);
    font-size: 1.5rem;
}

.doc-item span {
    flex: 1;
    font-weight: 600;
    color: #334155;
}

.doc-item small {
    color: #64748b;
    font-size: 0.85rem;
}

.doc-item .btn-danger {
    background: #ef4444;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.doc-item .btn-danger:hover {
    background: #dc2626;
    transform: scale(1.05);
}

/* Responsive */
@media (max-width: 768px) {
    .stats-cards .col-md-3 {
        margin-bottom: 15px;
    }
    
    .col-md-5, .col-md-7 {
        margin-bottom: 20px;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid main-container">
    <!-- HEADER SECTION -->
    <div class="page-header">
        <h1>🚚 Logistic Dashboard</h1>
        
        <!-- Location Filter Buttons -->
        <div class="location-filters">
            <button class="btn-location {{ !isset($location) || !$location ? 'active' : '' }}" 
                    onclick="window.location='{{ route('logistic.index') }}'">
                <i class="fas fa-globe"></i> All Locations
            </button>
            <button class="btn-location {{ isset($location) && $location == 'surabaya' ? 'active' : '' }}" 
                    onclick="window.location='{{ route('logistic.index') }}?location=surabaya'">
                <i class="fas fa-map-marker-alt"></i> Surabaya
            </button>
            <button class="btn-location {{ isset($location) && $location == 'semarang' ? 'active' : '' }}" 
                    onclick="window.location='{{ route('logistic.index') }}?location=semarang'">
                <i class="fas fa-map-marker-alt"></i> Semarang
            </button>
        </div>
    </div>

    <!-- STATISTICS CARDS -->
    <div class="row stats-cards">
        <div class="col-md-3">
            <div class="stat-card">
                <h3>{{ $stats['total_documents'] ?? 0 }}</h3>
                <p><i class="fas fa-file-alt"></i> Total Documents</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3>{{ $stats['today_uploads'] ?? 0 }}</h3>
                <p><i class="fas fa-calendar-day"></i> Today Uploads</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3>{{ $stats['this_month'] ?? 0 }}</h3>
                <p><i class="fas fa-calendar-alt"></i> This Month</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <h3>{{ $stats['pending_shipments'] ?? 0 }}</h3>
                <p><i class="fas fa-shipping-fast"></i> Pending Shipments</p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT: TWO COLUMNS -->
    <div class="row">
        <!-- LEFT COLUMN: Delivery List -->
        <div class="col-md-5">
            <div class="card delivery-list-card">
                <div class="card-header">
                    <h5><i class="fas fa-box"></i> Delivery Orders</h5>
                </div>
                <div class="card-body">
                    @if(isset($groupedData) && empty($groupedData))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No delivery orders found{{ isset($location) && $location ? ' for ' . ucfirst($location) : '' }}
                        </div>
                    @elseif(isset($groupedData))
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Delivery Order</th>
                                    <th>Customer</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedData as $key => $item)
                                <tr class="delivery-row" 
                                    data-delivery="{{ $item['delivery'] }}" 
                                    data-customer="{{ $item['customer_name'] }}">
                                    <td><strong>{{ $item['delivery'] }}</strong></td>
                                    <td>{{ $item['customer_name'] }}</td>
                                    <td>
                                        @php
                                            $percentage = $uploadStatus[$key]['percentage'] ?? 0;
                                        @endphp
                                        <div class="progress">
                                            <div class="progress-bar" 
                                                 role="progressbar"
                                                 style="width: {{ $percentage }}%"
                                                 aria-valuenow="{{ $percentage }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ $uploadStatus[$key]['uploaded'] ?? 0 }}/{{ $uploadStatus[$key]['total'] ?? 0 }} docs
                                        </small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No data available
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Upload Panel -->
        <div class="col-md-7">
            <!-- Upload Form -->
            <div class="card upload-form-card" id="uploadPanel" style="display:none;">
                <div class="card-header">
                    <h5><i class="fas fa-upload"></i> Upload Logistic Document</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Delivery Order</label>
                            <input type="text" class="form-control" name="delivery_order" id="delivery_order" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Customer Name</label>
                            <input type="text" class="form-control" name="customer_name" id="customer_name" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-file-signature"></i> Document Type *</label>
                            <select class="form-control" name="document_type" id="document_type" required>
                                <option value="">-- Select Document --</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-paperclip"></i> File *</label>
                            <input type="file" class="form-control" name="document_file" id="document_file" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                            <small class="form-text text-muted">
                                Accepted formats: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (Max: 10MB)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                            <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Add any additional notes..."></textarea>
                        </div>
                        
                        <!-- ✅ CRITICAL: Hidden flags for logistic -->
                        <input type="hidden" name="uploaded_from" value="logistic">
                        <input type="hidden" name="team" value="Logistic">
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Document
                        </button>
                    </form>
                </div>
            </div>

            <!-- Uploaded Documents List -->
            <div class="card upload-form-card mt-3" id="uploadedDocsPanel" style="display:none;">
                <div class="card-header">
                    <h5><i class="fas fa-list-alt"></i> Uploaded Logistic Documents</h5>
                </div>
                <div class="card-body" id="uploadedDocsList">
                    <!-- Will be populated by AJAX -->
                </div>
            </div>
            
            <!-- Initial state message -->
            <div class="card upload-form-card" id="initialMessage">
                <div class="card-body text-center py-5">
                    <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Select a delivery order from the left to start uploading documents</h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // ✅ CRITICAL: Set dashboard header untuk semua AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Dashboard': 'logistic'  // PENTING!
        }
    });

    // ✅ Click delivery row to load upload form
    $('.delivery-row').on('click', function() {
        const delivery = $(this).data('delivery');
        const customer = $(this).data('customer');
        
        console.log('Selected delivery:', delivery, customer);
        
        // Highlight selected row
        $('.delivery-row').removeClass('active');
        $(this).addClass('active');
        
        // Hide initial message
        $('#initialMessage').hide();
        
        // Show panels
        $('#uploadPanel').slideDown();
        $('#uploadedDocsPanel').slideDown();
        
        // Fill form
        $('#delivery_order').val(delivery);
        $('#customer_name').val(customer);
        
        // Load allowed documents
        loadAllowedDocuments(customer);
        
        // Load uploaded documents
        loadUploadedDocuments(delivery, customer);
    });

    // ✅ Load allowed documents for customer
    function loadAllowedDocuments(customerName) {
        console.log('Loading allowed documents for:', customerName);
        
        $.ajax({
            url: `/documents/allowed-documents/${encodeURIComponent(customerName)}/Logistic`,
            method: 'GET',
            success: function(response) {
                console.log('Allowed documents response:', response);
                
                let $select = $('#document_type');
                $select.empty();
                $select.append('<option value="">-- Select Document --</option>');
                
                if (response.documents && response.documents.length > 0) {
                    response.documents.forEach(function(doc) {
                        $select.append(`<option value="${doc}">${doc.replace(/_/g, ' ')}</option>`);
                    });
                } else {
                    $select.append('<option value="" disabled>No documents configured for logistic</option>');
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Documents',
                        text: 'Logistic documents not yet configured for this customer in Document Settings',
                        confirmButtonColor: '#2563eb'
                    });
                }
            },
            error: function(xhr) {
                console.error('Failed to load allowed documents:', xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load document types',
                    confirmButtonColor: '#2563eb'
                });
            }
        });
    }

    // ✅ Load uploaded documents
    function loadUploadedDocuments(delivery, customer) {
        console.log('Loading uploaded documents for:', delivery, customer);
        
        $.ajax({
            url: `/documents/uploads/${encodeURIComponent(delivery)}/${encodeURIComponent(customer)}`,
            method: 'GET',
            headers: {
                'X-Dashboard': 'logistic'  // CRITICAL!
            },
            success: function(response) {
                console.log('Uploaded documents response:', response);
                
                let html = '';
                
                if (response.success && response.total_count > 0) {
                    $.each(response.uploads, function(docType, files) {
                        html += `<div class="doc-group">
                            <h6><i class="fas fa-folder-open"></i> ${docType.replace(/_/g, ' ')}</h6>`;
                        
                        files.forEach(function(file) {
                            html += `
                                <div class="doc-item">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>${file.file_name}</span>
                                    <small>${formatFileSize(file.file_size)}</small>
                                    <small>${formatDate(file.uploaded_at)}</small>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDoc(${file.id}, '${file.file_name}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        });
                        html += `</div>`;
                    });
                } else {
                    html = `
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No documents uploaded yet for this delivery</p>
                        </div>
                    `;
                }
                
                $('#uploadedDocsList').html(html);
            },
            error: function(xhr) {
                console.error('Failed to load uploaded documents:', xhr);
                $('#uploadedDocsList').html('<p class="text-danger">Failed to load documents</p>');
            }
        });
    }

    // ✅ Upload form submit
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        // ✅ Double check flags
        formData.set('uploaded_from', 'logistic');
        formData.set('team', 'Logistic');
        
        console.log('Uploading document with FormData:', {
            delivery_order: formData.get('delivery_order'),
            customer_name: formData.get('customer_name'),
            document_type: formData.get('document_type'),
            uploaded_from: formData.get('uploaded_from'),
            team: formData.get('team')
        });
        
        // Show loading
        Swal.fire({
            title: 'Uploading...',
            html: 'Please wait while we upload your document',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '{{ route("documents.upload") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Dashboard': 'logistic'  // CRITICAL!
            },
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Document uploaded successfully',
                        confirmButtonColor: '#2563eb',
                        timer: 2000
                    });
                    
                    // Refresh uploaded docs list
                    loadUploadedDocuments($('#delivery_order').val(), $('#customer_name').val());
                    
                    // Clear form
                    $('#document_file').val('');
                    $('#document_type').val('');
                    $('#notes').val('');
                    
                    // Refresh page untuk update progress
                    setTimeout(() => location.reload(), 2000);
                }
            },
            error: function(xhr) {
                console.error('Upload error:', xhr);
                
                let errorMessage = 'An error occurred while uploading';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    html: errorMessage,
                    confirmButtonColor: '#2563eb'
                });
            }
        });
    });

    // Helper function: Format file size
    function formatFileSize(bytes) {
        if (!bytes || bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Helper function: Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});

// Delete document function (global scope for onclick)
function deleteDoc(id, fileName) {
    Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete:<br><strong>${fileName}</strong><br><br>This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/documents/delete/${id}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Dashboard': 'logistic'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Document has been deleted.',
                        confirmButtonColor: '#2563eb',
                        timer: 2000
                    });
                    setTimeout(() => location.reload(), 2000);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to delete document.',
                        confirmButtonColor: '#2563eb'
                    });
                }
            });
        }
    });
}
</script>
@endsection