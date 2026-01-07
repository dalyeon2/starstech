<?php
header('Content-Type: text/html; charset=utf-8');

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$root = realpath(__DIR__ . '/..');
if (!$root || !file_exists($root . '/_common.php') || !file_exists($root . '/common.php')) {
    http_response_code(500);
    echo 'Missing _common.php';
    exit;
}

chdir($root);
include_once $root . '/_common.php';

if (!isset($is_admin) || $is_admin !== 'super') {
    http_response_code(403);
    echo 'Super admin only.';
    exit;
}

$run = isset($_GET['run']) && $_GET['run'] === '1';
$targets = ['news', 'pr'];
$columns = ['wr_subject', 'wr_content'];
$charset = 'utf8mb4';
$collation = 'utf8mb4_unicode_ci';
$results = [];
$config_updated = false;
$config_message = '';

if ($run && defined('G5_DB_CHARSET') && G5_DB_CHARSET !== $charset) {
    $config_path = $root . '/config.php';
    if (is_writable($config_path)) {
        $config_raw = file_get_contents($config_path);
        if ($config_raw !== false) {
            $updated = preg_replace(
                "/define\\('G5_DB_CHARSET',\\s*'[^']*'\\);/",
                "define('G5_DB_CHARSET', '{$charset}');",
                $config_raw,
                1,
                $count
            );
            if ($count && $updated !== $config_raw) {
                file_put_contents($config_path, $updated);
                $config_updated = true;
            } else {
                $config_message = 'config.php charset line not updated.';
            }
        } else {
            $config_message = 'config.php read failed.';
        }
    } else {
        $config_message = 'config.php is not writable.';
    }
}

if ($run && function_exists('sql_set_charset') && isset($connect_db)) {
    sql_set_charset($charset, $connect_db);
}

if ($run) {
    foreach ($targets as $board) {
        $table = $g5['write_prefix'] . $board;
        $table_esc = sql_real_escape_string($table);
        $exists = sql_fetch("SHOW TABLES LIKE '{$table_esc}'");
        if (!$exists) {
            $results[] = "[skip] {$table} not found.";
            continue;
        }

        foreach ($columns as $column) {
            $col = sql_fetch("SHOW FULL COLUMNS FROM `{$table}` WHERE Field = '{$column}'");
            if (!$col) {
                $results[] = "[skip] {$table}.{$column} not found.";
                continue;
            }

            $type = $col['Type'];
            $null = ($col['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
            $default = '';
            if (!is_null($col['Default'])) {
                $default_val = sql_real_escape_string($col['Default']);
                $default = "DEFAULT '{$default_val}'";
            } elseif ($col['Null'] === 'YES') {
                $default = '';
            }

            $sql = "ALTER TABLE `{$table}` MODIFY `{$column}` {$type} CHARACTER SET {$charset} COLLATE {$collation} {$null} {$default}";
            sql_query($sql, true);
            $results[] = "[ok] {$table}.{$column} -> {$charset}/{$collation}";
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>Emoji Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        code, pre { font-family: Consolas, monospace; }
        .box { padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa; }
    </style>
</head>
<body>
    <h1>Emoji Setup</h1>
    <p>Targets: <code>news</code>, <code>pr</code> (wr_subject, wr_content)</p>
    <div class="box">
        <?php if (!$run) { ?>
            <p>Run this once:</p>
            <p><code><?php echo esc(G5_URL . '/api/emoji_setup.php?run=1'); ?></code></p>
        <?php } else { ?>
            <p>Done.</p>
            <?php if (defined('G5_DB_CHARSET')) { ?>
                <p>G5_DB_CHARSET: <code><?php echo esc(G5_DB_CHARSET); ?></code><?php echo $config_updated ? ' (updated)' : ''; ?></p>
                <?php if ($config_message) { ?><p><?php echo esc($config_message); ?></p><?php } ?>
            <?php } ?>
            <pre><?php echo esc(implode("\n", $results)); ?></pre>
        <?php } ?>
    </div>
</body>
</html>
