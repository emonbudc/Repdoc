<?php
declare(strict_types=1);

// Legacy endpoint kept so old bookmarks still work — delegates to the
// central logout (same session + landing page as every other tool).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

logout_user();
redirect(repdoc_url('public/login/'));
