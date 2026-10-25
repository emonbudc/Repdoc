<?php
declare(strict_types=1);

/**
 * jui.php used to be this tool's own login form (with hardcoded demo
 * credentials). The site now has ONE universal login, so this URL simply
 * forwards there and preserves nothing but the intent.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

redirect(repdoc_url('public/login/'));
