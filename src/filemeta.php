<?php
declare(strict_types=1);

/**
 * Repdoc — per-file descriptions for the file showcase.
 *
 * One row per published file, keyed by format folder + filename
 * (file_descriptions in the central DB). Admin writes them from
 * admin/files.php; the public showcase pages read them here.
 * Missing DB / missing row always degrades to an empty description.
 */

if (!function_exists('repdoc_file_description_key')) {
    function repdoc_file_description_key(string $type, string $name): string
    {
        return strtolower(trim($type)) . ':' . trim($name);
    }
}

if (!function_exists('repdoc_file_descriptions_all')) {
    /** @return array<string,string> keyed by "type:filename". */
    function repdoc_file_descriptions_all(bool $refresh = false): array
    {
        static $cache = null;
        if (!$refresh && is_array($cache)) return $cache;

        $cache = [];
        $db = repdoc_db();
        if (!$db instanceof PDO) return $cache;

        try {
            $rows = $db->query('SELECT file_type, file_name, description FROM file_descriptions');
            foreach ($rows as $row) {
                $key = repdoc_file_description_key((string) $row['file_type'], (string) $row['file_name']);
                $text = trim((string) $row['description']);
                if ($key !== ':' && $text !== '') $cache[$key] = $text;
            }
        } catch (Throwable $e) {
            $cache = [];
        }
        return $cache;
    }
}

if (!function_exists('repdoc_file_description_get')) {
    function repdoc_file_description_get(string $type, string $name): string
    {
        $all = repdoc_file_descriptions_all();
        return $all[repdoc_file_description_key($type, $name)] ?? '';
    }
}

if (!function_exists('repdoc_file_description_save')) {
    /** Upsert a description; an empty string removes the row. */
    function repdoc_file_description_save(string $type, string $name, string $description): bool
    {
        $type = strtolower(trim($type));
        $name = trim($name);
        if ($type === '' || $name === '') return false;

        $db = repdoc_db();
        if (!$db instanceof PDO) return false;

        $description = trim($description);
        try {
            if ($description === '') {
                $del = $db->prepare('DELETE FROM file_descriptions WHERE file_type = ? AND file_name = ?');
                $del->execute([$type, $name]);
                repdoc_file_descriptions_all(true);
                return true;
            }

            $now = date('Y-m-d H:i:s');
            $upd = $db->prepare('UPDATE file_descriptions SET description = ?, updated_at = ? WHERE file_type = ? AND file_name = ?');
            $upd->execute([$description, $now, $type, $name]);
            if ($upd->rowCount() === 0) {
                try {
                    $ins = $db->prepare('INSERT INTO file_descriptions (file_type, file_name, description, updated_at) VALUES (?, ?, ?, ?)');
                    $ins->execute([$type, $name, $description, $now]);
                } catch (Throwable $e) {
                    // Row exists with identical text (MySQL reports 0 changed rows).
                    $upd->execute([$description, $now, $type, $name]);
                }
            }
            repdoc_file_descriptions_all(true);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

/* ── Thumbnails ────────────────────────────────────────────────────
   Stored in /file-thumbs/{type}/{hash}.{jpg|png|gif|webp}. The name is
   a hash of type+filename, so a listing only needs a few file_exists
   checks — no extra DB column, no rename bookkeeping. */

if (!function_exists('repdoc_file_thumb_exts')) {
    function repdoc_file_thumb_exts(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    }
}

if (!function_exists('repdoc_file_thumb_hash')) {
    function repdoc_file_thumb_hash(string $type, string $name): string
    {
        return substr(sha1(strtolower(trim($type)) . ':' . trim($name)), 0, 16);
    }
}

if (!function_exists('repdoc_file_thumb_dir')) {
    function repdoc_file_thumb_dir(string $type): string
    {
        return REPDOC_ROOT . DIRECTORY_SEPARATOR . 'file-thumbs' . DIRECTORY_SEPARATOR . strtolower(trim($type));
    }
}

if (!function_exists('repdoc_file_thumb_path')) {
    /** Absolute path of the current thumbnail, or '' when none exists. */
    function repdoc_file_thumb_path(string $type, string $name): string
    {
        $base = repdoc_file_thumb_dir($type) . DIRECTORY_SEPARATOR . repdoc_file_thumb_hash($type, $name);
        foreach (repdoc_file_thumb_exts() as $ext) {
            if (is_file($base . '.' . $ext)) return $base . '.' . $ext;
        }
        return '';
    }
}

if (!function_exists('repdoc_file_thumb_url')) {
    /** Public URL of the current thumbnail, or '' when none exists. */
    function repdoc_file_thumb_url(string $type, string $name): string
    {
        $path = repdoc_file_thumb_path($type, $name);
        if ($path === '') return '';
        return repdoc_url('file-thumbs/' . strtolower(trim($type)) . '/' . basename($path));
    }
}

if (!function_exists('repdoc_file_thumb_delete')) {
    function repdoc_file_thumb_delete(string $type, string $name): bool
    {
        $ok = true;
        foreach (repdoc_file_thumb_exts() as $ext) {
            $path = repdoc_file_thumb_dir($type) . DIRECTORY_SEPARATOR . repdoc_file_thumb_hash($type, $name) . '.' . $ext;
            if (is_file($path) && !@unlink($path)) $ok = false;
        }
        return $ok;
    }
}

if (!function_exists('repdoc_file_thumb_save')) {
    /**
     * Move an uploaded image into the thumbnail store.
     * @param array $file $_FILES entry.
     * @return array{0:bool,1:string} [ok, error message]
     */
    function repdoc_file_thumb_save(string $type, string $name, array $file, int $maxBytes = 2097152): array
    {
        $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            return [false, 'Thumbnail is too large for the server.'];
        }
        if ($errCode !== UPLOAD_ERR_OK) {
            return [false, 'No thumbnail received.'];
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [false, 'Thumbnail upload failed.'];
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return [false, 'Thumbnail must be under ' . number_format($maxBytes / 1048576, 1) . ' MB.'];
        }
        $info = @getimagesize($tmp);
        $map = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        $ext = $info !== false ? ($map[$info[2]] ?? '') : '';
        if ($ext === '') {
            return [false, 'Thumbnail must be a JPG, PNG, GIF or WebP image.'];
        }

        $dir = repdoc_file_thumb_dir($type);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return [false, 'Thumbnail folder is not writable.'];
        }
        repdoc_file_thumb_delete($type, $name);
        $target = $dir . DIRECTORY_SEPARATOR . repdoc_file_thumb_hash($type, $name) . '.' . $ext;
        if (!@move_uploaded_file($tmp, $target)) {
            return [false, 'Could not store the thumbnail.'];
        }
        @chmod($target, 0644);
        return [true, ''];
    }
}

if (!function_exists('repdoc_file_description_delete')) {
    function repdoc_file_description_delete(string $type, string $name): bool
    {
        $db = repdoc_db();
        if (!$db instanceof PDO) return false;
        try {
            $del = $db->prepare('DELETE FROM file_descriptions WHERE file_type = ? AND file_name = ?');
            $del->execute([strtolower(trim($type)), trim($name)]);
            repdoc_file_descriptions_all(true);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
