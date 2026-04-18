@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Billing Data
                    </h4>
                    <small>Data from SAP Billing System</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark">
                        Total Records: {{ $totalRecords }}
                    </span>
                    @if(isset($responseTime))
                        <br><small class="text-light">Response Time: {{ $responseTime }}</small>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error:</strong> {{ $error }}
                    </div>
                    <div class="text-center">
                        <button onclick="location.reload()" class="btn btn-primary">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                @elseif(count($billingData) > 0)
                    <!-- Filters dan Search -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control" 
                                       placeholder="Search delivery, customer, billing document...">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button onclick="location.reload()" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt me-1"></i>Refresh Data
                            </button>
                            <button class="btn btn-outline-success" onclick="exportToCSV()">
                                <i class="fas fa-download me-1"></i>Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Data -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="billingTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="12%">Delivery</th>
                                    <th width="25%">Customer Name</th>
                                    <th width="15%">Billing Document</th>
                                    <th width="15%">Net Value</th>
                                    <th width="12%">Billing Date</th>
                                    <th width="21%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billingData as $index => $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['Delivery'] ?? '-' }}</strong>
                                    </td>
                                    <td>
                                        <div class="customer-name">
                                            {{ $item['Customer Name'] ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if(!empty($item['Billing Document']))
                                            <span class="badge bg-success">
                                                {{ $item['Billing Document'] }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                Not Billed
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($item['Net Value in Document Currency']))
                                            <strong class="text-success">
                                                {{ $item['Net Value in Document Currency'] }}
                                            </strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item['Billing Date'] ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success submit-btn"
                                                    data-delivery="{{ $item['Delivery'] ?? '' }}"
                                                    data-customer="{{ $item['Customer Name'] ?? '' }}"
                                                    title="Submit Billing">
                                                <i class="fas fa-check me-1"></i>Submit
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info detail-btn"
                                                    data-delivery="{{ $item['Delivery'] ?? '' }}"
                                                    data-customer="{{ $item['Customer Name'] ?? '' }}"
                                                    title="View Details">
                                                <i class="fas fa-eye me-1"></i>Detail
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Info -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Showing {{ count($billingData) }} of {{ $totalRecords }} records
                            </small>
                        </div>
                        <div>
                            <small class="text-muted">
                                Last updated: {{ now()->format('d M Y H:i:s') }}
                            </small>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Billing Data Available</h5>
                        <p class="text-muted">No data found from SAP billing system.</p>
                        <button onclick="location.reload()" class="btn btn-primary">
                            <i class="fas fa-refresh me-1"></i>Refresh Data
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Billing Detail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContent">
                    <!-- Detail content akan diisi via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Submit Confirmation -->
<div class="modal fade" id="submitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Submit Billing
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit this billing?</p>
                <div id="submitContent">
                    <!-- Submit info akan diisi via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmSubmit">
                    <i class="fas fa-check me-1"></i>Confirm Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('billingTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Submit button functionality
    document.querySelectorAll('.submit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const delivery = this.dataset.delivery;
            const customer = this.dataset.customer;
            
            document.getElementById('submitContent').innerHTML = `
                <div class="alert alert-info">
                    <strong>Delivery:</strong> ${delivery}<br>
                    <strong>Customer:</strong> ${customer}
                </div>
            `;
            
            document.getElementById('confirmSubmit').onclick = function() {
                submitBilling(delivery);
            };
            
            new bootstrap.Modal(document.getElementById('submitModal')).show();
        });
    });

    // Detail button functionality
    document.querySelectorAll('.detail-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const delivery = this.dataset.delivery;
            const customer = this.dataset.customer;
            
            document.getElementById('detailContent').innerHTML = `
                <div class="alert alert-info">
                    <h6>Billing Information</h6>
                    <strong>Delivery:</strong> ${delivery}<br>
                    <strong>Customer:</strong> ${customer}<br>
                    <br>
                    <em>Detail functionality will be implemented soon...</em>
                </div>
            `;
            
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
    });
});

function submitBilling(delivery) {
    // Placeholder untuk submit functionality
    alert(`Submit action for delivery: ${delivery}\n\nThis functionality will be implemented soon.`);
    bootstrap.Modal.getInstance(document.getElementById('submitModal')).hide();
}

function exportToCSV() {
    // Simple CSV export functionality
    const table = document.getElementById('billingTable');
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach((col, index) => {
            if (index < 5) { // Skip action column
                rowData.push(col.textContent.trim().replace(/,/g, ';'));
            }
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `billing_data_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<style>
.customer-name {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
}

.btn-group .btn {
    margin: 0 1px;
}

#searchInput:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>
@endsection