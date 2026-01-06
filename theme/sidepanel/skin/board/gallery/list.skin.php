<?php
if (!defined('_GNUBOARD_')) exit;

if (function_exists('add_stylesheet')) {
    $sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/skin/board/gallery/style.css">', 1);
}

$sidepanel_gallery_placeholder = '';
$sidepanel_gallery_readonly = ['gallery', 'video_all'];
$sidepanel_gallery_allow_write = $write_href && !in_array($bo_table, $sidepanel_gallery_readonly, true);
$sidepanel_is_pr = isset($bo_table) && $bo_table === 'pr';
$sidepanel_lang_codes = ['ko', 'en', 'ja', 'fr', 'mn'];
$sidepanel_use_lang_filter = false;
if (!empty($board['bo_category_list'])) {
    $raw_categories = array_map('trim', explode('|', $board['bo_category_list']));
    foreach ($raw_categories as $cat) {
        if ($cat === '') continue;
        if (in_array(strtolower($cat), $sidepanel_lang_codes, true)) {
            $sidepanel_use_lang_filter = true;
            break;
        }
    }
}

if (!function_exists('sidepanel_filter_lang_categories')) {
    function sidepanel_filter_lang_categories($category_option, $allowed) {
        if (!$category_option) return $category_option;
        $allowed_map = array_fill_keys($allowed, true);
        $items = [];
        if (preg_match_all('~<li[^>]*>.*?</li>~is', $category_option, $matches)) {
            foreach ($matches[0] as $li) {
                if (!preg_match('~>([^<]+)</a>~i', $li, $m)) continue;
                $label = trim(strip_tags($m[1]));
                $lower = strtolower($label);
                if ($label === '전체' || isset($allowed_map[$lower])) {
                    $items[] = $li;
                }
            }
        }
        return $items ? implode("\n", $items) : $category_option;
    }
}

if (!function_exists('sidepanel_gallery_is_video_ext')) {
    function sidepanel_gallery_is_video_ext($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4','webm','mkv','mov','avi','m4v','ogv'], true);
    }
}

if (!function_exists('sidepanel_gallery_youtube_thumb')) {
    function sidepanel_gallery_youtube_thumb($url) {
        if (!$url) return '';
        if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
        return '';
    }
}

if (!function_exists('sidepanel_gallery_existing_thumb')) {
    function sidepanel_gallery_existing_thumb($dir, $filename) {
        if (!$filename) return '';
        // If the file itself is already a generated thumb, just return it.
        if (preg_match('/^thumb-.*?_\\d+x\\d+\\.(jpe?g|png|gif|webp)$/i', $filename)) {
            return rtrim($dir, '/') . '/' . rawurlencode($filename);
        }
        $dot = strrpos($filename, '.');
        if ($dot === false) return '';
        $name = substr($filename, 0, $dot);
        $ext  = substr($filename, $dot + 1);
        $dir = rtrim($dir, '/');
        $candidates = [
            'thumb-' . $name . '_1170x780.' . $ext,
            'thumb-' . $name . '_1170x780.' . strtolower($ext)
        ];

        $fs_dir = '';
        if (defined('G5_URL') && strpos($dir, G5_URL) === 0 && defined('G5_PATH')) {
            $fs_dir = rtrim(str_replace(G5_URL, G5_PATH, $dir), '/');
        }

        foreach ($candidates as $cand) {
            $url = $dir . '/' . rawurlencode($cand);
            if ($fs_dir) {
                if (@is_file($fs_dir . '/' . $cand)) {
                    return $url;
                }
            } else {
                return $url;
            }
        }

        // If prefixed thumb not found, try original filename (without thumb- or size suffix) when accessible.
        if ($fs_dir && stripos($filename, 'thumb-') === 0) {
            $without_prefix = substr($filename, 6);
            if ($without_prefix && @is_file($fs_dir . '/' . $without_prefix)) {
                return $dir . '/' . rawurlencode($without_prefix);
            }
        }

        return $dir . '/' . rawurlencode($candidates[0]);
    }
}

