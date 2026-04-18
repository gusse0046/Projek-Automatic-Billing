<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DocumentUpload extends Model
{
    use HasFactory;

    /**
     * BACKWARD COMPATIBILITY: Fillable dengan field lama dan baru
     * Field baru akan diabaikan jika kolom tidak ada di database
     */
    protected $fillable = [
        // FIELD LAMA (wajib ada)
        'delivery_order',
        'customer_name',
        'document_type',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_at',
        
        // FIELD BARU (optional - akan dicek di runtime)
        'billing_document', // field baru untuk billing document
        'notes',           // untuk catatan
        'uploaded_by',     // siapa yang upload
        'team'            // team Finance atau Exim
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * BACKWARD COMPATIBILITY: Override create method untuk filter field yang tidak ada
     */
    public static function create(array $attributes = [])
    {
        // Filter attributes berdasarkan kolom yang ada di database
        $filteredAttributes = [];
        $columns = Schema::getColumnListing('document_uploads');
        
        foreach ($attributes as $key => $value) {
            if (in_array($key, $columns)) {
                $filteredAttributes[$key] = $value;
            }
        }
        
        return static::query()->create($filteredAttributes);
    }

    /**
     * BACKWARD COMPATIBILITY: Override fill method
     */
    public function fill(array $attributes)
    {
        // Filter attributes berdasarkan kolom yang ada di database
        $filteredAttributes = [];
        $columns = Schema::getColumnListing('document_uploads');
        
        foreach ($attributes as $key => $value) {
            if (in_array($key, $columns)) {
                $filteredAttributes[$key] = $value;
            }
        }
        
        return parent::fill($filteredAttributes);
    }

    /**
     * BACKWARD COMPATIBILITY: Get billing_document attribute dengan fallback ke delivery_order
     */
    public function getBillingDocumentAttribute($value)
    {
        // Jika kolom billing_document ada dan memiliki value
        if (Schema::hasColumn('document_uploads', 'billing_document') && !empty($value)) {
            return $value;
        }
        
        // Fallback ke delivery_order jika billing_document tidak ada atau kosong
        return $this->attributes['delivery_order'] ?? '';
    }

    /**
     * BACKWARD COMPATIBILITY: Set billing_document attribute
     */
    public function setBillingDocumentAttribute($value)
    {
        if (Schema::hasColumn('document_uploads', 'billing_document')) {
            $this->attributes['billing_document'] = $value;
        } else {
            // Jika kolom billing_document belum ada, simpan di delivery_order
            $this->attributes['delivery_order'] = $value;
        }
    }

    /**
     * BACKWARD COMPATIBILITY: Get delivery_order attribute dengan fallback ke billing_document
     */
    public function getDeliveryOrderAttribute($value)
    {
        // Jika ada value di delivery_order, gunakan itu
        if (!empty($value)) {
            return $value;
        }
        
        // Jika tidak ada, coba ambil dari billing_document
        if (Schema::hasColumn('document_uploads', 'billing_document')) {
            return $this->attributes['billing_document'] ?? '';
        }
        
        return $value;
    }

    /**
     * Scope untuk filter Finance documents
     */
    public function scopeFinanceDocuments($query)
    {
        return $query->whereIn('document_type', ['INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION']);
    }

    /**
     * Scope untuk filter Exim documents
     */
    public function scopeEximDocuments($query)
    {
        return $query->whereNotIn('document_type', ['INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION']);
    }

    /**
 * Scope untuk filter Logistic documents
 */
public function scopeLogisticDocuments($query)
{
    $logisticDocs = [
        'LOADING_CHECKLIST','CARB','CONTAINER_LOAD_PLAN', 'CONTAINER_CHECKLIST'
    ];
    return $query->whereIn('document_type', $logisticDocs);
}

    /**
     * Scope untuk filter by team (dengan backward compatibility)
     */
    public function scopeByTeam($query, $team)
    {
        // Jika kolom team ada, gunakan itu
        if (Schema::hasColumn('document_uploads', 'team')) {
            return $query->where('team', $team);
        }
        
        // Jika tidak ada, filter berdasarkan document_type
        if ($team === 'Finance') {
            return $query->financeDocuments();
        } elseif ($team === 'Exim') {
            return $query->eximDocuments();
        } elseif ($team === 'Logistic') {
            return $query->logisticDocuments();
        }
        
        return $query;
    }

    /**
 * Check if document is Logistic-related
 */
public function isLogisticDocument()
{
    $logisticDocuments = [
        'LOADING_CHECKLIST','CARB',
        'CONTAINER_LOAD_PLAN', 'CONTAINER_CHECKLIST'
    ];
    return in_array($this->document_type, $logisticDocuments);
}

    /**
     * Scope untuk delivery dan customer tertentu (dengan backward compatibility)
     */
    public function scopeForDelivery($query, $deliveryOrBilling, $customer)
    {
        // Cek apakah kolom billing_document ada
        if (Schema::hasColumn('document_uploads', 'billing_document')) {
            return $query->where(function($q) use ($deliveryOrBilling) {
                $q->where('billing_document', $deliveryOrBilling)
                  ->orWhere('delivery_order', $deliveryOrBilling);
            })->where('customer_name', $customer);
        }
        
        // Fallback ke delivery_order
        return $query->where('delivery_order', $deliveryOrBilling)
                     ->where('customer_name', $customer);
    }

    /**
     * Scope untuk mencari berdasarkan billing document (prioritas utama)
     */
    public function scopeForBillingDocument($query, $billingDocument, $customer)
    {
        if (Schema::hasColumn('document_uploads', 'billing_document')) {
            return $query->where('billing_document', $billingDocument)
                         ->where('customer_name', $customer);
        }
        
        // Jika kolom billing_document belum ada, cari di delivery_order
        return $query->where('delivery_order', $billingDocument)
                     ->where('customer_name', $customer);
    }

    /**
     * Scope untuk mencari berdasarkan billing document atau delivery order
     */
    public function scopeByBillingOrDelivery($query, $number)
    {
        if (Schema::hasColumn('document_uploads', 'billing_document')) {
            return $query->where(function($q) use ($number) {
                $q->where('billing_document', $number)
                  ->orWhere('delivery_order', $number);
            });
        }
        
        return $query->where('delivery_order', $number);
    }

    /**
     * Check if document is Finance-related
     */
    public function isFinanceDocument()
    {
        $financeDocuments = [
        'INVOICE', 
        'PACKING_LIST', 
        'PAYMENT_INSTRUCTION'
    
];
        return in_array($this->document_type, $financeDocuments);
    }

    /**
     * Check if document is Exim-related
     */
    public function isEximDocument()
    {
        return !$this->isFinanceDocument();
    }

    /**
     * Get document icon class
     */
    public function getDocumentIconAttribute()
    {
        $icons = [
            'INVOICE' => 'fa-file-invoice-dollar',
            'PACKING_LIST' => 'fa-boxes',
            'PAYMENT_INSTRUCTION' => 'fa-money-check',
            'CARB' => 'fa-car-side',
            'CONTAINER_CHECKLIST' => 'fa-clipboard-list',
            'CONTAINER_LOAD_PLAN' => 'fa-truck-loading',  // Baru
            'AWB' => 'fa-plane',
            'IREX' => 'fa-plane',
            'FLEGT' => 'fa-tree',
            'PEB' => 'fa-file-contract',
            'Freight Cargo Receipt' => 'fa-file-contract',
            'COO' => 'fa-certificate',
            'FUMIGASI' => 'fa-bug',
            'PYTOSANITARY' => 'fa-seedling',
            'LACEY_ACT' => 'fa-leaf',
            'BL' => 'fa-ship',
            'ISF' => 'fa-file-signature',
            'CONTAINER_LOAD' => 'fa-box-open',
            'TSCA' => 'fa-file-signature',
            'GCC' => 'fa-check-circle',
            'PPDF' => 'fa-file-pdf',
            'VLEGAL' => 'fa-balance-scale',
            'BILL OF LADING' => 'fa-ship'
        ];
        
        return $icons[$this->document_type] ?? 'fa-file';
    }

    /**
     * Get document color class
     */
    public function getDocumentColorAttribute()
    {
        $colors = [
            'INVOICE' => 'danger',
            'PACKING_LIST' => 'success',
            'PAYMENT_INSTRUCTION' => 'warning',
            'CONTAINER_CHECKLIST' => 'info',
            'CARB' => 'info',
            'CONTAINER_LOAD_PLAN' => 'secondary',  // Baru
            'AWB' => 'warning',  // Baru
            'IREX' => 'primary',
            'FLEGT' => 'success',
            'PEB' => 'primary',
            'Freight Cargo Receipt' => 'dark',
            'COO' => 'dark',
            'FUMIGASI' => 'secondary',
            'PYTOSANITARY' => 'success',
            'LACEY_ACT' => 'warning',
            'BL' => 'info',
            'ISF' => 'primary',
            'CONTAINER_LOAD' => 'secondary',
            'TSCA' => 'info',
            'GCC' => 'info',
            'PPDF' => 'danger',
            'VLEGAL' => 'purple'
        ];
        
        return $colors[$this->document_type] ?? 'secondary';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'Unknown size';
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get document type label
     */
    public function getDocumentTypeLabelAttribute()
    {
        $labels = [
            'INVOICE' => 'Invoice',
            'PACKING_LIST' => 'Packing List',
            'PAYMENT_INSTRUCTION' => 'Payment Intruction',
            'PEB' => 'PEB',
            'Freight Cargo Receipt' => 'FCR',
            'COO' => 'Certificate of Origin',
            'FUMIGASI' => 'Fumigation Certificate',
            'PYTOSANITARY' => 'Phytosanitary Certificate',
            'LACEY_ACT' => 'Lacey Act',
            'CARB' => 'CARB',
            'AWB' => 'Air Waybill',
            'IREX' => 'IREX',
            'FLEGT' => 'FLEGT',
            'BILL OF LADING' => 'Bill of Lading',
            'CONTAINER_LOAD_PLAN' => 'Container Load Plan',
            'ISF' => 'ISF',
            'CONTAINER_LOAD' => 'Container Load',
            'TSCA' => 'TSCA',
            'GCC' => 'GCC',
            'PPDF' => 'PPDF',
            'VLEGAL' => 'VLegal'
        ];
        
        return $labels[$this->document_type] ?? $this->document_type;
    }

    /**
     * BACKWARD COMPATIBILITY: Get team attribute dengan fallback logic
     */
   public function getTeamAttribute($value)
{
    if (!Schema::hasColumn('document_uploads', 'team')) {
        if ($this->isFinanceDocument()) {
            return 'Finance';
        } elseif ($this->isLogisticDocument()) {  // ← TAMBAHAN BARU
            return 'Logistic';
        } else {
            return 'Exim';
        }
    }
    
    if (!$value) {
        if ($this->isFinanceDocument()) {
            return 'Finance';
        } elseif ($this->isLogisticDocument()) {  // ← TAMBAHAN BARU
            return 'Logistic';
        } else {
            return 'Exim';
        }
    }
    
    return $value;
}

    /**
     * BACKWARD COMPATIBILITY: Get uploaded_by attribute dengan fallback
     */
    public function getUploadedByAttribute($value)
    {
        if (!Schema::hasColumn('document_uploads', 'uploaded_by')) {
            return 'System';
        }
        
        return $value ?? 'System';
    }

    /**
     * BACKWARD COMPATIBILITY: Get notes attribute dengan fallback
     */
    public function getNotesAttribute($value)
    {
        if (!Schema::hasColumn('document_uploads', 'notes')) {
            return '';
        }
        
        return $value ?? '';
    }

    /**
     * BACKWARD COMPATIBILITY: Set team attribute
     */
    public function setTeamAttribute($value)
    {
        if (Schema::hasColumn('document_uploads', 'team')) {
            $this->attributes['team'] = $value;
        }
        // Jika kolom tidak ada, abaikan (tidak error)
    }

    /**
     * BACKWARD COMPATIBILITY: Set uploaded_by attribute
     */
    public function setUploadedByAttribute($value)
    {
        if (Schema::hasColumn('document_uploads', 'uploaded_by')) {
            $this->attributes['uploaded_by'] = $value;
        }
        // Jika kolom tidak ada, abaikan (tidak error)
    }

    /**
     * BACKWARD COMPATIBILITY: Set notes attribute
     */
    public function setNotesAttribute($value)
    {
        if (Schema::hasColumn('document_uploads', 'notes')) {
            $this->attributes['notes'] = $value;
        }
        // Jika kolom tidak ada, abaikan (tidak error)
    }

    /**
     * Check if new fields are available in database
     */
    public function hasNewFields()
    {
        return Schema::hasColumn('document_uploads', 'billing_document') &&
               Schema::hasColumn('document_uploads', 'team') && 
               Schema::hasColumn('document_uploads', 'uploaded_by') && 
               Schema::hasColumn('document_uploads', 'notes');
    }

    /**
     * Get available columns in database
     */
    public static function getAvailableColumns()
    {
        return Schema::getColumnListing('document_uploads');
    }

    /**
     * Safe method untuk mengambil attributes dengan backward compatibility
     */
    public function getSafeAttributes()
    {
        $attributes = $this->toArray();
        $columns = Schema::getColumnListing('document_uploads');
        
        // Tambahkan virtual attributes jika kolom tidak ada
        if (!in_array('billing_document', $columns)) {
            $attributes['billing_document'] = $this->billing_document;
        }
        if (!in_array('team', $columns)) {
            $attributes['team'] = $this->team;
        }
        if (!in_array('uploaded_by', $columns)) {
            $attributes['uploaded_by'] = $this->uploaded_by;
        }
        if (!in_array('notes', $columns)) {
            $attributes['notes'] = $this->notes;
        }
        
        return $attributes;
    }

    /**
     * Helper method untuk mendapatkan nomor dokumen (billing atau delivery)
     */
    public function getDocumentNumber()
    {
        return $this->billing_document ?: $this->delivery_order;
    }

    /**
 * ENHANCED: Auto-upload dari Z:\sd dengan pattern yang lebih akurat
 */
public function enhancedAutoUpload(Request $request)
{
    return app(EnhancedAutoUploadController::class)->autoUploadFromSmartform($request);
}

public function monitorSmartformFolder(Request $request)
{
    return app(EnhancedAutoUploadController::class)->monitorSmartformFolder();
}

public function batchMonitorDocuments(Request $request)
{
    // Implementation for batch monitoring
    $billingDocuments = $request->get('billing_documents', []);
    $newFilesFound = 0;
    
    foreach ($billingDocuments as $billing) {
        // Check for new files for each billing document
        // Process if found
    }
    
    return response()->json([
        'success' => true,
        'checked_documents' => count($billingDocuments),
        'new_files_found' => $newFilesFound
    ]);
}
}