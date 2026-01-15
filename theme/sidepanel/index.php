<?php
$theme_path = __DIR__;
$theme_head = $theme_path . '/head.php';
$theme_tail = $theme_path . '/tail.php';

$use_local_theme = !defined('_GNUBOARD_') || (defined('G5_IS_ADMIN') && G5_IS_ADMIN);

if (!$use_local_theme && defined('_GNUBOARD_') && defined('G5_THEME_PATH') && realpath(G5_THEME_PATH) === realpath($theme_path)) {
    include_once(G5_THEME_PATH . '/head.php');
} else {
    include_once($theme_head);
}

$dash_write_prefix = defined('G5_WRITE_PREFIX')
    ? G5_WRITE_PREFIX
    : (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX . 'write_' : 'g5_write_');
$dash_bbs_url = defined('G5_BBS_URL') ? rtrim(G5_BBS_URL, '/') : '/bbs';
$dash_default_thumb = (defined('G5_URL') ? rtrim(G5_URL, '/') : '') . '/img/default-image.jpg';

if (!function_exists('dash_table_exists')) {
    function dash_table_exists($table)
    {
        if (!function_exists('sql_fetch')) {
            return false;
        }
        $escaped = addcslashes($table, '_%');
        if (function_exists('sql_escape_string')) {
            $escaped = sql_escape_string($escaped);
        } else {
            $escaped = addslashes($escaped);
        }
        $row = sql_fetch("SHOW TABLES LIKE '{$escaped}'");
        return is_array($row) && count($row);
    }
}

if (!function_exists('dash_board_list_url')) {
    function dash_board_list_url($bo_table)
    {
        global $dash_bbs_url;
        if (function_exists('get_pretty_url')) {
            return get_pretty_url($bo_table);
        }
        return $dash_bbs_url . '/board.php?bo_table=' . $bo_table;
    }
}

if (!function_exists('dash_board_view_url')) {
    function dash_board_view_url($bo_table, $wr_id)
    {
        global $dash_bbs_url;
        if (function_exists('get_pretty_url')) {
            return get_pretty_url($bo_table, $wr_id);
        }
        return $dash_bbs_url . '/board.php?bo_table=' . $bo_table . '&wr_id=' . $wr_id;
    }
}

if (!function_exists('dash_latest_post_link')) {
    function dash_latest_post_link($bo_table, $write_prefix)
    {
        if (!function_exists('sql_fetch')) {
            return dash_board_list_url($bo_table);
        }
        $table = $write_prefix . $bo_table;
        if (!dash_table_exists($table)) {
            return dash_board_list_url($bo_table);
        }
        $row = sql_fetch("SELECT wr_id FROM `{$table}` WHERE wr_is_comment = 0 ORDER BY wr_datetime DESC LIMIT 1");
        if ($row && isset($row['wr_id'])) {
            return dash_board_view_url($bo_table, (int)$row['wr_id']);
        }
        return dash_board_list_url($bo_table);
    }
}

if (!function_exists('dash_clean_text')) {
    function dash_clean_text($value)
    {
        if (function_exists('get_text')) {
            return get_text($value);
        }
        return strip_tags($value);
    }
}

if (!function_exists('dash_cut_text')) {
    function dash_cut_text($value, $length)
    {
        if (function_exists('cut_str')) {
            return cut_str($value, $length);
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value, 'UTF-8') > $length ? mb_substr($value, 0, $length, 'UTF-8') . '...' : $value;
        }
        return strlen($value) > $length ? substr($value, 0, $length) . '...' : $value;
    }
}

if (!function_exists('dash_youtube_thumb')) {
    function dash_youtube_thumb($url)
    {
        if (!$url) {
            return '';
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $match)) {
            return 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
        }
        return '';
    }
}

if (!function_exists('dash_escape')) {
    function dash_escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}

