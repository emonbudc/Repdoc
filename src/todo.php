<?php
declare(strict_types=1);

/**
 * Repdoc — admin TODO store (file-backed, like software/important links).
 *
 * Entries live in love/todo.json. Each item: id, task, detail, status
 * ('pending' or 'done'), subtasks (array of {id,text,status}), created_at, updated_at, done_at.
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

if (!function_exists('repdoc_todo_subtask_new_id')) {
    function repdoc_todo_subtask_new_id(): string
    {
        return 'st-' . bin2hex(random_bytes(6));
    }
}

if (!function_exists('repdoc_todo_valid_id')) {
    function repdoc_todo_valid_id(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }
}

if (!function_exists('repdoc_todo_valid_subtask_id')) {
    function repdoc_todo_valid_subtask_id(string $id): bool
    {
        return (bool) preg_match('/^st-[a-f0-9]{12}$/', $id);
    }
}

if (!function_exists('repdoc_todo_normalize_subtasks')) {
    function repdoc_todo_normalize_subtasks($raw): array
    {
        $out = [];
        if (!is_array($raw)) return $out;
        foreach ($raw as $s) {
            if (!is_array($s)) continue;
            $id = strtolower(trim((string) ($s['id'] ?? '')));
            if (!repdoc_todo_valid_subtask_id($id)) $id = repdoc_todo_subtask_new_id();
            $text = repdoc_todo_text((string) ($s['text'] ?? ''), 200);
            if ($text === '') continue;
            $status = strtolower(trim((string) ($s['status'] ?? 'pending')));
            if (!in_array($status, ['pending', 'done'], true)) $status = 'pending';
            $out[] = ['id' => $id, 'text' => $text, 'status' => $status];
        }
        return $out;
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

        $subtasks = repdoc_todo_normalize_subtasks($row['subtasks'] ?? []);

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
            'subtasks' => $subtasks,
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
        $subs = [];
        foreach (($row['subtasks'] ?? []) as $s) {
            if (!is_array($s)) continue;
            $subs[] = [
                'id' => (string) $s['id'],
                'text' => (string) $s['text'],
                'status' => (string) $s['status'],
            ];
        }
        return [
            'id' => (string) $row['id'],
            'task' => (string) $row['task'],
            'detail' => (string) $row['detail'],
            'status' => (string) $row['status'],
            'subtasks' => $subs,
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
        array_unshift($rows, repdoc_todo_prepare(['task' => $task, 'detail' => $detail, 'status' => 'pending', 'subtasks' => []], true));
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
            // toggle all subtasks accordingly
            $subs = [];
            foreach ($row['subtasks'] as $s) {
                $s['status'] = $newStatus;
                $subs[] = $s;
            }
            $rows[$i]['subtasks'] = $subs;
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_todo_write($rows);
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

if (!function_exists('repdoc_todo_find')) {
    function repdoc_todo_find(string $id): ?array
    {
        if (!repdoc_todo_valid_id($id)) return null;
        foreach (repdoc_todo_load_all() as $row) {
            if ($row['id'] === $id) return $row;
        }
        return null;
    }
}

if (!function_exists('repdoc_todo_recompute_status')) {
    function repdoc_todo_recompute_status(array $item): array
    {
        $subs = $item['subtasks'] ?? [];
        if (count($subs) === 0) return $item;
        $done = 0;
        foreach ($subs as $s) {
            if (($s['status'] ?? '') === 'done') $done++;
        }
        if ($done === count($subs)) {
            $item['status'] = 'done';
            $item['done_at'] = time();
        } else {
            $item['status'] = 'pending';
            $item['done_at'] = 0;
        }
        $item['updated_at'] = time();
        return $item;
    }
}

if (!function_exists('repdoc_todo_add_subtask')) {
    function repdoc_todo_add_subtask(string $parentId, string $text): bool
    {
        if (!repdoc_todo_valid_id($parentId)) return false;
        $text = repdoc_todo_text($text, 200);
        if ($text === '') return false;
        $rows = repdoc_todo_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $parentId) continue;
            $subs = $row['subtasks'] ?? [];
            $subs[] = ['id' => repdoc_todo_subtask_new_id(), 'text' => $text, 'status' => 'pending'];
            $row['subtasks'] = $subs;
            $row = repdoc_todo_recompute_status($row);
            $rows[$i] = $row;
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_toggle_subtask')) {
    function repdoc_todo_toggle_subtask(string $parentId, string $subtaskId): bool
    {
        if (!repdoc_todo_valid_id($parentId) || !repdoc_todo_valid_subtask_id($subtaskId)) return false;
        $rows = repdoc_todo_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $parentId) continue;
            $subs = $row['subtasks'] ?? [];
            foreach ($subs as $j => $s) {
                if ($s['id'] !== $subtaskId) continue;
                $subs[$j]['status'] = ($s['status'] === 'done') ? 'pending' : 'done';
                $found = true;
                break;
            }
            if (!$found) return false;
            $row['subtasks'] = $subs;
            $row = repdoc_todo_recompute_status($row);
            $rows[$i] = $row;
            break;
        }
        if (!$found) return false;
        return repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_delete_subtask')) {
    function repdoc_todo_delete_subtask(string $parentId, string $subtaskId): bool
    {
        if (!repdoc_todo_valid_id($parentId) || !repdoc_todo_valid_subtask_id($subtaskId)) return false;
        $rows = repdoc_todo_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $parentId) continue;
            $subs = $row['subtasks'] ?? [];
            $kept = array_values(array_filter($subs, static fn(array $s): bool => $s['id'] !== $subtaskId));
            if (count($kept) === count($subs)) return false;
            $row['subtasks'] = $kept;
            $row = repdoc_todo_recompute_status($row);
            $rows[$i] = $row;
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_todo_write($rows);
    }
}

if (!function_exists('repdoc_todo_progress')) {
    function repdoc_todo_progress(array $item): array
    {
        $subs = $item['subtasks'] ?? [];
        $total = count($subs);
        $done = 0;
        foreach ($subs as $s) {
            if (($s['status'] ?? '') === 'done') $done++;
        }
        if ($total > 0) {
            $pct = (int) round($done / $total * 100);
        } else {
            $pct = ($item['status'] ?? '') === 'done' ? 100 : 0;
            $total = 0;
            $done = ($item['status'] ?? '') === 'done' ? 1 : 0;
            if ($total === 0) { $total = 1; } // avoid div by zero; display 0/1? 
        }
        return ['done' => $done, 'total' => $total, 'pct' => $pct];
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

        $prog = repdoc_todo_progress($t);
        $pct = $prog['pct'];
        $done = $prog['done'];
        $total = $prog['total'];

        $html = '<li class="cl-todo-item' . $doneClass . '" data-id="' . $id . '">';
        $html .= '<span class="cl-todo-status cl-status-' . $status . '">' . $status . '</span>';
        $html .= '<div class="cl-todo-body">';
        $html .= '<span class="cl-todo-task">' . $task . '</span>';
        $html .= $detail;
        // progress bar
        $html .= '<div class="cl-progress-wrap">';
        $html .= '<div class="cl-progress-bar"><div class="cl-progress-fill" style="width:' . $pct . '%"></div></div>';
        $html .= '<span class="cl-progress-text">' . $done . '/' . $total . ' (' . $pct . '%)</span>';
        $html .= '</div>';
        // subtasks toggle
        $subCount = count($t['subtasks'] ?? []);
        $html .= '<button type="button" class="cl-subtask-toggle-btn" data-parent="' . $id . '" aria-expanded="false">';
        $html .= 'Sub-tasks (' . $subCount . ') ▸';
        $html .= '</button>';
        // subtasks (hidden by default)
        $html .= '<div class="cl-subtask-wrap" data-parent="' . $id . '" hidden>';
        $html .= '<ul class="cl-subtask-list" data-parent="' . $id . '">';
        foreach (($t['subtasks'] ?? []) as $s) {
            $sid = e((string) $s['id']);
            $stext = e((string) $s['text']);
            $sstatus = e((string) $s['status']);
            $sdone = $s['status'] === 'done' ? ' is-done' : '';
            $html .= '<li class="cl-subtask-item' . $sdone . '" data-subtask="' . $sid . '">';
            $html .= '<form method="post" action="changelog.php" style="margin:0" class="subtask-toggle-form">';
            $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            $html .= '<input type="hidden" name="action" value="toggle_subtask">';
            $html .= '<input type="hidden" name="parent_id" value="' . $id . '">';
            $html .= '<input type="hidden" name="subtask_id" value="' . $sid . '">';
            $html .= '<button type="submit" class="cl-subtask-check' . ($s['status'] === 'done' ? ' is-checked' : '') . '" aria-label="Toggle subtask">';
            $html .= $s['status'] === 'done' ? '✓' : '';
            $html .= '</button>';
            $html .= '</form>';
            $html .= '<span class="cl-subtask-text">' . $stext . '</span>';
            $html .= '<form method="post" action="changelog.php" style="margin:0" class="subtask-delete-form">';
            $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
            $html .= '<input type="hidden" name="action" value="delete_subtask">';
            $html .= '<input type="hidden" name="parent_id" value="' . $id . '">';
            $html .= '<input type="hidden" name="subtask_id" value="' . $sid . '">';
            $html .= '<button type="submit" class="cl-subtask-del" aria-label="Delete subtask">×</button>';
            $html .= '</form>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        // add subtask form
        $html .= '<form method="post" action="changelog.php" class="subtask-add-form">';
        $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
        $html .= '<input type="hidden" name="action" value="add_subtask">';
        $html .= '<input type="hidden" name="parent_id" value="' . $id . '">';
        $html .= '<input type="text" name="subtask_text" maxlength="200" placeholder="Add sub-task…" class="todo-input cl-subtask-input">';
        $html .= '<button type="submit" class="btn btn-secondary btn-sm">Add</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '<div class="cl-todo-actions">';
        $html .= '<form method="post" action="changelog.php" style="margin:0">';
        $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
        $html .= '<input type="hidden" name="action" value="toggle">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<button type="submit" class="btn btn-secondary btn-sm">' . $toggleLabel . '</button>';
        $html .= '</form>';
        $html .= '<form method="post" action="changelog.php" style="margin:0" class="todo-delete-form">';
        $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
        $html .= '<input type="hidden" name="action" value="delete">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<button type="submit" class="btn btn-secondary btn-sm" aria-label="Delete">';
        $html .= '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-trash"/></svg>';
        $html .= '</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</li>';
        return $html;
    }
}