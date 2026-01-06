<?php
@ini_set('display_errors', '0');
error_reporting(E_ALL);

function json_exit($code, $payload) {
    if (!headers_sent()) {
        http_response_code($code);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        json_exit(500, [
            'success' => false,
            'error'   => 'fatal',
            'detail'  => $error['message'] ?? 'unknown'
        ]);
    }
});

$root          = realpath(__DIR__ . '/..');
$common        = $root ? $root . '/_common.php' : null;
$common_fallback = $root ? $root . '/common.php' : null;

if (!$root || (!$common || !file_exists($common)) && (!$common_fallback || !file_exists($common_fallback))) {
    json_exit(500, ['success' => false, 'error' => 'common_not_found']);
}

$bootstrap_output = '';
if ($root) {
    @chdir($root);
}
ob_start();
if ($common && file_exists($common)) {
    include_once($common);
}
if (!defined('_GNUBOARD_') && $common_fallback && file_exists($common_fallback)) {
    include_once($common_fallback);
}
$bootstrap_output = ob_get_clean();

if (!defined('_GNUBOARD_')) {
    json_exit(500, [
        'success' => false,
        'error'   => 'gnuboard_not_initialized',
        'detail'  => $bootstrap_output ? 'bootstrap_output_supplied' : null
    ]);
}

// Force JSON headers after bootstrap to keep Chrome in JSON view.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!defined('G5_LIB_PATH')) {
    $maybe_lib = $root . '/lib';
    if (is_dir($maybe_lib)) {
        define('G5_LIB_PATH', $maybe_lib);
    }
}
if (!defined('G5_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    if ($host) {
        define('G5_URL', rtrim($scheme . '://' . $host, '/'));
    }
}

if (!defined('G5_LIB_PATH') || !defined('G5_URL')) {
    json_exit(500, ['success' => false, 'error' => 'g5_path_not_defined']);
}

include_once(G5_LIB_PATH . '/thumbnail.lib.php');

if (!isset($g5['write_prefix']) || !defined('G5_BBS_URL')) {
    json_exit(500, ['success' => false, 'error' => 'g5_not_initialized']);
}

$category = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : '';
$category = preg_replace('/[^a-z0-9_-]/', '', $category);

$map = [
    'company'   => 'company',
    'factories' => 'factories',
    'warehouse' => 'warehouse',
    'event'     => 'event',
    'video'     => 'video',
    'video_all' => 'video'
];

if (!$category || !isset($map[$category])) {
    json_exit(400, ['success' => false, 'error' => 'invalid_category']);
}

$bo_table = $map[$category];
$board_row = get_board_db($bo_table, true);
if (!$board_row || empty($board_row['bo_table'])) {
    json_exit(404, ['success' => false, 'error' => 'board_not_found']);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 60;
$limit = max(1, min(200, $limit));

$write_table = $g5['write_prefix'] . $bo_table;
$sql = "SELECT wr_id, wr_subject, wr_content, wr_datetime, wr_hit, wr_link1
        FROM {$write_table}
        WHERE wr_is_comment = 0
        ORDER BY wr_num, wr_reply
        LIMIT {$limit}";
$result = sql_query($sql, false);
if (!$result) {
    $detail = function_exists('sql_error_info') ? sql_error_info() : 'sql_query_failed';
    json_exit(500, ['success' => false, 'error' => 'sql_error', 'detail' => $detail]);
}

$bg_map = [
    'company'   => G5_URL . '/assets/img/bg/bg-gallery-company.jpg',
    'factories' => G5_URL . '/assets/img/bg/bg-gallery-factories.jpg',
    'warehouse' => G5_URL . '/assets/img/bg/bg-gallery-wrehouse.jpg',
    'event'     => G5_URL . '/assets/img/bg/bg-gallery-seminars.jpg',
    'video'     => G5_URL . '/assets/img/bg/bg-gallery-video.jpg',
];

$placeholder = ($bo_table === 'video') ? '' : G5_URL . '/assets/img/gallery/thumbnail01.png';
$items = [];

while ($row = sql_fetch_array($result)) {
    $title = get_text($row['wr_subject']);
    $content = isset($row['wr_content']) ? strip_tags($row['wr_content']) : '';
    $excerpt = $content ? cut_str(preg_replace('/\s+/', ' ', $content), 200, '...') : '';

    $thumb = get_list_thumbnail($bo_table, $row['wr_id'], 1170, 780, false, true);
    $img_src = ($thumb && !empty($thumb['src'])) ? $thumb['src'] : '';

    if ($bo_table === 'video' && !$img_src && !empty($row['wr_link1'])) {
        if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $row['wr_link1'], $m)) {
            $img_src = 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
    }

    $video_src = ($bo_table === 'video') ? trim($row['wr_link1']) : '';
    if ($bo_table === 'video' && !$video_src && function_exists('get_file')) {
        $files = get_file($bo_table, $row['wr_id']);
        if (!empty($files) && is_array($files)) {
            foreach ($files as $f) {
                if (!empty($f['path']) && !empty($f['file'])) {
                    $video_src = rtrim($f['path'], '/') . '/' . rawurlencode($f['file']);
                    break;
                } elseif (!empty($f['href'])) {
                    $video_src = $f['href'];
                    break;
                }
            }
        }
    }

    $items[] = [
        'id'       => (int)$row['wr_id'],
        'title'    => $title,
        'img'      => $img_src ?: $placeholder,
        'desc'     => $excerpt,
        'video'    => ($bo_table === 'video') ? $video_src : '',
        'datetime' => $row['wr_datetime'],
        'hit'      => (int)$row['wr_hit'],
    ];
}

json_exit(200, [
    'success'        => true,
    'category'       => $category,
    'bo_table'       => $bo_table,
    'board_subject'  => $board_row['bo_subject'],
    'bg'             => isset($bg_map[$bo_table]) ? $bg_map[$bo_table] : '',
    'count'          => count($items),
    'items'          => $items,
]);
