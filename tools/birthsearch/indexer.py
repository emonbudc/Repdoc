# -*- coding: utf-8 -*-
"""
BDRIS DOCX Indexer - SQLite version
Parses DOCX birth registration files and populates SQLite database.db.
"""
import zipfile
import xml.etree.ElementTree as ET
import re
import os
import sys
import sqlite3
import hashlib
import time

DATA_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'data')
DB_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'database.db')

BANGLA_DIGITS = '০১২৩৪৫৬৭৮৯'
ASCII_DIGITS = '0123456789'


def log(msg):
    print(msg, flush=True)


def bangla_to_int(s):
    trans = str.maketrans(BANGLA_DIGITS, ASCII_DIGITS)
    return int(s.translate(trans))


def file_hash(filepath):
    h = hashlib.md5()
    with open(filepath, 'rb') as f:
        for chunk in iter(lambda: f.read(8192), b''):
            h.update(chunk)
    return h.hexdigest()


def is_header_row(text):
    t = text.lower()
    for kw in ['নিবন্ধনের', 'নাম্বার', 'জন্ম নিবন্ধন', 'নাম \nজন্ম', 'সার্টিফিকেট']:
        if kw in t:
            return True
    return False


def remove_spaces_in_digits(text):
    result = list(text)
    i = 0
    while i < len(result):
        if result[i] in BANGLA_DIGITS or result[i] in ASCII_DIGITS:
            i += 1
            while i < len(result) and result[i] == ' ':
                if i + 1 < len(result) and (result[i+1] in BANGLA_DIGITS or result[i+1] in ASCII_DIGITS):
                    result[i] = ''
                    i += 1
                else:
                    break
        else:
            i += 1
    return ''.join(result)


