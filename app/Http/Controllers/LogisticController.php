<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\DocumentSetting;
use App\Models\BillingStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LogisticController extends Controller
{
    /**
     * Display logistic dashboard
     */
    public function index(Request $request)
    {
        try {
            // ✅ Handle location filter
            $location = $request->get('location'); // surabaya / semarang / null
            
            // Get statistics dengan location filter
            $stats = [
                'total_documents' => DocumentUpload::logisticDocuments()
                    ->when($location, function($q) use ($location) {
                        // Jika ada kolom location di document_uploads, uncomment baris ini
                        // $q->where('location', $location);
                    })
                    ->count(),
                'today_uploads' => DocumentUpload::logisticDocuments()
                    ->whereDate('uploaded_at', Carbon::today())
                    ->when($location, function($q) use ($location) {
                        // $q->where('location', $location);
                    })
                    ->count(),
                'this_month' => DocumentUpload::logisticDocuments()
                    ->whereMonth('uploaded_at', Carbon::now()->month)
                    ->whereYear('uploaded_at', Carbon::now()->year)
                    ->when($location, function($q) use ($location) {
                        // $q->where('location', $location);
                    })
                    ->count(),
                'pending_shipments' => BillingStatus::where('status', 'pending')
                    ->when($location, function($q) use ($location) {
                        // Jika ada kolom location di billing_statuses
                        // $q->where('location', $location);
                    })
                    ->count()
            ];

            // Get recent documents dengan location filter
            $recentDocuments = DocumentUpload::logisticDocuments()
                ->when($location, function($q) use ($location) {
                    // $q->where('location', $location);
                })
                ->orderBy('uploaded_at', 'desc')
                ->take(10)
                ->get();

            // Get document type distribution
            $documentDistribution = DocumentUpload::logisticDocuments()
                ->when($location, function($q) use ($location) {
                    // $q->where('location', $location);
                })
                ->selectRaw('document_type, COUNT(*) as count')
                ->groupBy('document_type')
                ->get()
                ->pluck('count', 'document_type');

            // Get available document types for logistic
            $availableDocuments = DocumentSetting::getDocumentsByTeam('Logistic');
            
            // ✅ Get grouped data for delivery list (seperti di DashboardController)
            $groupedData = BillingStatus::select('delivery_order', 'customer_name', 'location')
                ->when($location, function($q) use ($location) {
                    // $q->where('location', $location);
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('delivery_order')
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'delivery' => $first->delivery_order,
                        'customer_name' => $first->customer_name,
                        'location' => $first->location ?? 'Unknown'
                    ];
                });
            
            // ✅ Calculate upload status for each delivery
            $uploadStatus = [];
            foreach ($groupedData as $key => $item) {
                $allowedDocs = DocumentSetting::where('customer_name', $item['customer_name'])
                    ->first();
                $logisticRequired = $allowedDocs ? 
                    array_intersect($allowedDocs->allowed_documents ?? [], DocumentSetting::getDocumentsByTeam('Logistic')) : 
                    DocumentSetting::getDocumentsByTeam('Logistic');
                
                $uploaded = DocumentUpload::where('delivery_order', $item['delivery'])
                    ->where('customer_name', $item['customer_name'])
                    ->where(function($q) {
                        $q->where('team', 'Logistic')
                          ->orWhere('uploaded_from', 'logistic');
                    })
                    ->pluck('document_type')
                    ->unique()
                    ->toArray();
                
                $uploadedCount = count(array_intersect($uploaded, $logisticRequired));
                $totalCount = count($logisticRequired);
                $percentage = $totalCount > 0 ? round(($uploadedCount / $totalCount) * 100) : 0;
                
                $uploadStatus[$key] = [
                    'uploaded' => $uploadedCount,
                    'total' => $totalCount,
                    'percentage' => $percentage
                ];
            }

            return view('dashboard.logistic', compact(
                'stats',
                'recentDocuments',
                'documentDistribution',
                'availableDocuments',
                'groupedData',
                'uploadStatus',
                'location'
            ));
        } catch (\Exception $e) {
            Log::error('Error in logistic dashboard: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat dashboard.');
        }
    }

    /**
     * Upload document for logistic
     */
    public function uploadDocument(Request $request)
    {
        try {
            $request->validate([
                'delivery_order' => 'required|string',
                'customer_name' => 'required|string',
                'document_type' => 'required|string',
                'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('logistic_documents', $fileName, 'public');

            $document = DocumentUpload::create([
                'delivery_order' => $request->delivery_order,
                'customer_name' => $request->customer_name,
                'document_type' => $request->document_type,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_at' => now(),
                'team' => 'Logistic',
                'uploaded_by' => Auth::user()->name ?? 'System',
                'notes' => $request->notes ?? ''
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload!',
                'document' => $document
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading logistic document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get documents for specific delivery order
     */
    public function getDocuments(Request $request)
    {
        try {
            $request->validate([
                'delivery_order' => 'required|string',
                'customer_name' => 'required|string'
            ]);

            $documents = DocumentUpload::logisticDocuments()
                ->where('delivery_order', $request->delivery_order)
                ->where('customer_name', $request->customer_name)
                ->orderBy('uploaded_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'documents' => $documents->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'document_type' => $doc->document_type,
                        'document_type_label' => $doc->document_type_label,
                        'file_name' => $doc->file_name,
                        'file_size' => $doc->formatted_file_size,
                        'uploaded_at' => $doc->uploaded_at->format('d/m/Y H:i'),
                        'uploaded_by' => $doc->uploaded_by,
                        'icon' => $doc->document_icon,
                        'color' => $doc->document_color,
                        'download_url' => route('logistic.download', $doc->id),
                        'preview_url' => route('logistic.preview', $doc->id)
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting logistic documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil dokumen'
            ], 500);
        }
    }

    /**
     * Download document
     */
    public function downloadDocument($id)
    {
        try {
            $document = DocumentUpload::logisticDocuments()->findOrFail($id);
            
            if (!Storage::disk('public')->exists($document->file_path)) {
                return back()->with('error', 'File tidak ditemukan');
            }

            return Storage::disk('public')->download($document->file_path, $document->file_name);
        } catch (\Exception $e) {
            Log::error('Error downloading logistic document: ' . $e->getMessage());
            return back()->with('error', 'Gagal mendownload dokumen');
        }
    }

    /**
     * Preview document
     */
    public function previewDocument($id)
    {
        try {
            $document = DocumentUpload::logisticDocuments()->findOrFail($id);
            
            if (!Storage::disk('public')->exists($document->file_path)) {
                return back()->with('error', 'File tidak ditemukan');
            }

            $filePath = Storage::disk('public')->path($document->file_path);
            $mimeType = Storage::disk('public')->mimeType($document->file_path);

            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing logistic document: ' . $e->getMessage());
            return back()->with('error', 'Gagal preview dokumen');
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument($id)
    {
        try {
            $document = DocumentUpload::logisticDocuments()->findOrFail($id);
            
            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting logistic document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus dokumen'
            ], 500);
        }
    }

    /**
     * Search documents
     */
    public function searchDocuments(Request $request)
    {
        try {
            $query = DocumentUpload::logisticDocuments();

            if ($request->filled('delivery_order')) {
                $query->where('delivery_order', 'like', '%' . $request->delivery_order . '%');
            }

            if ($request->filled('customer_name')) {
                $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('uploaded_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('uploaded_at', '<=', $request->date_to);
            }

            $documents = $query->orderBy('uploaded_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching logistic documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari dokumen'
            ], 500);
        }
    }

    /**
     * Get document statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year

            $stats = [
                'total' => DocumentUpload::logisticDocuments()->count(),
                'by_type' => DocumentUpload::logisticDocuments()
                    ->selectRaw('document_type, COUNT(*) as count')
                    ->groupBy('document_type')
                    ->pluck('count', 'document_type'),
                'by_customer' => DocumentUpload::logisticDocuments()
                    ->selectRaw('customer_name, COUNT(*) as count')
                    ->groupBy('customer_name')
                    ->orderByDesc('count')
                    ->take(10)
                    ->pluck('count', 'customer_name')
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting logistic statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }
}