if (!function_exists('dash_is_image_file')) {
    function dash_is_image_file($name)
    {
        if (!$name) {
            return false;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }
}

if (!function_exists('dash_count_posts')) {
    function dash_count_posts($bo_table, $write_prefix)
    {
        if (!function_exists('sql_fetch')) {
            return 0;
        }
        $table = $write_prefix . $bo_table;
        if (!dash_table_exists($table)) {
            return 0;
        }
        $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE wr_is_comment = 0");
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
}

if (!function_exists('dash_recent_posts')) {
    function dash_recent_posts($boards, $limit, $write_prefix)
    {
        if (!function_exists('sql_query') || !function_exists('sql_fetch_array')) {
            return [];
        }
        $items = [];
        foreach ($boards as $board) {
            $table = $write_prefix . $board['table'];
            if (!dash_table_exists($table)) {
                continue;
            }
            $label = $board['label'];
            $bo_table = $board['table'];
            $sql = "SELECT wr_id, wr_subject, wr_name, wr_datetime, wr_link1 FROM `{$table}` WHERE wr_is_comment = 0 ORDER BY wr_datetime DESC LIMIT " . (int)$limit;
            $result = sql_query($sql);
            if (!$result) {
                continue;
            }
            while ($row = sql_fetch_array($result)) {
                $subject = dash_cut_text(dash_clean_text($row['wr_subject']), 48);
                $items[] = [
                    'bo_table' => $bo_table,
                    'board_label' => $label,
                    'wr_id' => (int)$row['wr_id'],
                    'title' => $subject,
                    'name' => dash_clean_text($row['wr_name'] ?? ''),
                    'date' => $row['wr_datetime'] ? date('Y-m-d', strtotime($row['wr_datetime'])) : '',
                    'wr_link1' => $row['wr_link1'] ?? '',
                    'datetime' => $row['wr_datetime'] ?? '',
                ];
            }
        }
        if (!$items) {
            return [];
        }
        usort($items, function ($a, $b) {
            $a_time = $a['datetime'] ? strtotime($a['datetime']) : 0;
            $b_time = $b['datetime'] ? strtotime($b['datetime']) : 0;
            return $b_time <=> $a_time;
        });
        $items = array_slice($items, 0, $limit);
        foreach ($items as $idx => $item) {
            unset($items[$idx]['datetime']);
        }
        return $items;
    }
}

if (!function_exists('dash_post_thumb')) {
    function dash_post_thumb($bo_table, $wr_id, $width = 120, $height = 90, $item = null)
    {
        global $dash_default_thumb;
        if (function_exists('get_list_thumbnail')) {
            $thumb = get_list_thumbnail($bo_table, $wr_id, $width, $height, true, true);
            if ($thumb && !empty($thumb['src'])) {
                return $thumb['src'];
            }
        }
        if (function_exists('get_file')) {
            $files = get_file($bo_table, $wr_id);
            if (!empty($files) && is_array($files)) {
                foreach ($files as $file) {
                    $fname = $file['file'] ?? ($file['source'] ?? '');
                    if (!$fname || !dash_is_image_file($fname)) {
                        continue;
                    }
                    if (!empty($file['path']) && !empty($file['file'])) {
                        return rtrim($file['path'], '/') . '/' . rawurlencode($file['file']);
                    }
                }
            }
        }
        if (is_array($item) && !empty($item['wr_link1'])) {
            $youtube_thumb = dash_youtube_thumb($item['wr_link1']);
            if ($youtube_thumb) {
                return $youtube_thumb;
            }
        }
        return $dash_default_thumb ?: '/img/default-image.jpg';
    }
}

if (!function_exists('dash_visit_stats')) {
    function dash_visit_stats()
    {
        $today = 0;
        $total = 0;
        if (!function_exists('sql_fetch')) {
            return ['today' => 0, 'total' => 0];
        }
        $prefix = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
        $visit_table = $prefix . 'visit';
        $visit_sum_table = $prefix . 'visit_sum';

        if (dash_table_exists($visit_sum_table)) {
            $row = sql_fetch("SELECT vs_count AS cnt FROM `{$visit_sum_table}` WHERE vs_date = CURDATE()");
            $today = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            $row = sql_fetch("SELECT SUM(vs_count) AS cnt FROM `{$visit_sum_table}`");
            $total = isset($row['cnt']) ? (int)$row['cnt'] : 0;
        } elseif (dash_table_exists($visit_table)) {
            $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$visit_table}` WHERE vi_date = CURDATE()");
            $today = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$visit_table}`");
            $total = isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }

        return ['today' => $today, 'total' => $total];
    }
}

$sections = [
    'products' => [
        'title' => '제품',
        'icon' => 'fa-boxes-stacked',
        'boards' => [
            ['table' => 'eco_st', 'label' => 'ECO-ST', 'icon' => 'fa-snowflake'],
            ['table' => 'eco_sl', 'label' => 'ECO-SL', 'icon' => 'fa-droplet'],
            ['table' => 'labope', 'label' => 'Labope', 'icon' => 'fa-flask'],
            ['table' => 'fertilizer', 'label' => 'Fertilizer', 'icon' => 'fa-seedling'],
        ],
    ],
    'pr' => [
        'title' => '홍보센터',
        'icon' => 'fa-bullhorn',
        'boards' => [
            ['table' => 'news', 'label' => 'Newsroom', 'icon' => 'fa-newspaper'],
            ['table' => 'pr', 'label' => 'PR', 'icon' => 'fa-bullhorn'],
        ],
    ],
    'support' => [
        'title' => '고객지원',
        'icon' => 'fa-headset',
        'boards' => [
            ['table' => 'inquiry', 'label' => 'Contact', 'icon' => 'fa-envelope-open-text'],
        ],
    ],
];

$totals = [
    'products' => 0,
    'pr' => 0,
    'support' => 0,
    'all' => 0,
];

foreach ($sections as $key => $section) {
    $section_total = 0;
    $boards = [];
    foreach ($section['boards'] as $board) {
        $board['count'] = dash_count_posts($board['table'], $dash_write_prefix);
        $board['href'] = dash_board_list_url($board['table']);
        $board['detail_href'] = dash_latest_post_link($board['table'], $dash_write_prefix);
        $section_total += $board['count'];
        $boards[] = $board;
    }
    $sections[$key]['boards'] = $boards;
    $sections[$key]['total'] = $section_total;
    $list_link = $boards ? $boards[0]['href'] : '#';
    $sections[$key]['link'] = $list_link;
    $sections[$key]['recent'] = dash_recent_posts($boards, 4, $dash_write_prefix);
    $detail_link = $list_link;
    if (!empty($sections[$key]['recent'])) {
        $first_item = $sections[$key]['recent'][0];
        $detail_link = dash_board_view_url($first_item['bo_table'], $first_item['wr_id']);
    } elseif (!empty($boards[0]['detail_href'])) {
        $detail_link = $boards[0]['detail_href'];
    }
    $sections[$key]['primary_link'] = in_array($key, ['products', 'pr'], true) ? $detail_link : $list_link;
    $totals[$key] = $section_total;
    $totals['all'] += $section_total;
}

$visit_stats = dash_visit_stats();

$quick_buttons = [
    ['label' => 'ECO-ST', 'icon' => 'fa-snowflake', 'href' => dash_board_list_url('eco_st')],
    ['label' => 'ECO-SL', 'icon' => 'fa-droplet', 'href' => dash_board_list_url('eco_sl')],
    ['label' => 'Labope', 'icon' => 'fa-flask', 'href' => dash_board_list_url('labope')],
    ['label' => 'Fertilizer', 'icon' => 'fa-seedling', 'href' => dash_board_list_url('fertilizer')],
    ['label' => 'Newsroom', 'icon' => 'fa-newspaper', 'href' => dash_board_list_url('news')],
    ['label' => 'PR', 'icon' => 'fa-bullhorn', 'href' => dash_board_list_url('pr')],
    ['label' => 'Contact', 'icon' => 'fa-envelope-open-text', 'href' => dash_board_list_url('inquiry')],
];
?>

<section class="hero dash-hero">
    <div class="hero-left">
        <p class="eyebrow">관리자 대시보드</p>
        <h1>콘텐츠 현황</h1>
        <p>제품, 홍보센터, 고객지원 콘텐츠를 한눈에 확인하세요.</p>
        <div class="quick-links">
            <?php foreach ($quick_buttons as $button) { ?>
                <a class="quick-link" href="<?php echo dash_escape($button['href']); ?>">
                    <i class="fa-solid <?php echo dash_escape($button['icon']); ?>"></i><?php echo dash_escape($button['label']); ?>
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="hero-right">
        <div class="pulse-label">업데이트 <?php echo date('Y.m.d'); ?></div>
        <div class="mini-stats">
            <div class="mini-card accent">
                <span class="label">전체 게시물</span>
                <strong class="value"><?php echo number_format($totals['all']); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">제품</span>
                <strong class="value"><?php echo number_format($totals['products']); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">홍보센터</span>
                <strong class="value"><?php echo number_format($totals['pr']); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">고객지원</span>
                <strong class="value"><?php echo number_format($totals['support']); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">오늘 방문</span>
                <strong class="value"><?php echo number_format($visit_stats['today']); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">누적 방문</span>
                <strong class="value"><?php echo number_format($visit_stats['total']); ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="section dash-overview">
    <div class="section-header">
        <h2>섹션별 현황</h2>
        <span class="small">최근 게시물과 카운트를 확인하세요</span>
    </div>
    <div class="dash-grid">
        <?php foreach ($sections as $key => $section) { ?>
            <article class="dash-card dash-card-<?php echo dash_escape($key); ?>">
                <div class="dash-card-top">
                    <div class="dash-card-icon">
                        <i class="fa-solid <?php echo dash_escape($section['icon']); ?>"></i>
                    </div>
                    <div class="dash-card-meta">
                        <span class="dash-card-label"><?php echo dash_escape($section['title']); ?></span>
                        <strong class="dash-card-count"><?php echo number_format($section['total']); ?></strong>
                    </div>
                    <a class="dash-card-link" href="<?php echo dash_escape($section['primary_link']); ?>">바로가기</a>
                </div>
                <div class="dash-chip-row">
                    <?php foreach ($section['boards'] as $board) { ?>
                        <?php
                        $chip_href = $board['href'];
                        if (in_array($key, ['products', 'pr'], true) && !empty($board['detail_href'])) {
                            $chip_href = $board['detail_href'];
                        }
                        ?>
                        <a class="dash-chip" href="<?php echo dash_escape($chip_href); ?>">
                            <i class="fa-solid <?php echo dash_escape($board['icon']); ?>"></i>
                            <span class="dash-chip-label"><?php echo dash_escape($board['label']); ?></span>
                            <span class="dash-chip-count"><?php echo number_format($board['count']); ?></span>
                        </a>
                    <?php } ?>
                </div>
            </article>
        <?php } ?>
    </div>
</section>

<section class="section dash-recent">
    <div class="section-header">
        <h2>최근 게시물</h2>
        <span class="small">섹션별 최근 게시물을 확인하세요</span>
    </div>
    <div class="dash-recent-grid">
        <?php foreach ($sections as $key => $section) { ?>
            <article class="dash-recent-box">
                <div class="dash-recent-head">
                    <h3><?php echo dash_escape($section['title']); ?></h3>
                    <a class="dash-recent-link" href="<?php echo dash_escape($section['primary_link']); ?>">바로가기</a>
                </div>
                <?php if ($key === 'support') { ?>
                    <?php if ($section['recent']) { ?>
                        <ul class="dash-list">
                            <?php foreach ($section['recent'] as $item) { ?>
                                <li class="dash-item">
                                    <a class="dash-title" href="<?php echo dash_escape(dash_board_view_url($item['bo_table'], $item['wr_id'])); ?>">
                                        <?php echo dash_escape($item['title']); ?>
                                    </a>
                                    <span class="dash-meta"><?php echo dash_escape($item['date']); ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="dash-empty">등록된 문의가 없습니다.</div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="dash-photo-grid">
                        <?php if ($section['recent']) { ?>
                            <?php foreach ($section['recent'] as $item) { ?>
                                <a class="dash-photo-card" href="<?php echo dash_escape(dash_board_view_url($item['bo_table'], $item['wr_id'])); ?>">
                                    <span class="dash-photo-thumb">
                                        <img src="<?php echo dash_escape(dash_post_thumb($item['bo_table'], $item['wr_id'], 120, 90, $item)); ?>" alt="">
                                    </span>
                                    <span class="dash-photo-title"><?php echo dash_escape($item['title']); ?></span>
                                    <span class="dash-photo-meta"><?php echo dash_escape($item['board_label']); ?> · <?php echo dash_escape($item['date']); ?></span>
                                </a>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="dash-empty">등록된 게시물이 없습니다.</div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </article>
        <?php } ?>
    </div>
</section>

<?php
if (!$use_local_theme && defined('_GNUBOARD_') && defined('G5_THEME_PATH') && realpath(G5_THEME_PATH) === realpath($theme_path)) {
    include_once(G5_THEME_PATH . '/tail.php');
} else {
    include_once($theme_tail);
}
?>
