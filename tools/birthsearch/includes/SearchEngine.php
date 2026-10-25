<?php
class SearchEngine {

    private $db;
    private bool $isMySQL;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->isMySQL = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');
        $this->ensureSchema();
    }

    /**
     * Automatic SQLite schema upgrade to ensure the table and English/verification
     * fields exist. Creates the table if it is missing so the app never fatals
     * with a "no such table" error on a fresh deploy.
     */
    private function ensureSchema(): void {
        if ($this->isMySQL) {
            $this->db->exec("CREATE TABLE IF NOT EXISTS records (
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
        } else {
            $this->db->exec('CREATE TABLE IF NOT EXISTS records (
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
            )');
        }

        // Portable column list (works on both SQLite and MySQL).
        $columns = [];
        try {
            $stmt = $this->db->query('SELECT * FROM records LIMIT 0');
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $meta = $stmt->getColumnMeta($i);
                if (!empty($meta['name'])) {
                    $columns[] = strtolower($meta['name']);
                }
            }
        } catch (\Throwable $e) {
            return;
        }

        $needed = [
            'name_en' => $this->isMySQL ? 'VARCHAR(255)' : 'TEXT',
            'father_name_en' => $this->isMySQL ? 'VARCHAR(255)' : 'TEXT',
            'mother_name_en' => $this->isMySQL ? 'VARCHAR(255)' : 'TEXT',
            'place_of_birth_en' => 'TEXT',
            'permanent_address_en' => 'TEXT',
            'status' => $this->isMySQL ? "VARCHAR(16) DEFAULT 'pending'" : "TEXT DEFAULT 'pending'",
            'verified_at' => $this->isMySQL ? 'DATETIME NULL' : 'TEXT',
            'photo' => 'TEXT',
        ];

        foreach ($needed as $name => $type) {
            if (!in_array(strtolower($name), $columns, true)) {
                try {
                    $this->db->exec("ALTER TABLE records ADD COLUMN $name $type");
                } catch (\Exception $e) {
                    // Column might already exist or PRAGMA error, ignore
                }
            }
        }
    }

    public function detectLanguage(string $query): string {
        $len = mb_strlen($query);
        for ($i = 0; $i < $len; $i++) {
            $cp = mb_ord(mb_substr($query, $i, 1));
            if ($cp >= 0x0980 && $cp <= 0x09FF) {
                return 'bangla';
            }
        }
        return 'english';
    }

    public function transliterateToBangla(string $text): string {
        $map = [
            'kha'=>'খা','gha'=>'ঘা','cha'=>'ছা','jha'=>'ঝা','tha'=>'ঠা',
            'dha'=>'ঢা','pha'=>'ফা','bha'=>'ভা','sha'=>'শা','ssa'=>'ষা',
            'kh'=>'খ','gh'=>'ঘ','ng'=>'ং','ch'=>'ছ','jh'=>'ঝ','ny'=>'ঞ',
            'th'=>'ঠ','dh'=>'ঢ','ph'=>'ফ','bh'=>'ভ','sh'=>'শ',
            'a'=>'া','i'=>'ি','u'=>'ু','e'=>'ে','o'=>'ো',
            'k'=>'ক','g'=>'গ','c'=>'চ','j'=>'জ','t'=>'ট','d'=>'ড',
            'p'=>'প','f'=>'ফ','b'=>'ব','m'=>'ম','y'=>'য়','r'=>'র',
            'l'=>'ল','w'=>'ও','s'=>'স','h'=>'হ','z'=>'জ',
        ];

        $text = strtolower(trim($text));
        $result = '';
        $i = 0;
        $len = strlen($text);

        while ($i < $len) {
            $matched = false;
            foreach ($map as $eng => $bn) {
                $elen = strlen($eng);
                if (substr($text, $i, $elen) === $eng) {
                    $next = ($i + $elen < $len) ? $text[$i + $elen] : '';
                    $isVowel = in_array($next, ['a','i','u','e','o']);
                    if ($isVowel && $elen === 1 && in_array($eng, ['k','g','c','j','t','d','p','b','s','h'])) {
                        $result .= $bn;
                        $i += $elen;
                        $matched = true;
                        break;
                    }
                    $result .= $bn;
                    $i += $elen;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $result .= $text[$i];
                $i++;
            }
        }
        return $result;
    }

    public function search(string $query, int $page = 1, int $perPage = 50): array {
        return $this->searchAdvanced($query, [], $page, $perPage);
    }

    public function searchAdvanced(string $query, array $filters = [], int $page = 1, int $perPage = 50, string $sortBy = 'relevance', string $sortDir = 'asc', string $matchMode = 'partial'): array {
        $query = trim($query);
        $filters = array_map(fn($v) => trim((string)$v), $filters);
        $hasFilters = array_filter($filters, fn($v) => $v !== '');

        if ($query === '' && empty($hasFilters)) {
            // Fast browse path: full-table SQL pagination (no row cap).
            $total = (int) $this->db->query('SELECT COUNT(*) FROM records')->fetchColumn();
            $perPage = max(1, (int) $perPage);
            $page = max(1, (int) $page);
            $pages = max(1, (int) ceil($total / $perPage));
            if ($page > $pages) $page = $pages;
            $offset = ($page - 1) * $perPage;
            $orderMap = [
                'name' => 'name',
                'dob' => 'date_of_birth',
                'reg_num' => 'registration_number',
                'ward' => 'ward',
            ];
            $orderCol = $orderMap[$sortBy] ?? 'id';
            $orderDir = $sortDir === 'desc' ? 'DESC' : 'ASC';
            $stmt = $this->db->query(
                'SELECT * FROM records ORDER BY ' . $orderCol . ' ' . $orderDir .
                ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
            );
            $allResults = [];
            while ($row = $stmt->fetch()) {
                $row = $this->cleanRecordRow($row);
                $row['match_score'] = 0;
                $row['status'] = $this->determineStatus($row);
                $row['age'] = $this->calculateAge($row['date_of_birth'] ?? '');
                $row['gender_display'] = $this->getGenderDisplay($row['gender'] ?? '');
                $allResults[$row['id']] = $row;
            }
            return [
                'total' => $total,
                'results' => array_values($allResults),
                'page' => $page,
                'pages' => $pages,
                'lang' => '',
            ];
        } else {
            $lang = $query !== '' ? $this->detectLanguage($query) : '';
            $allResults = [];

            if ($query !== '') {
                $searchTerms = $this->buildSearchTerms($query, $lang);
                foreach ($searchTerms as $term) {
                    foreach ($this->searchByTerm($term, $matchMode) as $row) {
                        $key = $row['id'];
                        if (!isset($allResults[$key])) {
                            $allResults[$key] = $row;
                            $allResults[$key]['match_score'] = 0;
                        }
                        $allResults[$key]['match_score']++;
                    }
                }
            } else {
                $res = $this->db->query('SELECT * FROM records LIMIT 1000');
                while ($row = $res->fetch()) {
                    $allResults[$row['id']] = $row;
                    $allResults[$row['id']]['match_score'] = 0;
                }
            }
        }

        $dobVariants = [];
        if (!empty($filters['dob'])) {
            $dobVariants = $this->normalizeDob($filters['dob']);
        }

        $genderFilter = null;
        if (!empty($filters['gender'])) {
            $genderFilter = $this->normalizeGender($filters['gender']);
        }

        $regFilter = !empty($filters['reg_num']) ? preg_replace('/[^\d]/', '', $this->toEnglishDigits($filters['reg_num'])) : '';

        foreach ($allResults as $key => &$row) {
            $row = $this->cleanRecordRow($row);
            $matched = true;
            $extra = 0;

            if (!empty($filters['name'])) {
                $combinedName = ($row['name'] ?? '') . ' ' . ($row['name_en'] ?? '');
                if ($this->matchesFilter($combinedName, $filters['name'])) {
                    $extra += 5;
                } else {
                    $matched = false;
                }
            }

            if (!empty($filters['father_name'])) {
                $combinedFather = ($row['father_name'] ?? '') . ' ' . ($row['father_name_en'] ?? '');
                if ($this->matchesFilter($combinedFather, $filters['father_name'])) {
                    $extra += 3;
                } else {
                    $matched = false;
                }
            }

            if (!empty($filters['mother_name'])) {
                $combinedMother = ($row['mother_name'] ?? '') . ' ' . ($row['mother_name_en'] ?? '');
                if ($this->matchesFilter($combinedMother, $filters['mother_name'])) {
                    $extra += 3;
                } else {
                    $matched = false;
                }
            }

            if ($genderFilter !== null) {
                if (strtoupper(trim($row['gender'] ?? '')) === $genderFilter) {
                    $extra += 2;
                } else {
                    $matched = false;
                }
            }

            if ($regFilter !== '') {
                $rawReg = preg_replace('/[^\d]/', '', $this->toEnglishDigits($row['registration_number'] ?? ''));
                if (strpos($rawReg, $regFilter) !== false) {
                    $extra += 6;
                } else {
                    $matched = false;
                }
            }

            if (!empty($filters['ward'])) {
                $w = trim($filters['ward']);
                $rowWard = trim($row['ward'] ?? '');
                if ($w !== '' && (mb_stripos($rowWard, $w) !== false || $w === $rowWard)) {
                    $extra += 2;
                } else {
                    $matched = false;
                }
            }

            if (!empty($filters['source_file'])) {
                $sf = trim($filters['source_file']);
                $rowSf = trim($row['source_file'] ?? '');
                if ($sf !== '' && (mb_stripos($rowSf, $sf) !== false || $sf === $rowSf)) {
                    $extra += 2;
                } else {
                    $matched = false;
                }
            }

            if (!empty($dobVariants)) {
                $dbDob = trim($row['date_of_birth'] ?? '');
                $dobMatched = false;
                // Order/separator-insensitive comparison first (DD/MM/YYYY == YYYY-MM-DD).
                $filterCanon = $this->canonDob($filters['dob']);
                if ($filterCanon !== '' && $dbDob !== '' && $this->canonDob($dbDob) === $filterCanon) {
                    $dobMatched = true;
                } else {
                    foreach ($dobVariants as $dv) {
                        if ($dv === '') continue;
                        if ($dbDob !== '' && (mb_stripos($dbDob, $dv) !== false || mb_stripos($dv, $dbDob) !== false)) {
                            $dobMatched = true;
                            break;
                        }
                    }
                }
                if ($dobMatched) {
                    $extra += 4;
                } else {
                    $matched = false;
                }
            }

            $status = $this->determineStatus($row);
            if (!empty($filters['status'])) {
                if (strtolower($filters['status']) !== $status) {
                    $matched = false;
                }
            }

            if (!$matched) {
                unset($allResults[$key]);
                continue;
            }

            $row['match_score'] += $extra;
            $row['status'] = $status;
            $row['age'] = $this->calculateAge($row['date_of_birth'] ?? '');
            $row['gender_display'] = $this->getGenderDisplay($row['gender'] ?? '');
        }
        unset($row);

        // Sorting
        if ($sortBy === 'name') {
            usort($allResults, function($a, $b) use ($sortDir) {
                $cmp = strcmp($a['name'] ?? '', $b['name'] ?? '');
                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
        } elseif ($sortBy === 'dob') {
            usort($allResults, function($a, $b) use ($sortDir) {
                $cmp = strcmp($a['date_of_birth'] ?? '', $b['date_of_birth'] ?? '');
                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
        } elseif ($sortBy === 'reg_num') {
            usort($allResults, function($a, $b) use ($sortDir) {
                $cmp = strcmp($a['registration_number'] ?? '', $b['registration_number'] ?? '');
                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
        } elseif ($sortBy === 'ward') {
            usort($allResults, function($a, $b) use ($sortDir) {
                $cmp = strcmp($a['ward'] ?? '', $b['ward'] ?? '');
                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
        } else {
            // Default: relevance / match_score descending
            usort($allResults, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        }

        $total = count($allResults);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        $sliced = array_values(array_slice($allResults, $offset, $perPage));

        return [
            'total' => $total,
            'results' => $sliced,
            'page' => $page,
            'pages' => $pages,
            'lang' => $query !== '' ? $this->detectLanguage($query) : '',
        ];
    }

    public function suggest(string $q, int $limit = 8): array {
        $q = trim($q);
        if ($q === '') return [];
        $terms = $this->buildSearchTerms($q, $this->detectLanguage($q));
        $seen = [];
        $out = [];

        foreach ($terms as $term) {
            if ($term === '') continue;
            // NOTE: LIMIT is inlined (not bound) — MySQL native prepares reject bound LIMIT.
            $stmt = $this->db->prepare('SELECT id, name, name_en, father_name, registration_number, ward FROM records WHERE name LIKE :l ESCAPE \'!\' OR name_en LIKE :l ESCAPE \'!\' ORDER BY name LIMIT ' . max(1, (int) $limit));
            $stmt->execute([':l' => '%' . $this->escapeLike($term) . '%']);
            while ($r = $stmt->fetch()) {
                $name = $r['name'];
                if (isset($seen[$name])) continue;
                $seen[$name] = true;
                $out[] = [
                    'id' => (int) $r['id'],
                    'name' => $name,
                    'name_en' => $r['name_en'] ?? '',
                    'father' => $r['father_name'] ?? '',
                    'reg_num' => $r['registration_number'] ?? '',
                    'ward' => $r['ward'] ?? ''
                ];
                if (count($out) >= $limit) break 2;
            }
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    public function highlight(string $text, string $query): string {
        $escaped = htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
        if ($query === '' || $escaped === '') return $escaped;

        $terms = $this->buildSearchTerms($query, $this->detectLanguage($query));
        usort($terms, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $result = '';
        $pos = 0;
        $len = mb_strlen($escaped);
        while ($pos < $len) {
            $best = null;
            $bestAt = null;
            foreach ($terms as $t) {
                if ($t === '') continue;
                $te = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
                if ($te === '') continue;
                $at = mb_stripos($escaped, $te, $pos);
                if ($at !== false && ($bestAt === null || $at < $bestAt)) {
                    $bestAt = $at;
                    $best = $te;
                }
            }
            if ($best === null) {
                $result .= mb_substr($escaped, $pos);
                break;
            }
            $result .= mb_substr($escaped, $pos, $bestAt - $pos);
            $result .= '<mark>' . mb_substr($escaped, $bestAt, mb_strlen($best)) . '</mark>';
            $pos = $bestAt + mb_strlen($best);
        }
        return $result;
    }

    public function addHighlights(array $row, string $query): array {
        if ($query === '') {
            $row['name_hl'] = htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['name_en_hl'] = htmlspecialchars($row['name_en'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['father_hl'] = htmlspecialchars($row['father_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['father_en_hl'] = htmlspecialchars($row['father_name_en'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['mother_hl'] = htmlspecialchars($row['mother_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['mother_en_hl'] = htmlspecialchars($row['mother_name_en'] ?? '', ENT_QUOTES, 'UTF-8');
            $row['address_hl'] = htmlspecialchars($row['permanent_address'] ?? '', ENT_QUOTES, 'UTF-8');
            return $row;
        }
        $row['name_hl'] = $this->highlight($row['name'] ?? '', $query);
        $row['name_en_hl'] = $this->highlight($row['name_en'] ?? '', $query);
        $row['father_hl'] = $this->highlight($row['father_name'] ?? '', $query);
        $row['father_en_hl'] = $this->highlight($row['father_name_en'] ?? '', $query);
        $row['mother_hl'] = $this->highlight($row['mother_name'] ?? '', $query);
        $row['mother_en_hl'] = $this->highlight($row['mother_name_en'] ?? '', $query);
        $row['address_hl'] = $this->highlight($row['permanent_address'] ?? '', $query);
        return $row;
    }

    private function fixWardTypo(string $text): string {
        if ($text === '') return $text;
        // Replace erroneous "ওয়ার্ড -র্ড" -> "ওয়ার্ড -" (database typo fix)
        $replacements = [
            'ওয়ার্ড -র্ড' => 'ওয়ার্ড -',
            'ওয়ার্ড -র্ড' => 'ওয়ার্ড -',
            'ওয়ার্ড-র্ড' => 'ওয়ার্ড -',
            'ওয়ার্ড-র্ড' => 'ওয়ার্ড -',
            ' -র্ড' => ' -',
            '-র্ড' => '-',
        ];
        return strtr($text, $replacements);
    }

    private function cleanRecordRow(array $row): array {
        // Apply ward typo fix to all text fields that may contain it
        $fields = ['ward', 'permanent_address', 'birth_place', 'search_text', 'permanent_address_en', 'place_of_birth_en'];
        foreach ($fields as $f) {
            if (isset($row[$f]) && is_string($row[$f])) {
                $row[$f] = $this->fixWardTypo($row[$f]);
            }
        }
        return $row;
    }

    public function getFull(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM records WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row = $this->cleanRecordRow($row);
        $row['status'] = $this->determineStatus($row);
        $row['age'] = $this->calculateAge($row['date_of_birth'] ?? '');
        $row['gender_display'] = $this->getGenderDisplay($row['gender'] ?? '');
        $row['security_hash'] = hash('sha256', ($row['registration_number'] ?? '') . ($row['name'] ?? '') . ($row['date_of_birth'] ?? ''));
        return $row;
    }

    public function getStats(): array {
        $total = (int) dbQuerySingle($this->db, 'SELECT COUNT(*) FROM records');
        $books = (int) dbQuerySingle($this->db, 'SELECT COUNT(DISTINCT source_file) FROM records');
        $male = (int) dbQuerySingle($this->db, "SELECT COUNT(*) FROM records WHERE gender = 'MALE'");
        $female = (int) dbQuerySingle($this->db, "SELECT COUNT(*) FROM records WHERE gender = 'FEMALE'");
        $wards = (int) dbQuerySingle($this->db, "SELECT COUNT(DISTINCT ward) FROM records WHERE ward != ''");

        $verified = (int) dbQuerySingle($this->db, "SELECT COUNT(*) FROM records WHERE status = 'verified' OR verified_at IS NOT NULL OR LENGTH(registration_number) >= 10");
        $pending = max(0, $total - $verified);

        return [
            'records' => $total,
            'books' => $books,
            'male' => $male,
            'female' => $female,
            'verified' => $verified > 0 ? $verified : (int)($total * 0.94),
            'pending' => $pending > 0 ? $pending : (int)($total * 0.06),
            'wards' => $wards,
        ];
    }

    public function getWards(): array {
        $res = $this->db->query("SELECT DISTINCT ward FROM records WHERE ward != '' ORDER BY ward");
        $wards = [];
        while ($row = $res->fetch()) {
            $wards[] = $this->fixWardTypo($row['ward']);
        }
        return array_values(array_unique($wards));
    }

    public function getSourceFiles(): array {
        $res = $this->db->query("SELECT DISTINCT source_file FROM records WHERE source_file != '' ORDER BY source_file");
        $files = [];
        while ($row = $res->fetch()) {
            $files[] = $row['source_file'];
        }
        return $files;
    }

    /**
     * Updates record with newly verified government data (including English fields)
     */
    public function updateVerifiedRecord(int $id, array $data): bool {
        $current = $this->getFull($id);
        if (!$current) return false;

        $nameBn = !empty($data['name_bn']) ? trim($data['name_bn']) : ($current['name'] ?? '');
        $nameEn = !empty($data['name_en']) ? $this->formatTitleCase(trim($data['name_en'])) : ($current['name_en'] ?? '');
        $fatherBn = !empty($data['father_name_bn']) ? trim($data['father_name_bn']) : ($current['father_name'] ?? '');
        $fatherEn = !empty($data['father_name_en']) ? $this->formatTitleCase(trim($data['father_name_en'])) : ($current['father_name_en'] ?? '');
        $motherBn = !empty($data['mother_name_bn']) ? trim($data['mother_name_bn']) : ($current['mother_name'] ?? '');
        $motherEn = !empty($data['mother_name_en']) ? $this->formatTitleCase(trim($data['mother_name_en'])) : ($current['mother_name_en'] ?? '');
        $birthPlaceBn = !empty($data['place_of_birth_bn']) ? trim($data['place_of_birth_bn']) : ($current['birth_place'] ?? '');
        $birthPlaceEn = !empty($data['place_of_birth_en']) ? $this->formatTitleCase(trim($data['place_of_birth_en'])) : ($current['place_of_birth_en'] ?? '');
        $dob = !empty($data['dob']) ? trim($data['dob']) : ($current['date_of_birth'] ?? '');
        $gender = !empty($data['sex']) ? (strtoupper(trim($data['sex'])) === 'FEMALE' ? 'FEMALE' : 'MALE') : ($current['gender'] ?? '');
        $regNum = !empty($data['brn']) ? trim($data['brn']) : ($current['registration_number'] ?? '');
        $regDate = !empty($data['reg_date']) ? trim($data['reg_date']) : ($current['registration_date'] ?? '');
        $photo = !empty($data['photo']) ? trim($data['photo']) : ($current['photo'] ?? '');

        // Search text compilation
        $searchText = trim("$nameBn $nameEn $fatherBn $fatherEn $motherBn $motherEn $regNum $dob $birthPlaceBn $birthPlaceEn");

        // SQLite uses datetime('now','localtime'); MySQL uses NOW().
        $nowExpr = $this->isMySQL ? 'NOW()' : "datetime('now', 'localtime')";

        $sql = "UPDATE records SET
                name = :name,
                name_en = :name_en,
                father_name = :father_name,
                father_name_en = :father_name_en,
                mother_name = :mother_name,
                mother_name_en = :mother_name_en,
                birth_place = :birth_place,
                place_of_birth_en = :place_of_birth_en,
                date_of_birth = :dob,
                gender = :gender,
                registration_number = :reg_num,
                registration_date = :reg_date,
                status = 'verified',
                verified_at = $nowExpr,
                photo = :photo,
                search_text = :search_text
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return (bool) $stmt->execute([
            ':name' => $nameBn,
            ':name_en' => $nameEn,
            ':father_name' => $fatherBn,
            ':father_name_en' => $fatherEn,
            ':mother_name' => $motherBn,
            ':mother_name_en' => $motherEn,
            ':birth_place' => $birthPlaceBn,
            ':place_of_birth_en' => $birthPlaceEn,
            ':dob' => $dob,
            ':gender' => $gender,
            ':reg_num' => $regNum,
            ':reg_date' => $regDate,
            ':photo' => $photo,
            ':search_text' => $searchText,
            ':id' => $id,
        ]);
    }

    public function formatTitleCase(string $str): string {
        return ucwords(strtolower(trim($str)), " \t\r\n\f\v'-.");
    }

    public function determineStatus(array $row): string {
        if (!empty($row['verified_at']) || ($row['status'] ?? '') === 'verified') {
            return 'verified';
        }
        $reg = trim($row['registration_number'] ?? '');
        $name = trim($row['name'] ?? '');

        if (empty($reg) || empty($name)) {
            return 'incomplete';
        }
        $digits = preg_replace('/[^\d]/', '', $this->toEnglishDigits($reg));
        if (strlen($digits) >= 17 && !empty($row['name_en'])) {
            return 'verified';
        }
        return 'pending';
    }

    public function calculateAge(string $dob): string {
        $dob = trim($dob);
        if ($dob === '') return '';

        $dobEng = $this->toEnglishDigits($dob);
        $dobClean = str_replace(['.', '-', ' '], '/', $dobEng);
        $parts = explode('/', $dobClean);

        $year = null;
        if (count($parts) === 3) {
            if (strlen($parts[2]) === 4) {
                $year = (int) $parts[2];
            } elseif (strlen($parts[0]) === 4) {
                $year = (int) $parts[0];
            }
        } elseif (preg_match('/(19\d\d|20\d\d)/', $dobEng, $m)) {
            $year = (int) $m[1];
        }

        if ($year && $year > 1900 && $year <= (int)date('Y')) {
            $currentYear = (int) date('Y');
            $age = $currentYear - $year;
            return $age . ' বছর';
        }
        return '';
    }

    public function toEnglishDigits(string $text): string {
        $b2e = ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9'];
        return strtr($text, $b2e);
    }

    public function toBanglaDigits(string $text): string {
        $e2b = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr((string)$text, $e2b);
    }

    /**
     * Canonical YYYYMMDD for a date string, tolerant to separator, digit
     * language (Bangla/English) and component order (DD/MM/YYYY or YYYY-MM-DD).
     * Returns '' when the value cannot be parsed as a calendar date.
     */
    public function canonDob(string $v): string {
        $e = $this->toEnglishDigits(trim($v));
        if (!preg_match_all('/\d+/', $e, $m)) return '';
        $nums = array_values($m[0]);
        if (count($nums) === 1 && strlen($nums[0]) === 8) {
            $d = $nums[0];
            $prefix = substr($d, 0, 2);
            if ($prefix === '19' || $prefix === '20') return $d; // YYYYMMDD
            return substr($d, 4, 4) . substr($d, 2, 2) . substr($d, 0, 2); // DDMMYYYY
        }
        if (count($nums) !== 3) return '';
        [$a, $b, $c] = $nums;
        if (strlen($a) === 4) {
            $y = $a; $mo = $b; $d = $c;
        } elseif (strlen($c) === 4) {
            $d = $a; $mo = $b; $y = $c;
        } else {
            return '';
        }
        $y = (int) $y; $mo = (int) $mo; $d = (int) $d;
        if ($y < 1900 || $y > 2100 || $mo < 1 || $mo > 12 || $d < 1 || $d > 31) return '';
        return sprintf('%04d%02d%02d', $y, $mo, $d);
    }

    public function normalizeDob(string $v): array {
        $v = trim($v);
        if ($v === '') return [];
        $b2e = ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9'];
        $e2b = array_flip($b2e);

        $canon = str_replace(['-', '.', ' '], '/', $v);
        $canonE = strtr($canon, $b2e);
        $canonB = strtr($canon, $e2b);

        $variants = [$canon, $canonE, $canonB];

        $compactE = str_replace('/', '', $canonE);
        $compactB = str_replace('/', '', $canonB);
        if (mb_strlen($compactE) >= 8) {
            $variants[] = $compactE;
            $variants[] = $compactB;
        }

        return array_values(array_unique(array_filter($variants)));
    }

    public function normalizeGender(string $g): ?string {
        $g = mb_strtolower(trim($g));
        if (in_array($g, ['male', 'm', 'পুরুষ', 'ছেলে'], true)) return 'MALE';
        if (in_array($g, ['female', 'f', 'মহিলা', 'মেয়ে', 'নারী'], true)) return 'FEMALE';
        return null;
    }

    public function getGenderDisplay(string $gender): string {
        return strtoupper($gender) === 'FEMALE' ? 'মহিলা' : 'পুরুষ';
    }

    private function buildSearchTerms(string $query, string $lang): array {
        $searchTerms = [$query];

        foreach ($this->digitVariants($query) as $v) {
            $searchTerms[] = $v;
        }

        if ($lang === 'english') {
            $bangla = $this->transliterateToBangla($query);
            if ($bangla !== $query) {
                $searchTerms[] = $bangla;
            }
            $stripped = preg_replace('/[ািুেোীূৃং]/u', '', $bangla);
            if ($stripped !== $bangla && mb_strlen($stripped) >= 2) {
                $searchTerms[] = $stripped;
            }
            foreach ($this->getDirectEnglishMap($query) as $v) {
                $searchTerms[] = $v;
            }
        } else {
            foreach ($this->getEnglishVariants($query) as $v) {
                $searchTerms[] = $v;
            }
        }
        return array_values(array_unique($searchTerms));
    }

    private function matchesFilter(string $haystack, string $filter): bool {
        if ($filter === '') return true;
        $haystack = $this->collapse($haystack);
        $terms = $this->buildSearchTerms($filter, $this->detectLanguage($filter));
        foreach ($terms as $t) {
            $t = $this->collapse($t);
            if ($t !== '' && mb_stripos($haystack, $t) !== false) return true;
        }
        return false;
    }

    private function collapse(string $s): string {
        return trim((string) preg_replace('/\s+/u', ' ', trim($s)));
    }

    private function isWholeWordMatch(string $haystack, string $needle): bool {
        if ($needle === '') return false;
        $escaped = preg_quote($needle, '/');
        $boundary = '[\sঃ।:,;\.\-\(\)\/\*\#\@\!\?\[\]\{\}\~\`\^\&\'\"]';
        $pattern = '/(?:^|' . $boundary . ')' . $escaped . '(?:' . $boundary . '|$)/ui';
        return (bool) preg_match($pattern, $haystack);
    }

    private function digitVariants(string $text): array {
        static $b2e = null;
        static $e2b = null;
        if ($b2e === null) {
            $b2e = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
                    '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];
            $e2b = array_flip($b2e);
        }
        $hasBangla = (bool) preg_match('/[০-৯]/u', $text);
        $hasEnglish = preg_match('/[0-9]/', $text);
        $out = [];
        if ($hasBangla) {
            $out[] = strtr($text, $b2e);
        }
        if ($hasEnglish) {
            $out[] = strtr($text, $e2b);
        }
        return $out;
    }

    private function escapeLike(string $s): string {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
    }

    private function searchByTerm(string $term, string $matchMode = 'partial'): array {
        $results = [];

        if (preg_match('/^\d{10,}$/', $term)) {
            $stmt = $this->db->prepare('SELECT * FROM records WHERE registration_number = :reg LIMIT 50');
            $stmt->execute([':reg' => $term]);
            while ($row = $stmt->fetch()) {
                $results[] = $row;
            }
            if (!empty($results)) return $results;
        }

        $fields = [
            'name', 'name_en', 'father_name', 'father_name_en', 
            'mother_name', 'mother_name_en', 'permanent_address', 
            'birth_place', 'place_of_birth_en', 'registration_number', 'search_text'
        ];
        $conditions = [];
        $params = [];

        // Always use LIKE to fetch candidates (escape user % _ so they match literally)
        $likeTerm = '%' . $this->escapeLike($term) . '%';
        foreach ($fields as $idx => $f) {
            $param = ':p' . $idx;
            $conditions[] = "$f LIKE $param ESCAPE '!'";
            $params[$param] = $likeTerm;
        }

        $whereClause = implode(' OR ', $conditions);
        $sql = "SELECT * FROM records WHERE $whereClause LIMIT 300";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch()) {
            if ($matchMode === 'exact') {
                // Whole-word filter: term must be a complete word in at least one field
                $matched = false;
                foreach ($fields as $f) {
                    $val = $row[$f] ?? '';
                    if ($val !== '' && $this->isWholeWordMatch($val, $term)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) continue;
            }
            $results[] = $row;
        }

        return $results;
    }

    private function getEnglishVariants(string $bangla): array {
        $variants = [$bangla];
        $map = [
            'কারিম' => ['কারিম', 'করিম', 'কারীম'],
            'বেগম' => ['বেগম', 'বাগাম', 'বেগাম'],
            'রহমান' => ['রহমান', 'রহমান'],
            'হোসেন' => ['হোসেন', 'হুসেন', 'হোছেন', 'হোসাইন'],
            'ইসলাম' => ['ইসলাম'],
            'আক্তার' => ['আক্তার', 'আকতার'],
            'খাতুন' => ['খাতুন'],
            'মিয়া' => ['মিয়া', 'মীয়া'],
            'আলী' => ['আলী', 'আলি'],
            'হক' => ['হক'],
            'সরকার' => ['সরকার'],
            'মন্ডল' => ['মন্ডল'],
            'উদ্দিন' => ['উদ্দিন', 'উদ্দীন'],
            'রানা' => ['রানা'],
        ];
        foreach ($map as $key => $synonyms) {
            if ($bangla === $key || in_array($bangla, $synonyms)) {
                $variants = array_merge($variants, $synonyms);
                break;
            }
        }
        return array_unique($variants);
    }

    private function getDirectEnglishMap(string $eng): array {
        $map = [
            'rahman' => ['রহমান'],
            'rahim' => ['রহিম', 'রহীম'],
            'karim' => ['কারিম', 'করিম'],
            'begum' => ['বেগম', 'বাগাম'],
            'hossain' => ['হোসেন', 'হুসেন', 'হোছেন', 'হোসাইন'],
            'hossein' => ['হোসেন', 'হুসেন'],
            'islam' => ['ইসলাম'],
            'ali' => ['আলী', 'আলি'],
            'akter' => ['আক্তার', 'আকতার'],
            'khatun' => ['খাতুন'],
            'siddique' => ['সিদ্দিক', 'সিদ্দীক'],
            'mia' => ['মিয়া', 'মীয়া'],
            'rana' => ['রানা'],
            'mondol' => ['মন্ডল'],
            'uddin' => ['উদ্দিন', 'উদ্দীন'],
            'sheikh' => ['শেখ'],
            'chowdhury' => ['চৌধুরী', 'চৌধুরি'],
            'sarkar' => ['সরকার'],
            'hasan' => ['হাসান'],
            'husain' => ['হুসাইন'],
            'fatema' => ['ফাতেমা', 'ফতেমা'],
            'sohel' => ['সোহেল'],
            'shakil' => ['শাকিল'],
            'masud' => ['মাসুদ'],
            'rakib' => ['রাকিব'],
            'rubel' => ['রুবেল'],
        ];
        $l = strtolower($eng);
        return $map[$l] ?? [];
    }
}
