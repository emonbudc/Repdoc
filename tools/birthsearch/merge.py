# -*- coding: utf-8 -*-
"""
Merge birth_register.db into database.db
Inserts ALL records from birth_register.db (duplicates kept).
"""
import sqlite3
import os
import re
import time

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, 'database.db')
NEW_DB_PATH = os.path.join(BASE_DIR, 'birth_register.db')

BANGLA_DIGITS = '০১২৩৪৫৬৭৮৯'
ASCII_DIGITS = '0123456789'


def log(msg):
    print(msg, flush=True)


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
    name = re.sub(r'^:\s*', '', name)
    name = name.strip()
    if name == '1' or name == '' or re.match(r'^\d+$', name) or len(name) < 2:
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


def map_gender(gender):
    g = gender.strip()
    if 'FEMALE' in g.upper() or 'মেয়ে' in g or 'মহিলা' in g:
        return 'FEMALE'
    if 'MALE' in g.upper() or 'ছেলে' in g or 'পুরুষ' in g:
        return 'MALE'
    return 'MALE'


def main():
    start_time = time.time()
    log("=" * 60)
    log("MERGE: birth_register.db -> database.db")
    log("=" * 60)

    if not os.path.exists(DB_PATH):
        log(f"ERROR: {DB_PATH} not found")
        return
    if not os.path.exists(NEW_DB_PATH):
        log(f"ERROR: {NEW_DB_PATH} not found")
        return

    cur_db = sqlite3.connect(DB_PATH)
    cur_db.execute('PRAGMA journal_mode=WAL')
    cur_db.execute('PRAGMA synchronous=NORMAL')

    new_db = sqlite3.connect(NEW_DB_PATH)

    existing_count = cur_db.execute('SELECT COUNT(*) FROM records').fetchone()[0]
    log(f"Current DB: {existing_count} records")

    new_count = new_db.execute('SELECT COUNT(*) FROM records').fetchone()[0]
    log(f"New DB: {new_count} records")

    log("\nReading and processing new records (duplicates kept)...")
    new_rows = new_db.execute(
        'SELECT source_file, page, reg_date, reg_number, odwd, child_name, '
        'birth_date, gender, birthplace, mother_name, mother_nationality, '
        'father_name, father_nationality, address, cert_date '
        'FROM records'
    ).fetchall()

    inserted = 0
    skipped = 0
    batch = []

    for row in new_rows:
        (src_file, page, reg_date, reg_number, odwd, child_name,
         birth_date, gender, birthplace, mother_name, mother_nat,
         father_name, father_nat, address, cert_date) = row

        name = clean_name(child_name or '')
        if len(name) < 2:
            skipped += 1
            continue

        dob = clean_date(birth_date or '')
        gender_mapped = map_gender(gender or '')
        father = clean_parent_name(father_name or '')
        mother = clean_parent_name(mother_name or '')
        address_clean = clean_address(address or '')
        birthplace_clean = clean_address(birthplace or '')
        reg_num = clean_reg_number(reg_number or '')
        reg_date_clean = clean_date(reg_date or '')
        cert_date_clean = clean_date(cert_date or '')
        ward = clean_text(odwd or '')
        try:
            page_num = int(re.sub(r'\D', '', page or '0') or '0')
        except ValueError:
            page_num = 0

        search_text = f"{name} {father} {mother} {address_clean} {birthplace_clean} {reg_num}".strip()

        batch.append((
            reg_num, name, father, mother, dob, gender_mapped,
            birthplace_clean, address_clean, reg_date_clean, cert_date_clean,
            ward, page_num, 0, f"birth_register_{src_file}", search_text
        ))

        inserted += 1
        if len(batch) >= 500:
            cur_db.executemany('''INSERT INTO records
                (registration_number, name, father_name, mother_name, date_of_birth, gender,
                 birth_place, permanent_address, registration_date, certificate_date,
                 ward, page_number, serial, source_file, search_text)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)''', batch)
            cur_db.commit()
            log(f"  Inserted batch: {inserted} so far...")
            batch = []

    if batch:
        cur_db.executemany('''INSERT INTO records
            (registration_number, name, father_name, mother_name, date_of_birth, gender,
             birth_place, permanent_address, registration_date, certificate_date,
             ward, page_number, serial, source_file, search_text)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)''', batch)
        cur_db.commit()

    new_total = cur_db.execute('SELECT COUNT(*) FROM records').fetchone()[0]
    new_db.close()
    cur_db.close()

    elapsed = time.time() - start_time

    log("")
    log("=" * 60)
    log(f"MERGE COMPLETE")
    log(f"  Read from new DB:      {new_count}")
    log(f"  Skipped (short name):  {skipped}")
    log(f"  Inserted:              {inserted}")
    log(f"  Previous total:        {existing_count}")
    log(f"  New total:             {new_total}")
    log(f"  Time: {elapsed:.1f}s")
    log("=" * 60)


if __name__ == '__main__':
    main()
