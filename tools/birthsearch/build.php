<?php
/**
 * build.php — Server-side DOCX indexer (CLI / local full rebuild).
 *
 * Uses the shared includes/DocxParser.php and PDO (same code path as the app).
 * For InfinityFree HOSTING use install.php in a browser instead (chunked import
 * into MySQL — a 28 MB SQLite file cannot exist on InfinityFree's 10 MB limit).
 *
 * Usage (local):
 *   1. Put your .docx files into /data/
 *   2. Run: php build.php
 *   3. Open index.php — records should appear.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/DocxParser.php';

function logMsg(string $msg): void {
    echo $msg . "\n";
    if (php_sapi_name() !== 'cli') echo "<br>";
    flush();
}

function buildSchema(PDO $db, bool $isMySQL): void {
    if ($isMySQL) {
        $db->exec("CREATE TABLE IF NOT EXISTS records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registration_number VARCHAR(64) DEFAULT '',
            name VARCHAR(255) DEFAULT '',
            father_name VARCHAR(255) DEFAULT '',
            mother_name VARCHAR(255) DEFAULT '',
            date_of_birth VARCHAR(64) DEFAULT '',
            gender VARCHAR(16) DEFAULT '',
            birth_place TEXT,
            permanent_address TEXT,
            registration_date VARCHAR(64) DEFAULT '',
            certificate_date VARCHAR(64) DEFAULT '',
            ward VARCHAR(128) DEFAULT '',
            page_number INT DEFAULT 0,
            serial INT DEFAULT 0,
            source_file VARCHAR(255) DEFAULT '',
            search_text TEXT,
            name_en VARCHAR(255) DEFAULT '',
            father_name_en VARCHAR(255) DEFAULT '',
            mother_name_en VARCHAR(255) DEFAULT '',
            place_of_birth_en TEXT,
            permanent_address_en TEXT,
            status VARCHAR(16) DEFAULT 'pending',
            verified_at DATETIME NULL,
            photo TEXT,
            INDEX idx_name (name(64)),
            INDEX idx_father (father_name(64)),
            INDEX idx_mother (mother_name(64)),
            INDEX idx_regnum (registration_number(32)),
            INDEX idx_dob (date_of_birth(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return;
    }
    $db->exec('CREATE TABLE records (
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
        search_text TEXT,
        name_en TEXT,
        father_name_en TEXT,
        mother_name_en TEXT,
        place_of_birth_en TEXT,
        permanent_address_en TEXT,
        status TEXT DEFAULT \'pending\',
        verified_at TEXT,
        photo TEXT
    )');
    $db->exec('CREATE INDEX idx_name ON records(name)');
    $db->exec('CREATE INDEX idx_father ON records(father_name)');
    $db->exec('CREATE INDEX idx_mother ON records(mother_name)');
    $db->exec('CREATE INDEX idx_regnum ON records(registration_number)');
    $db->exec('CREATE INDEX idx_dob ON records(date_of_birth)');
}

function main(): void {
    logMsg(str_repeat('=', 60));
    logMsg('BDRIS DOCX Indexer (PDO / shared parser)');
    logMsg(str_repeat('=', 60));

    $isMySQL = isMySQL();

    if (!$isMySQL) {
        $dbPath = DB_PATH;
        if (file_exists($dbPath)) {
            unlink($dbPath);
            logMsg("Removed old database.db");
        }
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $db->exec('PRAGMA journal_mode=DELETE');
        $db->exec('PRAGMA synchronous=NORMAL');
    } else {
        $db = getPDO();
        logMsg("Target: MySQL " . MYSQL_DB . ' @ ' . MYSQL_HOST);
        $db->exec('TRUNCATE TABLE records');
    }

    buildSchema($db, $isMySQL);

    logMsg("\nScanning data/ folder...");
    $docxFiles = docxListFiles(DATA_DIR);
    logMsg("Found " . count($docxFiles) . " DOCX files.");

    $totalRecords = 0;
    $insert = $db->prepare('INSERT INTO records
        (registration_number, name, father_name, mother_name, date_of_birth, gender,
         birth_place, permanent_address, registration_date, certificate_date,
         ward, page_number, serial, source_file, search_text)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

    foreach ($docxFiles as $idx => $filename) {
        $filepath = DATA_DIR . DIRECTORY_SEPARATOR . $filename;
        logMsg("[" . ($idx + 1) . "/" . count($docxFiles) . "] Processing: $filename");

        $parsed = docxParseFile($filepath);
        $pages = $parsed['pages'];
        if (!empty($parsed['error']) && empty($pages)) {
            logMsg("  ERROR: " . $parsed['error']);
            continue;
        }
        if (empty($pages)) {
            logMsg("  No pages found.");
            continue;
        }
        $records = docxExtractRecords($pages, $filename);
        logMsg("  Extracted " . count($records) . " records from " . count($pages) . " pages.");

        foreach ($records as $r) {
            $insert->execute($r);
        }
        $totalRecords += count($records);
        logMsg("  -> " . count($records) . " records inserted. (Total: $totalRecords)");
    }

    logMsg("\nDeduplicating...");
    $before = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
    // Portable "keep lowest id per (name, registration_number)" — works on both drivers.
    $db->exec('DELETE FROM records WHERE id NOT IN (SELECT MIN(id) FROM records GROUP BY name, registration_number)');
    $after = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
    $removed = $before - $after;
    logMsg($removed > 0 ? "Removed $removed duplicate records." : "No duplicates found.");

    logMsg("");
    logMsg(str_repeat('=', 60));
    logMsg("DONE! Total records indexed: $totalRecords");
    logMsg("Unique records: $after");
    logMsg(str_repeat('=', 60));
    logMsg("\nNow open index.php — records should appear. Delete build.php when done.");
}

main();