function sidepanel_gallery_thumb($bo_table, $wr_id, $item = null) {
    global $sidepanel_gallery_placeholder;

    $table = $bo_table;
    if (is_array($item) && !empty($item['board'])) {
        $table = $item['board'];
    }
    $is_video_table = ($table === 'video');

    // For video board: use the first non-video uploaded image directly, no thumb prefix/generation.
    if ($is_video_table && function_exists('get_file')) {
        $files = get_file($table, $wr_id);
        if (!empty($files) && is_array($files)) {
            foreach ($files as $f) {
                $fname = $f['file'] ?? ($f['source'] ?? '');
                if ($fname && sidepanel_gallery_is_video_ext($fname)) continue;
                if (!empty($f['path']) && !empty($f['file'])) {
                    return rtrim($f['path'], '/') . '/' . rawurlencode($fname);
                }
            }
        }
        return $sidepanel_gallery_placeholder;
    }

    if (function_exists('get_file')) {
        $files = get_file($table, $wr_id);
        if (!empty($files) && is_array($files)) {
            foreach ($files as $f) {
                $fname = $f['file'] ?? ($f['source'] ?? '');
                if ($fname && sidepanel_gallery_is_video_ext($fname)) continue;
                if (!empty($f['path']) && !empty($f['file'])) {
                    $pre_thumb = sidepanel_gallery_existing_thumb($f['path'], $fname);
                    if ($pre_thumb) return $pre_thumb;
                    if ($is_video_table) {
                        $direct = rtrim($f['path'], '/') . '/' . rawurlencode($fname);
                        if ($direct) return $direct;
                    }
                }
            }
        }
    }

    if (function_exists('get_list_thumbnail')) {
        $thumb = get_list_thumbnail($table, $wr_id, 240, 160, true, true);
        if ($thumb && !empty($thumb['src'])) {
            return $thumb['src'];
        }
    }

    if (function_exists('get_file')) {
        $files = get_file($table, $wr_id);
        if (!empty($files) && is_array($files)) {
            foreach ($files as $idx => $f) {
                $fname = $f['file'] ?? ($f['source'] ?? '');
                if ($fname && sidepanel_gallery_is_video_ext($fname)) continue;
                if (!empty($f['path']) && !empty($f['file'])) {
                    $filepath = rtrim($f['path'], '/');
                    $tname = function_exists('thumbnail') ? thumbnail($fname, $filepath, $filepath, 240, 160, true, true, 'center', true, '80/0.5/3') : '';
                    if ($tname) {
                        return $filepath . '/' . rawurlencode($tname);
                    }
                }
            }
        }
    }

    return $sidepanel_gallery_placeholder;
}

function sidepanel_gallery_excerpt($item) {
    static $cache = [];
    global $g5, $bo_table;
    if (empty($item['wr_id'])) return '';
    $cacheKey = (isset($item['board']) ? $item['board'] . ':' : '') . $item['wr_id'];
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];

    $raw = '';
    if (!empty($item['wr_content'])) {
        $raw = $item['wr_content'];
    } else {
        if (!empty($g5['write_prefix']) && function_exists('sql_fetch')) {
            $target_table = !empty($item['board']) ? $item['board'] : $bo_table;
            $write_table = $g5['write_prefix'] . $target_table;
            $wr_id = (int)$item['wr_id'];
            $row = sql_fetch("SELECT wr_content FROM {$write_table} WHERE wr_id = {$wr_id}");
            if ($row && isset($row['wr_content'])) {
                $raw = $row['wr_content'];
            }
        }
    }
    $text = cut_str(strip_tags($raw), 200, '...');
    return $cache[$cacheKey] = $text;
}

$sidepanel_gallery_sources_photo = ['company', 'factories', 'warehouse', 'event'];
$sidepanel_gallery_sources_video = ['video'];

