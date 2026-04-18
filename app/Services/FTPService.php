<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentUpload;
use Exception;

class FTPService
{
    private $ftpConfig;
    private $connection;
    private $isConnected = false;

    public function __construct()
    {
        $this->ftpConfig = [
            'host' => config('services.ftp.host', '192.168.253.2'),
            'username' => config('services.ftp.username', 'ftpuser'),
            'password' => config('services.ftp.password', '101010'),
            'port' => config('services.ftp.port', 21),
            'timeout' => config('services.ftp.timeout', 30),
            'passive' => config('services.ftp.passive', true),
            'folder' => config('services.ftp.folder', 'billing')
        ];
    }

    /**
     * Connect to FTP server
     */
    public function connect()
    {
        try {
            Log::info('=== FTP CONNECTION ATTEMPT ===', [
                'host' => $this->ftpConfig['host'],
                'port' => $this->ftpConfig['port'],
                'username' => $this->ftpConfig['username']
            ]);

            $this->connection = ftp_connect($this->ftpConfig['host'], $this->ftpConfig['port'], $this->ftpConfig['timeout']);
            
            if (!$this->connection) {
                throw new Exception('Could not connect to FTP server: ' . $this->ftpConfig['host']);
            }

            $login = ftp_login($this->connection, $this->ftpConfig['username'], $this->ftpConfig['password']);
            
            if (!$login) {
                throw new Exception('FTP login failed for user: ' . $this->ftpConfig['username']);
            }

            // Set passive mode
            if ($this->ftpConfig['passive']) {
                ftp_pasv($this->connection, true);
            }

            $this->isConnected = true;

            Log::info('✅ FTP connected successfully', [
                'host' => $this->ftpConfig['host'],
                'passive_mode' => $this->ftpConfig['passive']
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('❌ FTP connection failed: ' . $e->getMessage());
            $this->isConnected = false;
            throw $e;
        }
    }

    /**
     * Disconnect from FTP server
     */
    public function disconnect()
    {
        if ($this->connection && $this->isConnected) {
            ftp_close($this->connection);
            $this->isConnected = false;
            Log::info('FTP disconnected');
        }
    }

    /**
     * Test FTP connection
     */
    public function testConnection()
    {
        try {
            $this->connect();
            
            // Test basic operations
            $currentDir = ftp_pwd($this->connection);
            $files = ftp_nlist($this->connection, '.');
            
            $this->disconnect();
            
            return [
                'success' => true,
                'message' => 'FTP connection test successful',
                'current_directory' => $currentDir,
                'files_count' => count($files) ?? 0,
                'test_timestamp' => now()->toDateTimeString()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'FTP connection test failed: ' . $e->getMessage(),
                'error_type' => 'connection_error',
                'test_timestamp' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * List files in FTP directory
     */
    public function listFiles($directory = null, $deliveryOrder = null)
    {
        try {
            if (!$this->isConnected) {
                $this->connect();
            }

            $targetDir = $directory ?? $this->ftpConfig['folder'];
            
            // Change to target directory
            if (!ftp_chdir($this->connection, $targetDir)) {
                throw new Exception("Could not change to directory: {$targetDir}");
            }

            // Get file list
            $files = ftp_nlist($this->connection, '.');
            
            if ($files === false) {
                throw new Exception('Could not list files in directory');
            }

            $fileDetails = [];
            foreach ($files as $file) {
                // Skip directories
                if ($file === '.' || $file === '..') {
                    continue;
                }

                // Filter by delivery order if specified
                if ($deliveryOrder && strpos($file, $deliveryOrder) === false) {
                    continue;
                }

                $fileInfo = $this->getFileInfo($file);
                if ($fileInfo) {
                    $fileDetails[] = $fileInfo;
                }
            }

            Log::info('FTP files listed successfully', [
                'directory' => $targetDir,
                'total_files' => count($files),
                'filtered_files' => count($fileDetails),
                'delivery_filter' => $deliveryOrder
            ]);

            return [
                'success' => true,
                'files' => $fileDetails,
                'directory' => $targetDir,
                'total_count' => count($fileDetails)
            ];

        } catch (Exception $e) {
            Log::error('FTP list files failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to list FTP files: ' . $e->getMessage(),
                'files' => []
            ];
        }
    }

    /**
     * Get detailed file information
     */
    private function getFileInfo($filename)
    {
        try {
            $size = ftp_size($this->connection, $filename);
            $modified = ftp_mdtm($this->connection, $filename);
            
            return [
                'filename' => $filename,
                'size' => $size > 0 ? $size : 0,
                'size_formatted' => $this->formatFileSize($size),
                'modified' => $modified > 0 ? date('Y-m-d H:i:s', $modified) : null,
                'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                'document_type' => $this->detectDocumentType($filename),
                'delivery_order' => $this->extractDeliveryOrder($filename)
            ];
        } catch (Exception $e) {
            return [
                'filename' => $filename,
                'size' => 0,
                'size_formatted' => 'Unknown',
                'modified' => null,
                'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                'document_type' => null,
                'delivery_order' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download file from FTP to local storage
     */
    public function downloadFile($ftpFilename, $localPath = null)
    {
        try {
            if (!$this->isConnected) {
                $this->connect();
            }

            // Generate local path if not provided
            if (!$localPath) {
                $localPath = 'ftp_downloads/' . date('Y/m/d/') . $ftpFilename;
            }

            $fullLocalPath = storage_path('app/public/' . $localPath);
            
            // Create directory if not exists
            $dir = dirname($fullLocalPath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            // Download file
            $downloadSuccess = ftp_get($this->connection, $fullLocalPath, $ftpFilename, FTP_BINARY);
            
            if (!$downloadSuccess) {
                throw new Exception("Failed to download file: {$ftpFilename}");
            }

            // Verify file was downloaded
            if (!File::exists($fullLocalPath) || File::size($fullLocalPath) === 0) {
                throw new Exception("Downloaded file is empty or corrupted: {$ftpFilename}");
            }

            Log::info('FTP file downloaded successfully', [
                'ftp_filename' => $ftpFilename,
                'local_path' => $localPath,
                'file_size' => File::size($fullLocalPath)
            ]);

            return [
                'success' => true,
                'ftp_filename' => $ftpFilename,
                'local_path' => $localPath,
                'full_path' => $fullLocalPath,
                'file_size' => File::size($fullLocalPath),
                'downloaded_at' => now()->toDateTimeString()
            ];

        } catch (Exception $e) {
            Log::error('FTP download failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
                'ftp_filename' => $ftpFilename
            ];
        }
    }

    /**
     * Auto-fetch documents for specific delivery order
     */
    public function autoFetchDocuments($deliveryOrder, $customerName)
    {
        try {
            Log::info('=== AUTO-FETCH DOCUMENTS FROM FTP ===', [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName
            ]);

            if (!$this->isConnected) {
                $this->connect();
            }

            // List files for this delivery order
            $filesResult = $this->listFiles($this->ftpConfig['folder'], $deliveryOrder);
            
            if (!$filesResult['success']) {
                throw new Exception('Failed to list FTP files: ' . $filesResult['message']);
            }

            $files = $filesResult['files'];
            $fetchedDocuments = [];
            $errors = [];

            foreach ($files as $fileInfo) {
                try {
                    // Download file
                    $downloadResult = $this->downloadFile($fileInfo['filename']);
                    
                    if ($downloadResult['success']) {
                        // Create document upload record
                        $uploadResult = $this->createDocumentUploadRecord(
                            $deliveryOrder,
                            $customerName,
                            $fileInfo,
                            $downloadResult
                        );
                        
                        if ($uploadResult['success']) {
                            $fetchedDocuments[] = $uploadResult['upload'];
                        } else {
                            $errors[] = [
                                'filename' => $fileInfo['filename'],
                                'error' => $uploadResult['message']
                            ];
                        }
                    } else {
                        $errors[] = [
                            'filename' => $fileInfo['filename'],
                            'error' => $downloadResult['message']
                        ];
                    }
                } catch (Exception $fileError) {
                    $errors[] = [
                        'filename' => $fileInfo['filename'],
                        'error' => $fileError->getMessage()
                    ];
                }
            }

            Log::info('Auto-fetch completed', [
                'delivery_order' => $deliveryOrder,
                'total_files' => count($files),
                'successful_fetch' => count($fetchedDocuments),
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'message' => "Auto-fetch completed. {count($fetchedDocuments)} documents fetched successfully.",
                'fetched_documents' => $fetchedDocuments,
                'errors' => $errors,
                'summary' => [
                    'total_files' => count($files),
                    'successful_fetch' => count($fetchedDocuments),
                    'failed_fetch' => count($errors)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Auto-fetch failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Auto-fetch failed: ' . $e->getMessage(),
                'fetched_documents' => [],
                'errors' => []
            ];
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Create document upload record from FTP file
     */
    private function createDocumentUploadRecord($deliveryOrder, $customerName, $fileInfo, $downloadResult)
    {
        try {
            $documentType = $fileInfo['document_type'] ?? $this->detectDocumentType($fileInfo['filename']);
            
            if (!$documentType) {
                return [
                    'success' => false,
                    'message' => 'Could not determine document type from filename'
                ];
            }

            $uploadData = [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'document_type' => $documentType,
                'file_name' => $fileInfo['filename'],
                'file_path' => $downloadResult['local_path'],
                'file_type' => $fileInfo['extension'],
                'file_size' => $downloadResult['file_size'],
                'uploaded_at' => now(),
                'source' => 'ftp_auto',
                'notes' => 'Auto-fetched from FTP server',
                'uploaded_by' => 'FTP Auto-Fetch System',
                'team' => $this->determineTeamFromDocumentType($documentType)
            ];

            $upload = DocumentUpload::create($uploadData);

            return [
                'success' => true,
                'upload' => $upload,
                'message' => 'Document upload record created successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create upload record: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Detect document type from filename
     */
    private function detectDocumentType($filename)
    {
        $patterns = [
            'INVOICE' => '/invoice|inv[_-]?/i',
            'PACKING_LIST' => '/packing[_-]?list|pl[_-]?|packlist/i',
            'BL' => '/bl[_-]?|bill[_-]?of[_-]?lading/i',
            'COO' => '/coo[_-]?|certificate[_-]?origin/i',
            'FUMIGASI' => '/fumigation|fumigasi/i',
            'PEB' => '/peb[_-]?/i',
            'AWB' => '/awb[_-]?|airway[_-]?bill/i',
            'PYTOSANITARY' => '/phyto|pytosanitary/i',
            'LACEY_ACT' => '/lacey/i',
            'ISF' => '/isf[_-]?/i',
            'TSCA' => '/tsca[_-]?/i',
            'GCC' => '/gcc[_-]?/i',
            'PPDF' => '/ppdf[_-]?/i',
            'VLEGAL' => '/vlegal|v[_-]?legal/i',
            'PAYMENT_INSTRUCTION' => '/payment|instruction|pi[_-]?/i',
            'CONTAINER_LOAD_PLAN' => '/container|load[_-]?plan|clp[_-]?/i'
        ];

        foreach ($patterns as $docType => $pattern) {
            if (preg_match($pattern, $filename)) {
                return $docType;
            }
        }

        return null;
    }

    /**
     * Extract delivery order from filename
     */
    private function extractDeliveryOrder($filename)
    {
        // Look for patterns like 2010004609 (delivery order format)
        if (preg_match('/(\d{10})/', $filename, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Determine team from document type
     */
    private function determineTeamFromDocumentType($documentType)
    {
        $financeDocuments = ['INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION', 'CONTAINER_LOAD_PLAN'];
        $logisticDocuments = ['BL', 'AWB', 'CONTAINER_LOAD_PLAN', 'SHIPPING_INSTRUCTION', 'FREIGHT_INVOICE'];
        
        if (in_array($documentType, $financeDocuments)) {
            return 'Finance';
        } elseif (in_array($documentType, $logisticDocuments)) {
            return 'Logistic';
        } else {
            return 'Exim';
        }
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes <= 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Upload file to FTP server
     */
    public function uploadFile($localPath, $ftpFilename)
    {
        try {
            if (!$this->isConnected) {
                $this->connect();
            }

            if (!File::exists($localPath)) {
                throw new Exception("Local file does not exist: {$localPath}");
            }

            // Change to target directory
            if (!ftp_chdir($this->connection, $this->ftpConfig['folder'])) {
                throw new Exception("Could not change to upload directory: {$this->ftpConfig['folder']}");
            }

            $uploadSuccess = ftp_put($this->connection, $ftpFilename, $localPath, FTP_BINARY);
            
            if (!$uploadSuccess) {
                throw new Exception("Failed to upload file: {$ftpFilename}");
            }

            Log::info('File uploaded to FTP successfully', [
                'local_path' => $localPath,
                'ftp_filename' => $ftpFilename,
                'file_size' => File::size($localPath)
            ]);

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'ftp_filename' => $ftpFilename,
                'file_size' => File::size($localPath)
            ];

        } catch (Exception $e) {
            Log::error('FTP upload failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete file from FTP server
     */
    public function deleteFile($ftpFilename)
    {
        try {
            if (!$this->isConnected) {
                $this->connect();
            }

            // Change to target directory
            if (!ftp_chdir($this->connection, $this->ftpConfig['folder'])) {
                throw new Exception("Could not change to directory: {$this->ftpConfig['folder']}");
            }

            $deleteSuccess = ftp_delete($this->connection, $ftpFilename);
            
            if (!$deleteSuccess) {
                throw new Exception("Failed to delete file: {$ftpFilename}");
            }

            Log::info('File deleted from FTP successfully', [
                'ftp_filename' => $ftpFilename
            ]);

            return [
                'success' => true,
                'message' => 'File deleted successfully',
                'ftp_filename' => $ftpFilename
            ];

        } catch (Exception $e) {
            Log::error('FTP delete failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get FTP server info
     */
    public function getServerInfo()
    {
        try {
            if (!$this->isConnected) {
                $this->connect();
            }

            $systemType = ftp_systype($this->connection);
            $currentDir = ftp_pwd($this->connection);
            
            return [
                'success' => true,
                'system_type' => $systemType,
                'current_directory' => $currentDir,
                'host' => $this->ftpConfig['host'],
                'port' => $this->ftpConfig['port'],
                'passive_mode' => $this->ftpConfig['passive'],
                'connected' => $this->isConnected
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get server info: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cleanup - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}