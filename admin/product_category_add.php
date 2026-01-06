<?php
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

function pc_respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function pc_fail($msg, $status = 400) {
    pc_respond(['ok' => false, 'error' => $msg], $status);
}

$bo_table = 'product';
$write_table = $g5['write_prefix'] . $bo_table;
$tbl_exists = sql_fetch("SHOW TABLES LIKE '" . sql_escape_string($write_table) . "'");
if (!$tbl_exists) {
    pc_fail('product 게시판이 없습니다.', 404);
}

$action = trim($_POST['action'] ?? 'add');
$action = in_array($action, ['delete', 'reorder', 'edit'], true) ? $action : 'add';

if ($action === 'reorder') {
    $order_raw = trim($_POST['order'] ?? '');
    if ($order_raw === '') {
        pc_fail('order 파라미터가 없습니다.');
    }
    $order_arr = array_values(array_filter(array_map('trim', explode('|', $order_raw))));
    if (!$order_arr) pc_fail('순서가 올바르지 않습니다.');
    $wr_num = 0;
    foreach ($order_arr as $slug_item) {
        $wr_num += 1;
        sql_query("
            UPDATE {$write_table}
            SET wr_num = {$wr_num}
            WHERE wr_is_comment=0 AND ca_name='" . sql_escape_string($slug_item) . "'
        ");
    }
    $new_list = implode('|', $order_arr);
    sql_query("UPDATE {$g5['board_table']} SET bo_use_category='1', bo_category_list='" . sql_escape_string($new_list) . "' WHERE bo_table='" . sql_escape_string($bo_table) . "'");
    pc_respond(['ok' => true, 'order' => $order_arr]);
}

$slug = trim($_POST['slug'] ?? '');
if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{1,63}$/i', $slug)) {
    pc_fail('slug는 영문/숫자/-/_ 2~64자로 입력하세요.');
}

if ($action === 'delete') {
    sql_query("DELETE FROM {$write_table} WHERE ca_name='" . sql_escape_string($slug) . "'");

    $board = sql_fetch("SELECT bo_category_list FROM {$g5['board_table']} WHERE bo_table='" . sql_escape_string($bo_table) . "'");
    if ($board) {
        $cat_list = array_filter(array_map('trim', explode('|', $board['bo_category_list'] ?? '')));
        $cat_list = array_values(array_filter($cat_list, function($v) use ($slug){ return $v !== $slug; }));
        $new_list = implode('|', $cat_list);
        sql_query("UPDATE {$g5['board_table']} SET bo_category_list='" . sql_escape_string($new_list) . "' WHERE bo_table='" . sql_escape_string($bo_table) . "'");
    }

    pc_respond(['ok' => true, 'deleted' => $slug]);
}

$names = [
    'ko' => trim($_POST['name_ko'] ?? ''),
    'en' => trim($_POST['name_en'] ?? ''),
    'ja' => trim($_POST['name_ja'] ?? ''),
    'zh' => trim($_POST['name_zh'] ?? ''),
];
if ($names['ko'] === '' || $names['en'] === '') {
    pc_fail('카테고리명(한국어)/카테고리명(영어)은 필수입니다.');
}

$descs = [
    'ko' => trim($_POST['desc_ko'] ?? ''),
    'en' => trim($_POST['desc_en'] ?? ''),
    'ja' => trim($_POST['desc_ja'] ?? ''),
    'zh' => trim($_POST['desc_zh'] ?? ''),
];

$banner = trim($_POST['banner'] ?? '');
$content_img = trim($_POST['content'] ?? '');

