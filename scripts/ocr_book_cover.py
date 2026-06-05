#!/usr/bin/env python3
"""
OCR Book Cover Extractor
Usage: python ocr_book_cover.py <image_path1> [<image_path2> ...]

Reads book cover images via Tesseract OCR, extracts:
- ISBN (from barcode area, bottom of back cover)
- Title (largest text on front cover)
- Author
- Publisher
- Category hint

Output: JSON with extracted fields + raw text blocks.
"""
import json
import re
import sys
import os

try:
    from PIL import Image
    import pytesseract
except ImportError as e:
    print(json.dumps({"error": f"Python module missing: {e}. Run: pip install pytesseract pillow"}))
    sys.exit(0)

# ── Config ──
import platform

# Auto-detect Tesseract path on Windows
if platform.system() == 'Windows':
    TESSERACT_PATH = os.environ.get("TESSERACT_PATH", r"C:\Program Files\Tesseract-OCR\tesseract.exe")
    if not os.path.exists(TESSERACT_PATH):
        # Fallback: try common locations
        for candidate in [
            r"C:\Program Files\Tesseract-OCR\tesseract.exe",
            r"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe",
        ]:
            if os.path.exists(candidate):
                TESSERACT_PATH = candidate
                break
else:
    TESSERACT_PATH = os.environ.get("TESSERACT_PATH", "tesseract")

LANG = os.environ.get("OCR_LANG", "ind+eng")  # Indonesian + English

pytesseract.pytesseract.tesseract_cmd = TESSERACT_PATH

def preprocess(img: Image.Image) -> Image.Image:
    """Convert to grayscale, increase contrast for better OCR."""
    gray = img.convert("L")
    # Simple contrast enhancement
    return gray

def extract_isbn(text: str) -> str | None:
    """Find ISBN-10 or ISBN-13 in OCR text."""
    # ISBN-13: 978-... (with/without hyphens)
    match = re.search(r'(?:ISBN[:\s]*)?(97[89][-\s]?\d{1,5}[-\s]?\d{1,7}[-\s]?\d{1,7}[-\s]?\d)', text, re.IGNORECASE)
    if match:
        return re.sub(r'[^0-9Xx]', '', match.group(1))
    # ISBN-10
    match = re.search(r'(?:ISBN[:\s]*)?(\d[-\s]?\d{1,5}[-\s]?\d{1,7}[-\s]?\d{1,7}[-\s]?[\dXx])', text, re.IGNORECASE)
    if match:
        return re.sub(r'[^0-9Xx]', '', match.group(1))
    return None

def extract_title(lines: list[str]) -> str | None:
    """Heuristic: the longest non-ISBN line from the top half of the text."""
    candidates = [l.strip() for l in lines if l.strip() and len(l.strip()) > 3 and not re.match(r'^(ISBN|978|979)', l.strip(), re.IGNORECASE)]
    if not candidates:
        return None
    # Return the longest (likely the title)
    return max(candidates, key=len)

def extract_author(text: str) -> str | None:
    """Look for common author patterns."""
    patterns = [
        r'(?:Penulis|Pengarang|Author|Oleh)[:\s]+(.+?)(?:\n|$)',
        r'(?:oleh)\s+([A-Z][a-z]+(?:\s[A-Z][a-z]+)+)',
    ]
    for pat in patterns:
        match = re.search(pat, text, re.IGNORECASE)
        if match:
            return match.group(1).strip()
    return None

def extract_publisher(text: str) -> str | None:
    """Look for publisher in OCR text."""
    patterns = [
        r'(?:Penerbit|Publisher)[:\s]+(.+?)(?:\n|$)',
        r'(?:Penerbit|Publisher)[:\s]+([A-Z][a-z]+(?:\s[A-Z][a-z]+)*)',
    ]
    for pat in patterns:
        match = re.search(pat, text, re.IGNORECASE)
        if match:
            return match.group(1).strip()
    # Common Indonesian publishers
    common = ["Gramedia", "Erlangga", "Mizan", "Bentang", "Republika", "Grasindo", "Elex Media", "Andi", "Gava Media"]
    for pub in common:
        if pub.lower() in text.lower():
            return pub
    return None

def guess_category(text: str) -> str | None:
    """Guess book category from keywords."""
    lowered = text.lower()
    keywords = {
        "Fiksi": ["novel", "cerita", "fiksi", "kisah", "roman", "dongeng"],
        "Pendidikan": ["belajar", "sekolah", "kuliah", "universitas", "modul", "buku ajar", "pelajaran"],
        "Teknologi": ["komputer", "programming", "koding", "teknologi", "internet", "digital", "software"],
        "Agama": ["islam", "kristen", "agama", "al-quran", "hadits", "doa", "ibadah"],
        "Bisnis": ["bisnis", "manajemen", "ekonomi", "marketing", "keuangan", "investasi"],
        "Kesehatan": ["kesehatan", "medis", "dokter", "penyakit", "diet", "gizi"],
        "Sejarah": ["sejarah", "sejarah", "perang", "kolonial", "kerajaan"],
        "Sains": ["fisika", "kimia", "biologi", "matematika", "sains", "ilmu"],
        "Hukum": ["hukum", "undang-undang", "peraturan", "legal"],
        "Komik": ["komik", "manga", "ilustrasi", "kartun"],
    }
    for cat, words in keywords.items():
        if any(w in lowered for w in words):
            return cat
    return None

def ocr_image(path: str) -> dict:
    """Run OCR on a single image and extract structured data."""
    try:
        img = Image.open(path)
    except Exception as e:
        return {"error": str(e), "path": path}

    processed = preprocess(img)
    
    # Try Tesseract with LSTM engine
    try:
        text = pytesseract.image_to_string(processed, lang=LANG, config='--oem 1 --psm 3')
    except Exception:
        try:
            text = pytesseract.image_to_string(processed, lang='eng', config='--oem 1 --psm 3')
        except Exception as e:
            return {"error": f"Tesseract failed: {e}", "path": path}

    lines = text.split('\n')

    return {
        "path": path,
        "text": text,
        "isbn": extract_isbn(text),
        "title": extract_title(lines),
        "author": extract_author(text),
        "publisher": extract_publisher(text),
        "category": guess_category(text),
    }

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python ocr_book_cover.py <image1> [image2 ...]"}))
        sys.exit(0)

    results = []
    for path in sys.argv[1:]:
        if not os.path.exists(path):
            results.append({"error": f"File not found: {path}", "path": path})
            continue
        results.append(ocr_image(path))

    # Determine the best combined result
    best = {"isbn": None, "title": None, "author": None, "publisher": None, "category": None}
    for r in results:
        if "error" in r:
            continue
        for field in best:
            if not best[field] and r.get(field):
                best[field] = r[field]

    output = {
        "images": results,
        "best": best,
    }
    print(json.dumps(output, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    main()
