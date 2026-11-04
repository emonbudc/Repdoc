<?php
declare(strict_types=1);

/**
 * Repdoc — admin TODO store (file-backed, like software/important links).
 *
 * Entries live in love/todo.json. Each item: id, task, detail, status
 * ('pending' or 'done'), created_at, updated_at, done_at.
 */

if (!function_exists('repdoc_todo_file')) {
    function repdoc_todo_file(): string
    {
        return dirname(__DIR__) . '/love/todo.json';
    }
}

if (!function_exists('repdoc_todo_text')) {
    function repdoc_todo_text(string $value, int $max): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
        return mb_substr($value, 0, $max, 'UTF-8');
    }
}

if (!function_exists('repdoc_todo_new_id')) {
    function repdoc_todo_new_id(): string
    {
        return bin2hex(random_bytes(8));
    }
}

if (!function_exists('repdoc_todo_valid_id')) {
    function repdoc_todo_valid_id(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }
}

if (!function_exists('repdoc_todo_prepare')) {
    function repdoc_todo_prepare(array $row, bool $newId = false): array
    {
        $id = strtolower(trim((string) ($row['id'] ?? '')));
        if (!repdoc_todo_valid_id($id)) $id = '';

        $task = repdoc_todo_text((string) ($row['task'] ?? ''), 200);
        $detail = repdoc_todo_text((string) ($row['detail'] ?? ''), 500);
        $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
        if (!in_array($status, ['pending', 'done'], true)) $status = 'pending';

        $created = max(0, (int) ($row['created_at'] ?? 0));
        $updated = max(0, (int) ($row['updated_at'] ?? 0));
        $doneAt = max(0, (int) ($row['done_at'] ?? 0));
        if ($created === 0) $created = time();
        if ($updated === 0) $updated = $created;
        if ($status === 'done' && $doneAt === 0) $doneAt = $updated;

        if ($id === '') {
            $id = $newId ? repdoc_todo_new_id() : substr(sha1($task . ':' . $detail), 0, 16);
        }

        return [
            'id' => $id,
            'task' => $task,
            'detail' => $detail,
            'status' => $status,
            'created_at' => $created,
            'updated_at' => $updated,
            'done_at' => $doneAt,
        ];
    }
}

if (!function_exists('repdoc_todo_load_all')) {
    function repdoc_todo_load_all(): array
    {
        $file = repdoc_todo_file();
        if (!is_file($file)) return [];
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return [];
        if (isset($decoded['items']) && is_array($decoded['items'])) $decoded = $decoded['items'];

        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;
            $entry = repdoc_todo_prepare($row);
            if ($entry['task'] === '') continue;
            $out[] = $entry;
        }
        return $out;
    }
}

if (!function_exists('repdoc_todo_export_row')) {
    function repdoc_todo_export_row(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'task' => (string) $row['task'],
            'detail' => (string) $row['detail'],
            'status' => (string) $row['status'],
            'created_at' => (int) $row['created_at'],
            'updated_at' => (int) $row['updated_at'],
            'done_at' => (int) $row['done_at'],
        ];
    }
}

if (!function_exists('repdoc_todo_write')) {
    function repdoc_todo_write(array $rows): bool
    {
        $clean = [];
        foreach ($rows as $row) {
            if (is_array($row)) $clean[] = repdoc_todo_export_row($row);
        }
        $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) return false;
        $json .= "\n";
        $file = repdoc_todo_file();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json) === false) { @unlink($tmp); return false; }
        if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
        @chmod($file, 0644);
        return true;
    }
}