$dup = sql_fetch("
    SELECT wr_id
    FROM {$write_table}
    WHERE wr_is_comment = 0
      AND ca_name = '" . sql_escape_string($slug) . "'
      AND (wr_10 LIKE '%\"category_meta\"%' OR wr_10 LIKE '%\"type\":\"category_meta\"%')
    LIMIT 1
");
if ($dup && $action !== 'edit') {
    pc_fail('이미 사용 중인 카테고리 영문 ID입니다.');
}

function pc_next_wr_num($table) {
    $row = sql_fetch("SELECT MIN(wr_num) AS min_wr_num FROM {$table}");
    if (!$row || !isset($row['min_wr_num']) || $row['min_wr_num'] === null) {
        return 0;
    }
    return ((int)$row['min_wr_num']) - 1;
}

$payload = [
    'type' => 'category_meta',
    'names' => $names,
    'descs' => $descs,
    'banner' => $banner,
    'content' => $content_img,
    'content_image' => $content_img,
];
$payload['images'] = ['banner' => $banner, 'content' => $content_img];
$payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$now = G5_TIME_YMDHIS;
$mb_id = $member['mb_id'] ?? '';
$writer = $member['mb_name'] ?? ($member['mb_nick'] ?? '관리자');
$wr_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if ($action === 'edit') {
    sql_query("
        UPDATE {$write_table}
        SET wr_subject = '" . sql_escape_string($names['ko']) . "',
            wr_content = '" . sql_escape_string($payload_json) . "',
            wr_1 = '" . sql_escape_string($names['en']) . "',
            wr_2 = '" . sql_escape_string($names['ja']) . "',
            wr_3 = '" . sql_escape_string($names['zh']) . "',
            wr_4 = '" . sql_escape_string($descs['ko']) . "',
            wr_5 = '" . sql_escape_string($descs['en']) . "',
            wr_10 = '" . sql_escape_string($payload_json) . "',
            wr_last = '{$now}'
        WHERE wr_is_comment=0
          AND ca_name='" . sql_escape_string($slug) . "'
          AND (wr_10 LIKE '%\"type\":\"category_meta\"%' OR wr_10 LIKE '%\"category_meta\"%')
    ");
    pc_respond([
        'ok' => true,
        'item' => [
            'slug' => $slug,
            'names' => $names,
        ],
    ]);
}

$wr_num = pc_next_wr_num($write_table);

$sql = "
    INSERT INTO {$write_table} SET
        wr_num = {$wr_num},
        wr_reply = '',
        wr_parent = 0,
        wr_is_comment = 0,
        wr_comment = 0,
        wr_comment_reply = '',
        ca_name = '" . sql_escape_string($slug) . "',
        wr_option = '',
        wr_subject = '" . sql_escape_string($names['ko']) . "',
        wr_content = '" . sql_escape_string($payload_json) . "',
        wr_seo_title = '',
        wr_link1 = '',
        wr_link2 = '',
        wr_link1_hit = 0,
        wr_link2_hit = 0,
        wr_hit = 0,
        wr_good = 0,
        wr_nogood = 0,
        mb_id = '" . sql_escape_string($mb_id) . "',
        wr_password = '',
        wr_name = '" . sql_escape_string($writer) . "',
        wr_email = '" . sql_escape_string($member['mb_email'] ?? '') . "',
        wr_homepage = '',
        wr_datetime = '{$now}',
        wr_file = 0,
        wr_last = '{$now}',
        wr_ip = '" . sql_escape_string($wr_ip) . "',
        wr_facebook_user = '',
        wr_twitter_user = '',
        wr_1 = '" . sql_escape_string($names['en']) . "',
        wr_2 = '" . sql_escape_string($names['ja']) . "',
        wr_3 = '" . sql_escape_string($names['zh']) . "',
        wr_4 = '" . sql_escape_string($descs['ko']) . "',
        wr_5 = '" . sql_escape_string($descs['en']) . "',
        wr_6 = '',
        wr_7 = '',
        wr_8 = '',
        wr_9 = '',
        wr_10 = '" . sql_escape_string($payload_json) . "'
";

sql_query($sql);
$wr_id = (int)sql_insert_id();
if ($wr_id) {
    sql_query("UPDATE {$write_table} SET wr_parent = {$wr_id} WHERE wr_id = {$wr_id}");
}

$board = sql_fetch("SELECT bo_use_category, bo_category_list FROM {$g5['board_table']} WHERE bo_table='" . sql_escape_string($bo_table) . "'");
if ($board) {
    $cat_list = array_filter(array_map('trim', explode('|', $board['bo_category_list'] ?? '')));
    if (!in_array($slug, $cat_list, true)) {
        $cat_list[] = $slug;
        $new_list = implode('|', $cat_list);
        sql_query("UPDATE {$g5['board_table']} SET bo_use_category='1', bo_category_list='" . sql_escape_string($new_list) . "' WHERE bo_table='" . sql_escape_string($bo_table) . "'");
    } elseif (empty($board['bo_use_category'])) {
        sql_query("UPDATE {$g5['board_table']} SET bo_use_category='1' WHERE bo_table='" . sql_escape_string($bo_table) . "'");
    }
}

pc_respond([
    'ok' => true,
    'item' => [
        'id' => $wr_id,
        'slug' => $slug,
        'names' => $names,
    ],
]);
