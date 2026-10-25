<?php
declare(strict_types=1);

/**
 * Repdoc — admin: document logs.
 * Every generated birth / NID / smart NID submission, newest first —
 * NID-BRN ref, DOB, photo + signature, and the full submitted info.
 */

require_once __DIR__ . '/includes/layout.php';

$db = repdoc_db();

$allowedTools = [
    'birth_generator' => 'Birth',
    'nid'             => 'NID',
    'smartnid'        => 'Smart NID',
    'cvmaker'         => 'CV',
];
$tool  = (string) ($_GET['tool'] ?? 'all');
if ($tool !== 'all' && !isset($allowedTools[$tool])) $tool = 'all';
$limit = (int) ($_GET['limit'] ?? 100);
$limit = max(10, min(500, $limit));

$rows  = [];
$total = 0;

if ($db) {
    try {
        $sql = 'SELECT d.*, u.fullname, u.email FROM document_logs d
                LEFT JOIN users u ON u.id = d.user_id';
        $params = [];
        if ($tool !== 'all') {
            $sql .= ' WHERE d.tool_key = ?';
            $params[] = $tool;
        }
        $sql .= ' ORDER BY d.id DESC LIMIT ' . $limit;

        $st = $db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
    } catch (Throwable $e) {
        $db = null;
    }
}

if ($db) {
    try {
        if ($tool !== 'all') {
            $st = $db->prepare('SELECT COUNT(*) FROM document_logs WHERE tool_key = ?');
            $st->execute([$tool]);
            $total = (int) $st->fetchColumn();
        } else {
            $total = (int) $db->query('SELECT COUNT(*) FROM document_logs')->fetchColumn();
        }
    } catch (Throwable $e) {
        $total = count($rows);
    }
}

/** Render a stored project-relative file as a thumbnail link (or — when empty). */
function doc_file_cell(string $path): string
{
    if ($path === '') return '—';
    $url = repdoc_url($path);
    return '<a href="' . e($url) . '" target="_blank" rel="noopener">'
         . '<img src="' . e($url) . '" alt="" loading="lazy" style="height:44px;max-width:70px;object-fit:cover;border-radius:6px;border:1px solid var(--border-color);display:block">'
         . '</a>';
}

admin_page_start('Document Logs', 'documents.php');
admin_notice();
?>

<div class="admin-page-head">
    <h1 class="admin-title">Document Logs</h1>
    <p class="admin-sub">Birth, NID &amp; Smart NID submissions — <?= number_format($total) ?> entries total. Newest first.</p>
</div>

<?php if (!$db): ?>
    <div class="alert alert-error" role="alert">Could not connect to the database.</div>
<?php else: ?>

<form class="filter-bar" method="get" action="documents.php">
    <div class="field">
        <label for="tool">Tool</label>
        <select id="tool" name="tool">
            <option value="all"<?= $tool === 'all' ? ' selected' : '' ?>>All tools</option>
            <?php foreach ($allowedTools as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $tool === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="limit">Max entries</label>
        <select id="limit" name="limit">
            <?php foreach ([50, 100, 200, 500] as $n): ?>
                <option value="<?= $n ?>"<?= $limit === $n ? ' selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field" style="flex:0 0 auto">
        <button type="submit" class="btn btn-primary btn-sm">View</button>
    </div>
</form>

<div class="panel">
    <div class="panel-head">Submissions <span class="count">Showing <?= count($rows) ?></span></div>

    <?php if (!$rows): ?>
        <div class="empty-state-sm">No submissions yet. Entries appear here each time birth, NID or Smart NID is generated.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>#</th><th>User</th><th>Tool</th><th>NID / BRN</th><th>DOB</th><th>Photo</th><th>Signature</th><th>সব তথ্য</th><th>Time</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $info = [];
                    if (!empty($row['data'])) {
                        $decoded = json_decode((string) $row['data'], true);
                        if (is_array($decoded)) $info = $decoded;
                    }
                    ?>
                    <tr>
                        <td class="cell-sub"><?= (int) $row['id'] ?></td>
                        <td>
                            <span class="cell-name"><?= e($row['fullname'] ?: ('#' . (int) $row['user_id'])) ?></span>
                            <span class="cell-sub"><?= e((string) $row['email']) ?></span>
                        </td>
                        <td><span class="pill pill-approved"><?= e($allowedTools[$row['tool_key']] ?? $row['tool_key']) ?></span></td>
                        <td class="balance"><?= e((string) $row['ref']) ?: '—' ?></td>
                        <td class="cell-sub"><?= e((string) $row['dob']) ?: '—' ?></td>
                        <td><?= doc_file_cell((string) $row['photo']) ?></td>
                        <td><?= doc_file_cell((string) $row['signature']) ?></td>
                        <td>
                            <?php if ($info): ?>
                                <details>
                                    <summary style="cursor:pointer;color:var(--primary);font-weight:600">দেখুন (<?= count($info) ?>)</summary>
                                    <div class="cell-sub" style="max-width:280px">
                                        <?php foreach ($info as $k => $v): ?>
                                            <div><strong><?= e((string) $k) ?>:</strong> <?= e(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="cell-sub"><?= e((string) $row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php admin_page_end(); ?>
