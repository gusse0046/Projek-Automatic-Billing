#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
EasyOCR Container Number Extractor
Extracts text from images using EasyOCR for Laravel BL validation
"""

import sys
import os

def check_dependencies():
    """Check if required packages are installed"""
    try:
        import easyocr
        import cv2
        import numpy
        return True
    except ImportError as e:
        print(f"ERROR: Missing dependency: {str(e)}", file=sys.stderr)
        print("Please install: pip install easyocr opencv-python numpy", file=sys.stderr)
        return False

def preprocess_image(image_path):
    """
    Preprocess image for better OCR results
    """
    try:
        import cv2
        
        # Read image
        img = cv2.imread(image_path)
        
        if img is None:
            print(f"ERROR: Could not read image: {image_path}", file=sys.stderr)
            return None
        
        print(f"✅ Image loaded: {image_path}", file=sys.stderr)
        print(f"   Size: {img.shape}", file=sys.stderr)
        
        # Convert to grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Apply denoising
        denoised = cv2.fastNlMeansDenoising(gray, None, 10, 7, 21)
        
        # Apply adaptive thresholding
        thresh = cv2.adaptiveThreshold(
            denoised, 255, 
            cv2.ADAPTIVE_THRESH_GAUSSIAN_C, 
            cv2.THRESH_BINARY, 
            11, 2
        )
        
        # Save preprocessed image for debugging
        debug_path = image_path.replace('.png', '_preprocessed.png')
        debug_path = debug_path.replace('.jpg', '_preprocessed.jpg')
        debug_path = debug_path.replace('.jpeg', '_preprocessed.jpeg')
        
        try:
            cv2.imwrite(debug_path, thresh)
            print(f"✅ Preprocessed image saved: {debug_path}", file=sys.stderr)
        except:
            print(f"⚠️ Could not save preprocessed image", file=sys.stderr)
        
        return thresh
        
    except Exception as e:
        print(f"ERROR preprocessing image: {str(e)}", file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        return None

def extract_text_easyocr(image_path):
    """
    Extract text from image using EasyOCR
    """
    try:
        import easyocr
        
        print("=" * 70, file=sys.stderr)
        print("🚀 EasyOCR Container Number Extractor", file=sys.stderr)
        print("=" * 70, file=sys.stderr)
        print(f"📖 Image path: {image_path}", file=sys.stderr)
        
        if not os.path.exists(image_path):
            print(f"ERROR: Image file not found: {image_path}", file=sys.stderr)
            sys.exit(1)
        
        file_size_mb = os.path.getsize(image_path) / (1024 * 1024)
        print(f"📊 File size: {file_size_mb:.2f} MB", file=sys.stderr)
        
        # ✅ Initialize EasyOCR reader
        print("🔧 Initializing EasyOCR reader (English)...", file=sys.stderr)
        print("⚠️ First run will download model (~80MB)...", file=sys.stderr)
        
        reader = easyocr.Reader(['en'], gpu=False, verbose=False)
        
        print("✅ EasyOCR reader initialized", file=sys.stderr)
        
        # ✅ Preprocess image
        preprocessed = preprocess_image(image_path)
        
        all_results = []
        
        # ✅ Extract from original image
        print("🔍 Reading text from ORIGINAL image...", file=sys.stderr)
        results_original = reader.readtext(image_path, detail=0, paragraph=True)
        print(f"   Found {len(results_original)} text blocks", file=sys.stderr)
        all_results.extend(results_original)
        
        # ✅ Extract from preprocessed image if available
        if preprocessed is not None:
            print("🔍 Reading text from PREPROCESSED image...", file=sys.stderr)
            results_preprocessed = reader.readtext(preprocessed, detail=0, paragraph=True)
            print(f"   Found {len(results_preprocessed)} text blocks", file=sys.stderr)
            all_results.extend(results_preprocessed)
        
        # ✅ Combine results
        extracted_text = ' '.join(all_results)
        extracted_text = extracted_text.strip()
        
        print("=" * 70, file=sys.stderr)
        print("✅ EasyOCR extraction completed", file=sys.stderr)
        print(f"📊 Total text length: {len(extracted_text)} characters", file=sys.stderr)
        print(f"📝 Preview: {extracted_text[:200]}...", file=sys.stderr)
        print("=" * 70, file=sys.stderr)
        
        # ✅ Output to stdout (Laravel will capture this)
        print(extracted_text)
        
        return extracted_text
        
    except Exception as e:
        print("=" * 70, file=sys.stderr)
        print(f"❌ CRITICAL ERROR: {str(e)}", file=sys.stderr)
        print("=" * 70, file=sys.stderr)
        import traceback
        traceback.print_exc(file=sys.stderr)
        sys.exit(1)

def main():
    """
    Main function
    """
    if len(sys.argv) < 2:
        print("Usage: python easyocr_extractor.py <image_path>", file=sys.stderr)
        print("Example: python easyocr_extractor.py document.png", file=sys.stderr)
        sys.exit(1)
    
    # Check dependencies
    if not check_dependencies():
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    extract_text_easyocr(image_path)

if __name__ == "__main__":
    main()