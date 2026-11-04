<?php
declare(strict_types=1);

/**
 * Repdoc — admin plan store (file-backed).
 */

if (!function_exists('repdoc_plan_file')) {
    function repdoc_plan_file(): string
    {
        return dirname(__DIR__) . '/love/plan.json';
    }
}

if (!function_exists('repdoc_plan_text')) {
    function repdoc_plan_text(string $value, int $max): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return mb_substr($value, 0, $max, 'UTF-8');
    }
}

if (!function_exists('repdoc_plan_new_id')) {
    function repdoc_plan_new_id(): string
    {
        return bin2hex(random_bytes(8));
    }
}

if (!function_exists('repdoc_plan_valid_id')) {
    function repdoc_plan_valid_id(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }
}

if (!function_exists('repdoc_plan_normalize_details')) {
    function repdoc_plan_normalize_details($raw): array
    {
        $out = [];
        if (is_array($raw)) {
            foreach ($raw as $d) {
                $d = repdoc_plan_text((string) $d, 300);
                if ($d !== '') $out[] = $d;
            }
        } else {
            foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
                $line = repdoc_plan_text($line, 300);
                if ($line !== '') $out[] = $line;
            }
        }
        return $out;
    }
}

if (!function_exists('repdoc_plan_prepare')) {
    function repdoc_plan_prepare(array $row, bool $newId = false): array
    {
        $id = strtolower(trim((string) ($row['id'] ?? '')));
        if (!repdoc_plan_valid_id($id)) $id = '';

        $title = repdoc_plan_text((string) ($row['title'] ?? ''), 200);
        $summary = repdoc_plan_text((string) ($row['summary'] ?? ''), 500);
        $details = repdoc_plan_normalize_details($row['details'] ?? ($row['details_text'] ?? ''));

        $created = max(0, (int) ($row['created_at'] ?? 0));
        $updated = max(0, (int) ($row['updated_at'] ?? 0));
        if ($created === 0) $created = time();
        if ($updated === 0) $updated = $created;

        if ($id === '') {
            $id = $newId ? repdoc_plan_new_id() : substr(sha1($title . ':' . $summary), 0, 16);
        }

        return [
            'id' => $id,
            'title' => $title,
            'summary' => $summary,
            'details' => $details,
            'created_at' => $created,
            'updated_at' => $updated,
        ];
    }
}

if (!function_exists('repdoc_plan_load_all')) {
    function repdoc_plan_load_all(): array
    {
        $file = repdoc_plan_file();
        if (!is_file($file)) return [];
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return [];
        if (isset($decoded['items']) && is_array($decoded['items'])) $decoded = $decoded['items'];
        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;
            $entry = repdoc_plan_prepare($row);
            if ($entry['title'] === '') continue;
            $out[] = $entry;
        }
        return $out;
    }
}

if (!function_exists('repdoc_plan_export_row')) {
    function repdoc_plan_export_row(array $row): array
    {
        return [
            'id' => (string) $row['id'],
            'title' => (string) $row['title'],
            'summary' => (string) $row['summary'],
            'details' => array_values($row['details'] ?? []),
            'created_at' => (int) $row['created_at'],
            'updated_at' => (int) $row['updated_at'],
        ];
    }
}

if (!function_exists('repdoc_plan_write')) {
    function repdoc_plan_write(array $rows): bool
    {
        $clean = [];
        foreach ($rows as $row) {
            if (is_array($row)) $clean[] = repdoc_plan_export_row($row);
        }
        $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) return false;
        $json .= "\n";
        $file = repdoc_plan_file();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json) === false) { @unlink($tmp); return false; }
        if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
        @chmod($file, 0644);
        return true;
    }
}

if (!function_exists('repdoc_plan_seed_defaults')) {
    function repdoc_plan_seed_defaults(): void
    {
        if (repdoc_plan_load_all() !== []) return;
        $defaults = [
            [
                'title' => 'Prottoyon v2 port',
                'summary' => 'Replace the 41-type prottoyon generator with a 23-type digital-union-style application module.',
                'details' => [
                    'Keep Repdoc login, wallet, admin and UDC access; issue immediately (no approval queue).',
                    'Print-only output: no external PDF library, no download button (window.print()).',
                    'New 17-digit certificate numbers; legacy 11-digit stay valid.',
                    'Reuse document_logs as the application ledger; no second application table.',
                    'Public verification by certificate number; revoked certs show a "বাতিল" watermark.',
                    'Application screens follow the Repdoc homepage design system; printed cert stays digital-union-style.',
                    'Both present and permanent addresses, Bangla + English print variants.',
                ],
            ],
            [
                'title' => 'Ad & generator theming',
                'summary' => 'Unify generator headers/forms in the green theme and standardize ad placements.',
                'details' => [
                    'Green-bordered ad frames across generator tools.',
                    'Responsive birth form ads with desktop rail.',
                    'Shared placement examples in admin ad manager.',
                ],
            ],
        ];
        $rows = [];
        foreach ($defaults as $d) {
            $rows[] = repdoc_plan_prepare($d, true);
        }
        repdoc_plan_write($rows);
    }
}

