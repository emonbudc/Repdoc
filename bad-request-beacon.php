<?php
declare(strict_types=1);

/**
 * Repdoc — client-side beacon for bad request rows.
 *
 * The 404 page collects extra browser details (screen, timezone, language,
 * navigator.platform, high-entropy UA data, …) and POSTs them here so the
 * admin tracker can show everything possible about a probe. Updates the
 * matching bad_requests row by sig.
 */

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$sig = (string) ($_POST['sig'] ?? $_GET['sig'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $sig)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad sig']);
    exit;
}

$info = (string) ($_POST['info'] ?? '');
$decoded = json_decode($info, true);
if (!is_array($decoded)) $decoded = ['raw' => mb_substr($info, 0, 4000, 'UTF-8')];

// Enrich with server-side identity if the beacon carries a session.
$userId = 0; $fullname = ''; $email = '';
if (!empty($_SESSION['repdoc_user_id'])) {
    $userId = (int) $_SESSION['repdoc_user_id'];
    try {
        $db = repdoc_db();
        if ($db) {
            $st = $db->prepare('SELECT fullname, email FROM users WHERE id = ?');
            $st->execute([$userId]);
            $u = $st->fetch();
            if ($u) { $fullname = (string) ($u['fullname'] ?? ''); $email = (string) ($u['email'] ?? ''); }
        }
    } catch (Throwable $e) {}
}
if ($userId > 0) {
    $decoded['_server_user'] = ['id' => $userId, 'fullname' => $fullname, 'email' => $email];
}

$payload = mb_substr(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '', 0, 12000, 'UTF-8');

$db = repdoc_db();
if (!$db) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'no db']);
    exit;
}

try {
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $st = $db->prepare('UPDATE bad_requests SET client_info = ?, user_id = CASE WHEN user_id = 0 THEN ? ELSE user_id END, user_fullname = CASE WHEN user_fullname = "" THEN ? ELSE user_fullname END, user_email = CASE WHEN user_email = "" THEN ? ELSE user_email END, last_seen = last_seen WHERE sig = ?');
    } else {
        $st = $db->prepare('UPDATE bad_requests SET client_info = ?, user_id = CASE WHEN user_id = 0 THEN ? ELSE user_id END, user_fullname = CASE WHEN user_fullname = "" THEN ? ELSE user_fullname END, user_email = CASE WHEN user_email = "" THEN ? ELSE user_email END WHERE sig = ?');
    }
    $st->execute([$payload, $userId, $fullname, $email, $sig]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'update failed']);
}
