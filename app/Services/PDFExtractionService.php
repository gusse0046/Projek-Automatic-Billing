<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class PDFExtractionService
{
    /**
     * ✅ WINDOWS: Get Tesseract executable path (hardcoded)
     */
    private function getTesseractPath()
    {
        $tesseractPath = 'C:\Program Files\Tesseract-OCR\tesseract.exe';
        
        if (!file_exists($tesseractPath)) {
            Log::error('Tesseract not found', ['path' => $tesseractPath]);
            throw new \Exception('Tesseract OCR not found at: ' . $tesseractPath);
        }
        
        return $tesseractPath;
    }

    /**
     * ✅ Get GhostScript executable path
     */
   private function getGhostScriptPath()
{
    $gsPaths = [
        'C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe',  // ✅ NEW - Your version
        'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe',
        'C:\Program Files\gs\gs10.03.1\bin\gswin64c.exe',
        'C:\Program Files\gs\gs10.02.1\bin\gswin64c.exe',
        'C:\Program Files\gs\gs9.56.1\bin\gswin64c.exe',
        'C:\Program Files (x86)\gs\gs10.06.0\bin\gswin32c.exe',
        'C:\Program Files (x86)\gs\gs10.04.0\bin\gswin32c.exe',
    ];
    
    foreach ($gsPaths as $path) {
        if (file_exists($path)) {
            Log::info('✅ GhostScript found', ['path' => $path]);
            return $path;
        }
    }
    
    Log::warning('⚠️ GhostScript not found in common paths');
    throw new \Exception('GhostScript not found. Please install from: https://www.ghostscript.com/');
}

    /**
     * 🎯 MAIN: Extract and validate Container Number (WITH OCR PRIORITY)
     */
    public function extractAndValidateContainerNumber($filePath, $expectedContainerNumber)
    {
        $startTime = microtime(true);
        
        try {
            Log::info('=== 🚢 CONTAINER VALIDATION START (WITH OCR) ===', [
                'file_path' => $filePath,
                'expected_raw' => $expectedContainerNumber,
                'expected_normalized' => $this->normalizeContainer($expectedContainerNumber),
                'file_exists' => file_exists($filePath),
                'file_size_mb' => file_exists($filePath) ? round(filesize($filePath) / 1024 / 1024, 2) : 0,
                'file_extension' => pathinfo($filePath, PATHINFO_EXTENSION)
            ]);

            if (!file_exists($filePath)) {
                return $this->buildErrorResponse('File not found', 'file_not_found');
            }

            $normalizedExpected = $this->normalizeContainer($expectedContainerNumber);
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $allFoundContainers = [];

            // ========================================
            // 🔍 STRATEGY: Check file type
            // ========================================
            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'])) {
                Log::info('📸 IMAGE FILE DETECTED - Using OCR directly');
                
                // Direct OCR for images
                $ocrContainers = $this->extractWithOCR($filePath, $normalizedExpected);
                if (!empty($ocrContainers)) {
                    $allFoundContainers = array_merge($allFoundContainers, $ocrContainers);
                }
                
            } elseif ($fileExtension === 'pdf') {
                Log::info('📄 PDF FILE DETECTED - Checking if text-based or image-based');
                
                // ========================================
                // 🔍 STEP 1: Quick check if PDF is text-based or image-based
                // ========================================
                $isTextBased = $this->isTextBasedPDF($filePath);
                
                Log::info('PDF Type Detection', [
                    'is_text_based' => $isTextBased,
                    'strategy' => $isTextBased ? 'Parser First' : 'OCR First (PRIORITY)'
                ]);
                
                if (!$isTextBased) {
                    // ========================================
                    // 📸 IMAGE-BASED PDF: Use OCR directly (PRIORITY)
                    // ========================================
                    Log::info('🔍 IMAGE-BASED PDF DETECTED - Using OCR directly (PRIORITY)');
                    
                    $ocrContainers = $this->extractWithOCR($filePath, $normalizedExpected);
                    
                    if (!empty($ocrContainers)) {
                        Log::info('✅ OCR SUCCESS (Image PDF)!', [
                            'found' => $ocrContainers,
                            'time' => round(microtime(true) - $startTime, 2) . 's'
                        ]);
                        $allFoundContainers = array_merge($allFoundContainers, $ocrContainers);
                    } else {
                        Log::warning('⚠️ OCR failed for image-based PDF, trying other methods...');
                        
                        // Fallback: Try binary search
                        $binaryContainers = $this->superFastBinarySearch($filePath, $normalizedExpected);
                        if (!empty($binaryContainers)) {
                            $allFoundContainers = array_merge($allFoundContainers, $binaryContainers);
                        }
                    }
                    
                } else {
                    // ========================================
                    // 📝 TEXT-BASED PDF: Use Parser first
                    // ========================================
                    Log::info('🔍 TEXT-BASED PDF DETECTED - Using Parser first');
                    
                    // Layer 1: Binary Search
                    $binaryContainers = $this->superFastBinarySearch($filePath, $normalizedExpected);
                    if (!empty($binaryContainers)) {
                        Log::info('✅ LAYER 1 SUCCESS (Binary)!');
                        $allFoundContainers = array_merge($allFoundContainers, $binaryContainers);
                    }
                    
                    // Layer 2: PDF Parser
                    if (empty($allFoundContainers)) {
                        Log::info('🔍 LAYER 2: PDF Parser starting...');
                        $parserResult = $this->extractWithPdfParser($filePath);
                        
                        if ($parserResult['success'] && !empty($parserResult['text'])) {
                            $parserContainers = $this->findContainerInText($parserResult['text'], $normalizedExpected);
                            
                            if (!empty($parserContainers)) {
                                Log::info('✅ LAYER 2 SUCCESS (Parser)!');
                                $allFoundContainers = array_merge($allFoundContainers, $parserContainers);
                            }
                        }
                    }
                    
                    // Layer 3: OCR Fallback (if parser failed)
                    if (empty($allFoundContainers)) {
                        Log::info('🔍 LAYER 3: Parser failed, trying OCR as fallback...');
                        $ocrContainers = $this->extractWithOCR($filePath, $normalizedExpected);
                        
                        if (!empty($ocrContainers)) {
                            Log::info('✅ LAYER 3 SUCCESS (OCR Fallback)!');
                            $allFoundContainers = array_merge($allFoundContainers, $ocrContainers);
                        }
                    }
                    
                    // Layer 4: Aggressive Binary Scan
                    if (empty($allFoundContainers)) {
                        Log::info('🔍 LAYER 4: Aggressive binary scan...');
                        $aggressiveContainers = $this->aggressiveBinaryScan($filePath, $normalizedExpected);
                        
                        if (!empty($aggressiveContainers)) {
                            Log::info('✅ LAYER 4 SUCCESS (Aggressive)!');
                            $allFoundContainers = array_merge($allFoundContainers, $aggressiveContainers);
                        }
                    }
                }
            }

            // ========================================
            // ✅ CONSOLIDATE & VALIDATE
            // ========================================
            $uniqueContainers = array_values(array_unique($allFoundContainers));
            
            Log::info('📊 EXTRACTION COMPLETE', [
                'total_found' => count($uniqueContainers),
                'unique_containers' => $uniqueContainers,
                'total_time' => round(microtime(true) - $startTime, 2) . 's'
            ]);

            if (empty($uniqueContainers)) {
                Log::error('❌ ALL LAYERS FAILED - No container found!');
                
                return $this->buildErrorResponse(
                    'No Container Number found in document after exhaustive search',
                    'no_container_found',
                    [
                        'search_details' => [
                            'layers_tried' => $fileExtension === 'pdf' ? 4 : 1,
                            'expected_container' => $expectedContainerNumber,
                            'time_spent' => round(microtime(true) - $startTime, 2) . 's',
                            'file_type' => $fileExtension
                        ]
                    ]
                );
            }

            // ✅ VALIDATE
            return $this->validateContainer($uniqueContainers, $expectedContainerNumber);

        } catch (\Exception $e) {
            Log::error('❌ CRITICAL EXCEPTION', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->buildErrorResponse($e->getMessage(), 'exception');
        }
    }

    /**
     * 🔍 Check if PDF is text-based or image-based (scanned)
     */
    private function isTextBasedPDF($pdfPath)
    {
        try {
            Log::info('🔍 Checking PDF type (text-based vs image-based)...');
            
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pdfPath);
            
            // Try to extract text from first page
            $pages = $pdf->getPages();
            
            if (empty($pages)) {
                Log::info('❌ No pages found, assuming image-based');
                return false;
            }
            
            // Get first page
            $firstPage = reset($pages);
            $text = '';
            
            try {
                $text = $firstPage->getText();
            } catch (\Exception $e) {
                Log::warning('Failed to extract text from first page: ' . $e->getMessage());
            }
            
            // Clean text
            $cleanText = trim(preg_replace('/\s+/', ' ', $text));
            $textLength = strlen($cleanText);
            
            Log::info('PDF text extraction test', [
                'text_length' => $textLength,
                'text_preview' => substr($cleanText, 0, 200),
                'is_text_based' => $textLength > 50
            ]);
            
            // If extracted text > 50 chars, consider it text-based
            // If < 50 chars, it's likely image-based (scanned)
            return $textLength > 50;
            
        } catch (\Exception $e) {
            Log::error('Error checking PDF type: ' . $e->getMessage());
            
            // If parser fails completely, assume image-based
            return false;
        }
    }

    /**
     * 🔍 Extract text using Tesseract OCR
     */
    private function extractWithOCR($filePath, $normalizedExpected)
    {
        try {
            $tesseractPath = $this->getTesseractPath();
            
            Log::info('🤖 Starting Tesseract OCR', [
                'tesseract_path' => $tesseractPath,
                'file_path' => $filePath
            ]);

            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $imagePath = $filePath;

            // ✅ Convert PDF to image if needed
            if ($fileExtension === 'pdf') {
                Log::info('📄 Converting PDF to image for OCR...');
                $imagePath = $this->convertPdfToImage($filePath);
                
                if (!$imagePath || !file_exists($imagePath)) {
                    Log::warning('⚠️ PDF to image conversion failed');
                    return [];
                }
                
                Log::info('✅ PDF converted successfully', [
                    'image_path' => $imagePath,
                    'image_size_mb' => round(filesize($imagePath) / 1024 / 1024, 2)
                ]);
            }

            // ✅ Run Tesseract OCR
            Log::info('🤖 Running Tesseract OCR...');
            
            $ocr = new TesseractOCR($imagePath);
            $ocr->executable($tesseractPath);
            $ocr->psm(6); // Assume uniform block of text
            $ocr->oem(3); // Default OCR Engine Mode
            $ocr->lang('eng'); // English language
            
            $text = $ocr->run();
            
            Log::info('🤖 OCR extraction completed', [
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 500),
                'has_text' => !empty($text)
            ]);

            // ✅ Clean up temporary image
            if ($fileExtension === 'pdf' && $imagePath !== $filePath && file_exists($imagePath)) {
                unlink($imagePath);
                Log::info('🗑️ Temporary image deleted');
            }

            if (empty($text)) {
                Log::warning('⚠️ OCR returned empty text');
                return [];
            }

            // ✅ Find container numbers in OCR text
            return $this->findContainerInText($text, $normalizedExpected);

        } catch (\Exception $e) {
            Log::error('❌ OCR extraction failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }

    /**
     * 🔍 Convert PDF to image for OCR (GD + GhostScript)
     */
    private function convertPdfToImage($pdfPath)
    {
        try {
            Log::info('📸 Converting PDF to image for OCR...');
            
            // ========================================
            // METHOD 1: Using Imagick (if available)
            // ========================================
            if (extension_loaded('imagick')) {
                Log::info('✅ Using Imagick for PDF conversion');
                
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(300, 300);
                    $imagick->readImage($pdfPath . '[0]');
                    $imagick->setImageFormat('png');
                    $imagick->normalizeImage();
                    $imagick->enhanceImage();
                    $imagick->sharpenImage(0, 1);
                    
                    $tempImagePath = storage_path('app/temp/ocr_' . uniqid() . '.png');
                    $tempDir = dirname($tempImagePath);
                    if (!is_dir($tempDir)) {
                        mkdir($tempDir, 0755, true);
                    }
                    
                    $imagick->writeImage($tempImagePath);
                    $imagick->clear();
                    $imagick->destroy();
                    
                    Log::info('✅ PDF converted (Imagick)', [
                        'temp_image' => $tempImagePath,
                        'file_size_mb' => round(filesize($tempImagePath) / 1024 / 1024, 2)
                    ]);
                    
                    return $tempImagePath;
                    
                } catch (\Exception $imagickError) {
                    Log::error('Imagick failed: ' . $imagickError->getMessage());
                }
            }
            
            // ========================================
            // METHOD 2: Using GhostScript + GD (FALLBACK)
            // ========================================
            Log::info('⚠️ Imagick not available, using GhostScript + GD...');
            
            $tempImagePath = storage_path('app/temp/ocr_' . uniqid() . '.png');
            $tempDir = dirname($tempImagePath);
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // ✅ STEP 1: Get GhostScript path
            try {
                $gsPath = $this->getGhostScriptPath();
            } catch (\Exception $e) {
                Log::error('❌ GhostScript not found: ' . $e->getMessage());
                return null;
            }
            
            // ✅ STEP 2: Convert PDF to PNG using GhostScript
            $gsCommand = sprintf(
                '"%s" -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile="%s" "%s" 2>&1',
                $gsPath,
                $tempImagePath,
                $pdfPath
            );
            
            Log::info('🔧 Running GhostScript command', [
                'command' => $gsCommand
            ]);
            
            exec($gsCommand, $output, $returnCode);
            
            Log::info('GhostScript result', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
                'file_exists' => file_exists($tempImagePath),
                'file_size' => file_exists($tempImagePath) ? filesize($tempImagePath) : 0
            ]);
            
            if ($returnCode === 0 && file_exists($tempImagePath) && filesize($tempImagePath) > 0) {
                // ✅ STEP 3: Enhance image using GD
                try {
                    $image = imagecreatefrompng($tempImagePath);
                    
                    if ($image) {
                        // Enhance contrast
                        imagefilter($image, IMG_FILTER_CONTRAST, -30);
                        
                        // Sharpen
                        imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
                        
                        // Save enhanced image
                        imagepng($image, $tempImagePath);
                        imagedestroy($image);
                        
                        Log::info('✅ PDF converted and enhanced (GhostScript + GD)', [
                            'temp_image' => $tempImagePath,
                            'file_size_mb' => round(filesize($tempImagePath) / 1024 / 1024, 2)
                        ]);
                        
                        return $tempImagePath;
                    }
                } catch (\Exception $gdError) {
                    Log::warning('GD enhancement failed: ' . $gdError->getMessage());
                    
                    // Return unenhanced image if GD fails
                    if (file_exists($tempImagePath) && filesize($tempImagePath) > 0) {
                        return $tempImagePath;
                    }
                }
            } else {
                Log::error('❌ GhostScript conversion failed', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output)
                ]);
            }
            
            // ========================================
            // METHOD 3: Direct use PDF without conversion (last resort)
            // ========================================
            Log::warning('⚠️ All conversion methods failed, trying direct OCR on PDF...');
            
            // Some Tesseract versions can handle PDF directly (unlikely to work)
            return $pdfPath;
            
        } catch (\Exception $e) {
            Log::error('❌ PDF conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 🔍 LAYER 1: Super fast binary search
     */
    private function superFastBinarySearch($pdfPath, $normalizedExpected)
    {
        try {
            $content = file_get_contents($pdfPath);
            $found = [];
            
            $prefix = substr($normalizedExpected, 0, 4); // TLLU
            $digits = substr($normalizedExpected, 4);    // 4690343
            
            Log::info('🔍 Binary search starting', [
                'prefix' => $prefix,
                'digits' => $digits,
                'full_container' => $normalizedExpected,
                'file_size' => strlen($content)
            ]);
            
            // ✅ Search 1: Exact match (no space)
            if (strpos($content, $normalizedExpected) !== false) {
                Log::info('✅ Binary: Found exact match!');
                $found[] = $normalizedExpected;
                return $found;
            }
            
            // ✅ Search 2: With various separators
            $variations = [
                $prefix . ' ' . $digits,           // "TLLU 4690343"
                $prefix . '  ' . $digits,          // "TLLU  4690343"
                $prefix . "\n" . $digits,          // "TLLU\n4690343"
                $prefix . "\r\n" . $digits,        // "TLLU\r\n4690343"
                $prefix . "\t" . $digits,          // "TLLU\t4690343"
            ];
            
            foreach ($variations as $variation) {
                if (strpos($content, $variation) !== false) {
                    Log::info('✅ Binary: Found variation match!');
                    $found[] = $normalizedExpected;
                    return $found;
                }
            }
            
            return array_unique($found);
            
        } catch (\Exception $e) {
            Log::error('Binary search exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔍 LAYER 2: PDF Parser (Enhanced - All Pages)
     */
    private function extractWithPdfParser($pdfPath)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pdfPath);
            
            $allText = '';
            $pageCount = 0;
            $maxPages = 10; // ✅ Scan up to 10 pages
            
            Log::info('📄 PDF Parser: Starting multi-page extraction', [
                'total_pages' => count($pdf->getPages()),
                'max_pages_to_scan' => $maxPages
            ]);
            
            foreach ($pdf->getPages() as $pageNum => $page) {
                if ($pageCount >= $maxPages) {
                    Log::info('⚠️ Reached max page limit', ['pages_scanned' => $pageCount]);
                    break;
                }
                
                try {
                    $pageText = $page->getText();
                    
                    if (!empty($pageText)) {
                        $allText .= "\n=== PAGE " . ($pageCount + 1) . " ===\n";
                        $allText .= $pageText . "\n";
                        
                        Log::debug("Page " . ($pageCount + 1) . " extracted", [
                            'page_number' => $pageCount + 1,
                            'text_length' => strlen($pageText),
                            'preview' => substr($pageText, 0, 200)
                        ]);
                        
                        $pageCount++;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to extract page " . ($pageCount + 1) . ": " . $e->getMessage());
                    continue;
                }
            }
            
            Log::info('✅ PDF Parser completed', [
                'pages_extracted' => $pageCount,
                'total_text_length' => strlen($allText),
                'text_preview' => substr($allText, 0, 500)
            ]);
            
            return [
                'success' => !empty($allText),
                'text' => $allText,
                'pages' => $pageCount,
                'total_pages_in_pdf' => count($pdf->getPages())
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ PDF Parser exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                'success' => false,
                'text' => '',
                'pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 🔍 LAYER 4: Aggressive binary scan
     */
    private function aggressiveBinaryScan($pdfPath, $normalizedExpected)
    {
        try {
            $content = file_get_contents($pdfPath);
            $found = [];
            
            Log::info('Aggressive scan starting', [
                'file_size' => strlen($content)
            ]);
            
            // Convert to readable ASCII
            $readable = '';
            for ($i = 0; $i < strlen($content); $i++) {
                $ascii = ord($content[$i]);
                
                if (($ascii >= 48 && $ascii <= 57) ||   // 0-9
                    ($ascii >= 65 && $ascii <= 90) ||   // A-Z
                    $ascii == 32) {                     // Space
                    $readable .= $content[$i];
                } else {
                    $readable .= ' ';
                }
            }
            
            $readable = strtoupper($readable);
            
            // Search for container pattern
            if (preg_match_all('/\b([A-Z]{4})\s*(\d{7})\b/', $readable, $matches, PREG_SET_ORDER)) {
                Log::info('✅ Found patterns in readable text', [
                    'count' => count($matches)
                ]);
                
                foreach ($matches as $match) {
                    $container = $match[1] . $match[2];
                    $found[] = $container;
                }
            }
            
            // Direct search for normalized expected
            if (strpos($readable, $normalizedExpected) !== false) {
                Log::info('✅ Found normalized expected in readable');
                $found[] = $normalizedExpected;
            }
            
            return array_unique($found);
            
        } catch (\Exception $e) {
            Log::error('Aggressive scan exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 🔍 Find container in text (ENHANCED)
     */
    private function findContainerInText($text, $normalizedExpected)
    {
        $found = [];
        $text = strtoupper($text);
        
        Log::info('🔍 Searching for containers in extracted text', [
            'text_length' => strlen($text),
            'expected_container' => $normalizedExpected,
            'text_preview' => substr($text, 0, 500)
        ]);
        
        // ✅ PATTERN 1: Standard format (4 letters + 7 digits)
        if (preg_match_all('/\b([A-Z]{4})\s*(\d{7})\b/', $text, $matches, PREG_SET_ORDER)) {
            Log::info('✅ Pattern 1 matched', ['count' => count($matches)]);
            
            foreach ($matches as $match) {
                $container = $match[1] . $match[2];
                $found[] = $container;
                
                Log::debug('Found container (Pattern 1)', [
                    'container' => $container,
                    'prefix' => $match[1],
                    'digits' => $match[2]
                ]);
            }
        }
        
        // ✅ PATTERN 2: After "CONTAINER NO" keyword
        $keywords = [
            'CONTAINER NO',
            'CONTAINER NUMBER',
            'CONTAINER NO.',
            'CNTR NO',
            'CTR NO',
            'CONTAINER\s+NO\.?',
            'CONTAINER\s+NUMBER'
        ];
        
        foreach ($keywords as $keyword) {
            $pattern = '/' . $keyword . '\.?\s*:?\s*([A-Z]{4})\s*(\d{7})/i';
            
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                Log::info('✅ Pattern 2 matched (keyword)', [
                    'keyword' => $keyword,
                    'count' => count($matches)
                ]);
                
                foreach ($matches as $match) {
                    $container = strtoupper($match[1] . $match[2]);
                    $found[] = $container;
                    
                    Log::debug('Found container (Pattern 2)', [
                        'keyword' => $keyword,
                        'container' => $container
                    ]);
                }
            }
        }
        
        // ✅ PATTERN 3: In table format (container + type)
        if (preg_match_all('/([A-Z]{4}\d{7})\s+(40HC|20GP|40GP|20HC)/i', $text, $matches, PREG_SET_ORDER)) {
            Log::info('✅ Pattern 3 matched (table format)', ['count' => count($matches)]);
            
            foreach ($matches as $match) {
                $container = strtoupper($match[1]);
                $found[] = $container;
                
                Log::debug('Found container (Pattern 3 - table)', [
                    'container' => $container,
                    'type' => $match[2]
                ]);
            }
        }
        
        // ✅ PATTERN 4: Direct search for expected container
        $prefix = substr($normalizedExpected, 0, 4);
        $digits = substr($normalizedExpected, 4, 7);
        
        $searchVariations = [
            $normalizedExpected,
            $prefix . ' ' . $digits,
            $prefix . "\n" . $digits,
            $prefix . "\t" . $digits
        ];
        
        foreach ($searchVariations as $variation) {
            if (stripos($text, $variation) !== false) {
                Log::info('✅ Pattern 4 matched (direct search)', [
                    'variation' => $variation
                ]);
                $found[] = $normalizedExpected;
                break;
            }
        }
        
        $uniqueFound = array_values(array_unique($found));
        
        Log::info('📊 Container search completed', [
            'total_found' => count($uniqueFound),
            'unique_containers' => $uniqueFound
        ]);
        
        return $uniqueFound;
    }

 /**
     * ✅ Validate containers
     */
    private function validateContainer($foundNumbers, $expectedContainer)
    {
        if (empty($foundNumbers)) {
            return $this->buildErrorResponse('No container found', 'no_container_found');
        }

        $normalizedExpected = $this->normalizeContainer($expectedContainer);

        Log::info('🔍 VALIDATION', [
            'found_containers' => $foundNumbers,
            'expected_raw' => $expectedContainer,
            'expected_normalized' => $normalizedExpected
        ]);

        foreach ($foundNumbers as $number) {
            $normalizedFound = $this->normalizeContainer($number);

            Log::info('Comparing', [
                'found' => $normalizedFound,
                'expected' => $normalizedExpected,
                'exact_match' => $normalizedFound === $normalizedExpected
            ]);

            // Exact match
            if ($normalizedFound === $normalizedExpected) {
                Log::info('✅✅✅ VALIDATION PASSED - EXACT MATCH!');
                
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
                Log::info('✅ VALIDATION PASSED - CONTAINS MATCH');
                
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
                Log::info('✅ VALIDATION PASSED - SIMILARITY', ['percent' => $percent]);
                
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
        Log::error('❌ VALIDATION FAILED - No matching container', [
            'found' => $foundNumbers,
            'expected' => $expectedContainer
        ]);
        
        return [
            'validation_passed' => false,
            'matched_container_number' => $foundNumbers[0] ?? null,
            'found_container_numbers' => $foundNumbers,
            'confidence' => 0,
            'match_type' => 'none',
            'error' => 'Container number does not match'
        ];
    }

    /**
     * ✅ Normalize: Remove ALL non-alphanumeric
     */
    private function normalizeContainer($container)
    {
        $normalized = preg_replace('/[^A-Z0-9]/i', '', strtoupper(trim($container)));
        return $normalized;
    }

    /**
     * ✅ Build error response
     */
    private function buildErrorResponse($message, $errorType, $extraData = [])
    {
        return array_merge([
            'validation_passed' => false,
            'matched_container_number' => null,
            'found_container_numbers' => [],
            'confidence' => 0,
            'match_type' => 'none',
            'error_type' => $errorType,
            'error' => $message
        ], $extraData);
    }

    /**
     * 🔧 DEBUG: Test extraction
     */
    public function debugExtractText($filePath)
    {
        try {
            Log::info('=== DEBUG EXTRACTION START ===');
            
            $result = [
                'file_info' => [
                    'path' => $filePath,
                    'exists' => file_exists($filePath),
                    'size_mb' => file_exists($filePath) ? round(filesize($filePath) / 1024 / 1024, 2) : 0,
                    'readable' => is_readable($filePath),
                    'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
                ],
                'layers' => []
            ];
            
            // Test PDF type detection
            if (pathinfo($filePath, PATHINFO_EXTENSION) === 'pdf') {
                $result['pdf_type'] = [
                    'is_text_based' => $this->isTextBasedPDF($filePath),
                    'strategy' => $this->isTextBasedPDF($filePath) ? 'Parser First' : 'OCR First'
                ];
            }
            
            // Test each layer
            $result['layers']['binary'] = $this->superFastBinarySearch($filePath, 'TLLU4690343');
            $result['layers']['parser'] = $this->extractWithPdfParser($filePath);
            $result['layers']['ocr'] = $this->extractWithOCR($filePath, 'TLLU4690343');
            $result['layers']['aggressive'] = $this->aggressiveBinaryScan($filePath, 'TLLU4690343');
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
}