if (!function_exists('repdoc_plan_add')) {
    function repdoc_plan_add(string $title, string $summary, string $detailsText): bool
    {
        $title = repdoc_plan_text($title, 200);
        if ($title === '') return false;
        $rows = repdoc_plan_load_all();
        array_unshift($rows, repdoc_plan_prepare([
            'title' => $title,
            'summary' => $summary,
            'details' => $detailsText,
        ], true));
        return repdoc_plan_write($rows);
    }
}

if (!function_exists('repdoc_plan_update')) {
    function repdoc_plan_update(string $id, string $title, string $summary, string $detailsText): bool
    {
        if (!repdoc_plan_valid_id($id)) return false;
        $title = repdoc_plan_text($title, 200);
        if ($title === '') return false;
        $rows = repdoc_plan_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $id) continue;
            $row['title'] = $title;
            $row['summary'] = repdoc_plan_text($summary, 500);
            $row['details'] = repdoc_plan_normalize_details($detailsText);
            $row['updated_at'] = time();
            $rows[$i] = repdoc_plan_prepare($row);
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_plan_write($rows);
    }
}

if (!function_exists('repdoc_plan_delete')) {
    function repdoc_plan_delete(string $id): bool
    {
        if (!repdoc_plan_valid_id($id)) return false;
        $rows = repdoc_plan_load_all();
        $kept = array_values(array_filter($rows, static fn(array $r): bool => $r['id'] !== $id));
        if (count($kept) === count($rows)) return false;
        return repdoc_plan_write($kept);
    }
}

if (!function_exists('repdoc_plan_find')) {
    function repdoc_plan_find(string $id): ?array
    {
        if (!repdoc_plan_valid_id($id)) return null;
        foreach (repdoc_plan_load_all() as $row) {
            if ($row['id'] === $id) return $row;
        }
        return null;
    }
}

if (!function_exists('repdoc_plan_render_item')) {
    function repdoc_plan_render_item(array $p): string
    {
        $id = e((string) $p['id']);
        $title = e((string) $p['title']);
        $summary = e((string) $p['summary']);
        $csrf = e(csrf_token());
        $detailsText = implode("\n", $p['details'] ?? []);
        $html = '<div class="cl-plan" data-plan="' . $id . '">';
        $html .= '<div class="cl-plan-view">';
        $html .= '<h3 class="cl-plan-title">' . $title . '</h3>';
        if ($summary !== '') $html .= '<p class="cl-plan-summary">' . $summary . '</p>';
        if (!empty($p['details'])) {
            $html .= '<ul class="cl-details">';
            foreach ($p['details'] as $d) {
                $html .= '<li>' . e((string) $d) . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '<div class="cl-links">';
        $html .= '<button type="button" class="cl-link plan-edit-btn" data-id="' . $id . '">Edit</button>';
        $html .= '<form method="post" action="changelog.php" style="margin:0;display:inline" class="plan-delete-form">';
        $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
        $html .= '<input type="hidden" name="action" value="delete_plan">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<button type="submit" class="cl-link">Delete</button>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        // edit form (hidden)
        $html .= '<form method="post" action="changelog.php" class="plan-edit-form" data-id="' . $id . '" hidden>';
        $html .= '<input type="hidden" name="csrf_token" value="' . $csrf . '">';
        $html .= '<input type="hidden" name="action" value="update_plan">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<input type="text" name="title" required maxlength="200" value="' . $title . '" class="todo-input" placeholder="Title">';
        $html .= '<input type="text" name="summary" maxlength="500" value="' . $summary . '" class="todo-input" placeholder="Summary (optional)">';
        $html .= '<textarea name="details" rows="4" class="todo-input" placeholder="One detail per line">' . e($detailsText) . '</textarea>';
        $html .= '<div class="cl-links">';
        $html .= '<button type="submit" class="btn btn-primary btn-sm">Save</button>';
        $html .= '<button type="button" class="btn btn-secondary btn-sm plan-edit-cancel" data-id="' . $id . '">Cancel</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }
}