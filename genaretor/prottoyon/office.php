<?php
declare(strict_types=1);

/**
 * Repdoc — legacy office settings URL.
 * The page now lives inside the UDC panel.
 */

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';
redirect(repdoc_url('Entrepreneur/panel/office.php'));
