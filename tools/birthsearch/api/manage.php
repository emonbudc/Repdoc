<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode(fixUtf8($data), JSON_UNESCAPED_UNICODE);
    exit;
}

function requirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['ok' => false, 'error' => 'POST required'], 405);
    }
}

function requireParam($name, $type = 'string') {
    $val = $_POST[$name] ?? $_GET[$name] ?? '';
    if ($type === 'int') $val = (int) $val;
    if ($type === 'string') $val = trim((string) $val);
    return $val;
}

function escapeLikeParam(string $s): string {
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
}

function isWholeWordMatch(string $haystack, string $needle): bool {
    if ($needle === '') return false;
    $escaped = preg_quote($needle, '/');
    $boundary = '[\sঃ।:,;\.\-\(\)\/\*\#\@\!\?\[\]\{\}\~\`\^\&\'\"]';
    $pattern = '/(?:^|' . $boundary . ')' . $escaped . '(?:' . $boundary . '|$)/ui';
    return (bool) preg_match($pattern, $haystack);
}

switch ($action) {

    // ── List records (for edit/delete) ──
    case 'list':
        $q = requireParam('q');
        $field = requireParam('field');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int) ($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        if ($q !== '') {
            $searchFields = ['name', 'name_en', 'father_name', 'father_name_en', 'mother_name', 'mother_name_en',
                'registration_number', 'ward', 'birth_place', 'permanent_address', 'date_of_birth'];

            $likeTerm = '%' . escapeLikeParam($q) . '%';
            if ($field !== '' && in_array($field, $searchFields, true)) {
                $conditions = ["$field LIKE :q ESCAPE '!'"];
                $params = [':q' => $likeTerm];
            } else {
                $conditions = [];
                $params = [];
                foreach ($searchFields as $idx => $f) {
                    $param = ':p' . $idx;
                    $conditions[] = "$f LIKE $param ESCAPE '!'";
                    $params[$param] = $likeTerm;
                }
            }

            $whereClause = implode(' OR ', $conditions);
            $countSql = "SELECT COUNT(*) FROM records WHERE $whereClause";
            // LIMIT/OFFSET are inlined as ints — bound LIMIT breaks MySQL native prepares.
            $sql = "SELECT * FROM records WHERE $whereClause ORDER BY id DESC LIMIT " . $perPage . " OFFSET " . $offset;

            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            $total = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
            $stmt = $db->prepare('SELECT * FROM records ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
            $stmt->execute();
        }

        $rows = [];
        while ($row = $stmt->fetch()) {
            $row['id'] = (int) $row['id'];
            $row['page_number'] = (int) ($row['page_number'] ?? 0);
            $row['serial'] = (int) ($row['serial'] ?? 0);
            $rows[] = $row;
        }

        jsonResponse([
            'ok' => true,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'results' => $rows,
        ]);
        break;

    // ── Get single record ──
    case 'get':
        $id = requireParam('id', 'int');
        if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'Invalid ID'], 400);

        $row = $engine->getFull($id);
        if (!$row) jsonResponse(['ok' => false, 'error' => 'Record not found'], 404);

        jsonResponse(['ok' => true, 'record' => $row]);
        break;

    // ── Add new record ──
    case 'add':
        requirePost();

        $fields = [
            'registration_number', 'name', 'name_en', 'father_name', 'father_name_en',
            'mother_name', 'mother_name_en', 'date_of_birth', 'gender', 'birth_place',
            'place_of_birth_en', 'permanent_address', 'permanent_address_en',
            'registration_date', 'certificate_date', 'ward', 'page_number', 'serial',
            'source_file', 'status'
        ];

        $values = [];
        foreach ($fields as $f) {
            $values[$f] = requireParam($f);
        }

        if ($values['name'] === '') {
            jsonResponse(['ok' => false, 'error' => 'নাম আবশ্যক'], 400);
        }

        $searchText = trim(implode(' ', array_filter([
            $values['name'], $values['name_en'], $values['father_name'], $values['father_name_en'],
            $values['mother_name'], $values['mother_name_en'], $values['registration_number'],
            $values['date_of_birth'], $values['birth_place'], $values['place_of_birth_en']
        ])));

        $sql = 'INSERT INTO records (registration_number, name, name_en, father_name, father_name_en,
            mother_name, mother_name_en, date_of_birth, gender, birth_place, place_of_birth_en,
            permanent_address, permanent_address_en, registration_date, certificate_date,
            ward, page_number, serial, source_file, status, search_text)
            VALUES (:registration_number, :name, :name_en, :father_name, :father_name_en,
            :mother_name, :mother_name_en, :date_of_birth, :gender, :birth_place, :place_of_birth_en,
            :permanent_address, :permanent_address_en, :registration_date, :certificate_date,
            :ward, :page_number, :serial, :source_file, :status, :search_text)';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':registration_number' => $values['registration_number'],
            ':name' => $values['name'],
            ':name_en' => $values['name_en'],
            ':father_name' => $values['father_name'],
            ':father_name_en' => $values['father_name_en'],
            ':mother_name' => $values['mother_name'],
            ':mother_name_en' => $values['mother_name_en'],
            ':date_of_birth' => $values['date_of_birth'],
            ':gender' => $values['gender'],
            ':birth_place' => $values['birth_place'],
            ':place_of_birth_en' => $values['place_of_birth_en'],
            ':permanent_address' => $values['permanent_address'],
            ':permanent_address_en' => $values['permanent_address_en'],
            ':registration_date' => $values['registration_date'],
            ':certificate_date' => $values['certificate_date'],
            ':ward' => $values['ward'],
            ':page_number' => (int) ($values['page_number'] ?: 0),
            ':serial' => (int) ($values['serial'] ?: 0),
            ':source_file' => $values['source_file'],
            ':status' => $values['status'],
            ':search_text' => $searchText,
        ]);
        $newId = $db->lastInsertId();

        jsonResponse(['ok' => true, 'id' => (int) $newId, 'message' => 'রেকর্ড যোগ হয়েছে']);
        break;

    // ── Update record ──
    case 'update':
        requirePost();

        $id = requireParam('id', 'int');
        if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'Invalid ID'], 400);

        $existing = $engine->getFull($id);
        if (!$existing) jsonResponse(['ok' => false, 'error' => 'Record not found'], 404);

        $fields = [
            'registration_number', 'name', 'name_en', 'father_name', 'father_name_en',
            'mother_name', 'mother_name_en', 'date_of_birth', 'gender', 'birth_place',
            'place_of_birth_en', 'permanent_address', 'permanent_address_en',
            'registration_date', 'certificate_date', 'ward', 'source_file', 'status'
        ];

        $updates = [];
        $params = [':id' => $id];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $updates[] = "$f = :$f";
                $params[":$f"] = trim($_POST[$f]);
            }
        }

        if (isset($_POST['page_number'])) {
            $updates[] = 'page_number = :page_number';
            $params[':page_number'] = (int) $_POST['page_number'];
        }
        if (isset($_POST['serial'])) {
            $updates[] = 'serial = :serial';
            $params[':serial'] = (int) $_POST['serial'];
        }

        if (empty($updates)) {
            jsonResponse(['ok' => false, 'error' => 'কোনো ফিল্ড দেওয়া হয়নি'], 400);
        }

        // Rebuild search_text
        $name = $_POST['name'] ?? $existing['name'] ?? '';
        $nameEn = $_POST['name_en'] ?? $existing['name_en'] ?? '';
        $father = $_POST['father_name'] ?? $existing['father_name'] ?? '';
        $fatherEn = $_POST['father_name_en'] ?? $existing['father_name_en'] ?? '';
        $mother = $_POST['mother_name'] ?? $existing['mother_name'] ?? '';
        $motherEn = $_POST['mother_name_en'] ?? $existing['mother_name_en'] ?? '';
        $regNum = $_POST['registration_number'] ?? $existing['registration_number'] ?? '';
        $dob = $_POST['date_of_birth'] ?? $existing['date_of_birth'] ?? '';
        $bp = $_POST['birth_place'] ?? $existing['birth_place'] ?? '';
        $bpEn = $_POST['place_of_birth_en'] ?? $existing['place_of_birth_en'] ?? '';

        $searchText = trim(implode(' ', array_filter([$name, $nameEn, $father, $fatherEn, $mother, $motherEn, $regNum, $dob, $bp, $bpEn])));
        $updates[] = 'search_text = :search_text';
        $params[':search_text'] = $searchText;

        $sql = 'UPDATE records SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['ok' => true, 'message' => 'রেকর্ড আপডেট হয়েছে']);
        break;

    // ── Delete record ──
    case 'delete':
        requirePost();

        $id = requireParam('id', 'int');
        if ($id <= 0) jsonResponse(['ok' => false, 'error' => 'Invalid ID'], 400);

        $existing = $engine->getFull($id);
        if (!$existing) jsonResponse(['ok' => false, 'error' => 'Record not found'], 404);

        $stmt = $db->prepare('DELETE FROM records WHERE id = :id');
        $stmt->execute([':id' => $id]);

        jsonResponse(['ok' => true, 'message' => 'রেকর্ড মুছে ফেলা হয়েছে']);
        break;

    // ── Find & Replace preview ──
    case 'find':
        $find = requireParam('find');
        $replace = requireParam('replace');
        $field = requireParam('field');
        $matchMode = trim($_GET['match_mode'] ?? $_POST['match_mode'] ?? 'partial');
        if (!in_array($matchMode, ['exact', 'partial'], true)) $matchMode = 'partial';

        if ($find === '') jsonResponse(['ok' => false, 'error' => 'অনুসন্ধানের টেক্সট দিন'], 400);

        $allFields = ['name', 'name_en', 'father_name', 'father_name_en', 'mother_name', 'mother_name_en',
            'registration_number', 'ward', 'birth_place', 'place_of_birth_en',
            'permanent_address', 'permanent_address_en', 'date_of_birth', 'source_file'];

        if ($field !== '' && in_array($field, $allFields, true)) {
            $targetFields = [$field];
        } else {
            $targetFields = $allFields;
        }

        $conditions = [];
        $params = [];
        foreach ($targetFields as $idx => $f) {
            $param = ':q' . $idx;
            $conditions[] = "$f LIKE $param ESCAPE '!'";
            $params[$param] = '%' . escapeLikeParam($find) . '%';
        }
        $whereClause = implode(' OR ', $conditions);

        // Total matches (for the count), preview capped to protect memory/time.
        $countStmt = $db->prepare("SELECT COUNT(*) FROM records WHERE $whereClause");
        $countStmt->execute($params);
        $totalMatches = (int) $countStmt->fetchColumn();

        $previewLimit = 200;
        $sql = "SELECT id, name, name_en, father_name, father_name_en, mother_name, mother_name_en,
            registration_number, ward, birth_place, place_of_birth_en,
            permanent_address, permanent_address_en, date_of_birth, source_file, status
            FROM records WHERE $whereClause ORDER BY id LIMIT $previewLimit";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch()) {
            $fieldChanges = [];
            foreach ($targetFields as $f) {
                $val = $row[$f] ?? '';
                if ($val === '') continue;

                if ($matchMode === 'exact') {
                    if (!isWholeWordMatch($val, $find)) continue;
                } else {
                    if (mb_stripos($val, $find) === false) continue;
                }

                $fieldChanges[] = [
                    'field' => $f,
                    'original' => $val,
                    'replaced' => str_ireplace($find, $replace, $val),
                ];
            }
            if (!empty($fieldChanges)) {
                $results[] = [
                    'id' => (int) $row['id'],
                    'name' => $row['name'] ?? '',
                    'name_en' => $row['name_en'] ?? '',
                    'father_name' => $row['father_name'] ?? '',
                    'reg_num' => $row['registration_number'] ?? '',
                    'ward' => $row['ward'] ?? '',
                    'status' => $row['status'] ?? 'pending',
                    'changes' => $fieldChanges,
                ];
            }
        }

        jsonResponse([
            'ok' => true,
            'find' => $find,
            'replace' => $replace,
            'match_count' => $totalMatches,
            'preview_capped' => $totalMatches > count($results),
            'matches' => $results,
        ]);
        break;

    // ── Find & Replace execute ──
    case 'replace':
        requirePost();

        $find = requireParam('find');
        $replace = requireParam('replace');
        $field = requireParam('field');
        $matchMode = trim($_POST['match_mode'] ?? 'partial');
        if (!in_array($matchMode, ['exact', 'partial'], true)) $matchMode = 'partial';

        if ($find === '') jsonResponse(['ok' => false, 'error' => 'অনুসন্ধানের টেক্সট দিন'], 400);

        $allFields = ['name', 'name_en', 'father_name', 'father_name_en', 'mother_name', 'mother_name_en',
            'registration_number', 'ward', 'birth_place', 'place_of_birth_en',
            'permanent_address', 'permanent_address_en', 'date_of_birth', 'source_file'];

        if ($field !== '' && in_array($field, $allFields, true)) {
            $targetFields = [$field];
        } else {
            $targetFields = $allFields;
        }

        $totalReplaced = 0;
        $affectedIds = [];

        // Fetch candidate IDs using LIKE first
        foreach ($targetFields as $f) {
            $stmt = $db->prepare("SELECT id, $f as val FROM records WHERE $f LIKE :like ESCAPE '!'");
            $stmt->execute([':like' => '%' . escapeLikeParam($find) . '%']);
            $candidateIds = [];
            while ($row = $stmt->fetch()) {
                if ($matchMode === 'exact') {
                    if (!isWholeWordMatch($row['val'] ?? '', $find)) continue;
                }
                $candidateIds[] = (int) $row['id'];
            }

            // Update only whole-word matched records.
            // NOTE: all-positional placeholders — PDO forbids mixing named + positional.
            if (!empty($candidateIds)) {
                $idPlaceholders = implode(',', array_fill(0, count($candidateIds), '?'));
                $stmt = $db->prepare("UPDATE records SET $f = REPLACE($f, ?, ?) WHERE id IN ($idPlaceholders)");
                $stmt->execute(array_merge([$find, $replace], $candidateIds));
                $changed = $stmt->rowCount();
                $totalReplaced += $changed;
            }
        }

        // Rebuild search_text for all affected records.
        // NOTE: SQLite concatenates with ||, MySQL needs CONCAT_WS.
        if ($totalReplaced > 0) {
            if (isMySQL()) {
                $db->exec("UPDATE records SET search_text = TRIM(CONCAT_WS(' ', name, name_en, father_name, father_name_en, mother_name, mother_name_en, registration_number, date_of_birth, birth_place, place_of_birth_en))");
            } else {
                $db->exec("UPDATE records SET search_text = TRIM(COALESCE(name,'') || ' ' || COALESCE(name_en,'') || ' ' || COALESCE(father_name,'') || ' ' || COALESCE(father_name_en,'') || ' ' || COALESCE(mother_name,'') || ' ' || COALESCE(mother_name_en,'') || ' ' || COALESCE(registration_number,'') || ' ' || COALESCE(date_of_birth,'') || ' ' || COALESCE(birth_place,'') || ' ' || COALESCE(place_of_birth_en,''))");
            }
        }

        jsonResponse([
            'ok' => true,
            'replaced' => $totalReplaced,
            'message' => "$totalReplacedটি রেকর্ড আপডেট হয়েছে",
        ]);
        break;

    // ── Stats ──
    case 'stats':
        $total = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
        jsonResponse(['ok' => true, 'total' => $total]);
        break;

    default:
        jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
}
