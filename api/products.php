<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_exit($code, $payload)
{
    if (!headers_sent()) {
        http_response_code($code);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

register_shutdown_function(function () use ($debug) {
    $error = error_get_last();
    if (!$error) return;
    $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'], $fatal_types, true)) return;
    if (headers_sent()) return;
    http_response_code(500);
    $payload = ['success' => false, 'error' => 'fatal_error'];
    if ($debug) {
        $payload['debug'] = $error;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

$allowed_boards = ['eco_st', 'labope', 'fertilizer'];
$board_key = isset($_GET['board']) ? strtolower(trim($_GET['board'])) : '';
if (!$board_key || !in_array($board_key, $allowed_boards, true)) {
    json_exit(400, ['success' => false, 'error' => 'invalid_board']);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if ($limit < 0) $limit = 0;
if ($limit > 200) $limit = 200;
if ($offset < 0) $offset = 0;

$lang = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : '';
$req_id = 0;
if (isset($_GET['id'])) {
    $req_id = (int)$_GET['id'];
} elseif (isset($_GET['wr_id'])) {
    $req_id = (int)$_GET['wr_id'];
}
if ($req_id < 0) $req_id = 0;

if (!defined('G5_DATA_URL')) {
    $common_path = __DIR__ . '/../common.php';
    if (!is_file($common_path)) {
        $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '';
        if ($doc_root !== '') {
            $fallback = $doc_root . '/common.php';
            if (is_file($fallback)) {
                $common_path = $fallback;
            }
        }
    }
    if (isset($common_path) && is_file($common_path)) {
        include_once $common_path;
    }
}

if (!isset($g5) || !is_array($g5) || !isset($g5['write_prefix']) || !function_exists('sql_query') || !function_exists('sql_fetch_array')) {
    $payload = ['success' => false, 'error' => 'gnuboard_unavailable'];
    if ($debug) {
        $payload['debug'] = [
            'common_loaded' => defined('G5_DATA_URL'),
            'has_write_prefix' => (isset($g5) && is_array($g5) && isset($g5['write_prefix'])),
            'has_sql_query' => function_exists('sql_query'),
            'has_sql_fetch_array' => function_exists('sql_fetch_array')
        ];
    }
    json_exit(500, $payload);
}

$bo_table = $board_key;
$board = function_exists('get_board_db') ? get_board_db($bo_table, true) : null;
if (!$board) {
    json_exit(404, ['success' => false, 'error' => 'board_not_found']);
}

function resolve_media_url($file)
{
    if (!empty($file['bf_fileurl'])) {
        return $file['bf_fileurl'];
    }
    if (!empty($file['path']) && !empty($file['file'])) {
        return rtrim($file['path'], '/') . '/' . rawurlencode($file['file']);
    }
    if (!empty($file['href'])) {
        return html_entity_decode($file['href'], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function is_image_file($name)
{
    if (!$name) return false;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
}

function format_date($value)
{
    if (!$value) return '';
    $ts = strtotime($value);
    if (!$ts) return '';
    return date('Y.m.d', $ts);
}

function pick_lang_key($product_data, $requested)
{
    if (!empty($requested) && !empty($product_data['lang'][$requested])) {
        return $requested;
    }
    if (!empty($product_data['lang']['ko'])) {
        return 'ko';
    }
    if (!empty($product_data['lang']) && is_array($product_data['lang'])) {
        $keys = array_keys($product_data['lang']);
        return $keys ? $keys[0] : '';
    }
    return '';
}

function sanitize_features($raw)
{
    if (!is_array($raw)) return [];
    $features = [];
    foreach ($raw as $feature) {
        if (!is_array($feature)) continue;
        $title = trim((string)($feature['title'] ?? ''));
        $items_raw = $feature['items'] ?? [];
        if (!is_array($items_raw)) $items_raw = [];
        $items = [];
        foreach ($items_raw as $item) {
            $item = trim((string)$item);
            if ($item !== '') $items[] = $item;
        }
        if ($title === '' && empty($items)) continue;
        $features[] = ['title' => $title, 'items' => $items];
    }
    return $features;
}

function find_manual_file($files)
{
    if (!is_array($files)) return null;
    foreach ($files as $file) {
        $fname = $file['file'] ?? ($file['source'] ?? '');
        if (!$fname || is_image_file($fname)) continue;
        $src = resolve_media_url($file);
        if ($src) {
            return [
                'src' => $src,
                'name' => $file['source'] ?? $fname
            ];
        }
    }
    return null;
}

$write_table = $g5['write_prefix'] . $bo_table;
$filters = ['wr_is_comment = 0'];
if ($req_id > 0) {
    $filters[] = 'wr_id = ' . $req_id;
}
$where = implode(' AND ', $filters);

$total = null;
$count_sql = "SELECT COUNT(*) AS cnt FROM {$write_table} WHERE {$where}";
$count_res = sql_query($count_sql, false);
if (!$count_res) {
    $payload = ['success' => false, 'error' => 'sql_error'];
    if ($debug && function_exists('sql_error_info')) {
        $payload['debug'] = [
            'stage' => 'count',
            'table' => $write_table,
            'sql' => $count_sql,
            'error' => sql_error_info()
        ];
    }
    json_exit(500, $payload);
}
$cnt_row = sql_fetch_array($count_res);
$total = isset($cnt_row['cnt']) ? (int)$cnt_row['cnt'] : 0;

$sql = "SELECT wr_id, wr_subject, wr_content, wr_datetime FROM {$write_table} WHERE {$where} ORDER BY wr_datetime DESC";
if ($req_id > 0) {
    $sql .= " LIMIT 1";
} elseif ($limit > 0) {
    $sql .= " LIMIT {$offset}, {$limit}";
}

$items = [];
$res = sql_query($sql, false);
if (!$res) {
    $payload = ['success' => false, 'error' => 'sql_error'];
    if ($debug && function_exists('sql_error_info')) {
        $payload['debug'] = [
            'stage' => 'list',
            'table' => $write_table,
            'sql' => $sql,
            'error' => sql_error_info()
        ];
    }
    json_exit(500, $payload);
}

$manual_index_map = ['ko' => 3, 'en' => 4, 'ja' => 5, 'fr' => 6, 'mn' => 7];
$placeholder = defined('G5_URL') ? rtrim(G5_URL, '/') . '/img/default-image.jpg' : '/img/default-image.jpg';

while ($row = sql_fetch_array($res)) {
    $wr_id = (int)$row['wr_id'];
    $product_raw = $row['wr_content'] ?? '';
    $product_data = [];
    if ($product_raw) {
        $decoded = json_decode($product_raw, true);
        if (is_array($decoded)) $product_data = $decoded;
    }

    $lang_key = pick_lang_key($product_data, $lang);
    $lang_data = [];
    if ($lang_key && !empty($product_data['lang'][$lang_key]) && is_array($product_data['lang'][$lang_key])) {
        $lang_data = $product_data['lang'][$lang_key];
    }

    $title = trim((string)($lang_data['title'] ?? ''));
    if ($title === '' && isset($row['wr_subject'])) {
        if (function_exists('get_text')) {
            $title = get_text($row['wr_subject']);
        } else {
            $title = strip_tags($row['wr_subject']);
        }
    }
    $desc = trim((string)($lang_data['desc'] ?? ''));
    $buy = trim((string)($lang_data['buy'] ?? ''));
    $price = trim((string)($lang_data['price'] ?? ''));
    $features = sanitize_features($lang_data['features'] ?? []);

    $files = function_exists('get_file') ? get_file($bo_table, $wr_id) : [];
    $images = [];
    if (is_array($files)) {
        for ($i = 0; $i < 3; $i++) {
            if (!isset($files[$i]) || !is_array($files[$i])) continue;
            $file = $files[$i];
            $fname = $file['file'] ?? ($file['source'] ?? '');
            if (!$fname || !is_image_file($fname)) continue;
            $src = resolve_media_url($file);
            if ($src) $images[] = $src;
        }
    }

    $thumb = $images ? $images[0] : $placeholder;

    $manual = null;
    $manual_idx = $lang_key && isset($manual_index_map[$lang_key]) ? $manual_index_map[$lang_key] : null;
    if ($manual_idx !== null && isset($files[$manual_idx])) {
        $file = $files[$manual_idx];
        $fname = $file['file'] ?? ($file['source'] ?? '');
        if ($fname && !is_image_file($fname)) {
            $src = resolve_media_url($file);
            if ($src) {
                $manual = [
                    'src' => $src,
                    'name' => $file['source'] ?? $fname
                ];
            }
        }
    }
    if (!$manual) {
        $manual = find_manual_file($files);
    }

    $items[] = [
        'id' => $wr_id,
        'board' => $bo_table,
        'title' => $title,
        'desc' => $desc,
        'buy' => $buy,
        'price' => $price,
        'features' => $features,
        'images' => $images,
        'thumb' => $thumb,
        'manual' => $manual,
        'date' => format_date($row['wr_datetime'] ?? ''),
        'datetime' => $row['wr_datetime'] ?? '',
        'lang' => $lang_key
    ];
}

if ($req_id > 0) {
    $item = $items ? $items[0] : null;
    json_exit(200, [
        'success' => true,
        'board' => $bo_table,
        'lang' => $lang,
        'item' => $item
    ]);
}

json_exit(200, [
    'success' => true,
    'board' => $bo_table,
    'lang' => $lang,
    'items' => $items,
    'total' => $total
]);
