#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
PDF to Image Converter
Converts PDF first page to PNG image for OCR processing
Uses PyMuPDF (fitz) - NO POPPLER REQUIRED!
"""

import sys
import os

def check_dependencies():
    """Check if PyMuPDF is installed"""
    try:
        import fitz
        return True
    except ImportError:
        print("ERROR: PyMuPDF not installed", file=sys.stderr)
        print("Please install: pip install PyMuPDF", file=sys.stderr)
        return False

def convert_pdf_to_image(pdf_path, output_path):
    """
    Convert PDF first page to PNG image using PyMuPDF
    """
    try:
        import fitz  # PyMuPDF
        
        print("=" * 70, file=sys.stderr)
        print("📄 PDF to Image Converter (PyMuPDF)", file=sys.stderr)
        print("=" * 70, file=sys.stderr)
        print(f"📖 PDF path: {pdf_path}", file=sys.stderr)
        print(f"💾 Output path: {output_path}", file=sys.stderr)
        
        if not os.path.exists(pdf_path):
            print(f"ERROR: PDF file not found: {pdf_path}", file=sys.stderr)
            sys.exit(1)
        
        pdf_size_mb = os.path.getsize(pdf_path) / (1024 * 1024)
        print(f"📊 PDF size: {pdf_size_mb:.2f} MB", file=sys.stderr)
        
        # ✅ Open PDF
        print("🔓 Opening PDF document...", file=sys.stderr)
        pdf_document = fitz.open(pdf_path)
        
        total_pages = len(pdf_document)
        print(f"📄 Total pages: {total_pages}", file=sys.stderr)
        
        if total_pages == 0:
            print("ERROR: PDF has no pages", file=sys.stderr)
            sys.exit(1)
        
        # ✅ Get first page
        print("📄 Extracting first page...", file=sys.stderr)
        page = pdf_document[0]
        
        # ✅ Render page to image (300 DPI for good quality)
        print("🎨 Rendering page to image (300 DPI)...", file=sys.stderr)
        zoom = 300 / 72  # 300 DPI (72 is default DPI)
        mat = fitz.Matrix(zoom, zoom)
        pix = page.get_pixmap(matrix=mat)
        
        print(f"📐 Image dimensions: {pix.width} x {pix.height}", file=sys.stderr)
        
        # ✅ Save as PNG
        print("💾 Saving image...", file=sys.stderr)
        pix.save(output_path)
        
        # ✅ Close PDF
        pdf_document.close()
        
        if os.path.exists(output_path):
            output_size_mb = os.path.getsize(output_path) / (1024 * 1024)
            print("=" * 70, file=sys.stderr)
            print("✅ PDF converted successfully!", file=sys.stderr)
            print(f"💾 Output file: {output_path}", file=sys.stderr)
            print(f"📊 Output size: {output_size_mb:.2f} MB", file=sys.stderr)
            print("=" * 70, file=sys.stderr)
        else:
            print("ERROR: Output file was not created", file=sys.stderr)
            sys.exit(1)
        
        return output_path
        
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
    if len(sys.argv) < 3:
        print("Usage: python pdf_to_image.py <pdf_path> <output_path>", file=sys.stderr)
        print("Example: python pdf_to_image.py document.pdf output.png", file=sys.stderr)
        sys.exit(1)
    
    # Check dependencies
    if not check_dependencies():
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    output_path = sys.argv[2]
    
    convert_pdf_to_image(pdf_path, output_path)

if __name__ == "__main__":
    main()