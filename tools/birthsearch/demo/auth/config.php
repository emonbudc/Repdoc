<?php
declare(strict_types=1);

/**
 * Demo tree (second install) — auth shim.
 *
 * Same as the other demo tree: the duplicated auth pages and birth.db were
 * removed in favour of the central login and /love/ databases.
 */

if (!defined('REPDOC_ROOT')) {
    define('REPDOC_ROOT', dirname(__DIR__, 4)); // demo/auth → … → project root
}
require_once REPDOC_ROOT . '/src/bootstrap.php';
