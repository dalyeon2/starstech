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

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if ($limit < 0) $limit = 0;
if ($limit > 200) $limit = 200;
if ($offset < 0) $offset = 0;

$lang = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : '';

if (!defined('G5_DATA_URL') && is_file(__DIR__ . '/../common.php')) {
    include_once __DIR__ . '/../common.php';
}

$bo_table = 'pr';

if (!isset($g5['write_prefix']) || !function_exists('sql_query')) {
    json_exit(500, ['success' => false, 'error' => 'gnuboard_unavailable']);
}

$board = function_exists('get_board_db') ? get_board_db($bo_table, true) : null;
if (!$board) {
    json_exit(404, ['success' => false, 'error' => 'board_not_found']);
}

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
$debug_allowed = $debug && isset($is_admin) && $is_admin === 'super';

function normalize_content_text($html, $keep_media = false)
{
    $html = (string)$html;
    if ($html === '') return '';
    if (!$keep_media) {
        $html = preg_replace('/\\[(?:media|image)\\s*:\\s*\\d+\\]/i', '', $html);
    }
    $html = preg_replace('~<\\s*(script|style)[^>]*>.*?<\\s*/\\s*\\1\\s*>~is', '', $html);
    $html = preg_replace('~<\\s*br\\s*/?\\s*>~i', "\n", $html);
    $html = preg_replace('~<\\s*/\\s*(p|div|li|section|article|header|footer|h[1-6])\\s*>~i', "\n\n", $html);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace("/\\n{3,}/", "\n\n", $text);
    return trim($text);
}

function resolve_media_url($file, $placeholder = '')
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
    return $placeholder;
}

function split_caption($raw)
{
    if ($raw === '') return '';
    $parts = explode('||', $raw, 2);
    return trim($parts[0]);
}

function youtube_thumb($url)
{
    if (!$url) return '';
    if (preg_match('~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
    }
    return '';
}

function format_news_date($value)
{
    if (!$value) return '';
    $ts = strtotime($value);
    if (!$ts) return '';
    return date('Y.m.d', $ts);
}

$lang_codes = ['ko', 'en', 'ja', 'fr', 'mn'];
$lang_allowed = [];
if (!empty($board['bo_category_list'])) {
    $raw_categories = array_map('trim', explode('|', $board['bo_category_list']));
    foreach ($raw_categories as $cat) {
        if ($cat === '') continue;
        $lower = strtolower($cat);
        $lang_allowed[$lower] = $cat;
    }
}

if ($lang && (!in_array($lang, $lang_codes, true) || !isset($lang_allowed[$lang]))) {
    $lang = '';
}

$filters = ['wr_is_comment = 0'];
if ($lang) {
    $filters[] = "ca_name = '" . sql_escape_string($lang_allowed[$lang]) . "'";
}
$where = implode(' AND ', $filters);

$write_table = $g5['write_prefix'] . $bo_table;

$total = null;
$count_sql = "SELECT COUNT(*) AS cnt FROM {$write_table} WHERE {$where}";
$count_res = sql_query($count_sql, false);
if (!$count_res) {
    $payload = ['success' => false, 'error' => 'sql_error'];
    if ($debug_allowed) {
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

$sql = "SELECT wr_id, wr_subject, wr_content, wr_datetime, wr_link1, ca_name
        FROM {$write_table}
        WHERE {$where}
        ORDER BY wr_datetime DESC";
if ($limit > 0) {
    $sql .= " LIMIT {$offset}, {$limit}";
}

$items = [];
$res = sql_query($sql, false);
if (!$res) {
    $payload = ['success' => false, 'error' => 'sql_error'];
    if ($debug_allowed) {
        $payload['debug'] = [
            'stage' => 'list',
            'table' => $write_table,
            'sql' => $sql,
            'error' => sql_error_info()
        ];
    }
    json_exit(500, $payload);
}
while ($row = sql_fetch_array($res)) {
    $wr_id = (int)$row['wr_id'];
    $files = function_exists('get_file') ? get_file($bo_table, $wr_id) : [];
    $images = [];
    $thumb = '';

    if (is_array($files)) {
        foreach ($files as $file) {
            if (!is_array($file) || empty($file['file'])) continue;
            $is_image = !empty($file['image_type']) || preg_match('/\\.(jpe?g|png|gif|webp)$/i', $file['file']);
            if (!$is_image) continue;
            $src = resolve_media_url($file);
            if (!$src) continue;
            if ($thumb === '') $thumb = $src;
            $caption = '';
            if (!empty($file['bf_content'])) {
                $caption = split_caption($file['bf_content']);
            }
            $entry = ['src' => $src];
            if ($caption !== '') {
                $entry['caption'] = $caption;
            }
            $images[] = $entry;
        }
    }

    $link = trim($row['wr_link1'] ?? '');
    $youtube_thumb = $link ? youtube_thumb($link) : '';
    $is_youtube = $youtube_thumb !== '';

    if ($thumb === '' && $youtube_thumb) {
        $thumb = $youtube_thumb;
    }

    $type = $is_youtube ? 'pr' : 'notice';

    $raw_content = $row['wr_content'] ?? '';
    $item = [
        'id' => $bo_table . '-' . $wr_id,
        'title' => get_text($row['wr_subject'] ?? ''),
        'date' => format_news_date($row['wr_datetime'] ?? ''),
        'datetime' => $row['wr_datetime'] ?? '',
        'thumb' => $thumb,
        'images' => $images,
        'content' => normalize_content_text($raw_content),
        'content_media' => normalize_content_text($raw_content, true),
        'type' => $type,
        'label' => $type === 'notice' ? '공지사항' : '홍보자료'
    ];

    if ($link !== '') {
        $item['link'] = $link;
    }

    $items[] = $item;
}

json_exit(200, [
    'success' => true,
    'items' => $items,
    'total' => $total
]);
