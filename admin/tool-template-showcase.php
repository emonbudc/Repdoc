<?php
declare(strict_types=1);

/**
 * Repdoc — admin: control page for one tool.
 *
 * Thin wrapper: the shared registry-driven renderer lives in
 * includes/tool-page.php (price + Active switch, usage stats, ভিজিট সাইট).
 */

$repdoc_tool_slug = 'template-showcase';
require __DIR__ . '/includes/tool-page.php';