def clean_text(text):
    text = remove_spaces_in_digits(text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()


def clean_name(name):
    name = clean_text(name)
    name = re.sub(r'^(মোছাঃ?|মোছা:?\s*|মোঃ?\s*|মো:\s*|মো\s+|মহোদয়া:?\s*)', '', name)
    name = re.sub(r'^(শ্রী:?\s*|শ্রীমতী:?\s*|শ্রীমতি:?\s*)', '', name)
    name = re.sub(r'^:\s*', '', name)
    return name.strip()


def clean_parent_name(name):
    name = clean_text(name)
    name = re.sub(r'^(মোছাঃ?|মোছা:?\s*|মোঃ?\s*|মো:\s*|মো\s+)', '', name)
    name = re.sub(r'^(মৃতমৃ\s*|মৃত\s+)', '', name)
    name = re.sub(r'^[.:]\s*', '', name)
    name = name.strip()
    if re.match(r'^\d+$', name) or len(name) < 2:
        return ''
    return name


def clean_date(date):
    return clean_text(date)


def clean_address(addr):
    addr = clean_text(addr)
    addr = re.sub(r'মৃতমৃ\s*', '', addr)
    addr = addr.strip(' \t\n\r।,')
    return addr


def clean_reg_number(num):
    return re.sub(r'\s+', '', num).strip()


def clean_gender(gender):
    g = gender.strip().upper()
    if 'FEMALE' in g or 'মহিলা' in g:
        return 'FEMALE'
    if 'MALE' in g or 'পুরুষ' in g:
        return 'MALE'
    return g


def clean_ward(ward):
    ward = clean_text(ward)
    ward = re.sub(r'ওয়ার্ড\s*-\s*র্ড\s*', 'ওয়ার্ড ', ward)
    ward = re.sub(r'ওয়ার্ড\s*', '', ward)
    return ward.strip()


def get_cell_text(tc_elem):
    ns = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
    parts = []
    for p in tc_elem.iter(ns + 'p'):
        line_parts = []
        for t in p.iter(ns + 't'):
            if t.text:
                line_parts.append(t.text)
        parts.append(''.join(line_parts).strip())
    return '\n'.join(parts)


def parse_docx(filepath):
    pages = []
    current_page = 0

    try:
        with zipfile.ZipFile(filepath, 'r') as z:
            doc_xml = z.read('word/document.xml')
    except Exception as e:
        log(f"  ERROR: Cannot read {filepath}: {e}")
        return pages

    root = ET.fromstring(doc_xml)
    ns = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
    body = root.find('.//' + ns + 'body')
    if body is None:
        return pages

    current_page_rows = []

    for child in body:
        tag = child.tag.split('}')[-1] if '}' in child.tag else child.tag

        if tag == 'p':
            text_parts = []
            for t in child.iter(ns + 't'):
                if t.text:
                    text_parts.append(t.text)
            text = ''.join(text_parts).strip()

            m = re.search(r'প[া\s]*ত[া\s]*\s*-\s*(\d+)', text)
            if m:
                if current_page_rows:
                    pages.append({'page': current_page, 'rows': current_page_rows})
                current_page = bangla_to_int(m.group(1))
                current_page_rows = []

        elif tag == 'tbl':
            rows = []
            for tr in child.iter(ns + 'tr'):
                cells = []
                for tc in tr.iter(ns + 'tc'):
                    cells.append(get_cell_text(tc))
                if cells:
                    rows.append(cells)
            current_page_rows.extend(rows)

    if current_page_rows:
        pages.append({'page': current_page, 'rows': current_page_rows})

    return pages


def extract_records(pages, source_file):
    records = []
    for page_data in pages:
        page_num = page_data['page']
        for row_idx, row in enumerate(page_data['rows']):
            if len(row) < 13:
                continue
            if is_header_row(row[0]) or is_header_row(row[3]):
                continue

            name = clean_name(row[3])
            if len(name) < 2:
                continue

            reg_num     = clean_reg_number(row[1])
            father      = clean_parent_name(row[9])
            mother      = clean_parent_name(row[7])
            dob         = clean_date(row[4])
            gender      = clean_gender(row[5])
            birth_place = clean_address(row[6])
            address     = clean_address(row[11])
            reg_date    = clean_date(row[0])
            cert_date   = clean_date(row[12])
            ward        = clean_ward(row[2])

            search_text = f"{name} {father} {mother} {address} {birth_place} {reg_num}".strip()

            records.append((
                reg_num, name, father, mother, dob, gender,
                birth_place, address, reg_date, cert_date,
                ward, page_num, row_idx, source_file, search_text
            ))

    return records


def main():
    start_time = time.time()
    log("=" * 60)
    log("BDRIS DOCX Indexer (SQLite)")
    log("=" * 60)

    if os.path.exists(DB_PATH):
        os.remove(DB_PATH)
        log("Removed old database.db")

    conn = sqlite3.connect(DB_PATH)
    # Use the default rollback-journal mode (not WAL). WAL leaves -wal/-shm
    # sidecar files that are not uploaded to shared hosts (e.g. InfinityFree)
    # and rely on shared-memory locking that fails there. A single self-contained
    # database.db file is required for deployment.
    conn.execute('PRAGMA journal_mode=DELETE')
    conn.execute('PRAGMA synchronous=NORMAL')

    conn.execute('''CREATE TABLE records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        registration_number TEXT,
        name TEXT,
        father_name TEXT,
        mother_name TEXT,
        date_of_birth TEXT,
        gender TEXT,
        birth_place TEXT,
        permanent_address TEXT,
        registration_date TEXT,
        certificate_date TEXT,
        ward TEXT,
        page_number INTEGER,
        serial INTEGER,
        source_file TEXT,
        search_text TEXT
    )''')

    conn.execute('CREATE INDEX idx_name ON records(name)')
    conn.execute('CREATE INDEX idx_father ON records(father_name)')
    conn.execute('CREATE INDEX idx_mother ON records(mother_name)')
    conn.execute('CREATE INDEX idx_regnum ON records(registration_number)')
    conn.execute('CREATE INDEX idx_dob ON records(date_of_birth)')
    conn.execute('CREATE INDEX idx_source ON records(source_file, page_number)')
    conn.commit()

    log("Database and table created.")

    log("\nScanning data/ folder...")
    docx_files = sorted([f for f in os.listdir(DATA_DIR) if f.endswith('.docx')])
    log(f"Found {len(docx_files)} DOCX files.")

    seen_hashes = set()
    total_records = 0

    for file_idx, filename in enumerate(docx_files):
        filepath = os.path.join(DATA_DIR, filename)
        fhash = file_hash(filepath)

        if fhash in seen_hashes:
            log(f"[{file_idx+1}/{len(docx_files)}] SKIP (duplicate): {filename}")
            continue
        seen_hashes.add(fhash)

        log(f"[{file_idx+1}/{len(docx_files)}] Processing: {filename}")

        pages = parse_docx(filepath)
        if not pages:
            log(f"  No pages found.")
            continue

        records = extract_records(pages, filename)
        log(f"  Extracted {len(records)} records from {len(pages)} pages.")

        if not records:
            continue

        conn.executemany('''INSERT INTO records
            (registration_number, name, father_name, mother_name, date_of_birth, gender,
             birth_place, permanent_address, registration_date, certificate_date,
             ward, page_number, serial, source_file, search_text)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)''', records)
        conn.commit()

        total_records += len(records)
        log(f"  -> {len(records)} records inserted. (Total: {total_records})")

    # Deduplicate
    log("\nDeduplicating...")
    before = conn.execute('SELECT COUNT(*) FROM records').fetchone()[0]
    conn.execute('DELETE FROM records WHERE id NOT IN (SELECT MIN(id) FROM records GROUP BY name, registration_number)')
    conn.commit()
    total_unique = conn.execute('SELECT COUNT(*) FROM records').fetchone()[0]
    removed = before - total_unique
    if removed > 0:
        log(f"Removed {removed} duplicate records.")
    else:
        log("No duplicates found.")

    conn.close()
    elapsed = time.time() - start_time

    log("")
    log("=" * 60)
    log(f"DONE! Total records indexed: {total_records}")
    log(f"Deduplicated to: {total_unique} unique records")
    log(f"Database: {DB_PATH}")
    log(f"Time elapsed: {elapsed:.1f} seconds")
    log("=" * 60)
    log("\nOpen http://localhost/Birth-Search/ in your browser.")


if __name__ == '__main__':
    main()
