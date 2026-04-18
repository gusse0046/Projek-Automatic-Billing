<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'allowed_documents'
    ];

    protected $casts = [
        'allowed_documents' => 'array'
    ];

    // List semua dokumen yang tersedia
    public static function getAvailableDocuments()
    {
        return [
            'PEB',
            'INVOICE',
            'PACKING_LIST',
            'CARB',
            'LACEY_ACT',
            'COO',
            'FUMIGASI',
            'PYTOSANITARY',
            'ISF',
            'CONTAINER_LOAD',
            'TSCA',
            'GCC',
            'PPDF',
            'VLEGAL',
            'AWB',
            'IREX',
            'FLEGT',
            'BILL_OF_LADING',
            'CONTAINER_LOAD_PLAN',
            'CONTAINER_CHECKLIST'  // ✅ FIXED: Menggunakan CONTAINER_CHECKLIST (bukan CONTAINER_CHEKLIST)
        ];
    }

    // Helper method untuk cek apakah dokumen diizinkan
    public function isDocumentAllowed($documentType)
    {
        return in_array($documentType, $this->allowed_documents ?? []);
    }

    /**
     * ✅ ENHANCED: Get documents by team dengan filter khusus untuk Logistic
     */
    public static function getDocumentsByTeam($team)
    {
        $documents = [
            'Finance' => [
                'INVOICE',
                'PACKING_LIST',
                'PAYMENT_INSTRUCTION'
            ],
            'Exim' => [
                'PEB', 'CARB', 'LACEY_ACT', 'COO', 'FUMIGASI', 
                'PYTOSANITARY', 'ISF', 'CONTAINER_LOAD', 'TSCA', 
                'GCC', 'PPDF', 'VLEGAL', 'AWB', 'IREX',
                'FLEGT', 'BILL_OF_LADING',
                'CONTAINER_LOAD_PLAN', 'CONTAINER_CHECKLIST'
            ],
            'Logistic' => [
                'CONTAINER_LOAD',
                'CONTAINER_CHECKLIST'  // ✅ FIXED: Konsisten dengan naming
            ]
        ];

        return $documents[$team] ?? [];
    }
}