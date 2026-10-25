<?php
declare(strict_types=1);

// Repdoc — geo API (same pattern as the reference divisions/districts/
// upazilas/unions endpoints): cascading Bangladesh geo as JSON.
require_once dirname(__DIR__, 2) . '/../src/bootstrap.php';
// Public read-only geo data (same as the reference endpoints) — no login needed.
header('Content-Type: application/json; charset=utf-8');

$out = static function (array $rows): void {
    echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
    exit;
};

$db = repdoc_db();
if (!$db) $out([]);

repdoc_seed_geo();

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

try {
    switch ($action) {
        case 'divisions':
            $rows = $db->query('SELECT id, name_bn, name_en FROM geo_divisions ORDER BY name_en')->fetchAll();
            $out($rows);

        case 'districts':
            $div = (int) ($_GET['division_id'] ?? $_POST['division_id'] ?? 0);
            if ($div <= 0) $out([]);
            $st = $db->prepare('SELECT id, name_bn, name_en FROM geo_districts WHERE division_id = ? ORDER BY name_en');
            $st->execute([$div]);
            $out($st->fetchAll());

        case 'upazilas':
            $dis = (int) ($_GET['district_id'] ?? $_POST['district_id'] ?? 0);
            if ($dis <= 0) $out([]);
            $st = $db->prepare('SELECT id, name_bn, name_en FROM geo_upazilas WHERE district_id = ? ORDER BY name_en');
            $st->execute([$dis]);
            $out($st->fetchAll());

        case 'unions':
            $upa = (int) ($_GET['upazila_id'] ?? $_POST['upazila_id'] ?? 0);
            if ($upa <= 0) $out([]);
            $st = $db->prepare('SELECT id, name_bn, name_en FROM geo_unions WHERE upazila_id = ? ORDER BY name_en');
            $st->execute([$upa]);
            $out($st->fetchAll());

        case 'municipalities':
            $dis = (int) ($_GET['district_id'] ?? $_POST['district_id'] ?? 0);
            if ($dis <= 0) $out([]);
            $st = $db->prepare('SELECT id, name_bn, name_en FROM geo_municipalities WHERE district_id = ? ORDER BY name_en');
            $st->execute([$dis]);
            $out($st->fetchAll());

        case 'city-corporations':
            $dis = (int) ($_GET['district_id'] ?? $_POST['district_id'] ?? 0);
            if ($dis <= 0) $out([]);
            $st = $db->prepare('SELECT id, name_bn, name_en FROM geo_city_corporations WHERE district_id = ? ORDER BY name_en');
            $st->execute([$dis]);
            $out($st->fetchAll());

        case 'locate':
            // Find the id chain for saved office names (district + upazila).
            $found = ['division_id' => 0, 'district_id' => 0, 'upazila_id' => 0];
            $dn = trim((string) ($_GET['district'] ?? $_POST['district'] ?? ''));
            $un = trim((string) ($_GET['upazila'] ?? $_POST['upazila'] ?? ''));
            if ($dn !== '') {
                $st = $db->prepare('SELECT id, division_id FROM geo_districts WHERE name_bn = ? LIMIT 1');
                $st->execute([$dn]);
                if ($row = $st->fetch()) {
                    $found['division_id'] = (int) $row['division_id'];
                    $found['district_id'] = (int) $row['id'];
                    if ($un !== '') {
                        $st2 = $db->prepare('SELECT id FROM geo_upazilas WHERE district_id = ? AND name_bn = ? LIMIT 1');
                        $st2->execute([$found['district_id'], $un]);
                        if ($row2 = $st2->fetch()) $found['upazila_id'] = (int) $row2['id'];
                    }
                }
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($found, JSON_UNESCAPED_UNICODE);
            exit;

        default:
            $out([]);
    }
} catch (Throwable $e) {
    $out([]);
}
