<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OCRSpaceService
{
    private $apiKey = 'K87899142388957'; // FREE API Key dari OCR.space
    private $apiUrl = 'https://api.ocr.space/parse/image';
    
    /**
     * ✅ Extract text dari image/PDF menggunakan OCR.space API
     */
    public function extractText($filePath)
    {
        try {
            Log::info('🌐 OCR.space: Starting extraction', [
                'file' => $filePath,
                'size_mb' => round(filesize($filePath) / 1024 / 1024, 2)
            ]);
            
            // Check file exists
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }
            
            // Check file size (OCR.space free tier: max 1MB)
            $fileSizeMB = filesize($filePath) / 1024 / 1024;
            if ($fileSizeMB > 1) {
                Log::warning('⚠️ File too large for free tier', [
                    'size_mb' => round($fileSizeMB, 2),
                    'max_mb' => 1
                ]);
                // Compress atau resize jika perlu
            }
            
            // Prepare file for upload
            $fileContent = file_get_contents($filePath);
            $base64File = base64_encode($fileContent);
            
            // Call OCR.space API
            $response = Http::timeout(60)
                ->asForm()
                ->post($this->apiUrl, [
                    'apikey' => $this->apiKey,
                    'base64Image' => 'data:image/png;base64,' . $base64File,
                    'language' => 'eng',
                    'isOverlayRequired' => false,
                    'detectOrientation' => true,
                    'scale' => true,
                    'OCREngine' => 2, // Engine 2 lebih akurat untuk dokumen
                ]);
            
            if (!$response->successful()) {
                throw new \Exception('OCR API failed: ' . $response->status());
            }
            
            $result = $response->json();
            
            Log::info('🌐 OCR.space response', [
                'is_success' => $result['IsErroredOnProcessing'] ?? true,
                'error' => $result['ErrorMessage'] ?? null
            ]);
            
            // Check for errors
            if ($result['IsErroredOnProcessing'] ?? true) {
                throw new \Exception('OCR processing error: ' . ($result['ErrorMessage'][0] ?? 'Unknown error'));
            }
            
            // Extract text from results
            $extractedText = '';
            if (isset($result['ParsedResults']) && is_array($result['ParsedResults'])) {
                foreach ($result['ParsedResults'] as $parsed) {
                    $extractedText .= $parsed['ParsedText'] ?? '';
                }
            }
            
            Log::info('✅ OCR.space extraction complete', [
                'text_length' => strlen($extractedText),
                'has_text' => !empty($extractedText)
            ]);
            
            return [
                'success' => true,
                'text' => $extractedText,
                'confidence' => $result['ParsedResults'][0]['TextOverlay']['Lines'][0]['Words'][0]['WordText'] ?? 'N/A',
                'processing_time' => $result['ProcessingTimeInMilliseconds'] ?? 0
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ OCR.space extraction failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                'success' => false,
                'text' => '',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔍 Extract and validate Container Number
     */
    public function extractAndValidateContainerNumber($filePath, $expectedContainer)
    {
        try {
            // Step 1: Extract text using OCR
            $ocrResult = $this->extractText($filePath);
            
            if (!$ocrResult['success'] || empty($ocrResult['text'])) {
                return [
                    'validation_passed' => false,
                    'error' => 'OCR extraction failed',
                    'details' => $ocrResult
                ];
            }
            
            // Step 2: Find container numbers in text
            $foundContainers = $this->findContainerInText($ocrResult['text'], $expectedContainer);
            
            if (empty($foundContainers)) {
                return [
                    'validation_passed' => false,
                    'matched_container_number' => null,
                    'found_container_numbers' => [],
                    'error' => 'No container number found in document',
                    'extracted_text_preview' => substr($ocrResult['text'], 0, 500)
                ];
            }
            
            // Step 3: Validate match
            return $this->validateContainer($foundContainers, $expectedContainer);
            
        } catch (\Exception $e) {
            Log::error('Container validation failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'validation_passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 🔍 Find container numbers in text
     */
    private function findContainerInText($text, $expectedContainer)
    {
        $found = [];
        $text = strtoupper($text);
        
        // Pattern 1: Standard (4 letters + 7 digits)
        if (preg_match_all('/\b([A-Z]{4})\s*(\d{7})\b/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $found[] = $match[1] . $match[2];
            }
        }
        
        // Pattern 2: After keywords
        $keywords = ['CONTAINER NO', 'CONTAINER NUMBER', 'CNTR NO', 'CTR NO'];
        foreach ($keywords as $keyword) {
            $pattern = '/' . $keyword . '\.?\s*:?\s*([A-Z]{4})\s*(\d{7})/i';
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $found[] = strtoupper($match[1] . $match[2]);
                }
            }
        }
        
        // Pattern 3: Direct search
        $normalized = preg_replace('/[^A-Z0-9]/i', '', strtoupper($expectedContainer));
        $prefix = substr($normalized, 0, 4);
        $digits = substr($normalized, 4, 7);
        
        $variations = [
            $normalized,
            $prefix . ' ' . $digits,
            $prefix . '  ' . $digits
        ];
        
        foreach ($variations as $variation) {
            if (stripos($text, $variation) !== false) {
                $found[] = $normalized;
                break;
            }
        }
        
        return array_values(array_unique($found));
    }
    
    /**
     * ✅ Validate container match
     */
    private function validateContainer($foundNumbers, $expectedContainer)
    {
        $normalizedExpected = preg_replace('/[^A-Z0-9]/i', '', strtoupper($expectedContainer));
        
        foreach ($foundNumbers as $number) {
            $normalizedFound = preg_replace('/[^A-Z0-9]/i', '', strtoupper($number));
            
            // Exact match
            if ($normalizedFound === $normalizedExpected) {
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => 100,
                    'match_type' => 'exact'
                ];
            }
            
            // Contains match
            if (strpos($normalizedFound, $normalizedExpected) !== false ||
                strpos($normalizedExpected, $normalizedFound) !== false) {
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => 95,
                    'match_type' => 'contains'
                ];
            }
            
            // Similarity
            similar_text($normalizedFound, $normalizedExpected, $percent);
            if ($percent >= 85) {
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => round($percent),
                    'match_type' => 'similarity'
                ];
            }
        }
        
        // No match
        return [
            'validation_passed' => false,
            'matched_container_number' => $foundNumbers[0] ?? null,
            'found_container_numbers' => $foundNumbers,
            'confidence' => 0,
            'match_type' => 'none',
            'error' => 'Container number does not match'
        ];
    }
}