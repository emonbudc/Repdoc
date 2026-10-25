<?php
declare(strict_types=1);

/**
 * Repdoc — logout.
 * Clears the shared session and returns to the homepage.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

logout_user();
redirect(repdoc_url('public/login/') . '?msg=logged_out');