if (in_array($bo_table, ['gallery', 'video_all'], true)) {
    $sources = $bo_table === 'gallery' ? $sidepanel_gallery_sources_photo : $sidepanel_gallery_sources_video;
    $page_rows = isset($board['bo_page_rows']) ? (int)$board['bo_page_rows'] : 15;
    $page_rows = $page_rows > 0 ? $page_rows : 15;
    $page = isset($page) && $page > 0 ? (int)$page : 1;
    $total_count = 0;
    $sidepanel_total_override = 0;
    $list = [];

    if (!empty($g5['board_table']) && function_exists('sql_fetch') && function_exists('sql_query')) {
        $union_sql = [];
        foreach ($sources as $src) {
            $write_table = $g5['write_prefix'] . $src;
            $cnt_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_is_comment = 0");
            $total_count += (int)($cnt_row['cnt'] ?? 0);
            $union_sql[] = "SELECT '{$src}' AS board, wr_id, wr_subject, wr_name, wr_datetime, wr_hit, wr_content, wr_link1 FROM {$write_table} WHERE wr_is_comment = 0";
        }

        if (!empty($union_sql)) {
            $sidepanel_total_override = $total_count;
            $total_page = $page_rows ? (int)ceil($total_count / $page_rows) : 1;
            $page = max(1, min($page, max($total_page, 1)));
            $from = ($page - 1) * $page_rows;
            if (function_exists('get_paging')) {
                $write_pages_count = isset($config['cf_write_pages']) ? (int)$config['cf_write_pages'] : 10;
                $base = $_SERVER['PHP_SELF'] . '?bo_table=' . urlencode($bo_table) . '&page=';
                $write_pages = get_paging($write_pages_count, $page, $total_page, $base);
            }
            $union_body = implode(' UNION ALL ', $union_sql);
            $sql = "SELECT * FROM ({$union_body}) AS combined ORDER BY wr_datetime DESC LIMIT {$from}, {$page_rows}";
            $res = sql_query($sql);
            while ($row = sql_fetch_array($res)) {
                $list[] = [
                    'board' => $row['board'],
                    'wr_id' => $row['wr_id'],
                    'subject' => $row['wr_subject'],
                    'wr_name' => $row['wr_name'],
                    'wr_datetime' => $row['wr_datetime'],
                    'wr_hit' => $row['wr_hit'],
                    'wr_content' => $row['wr_content'],
                    'wr_link1' => $row['wr_link1'],
                    'href' => get_pretty_url($row['board'], $row['wr_id'])
                ];
            }

            if (!$list && $total_count > 0) {
                $sql = "SELECT * FROM ({$union_body}) AS combined ORDER BY wr_datetime DESC LIMIT {$page_rows}";
                $res = sql_query($sql);
                while ($row = sql_fetch_array($res)) {
                    $list[] = [
                        'board' => $row['board'],
                        'wr_id' => $row['wr_id'],
                        'subject' => $row['wr_subject'],
                        'wr_name' => $row['wr_name'],
                        'wr_datetime' => $row['wr_datetime'],
                        'wr_hit' => $row['wr_hit'],
                        'wr_content' => $row['wr_content'],
                        'wr_link1' => $row['wr_link1'],
                        'href' => get_pretty_url($row['board'], $row['wr_id'])
                    ];
                }
            }

            if (!$list && $total_count > 0) {
                $legacy = [];
                foreach ($sources as $src) {
                    $write_table = $g5['write_prefix'] . $src;
                    $res = sql_query("SELECT wr_id, wr_subject, wr_name, wr_datetime, wr_hit, wr_content, wr_link1 FROM {$write_table} WHERE wr_is_comment = 0 ORDER BY wr_datetime DESC LIMIT 300");
                    while ($row = sql_fetch_array($res)) {
                        $legacy[] = [
                            'board' => $src,
                            'wr_id' => $row['wr_id'],
                            'subject' => $row['wr_subject'],
                            'wr_name' => $row['wr_name'],
                            'wr_datetime' => $row['wr_datetime'],
                            'wr_hit' => $row['wr_hit'],
                            'wr_content' => $row['wr_content'],
                            'wr_link1' => $row['wr_link1'],
                            'href' => get_pretty_url($src, $row['wr_id'])
                        ];
                    }
                }
                usort($legacy, function ($a, $b) {
                    $at = strtotime($a['wr_datetime']);
                    $bt = strtotime($b['wr_datetime']);
                    if ($at === $bt) return 0;
                    return $at > $bt ? -1 : 1;
                });
                $list = array_slice($legacy, 0, $page_rows);
            }
        }
    }
}
?>

