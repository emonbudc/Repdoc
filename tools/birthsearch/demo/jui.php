<?php
declare(strict_types=1);

/**
 * Demo tree (second install) — the old login form forwards to the universal login.
 */

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';

redirect(repdoc_url('public/login/'));