if (!function_exists('repdoc_todo_seed_defaults')) {
    /** Seed default TODO items if the store is empty. */
    function repdoc_todo_seed_defaults(): void
    {
        if (repdoc_todo_load_all() !== []) return;
        $defaults = [
            ['task' => 'Schema & helpers', 'detail' => 'number_sequences table, indexes, office columns, canonical type registry.', 'status' => 'pending'],
            ['task' => 'Pricing & labels', 'detail' => '23 prottoyon types, aliases, homepage/nav/UDC copy.', 'status' => 'pending'],
            ['task' => 'Frontend shell', 'detail' => 'Homepage header/nav/footer, tokens, type picker, form/list/verify styles.', 'status' => 'pending'],
            ['task' => 'Form & submission', 'detail' => 'Validation, uploads, 17-digit number, wallet charge, log insert.', 'status' => 'pending'],
            ['task' => 'Renderer', 'detail' => 'v2 templates, both addresses, Bangla/English, print CSS, QR.', 'status' => 'pending'],
            ['task' => 'Applicant pages', 'detail' => 'List, view, reapply, ownership.', 'status' => 'pending'],
            ['task' => 'Admin & UDC pages', 'detail' => 'Filters, view, print, revoke/restore.', 'status' => 'pending'],
            ['task' => 'Public verification', 'detail' => 'verify/index.php by certificate number.', 'status' => 'pending'],
            ['task' => 'Legacy fallback & migration checks', 'detail' => 'Old 41-type records remain readable.', 'status' => 'pending'],
            ['task' => 'Security & QA pass', 'detail' => 'CSRF, ownership, uploads, no PDF endpoint.', 'status' => 'pending'],
            ['task' => 'php -l on changed files', 'detail' => 'Run syntax checks before deploy.', 'status' => 'pending'],
            ['task' => 'Keep sidebars in sync', 'detail' => 'Update admin/UDC nav when adding pages.', 'status' => 'pending'],
            ['task' => 'Office profile completeness', 'detail' => 'Union code, contacts, logo/stamp before issuing certs.', 'status' => 'pending'],
        ];
        $rows = [];
        foreach ($defaults as $d) {
            $rows[] = repdoc_todo_prepare($d, true);
        }
        repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_add')) {
    function repdoc_todo_add(string $task, string $detail): bool
    {
        $task = repdoc_todo_text($task, 200);
        if ($task === '') return false;
        $rows = repdoc_todo_load_all();
        array_unshift($rows, repdoc_todo_prepare(['task' => $task, 'detail' => $detail, 'status' => 'pending'], true));
        return repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_toggle_done')) {
    function repdoc_todo_toggle_done(string $id): bool
    {
        if (!repdoc_todo_valid_id($id)) return false;
        $rows = repdoc_todo_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $id) continue;
            $newStatus = $row['status'] === 'done' ? 'pending' : 'done';
            $rows[$i]['status'] = $newStatus;
            $rows[$i]['updated_at'] = time();
            $rows[$i]['done_at'] = $newStatus === 'done' ? time() : 0;
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_render_item')) {
    function repdoc_todo_render_item(array $t): string
    {
        $id = e((string) $t['id']);
        $status = e((string) $t['status']);
        $task = e((string) $t['task']);
        $detail = $t['detail'] !== '' ? '<span class="cl-todo-detail">' . e((string) $t['detail']) . '</span>' : '';
        $doneClass = $t['status'] === 'done' ? ' is-done' : '';
        $toggleLabel = $t['status'] === 'done' ? 'Undo' : 'Mark done';
        $csrf = e(csrf_token());
        return '<li class="cl-todo-item' . $doneClass . '" data-id="' . $id . '">'
            . '<span class="cl-todo-status cl-status-' . $status . '">' . $status . '</span>'
            . '<div class="cl-todo-body">'
            . '<span class="cl-todo-task">' . $task . '</span>'
            . $detail
            . '</div>'
            . '<div class="cl-todo-actions">'
            . '<form method="post" action="changelog.php" style="margin:0">'
            . '<input type="hidden" name="csrf_token" value="' . $csrf . '">'
            . '<input type="hidden" name="action" value="toggle">'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn btn-secondary btn-sm">' . $toggleLabel . '</button>'
            . '</form>'
            . '<form method="post" action="changelog.php" style="margin:0" onsubmit="return confirm(\'Delete this TODO?\');">'
            . '<input type="hidden" name="csrf_token" value="' . $csrf . '">'
            . '<input type="hidden" name="action" value="delete">'
            . '<input type="hidden" name="id" value="' . $id . '">'
            . '<button type="submit" class="btn btn-secondary btn-sm" aria-label="Delete">'
            . '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>'
            . '</button>'
            . '</form>'
            . '</div>'
            . '</li>';
    }
}

if (!function_exists('repdoc_todo_delete')) {
    function repdoc_todo_delete(string $id): bool
    {
        if (!repdoc_todo_valid_id($id)) return false;
        $rows = repdoc_todo_load_all();
        $kept = array_values(array_filter($rows, static fn(array $r): bool => $r['id'] !== $id));
        if (count($kept) === count($rows)) return false;
        return repdoc_todo_write($kept);
    }
}