<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/skin/board/gallery/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $sidepanel_theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('theme-sidepanel');});</script>

<form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" method="post"<?php echo $is_checkbox ? ' onsubmit="return fboardlist_submit(this);"' : ''; ?>>
<input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
<input type="hidden" name="stx" value="<?php echo $stx; ?>">
<input type="hidden" name="spt" value="<?php echo $spt; ?>">
<input type="hidden" name="sca" value="<?php echo $sca; ?>">
<input type="hidden" name="sst" value="<?php echo $sst; ?>">
<input type="hidden" name="sod" value="<?php echo $sod; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="sw" value="">

<div class="section board">
    <div class="section-header">
        <h2><?php echo $board['bo_subject']; ?></h2>
    </div>
    <div class="search-row">
        <div class="meta-count">
            <span>Total <?php echo number_format(isset($sidepanel_total_override) && $sidepanel_total_override ? $sidepanel_total_override : ($total_count ?? 0)); ?></span>
            <?php if ($is_checkbox) { ?>
            <label class="card-check check-all list-check-all" for="chkall">
                <input type="checkbox" id="chkall">
                <span class="box"></span>
                <span class="check-all-text">전체 선택</span>
            </label>
            <?php } ?>
        </div>
        <div class="top-actions">
            <?php if ($sidepanel_gallery_allow_write) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-trash"></i> 선택삭제</button><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-copy"></i> 선택복사</button><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-arrow-right-arrow-left"></i> 선택이동</button><?php } ?>
            <?php if (isset($is_admin) && $is_admin === 'super') { ?><a class="btn-minimal" href="/adm/config_form.php"><i class="fa-solid fa-sliders"></i> 환경설정</a><?php } ?>
        </div>
    </div>

    <?php if ($is_category) { ?>
        <nav id="bo_cate" class="category-tabs" aria-label="Category">
            <ul id="bo_cate_ul" class="category-list">
                <?php echo $sidepanel_use_lang_filter ? sidepanel_filter_lang_categories($category_option, $sidepanel_lang_codes) : $category_option; ?>
            </ul>
        </nav>
    <?php } ?>

    <ul class="card-grid">
        <?php if ($list && count($list)) { $i = 0; ?>
            <?php foreach ($list as $item) { 
                $thumb = sidepanel_gallery_thumb($bo_table, $item['wr_id'], $item);
                $thumb_src = $thumb ?: $sidepanel_gallery_placeholder;
                $link_href = $item['href'];
                $link_attrs = '';
                if ($sidepanel_is_pr && !empty($item['wr_link1'])) {
                    $link_href = $item['wr_link1'];
                    $link_attrs = ' target="_blank" rel="noopener"';
                }
            ?>
                <li class="card">
                    <?php if ($is_checkbox) { ?>
                        <label class="card-check" for="chk_wr_id_<?php echo $i; ?>">
                            <input type="checkbox" name="chk_wr_id[]" value="<?php echo $item['wr_id']; ?>" id="chk_wr_id_<?php echo $i; ?>">
                            <span class="box"></span>
                        </label>
                    <?php } ?>
                    <a class="thumb" href="<?php echo $link_href; ?>"<?php echo $link_attrs; ?>>
                        <img src="<?php echo $thumb_src; ?>" loading="lazy" alt="" onerror="this.onerror=null;this.style.display='none';">
                    </a>
                    <div class="card-body">
                        <a class="title" href="<?php echo $link_href; ?>"<?php echo $link_attrs; ?>>
                            <?php echo $item['subject']; ?>
                        </a>
                        <p class="excerpt"><?php echo sidepanel_gallery_excerpt($item); ?></p>
                        <div class="meta">
                            <span><i class="fa-solid fa-user"></i><?php echo get_text($item['wr_name']); ?></span>
                            <span><i class="fa-solid fa-calendar"></i><?php echo date('m-d', strtotime($item['wr_datetime'])); ?></span>
                            <span><i class="fa-solid fa-eye"></i><?php echo number_format($item['wr_hit']); ?></span>
                        </div>
                    </div>
                </li>
                <?php $i++; ?>
            <?php } ?>
        <?php } else { ?>
            <li class="board-empty">
                <div class="meta">등록된 게시물이 없습니다.</div>
            </li>
        <?php } ?>
    </ul>

    <?php if (!empty($write_pages)) { ?>
    <div class="board-pagination">
        <?php echo $write_pages; ?>
    </div>
    <?php } ?>

    <div class="board-actions" style="justify-content: flex-end;">
        <?php if ($sidepanel_gallery_allow_write) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
    </div>
