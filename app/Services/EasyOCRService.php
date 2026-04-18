<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class EasyOCRService
{
    private $maxRetries = 2;
    private $timeout = 120;
    
    private function getPythonPath()
    {
        try {
            return Cache::remember('python_path', 3600, function() {
                $pythonPaths = [
                    'C:\\Python312\\python.exe',
                    'C:\\Python311\\python.exe',
                    'C:\\Python310\\python.exe',
                    'C:\\Python39\\python.exe',
                    'python',
                ];

                foreach ($pythonPaths as $path) {
                    if ($path === 'python') {
                        try {
                            $result = @shell_exec('python --version 2>&1');
                            if ($result && stripos($result, 'Python') !== false) {
                                Log::info('Python found in PATH');
                                return 'python';
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    } elseif (file_exists($path)) {
                        Log::info('Python found', ['path' => $path]);
                        return $path;
                    }
                }
                
                throw new \Exception('Python 3.8+ not found. Please install Python.');
            });
        } catch (\Exception $e) {
            Log::error('Python path error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getOCRScriptPath()
    {
        $scriptPath = base_path('scripts/easyocr_extractor.py');
        
        if (!file_exists($scriptPath)) {
            throw new \Exception("EasyOCR script not found: {$scriptPath}");
        }
        
        return $scriptPath;
    }

    private function getPdfConversionScriptPath()
    {
        $scriptPath = base_path('scripts/pdf_to_image.py');
        
        if (!file_exists($scriptPath)) {
            throw new \Exception("PDF conversion script not found: {$scriptPath}");
        }
        
        return $scriptPath;
    }

    public function extractAndValidateContainerNumber($filePath, $expectedContainerNumber)
    {
        $startTime = microtime(true);
        
        try {
            Log::info('=== CONTAINER VALIDATION START (EasyOCR) ===', [
                'file_path' => basename($filePath),
                'expected' => $expectedContainerNumber,
                'file_exists' => file_exists($filePath),
                'file_size_mb' => file_exists($filePath) ? round(filesize($filePath) / 1024 / 1024, 2) : 0,
            ]);

            if (!file_exists($filePath)) {
                return $this->buildErrorResponse('File not found', 'file_not_found');
            }

            if (!is_readable($filePath)) {
                return $this->buildErrorResponse('File is not readable', 'file_not_readable');
            }

            $normalizedExpected = $this->normalizeContainer($expectedContainerNumber);
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            $imagePath = $filePath;
            $needsCleanup = false;
            
            if ($fileExtension === 'pdf') {
                Log::info('PDF detected - converting to image...');
                
                try {
                    $imagePath = $this->convertPdfToImage($filePath);
                    
                    if (!$imagePath || !file_exists($imagePath)) {
                        return $this->buildErrorResponse(
                            'Failed to convert PDF to image',
                            'conversion_error'
                        );
                    }
                    
                    $needsCleanup = true;
                    Log::info('PDF converted', ['image' => basename($imagePath)]);
                    
                } catch (\Exception $conversionError) {
                    return $this->buildErrorResponse(
                        'PDF conversion failed: ' . $conversionError->getMessage(),
                        'conversion_exception'
                    );
                }
            }

            try {
                $extractedText = $this->runEasyOCR($imagePath);
            } catch (\Exception $ocrError) {
                if ($needsCleanup && file_exists($imagePath)) {
                    @unlink($imagePath);
                }
                
                return $this->buildErrorResponse(
                    'OCR processing failed: ' . $ocrError->getMessage(),
                    'ocr_error'
                );
            }
            
            if ($needsCleanup && file_exists($imagePath)) {
                @unlink($imagePath);
                Log::info('Temp file deleted');
            }

            if (empty($extractedText)) {
                return $this->buildErrorResponse(
                    'No text extracted from document',
                    'no_text_found'
                );
            }

            Log::info('OCR completed', [
                'text_length' => strlen($extractedText)
            ]);

            $foundContainers = $this->findContainerInText($extractedText, $normalizedExpected);
            
            if (empty($foundContainers)) {
                return $this->buildErrorResponse(
                    'No Container Number found in BL document',
                    'no_container_found',
                    [
                        'expected' => $expectedContainerNumber,
                        'text_preview' => substr($extractedText, 0, 500),
                        'extraction_method' => 'EasyOCR'
                    ]
                );
            }

            $validationResult = $this->validateContainer($foundContainers, $expectedContainerNumber);
            $validationResult['processing_time'] = round(microtime(true) - $startTime, 2) . 's';
            
            return $validationResult;

        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return $this->buildErrorResponse(
                'Validation system error: ' . $e->getMessage(),
                'exception'
            );
        }
    }

    private function runEasyOCR($imagePath)
    {
        try {
            $pythonPath = $this->getPythonPath();
            $scriptPath = $this->getOCRScriptPath();
            
            Log::info('Executing EasyOCR', [
                'python' => basename($pythonPath),
                'image' => basename($imagePath)
            ]);
            
            $command = sprintf(
                '"%s" "%s" "%s" 2>&1',
                $pythonPath,
                $scriptPath,
                $imagePath
            );
            
            $process = Process::timeout($this->timeout)->run($command);
            
            if (!$process->successful()) {
                $error = $process->errorOutput() ?: $process->output();
                Log::error('EasyOCR failed', ['error' => $error]);
                throw new \Exception("EasyOCR failed: {$error}");
            }
            
            $output = $process->output();
            Log::info('EasyOCR completed', ['output_length' => strlen($output)]);
            
            return trim($output);
            
        } catch (ProcessTimedOutException $e) {
            throw new \Exception("OCR timed out after {$this->timeout} seconds");
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function convertPdfToImage($pdfPath)
    {
        try {
            $tempImagePath = storage_path('app/temp/ocr_' . uniqid() . '.png');
            $tempDir = dirname($tempImagePath);
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->setResolution(300, 300);
                    $imagick->readImage($pdfPath . '[0]');
                    $imagick->setImageFormat('png');
                    $imagick->writeImage($tempImagePath);
                    $imagick->clear();
                    $imagick->destroy();
                    
                    if (file_exists($tempImagePath)) {
                        return $tempImagePath;
                    }
                } catch (\Exception $e) {
                    Log::warning('Imagick failed: ' . $e->getMessage());
                }
            }
            
            $pythonPath = $this->getPythonPath();
            $convertScript = $this->getPdfConversionScriptPath();
            
            $command = sprintf(
                '"%s" "%s" "%s" "%s" 2>&1',
                $pythonPath,
                $convertScript,
                $pdfPath,
                $tempImagePath
            );
            
            $process = Process::timeout(60)->run($command);
            
            if ($process->successful() && file_exists($tempImagePath)) {
                return $tempImagePath;
            }
            
            throw new \Exception('PDF conversion failed');
            
        } catch (\Exception $e) {
            return null;
        }
    }

    private function findContainerInText($text, $normalizedExpected)
    {
        $found = [];
        $text = strtoupper($text);
        
        Log::info('Searching containers', [
            'text_length' => strlen($text),
            'expected' => $normalizedExpected
        ]);
        
        if (preg_match_all('/\b([A-Z]{4})\s*[-\s]?\s*(\d{7})\b/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $container = $match[1] . $match[2];
                $found[] = $container;
                Log::debug('Found (Standard)', ['container' => $container]);
            }
        }
        
        $keywords = [
            'CONTAINER\s+NO',
            'CONTAINER\s+NUMBER',
            'CNTR\s+NO',
            'CTR\s+NO',
        ];
        
        foreach ($keywords as $keyword) {
            $pattern = '/' . $keyword . '\.?\s*[:=#-\s]*([A-Z]{4})\s*[-\s]?\s*(\d{7})/i';
            
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $container = strtoupper($match[1] . $match[2]);
                    $found[] = $container;
                    Log::debug('Found (Keyword)', ['container' => $container]);
                }
            }
        }
        
        $prefix = substr($normalizedExpected, 0, 4);
        $digits = substr($normalizedExpected, 4, 7);
        
        $variations = [
            $normalizedExpected,
            $prefix . ' ' . $digits,
            $prefix . '  ' . $digits,
            $prefix . '-' . $digits,
            $prefix . "\n" . $digits,
        ];
        
        foreach ($variations as $variation) {
            if (stripos($text, $variation) !== false) {
                $found[] = $normalizedExpected;
                Log::info('Found (Direct)', ['variation' => str_replace(["\n", "\r"], ['\\n', '\\r'], $variation)]);
                break;
            }
        }
        
        if (empty($found)) {
            $prefixPos = stripos($text, $prefix);
            if ($prefixPos !== false) {
                $nearbyText = substr($text, $prefixPos, 30);
                if (stripos($nearbyText, $digits) !== false) {
                    $found[] = $normalizedExpected;
                    Log::info('Found (Fuzzy)', ['nearby' => $nearbyText]);
                }
            }
        }
        
        $unique = array_values(array_unique($found));
        
        Log::info('Search complete', [
            'found_count' => count($unique),
            'containers' => $unique
        ]);
        
        return $unique;
    }

    private function validateContainer($foundNumbers, $expectedContainer)
    {
        if (empty($foundNumbers)) {
            return $this->buildErrorResponse('No container found', 'no_container_found');
        }

        $normalizedExpected = $this->normalizeContainer($expectedContainer);

        foreach ($foundNumbers as $number) {
            $normalizedFound = $this->normalizeContainer($number);

            if ($normalizedFound === $normalizedExpected) {
                Log::info('VALIDATION PASSED - EXACT');
                
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => 100,
                    'match_type' => 'exact',
                    'extraction_method' => 'EasyOCR'
                ];
            }

            if (strpos($normalizedFound, $normalizedExpected) !== false ||
                strpos($normalizedExpected, $normalizedFound) !== false) {
                Log::info('VALIDATION PASSED - CONTAINS');
                
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => 95,
                    'match_type' => 'contains',
                    'extraction_method' => 'EasyOCR'
                ];
            }

            similar_text($normalizedFound, $normalizedExpected, $percent);
            if ($percent >= 85) {
                Log::info('VALIDATION PASSED - SIMILAR', ['percent' => $percent]);
                
                return [
                    'validation_passed' => true,
                    'matched_container_number' => $number,
                    'found_container_numbers' => $foundNumbers,
                    'confidence' => round($percent),
                    'match_type' => 'similarity',
                    'extraction_method' => 'EasyOCR'
                ];
            }
        }

        Log::error('VALIDATION FAILED', [
            'found' => $foundNumbers,
            'expected' => $expectedContainer
        ]);
        
        return [
            'validation_passed' => false,
            'matched_container_number' => $foundNumbers[0] ?? null,
            'found_container_numbers' => $foundNumbers,
            'confidence' => 0,
            'match_type' => 'none',
            'error' => 'Container number mismatch',
            'extraction_method' => 'EasyOCR'
        ];
    }

    private function normalizeContainer($container)
    {
        return preg_replace('/[^A-Z0-9]/i', '', strtoupper(trim($container)));
    }

    private function buildErrorResponse($message, $errorType, $extraData = [])
    {
        return array_merge([
            'validation_passed' => false,
            'matched_container_number' => null,
            'found_container_numbers' => [],
            'confidence' => 0,
            'match_type' => 'none',
            'error_type' => $errorType,
            'error' => $message,
            'extraction_method' => 'EasyOCR'
        ], $extraData);
    }

    public function debugExtractText($filePath)
    {
        try {
            $result = [
                'file_info' => [
                    'path' => basename($filePath),
                    'exists' => file_exists($filePath),
                    'size_mb' => file_exists($filePath) ? round(filesize($filePath) / 1024 / 1024, 2) : 0,
                ]
            ];
            
            $imagePath = $filePath;
            if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf') {
                $imagePath = $this->convertPdfToImage($filePath);
            }
            
            if (!$imagePath || !file_exists($imagePath)) {
                return ['success' => false, 'error' => 'Image conversion failed'];
            }
            
            $extractedText = $this->runEasyOCR($imagePath);
            
            $result['extracted_text'] = [
                'length' => strlen($extractedText),
                'preview' => substr($extractedText, 0, 1000),
                'full_text' => $extractedText
            ];
            
            if ($imagePath !== $filePath && file_exists($imagePath)) {
                @unlink($imagePath);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
