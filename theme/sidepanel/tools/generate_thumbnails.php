<?php
// Usage (CLI or web, admin only suggested):
// php theme/sidepanel/tools/generate_thumbnails.php
// or /theme/sidepanel/tools/generate_thumbnails.php?token=YOUR_TOKEN

include_once __DIR__ . '/../../common.php';
include_once G5_LIB_PATH . '/thumbnail.lib.php';

// Limit boards to known gallery tables
$boards = ['company', 'factories', 'warehouse', 'event'];

// Optional access guard (set TOKEN to use via web)
$guard_token = getenv('THUMB_TOKEN') ?: '';
if (PHP_SAPI !== 'cli' && $guard_token) {
    $request_token = isset($_GET['token']) ? $_GET['token'] : '';
    if ($request_token !== $guard_token) {
        http_response_code(403);
        exit('forbidden');
    }
}

$thumb_w = 240;
$thumb_h = 160;
$total = 0;
$created = 0;
$skipped = 0;

foreach ($boards as $bo_table) {
    $write_table = $g5['write_prefix'] . $bo_table;
    $sql = "SELECT wr_id FROM {$write_table} WHERE wr_is_comment = 0";
    $res = sql_query($sql);
    while ($row = sql_fetch_array($res)) {
        $wr_id = (int)$row['wr_id'];
        $total++;
        $thumb = get_list_thumbnail($bo_table, $wr_id, $thumb_w, $thumb_h, true, true);
        if (!empty($thumb['src'])) {
            $created++;
        } else {
            $skipped++;
        }
    }
}

$summary = [
    'boards' => $boards,
    'thumb_size' => "{$thumb_w}x{$thumb_h}",
    'total_checked' => $total,
    'created_or_existing' => $created,
    'skipped' => $skipped
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