</div>
</form>

<?php if ($is_checkbox) { ?>
<script>
(function() {
    var form = document.getElementById('fboardlist');
    var master = document.getElementById('chkall');
    var allLabels = Array.prototype.slice.call(document.querySelectorAll('.list-check-all'));

    function setMasterState(checked, indeterminate) {
        if (!master) return;
        master.checked = checked;
        master.indeterminate = indeterminate;
        for (var i = 0; i < allLabels.length; i++) {
            allLabels[i].classList.toggle('is-checked', checked);
            allLabels[i].classList.toggle('is-indeterminate', !!indeterminate);
        }
    }

    function syncMasterFromRows() {
        if (!form) return;
        var boxes = form.querySelectorAll("input[name='chk_wr_id[]']");
        if (!boxes.length) {
            setMasterState(false, false);
            return;
        }
        var checkedCount = 0;
        for (var i = 0; i < boxes.length; i++) {
            if (boxes[i].checked) checkedCount++;
        }
        setMasterState(checkedCount === boxes.length, checkedCount > 0 && checkedCount < boxes.length);
    }

    if (master && form) {
        master.addEventListener('change', function() {
            var boxes = form.querySelectorAll("input[name='chk_wr_id[]']");
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].checked = master.checked;
            }
            setMasterState(master.checked, false);
        });

        form.addEventListener('change', function(e) {
            if (e.target && e.target.name === 'chk_wr_id[]') {
                syncMasterFromRows();
            }
        });

        syncMasterFromRows();
    }

    window.fboardlist_submit = function(f) {
        var checked = f.querySelectorAll("input[name='chk_wr_id[]']:checked");
        if (!checked.length) {
            alert((document.pressed || '선택한 작업') + '할 게시물을 하나 이상 선택해주세요.');
            return false;
        }

        var bbsUrl = (typeof g5_bbs_url !== 'undefined') ? g5_bbs_url : '<?php echo G5_BBS_URL; ?>';

        if (document.pressed === '선택복사' || document.pressed === '선택이동') {
            var mode = document.pressed === '선택복사' ? 'copy' : 'move';
            f.sw.value = mode;
            f.target = 'move';
            f.action = bbsUrl + '/move.php';
            window.open('', 'move', 'left=50, top=50, width=500, height=550, scrollbars=1');
            return true;
        }

        if (document.pressed === '선택삭제') {
            if (!confirm('선택한 게시물을 삭제하시겠습니까?\n\n삭제 후에는 복구할 수 없습니다.')) return false;
            f.removeAttribute('target');
            f.action = bbsUrl + '/board_list_update.php';
        }

        return true;
    };
})();
</script>
<?php } ?>
