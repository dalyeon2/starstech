<?php
include_once('./_common.php');

if (!defined('G5_IS_ADMIN')) {
    define('G5_IS_ADMIN', true);
}

if (!defined('G5_THEME_URL')) {
    define('G5_THEME_URL', rtrim(G5_URL, '/') . '/theme/sidepanel');
}

if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="/theme/sidepanel/style.css">', 0);
} else {
    echo '<link rel="stylesheet" href="/theme/sidepanel/style.css">' . PHP_EOL;
}

include_once(G5_PATH . '/theme/sidepanel/index.php');
exit;
