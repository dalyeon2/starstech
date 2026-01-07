<?php
if (!defined('_GNUBOARD_')) exit;

$theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/skin/board/product/style.css">', 1);
}

$product_placeholder = G5_URL . '/img/default-image.jpg';
$product_allow_write = (bool)$write_href;

if (!function_exists('sidepanel_product_is_image_ext')) {
    function sidepanel_product_is_image_ext($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
    }
}

if (!function_exists('sidepanel_product_thumb')) {
    function sidepanel_product_thumb($bo_table, $wr_id) {
        if (function_exists('get_list_thumbnail')) {
            $thumb = get_list_thumbnail($bo_table, $wr_id, 360, 270, true, true);
            if (!empty($thumb['src'])) return $thumb['src'];
        }
        if (function_exists('get_file')) {
            $files = get_file($bo_table, $wr_id);
            if (!empty($files) && is_array($files)) {
                foreach ($files as $file) {
                    $fname = $file['file'] ?? ($file['source'] ?? '');
                    if (!$fname || !sidepanel_product_is_image_ext($fname)) continue;
                    if (!empty($file['path']) && !empty($file['file'])) {
                        return rtrim($file['path'], '/') . '/' . rawurlencode($fname);
                    }
                }
            }
        }
        return '';
    }
}
?>

<link rel="stylesheet" href="<?php echo $theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $theme_url; ?>/skin/board/product/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

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

<div class="section board board-product">
    <div class="section-header">
        <h2><?php echo $board['bo_subject']; ?></h2>
    </div>
    <div class="search-row">
        <div class="meta-count">
            <span>Total <?php echo number_format($total_count ?? 0); ?></span>
            <?php if ($is_checkbox) { ?>
            <label class="card-check check-all list-check-all" for="chkall">
                <input type="checkbox" id="chkall">
                <span class="box"></span>
                <span class="check-all-text">전체 선택</span>
            </label>
            <?php } ?>
        </div>
        <div class="top-actions">
            <?php if ($product_allow_write) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-trash"></i> 선택삭제</button><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-copy"></i> 선택복사</button><?php } ?>
            <?php if ($is_checkbox) { ?><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value;" class="btn-minimal"><i class="fa-solid fa-arrow-right-arrow-left"></i> 선택이동</button><?php } ?>
            <?php if (isset($is_admin) && $is_admin === 'super') { ?><a class="btn-minimal" href="/adm/config_form.php"><i class="fa-solid fa-sliders"></i> 환경설정</a><?php } ?>
        </div>
    </div>

    <ul class="product-grid">
        <?php if ($list && count($list)) { $i = 0; ?>
            <?php foreach ($list as $item) {
                $thumb = sidepanel_product_thumb($bo_table, $item['wr_id']);
                $thumb_src = $thumb ?: $product_placeholder;
            ?>
            <li class="product-card">
                <?php if ($is_checkbox) { ?>
                    <label class="card-check" for="chk_wr_id_<?php echo $i; ?>">
                        <input type="checkbox" name="chk_wr_id[]" value="<?php echo $item['wr_id']; ?>" id="chk_wr_id_<?php echo $i; ?>">
                        <span class="box"></span>
                    </label>
                <?php } ?>
                <a class="thumb" href="<?php echo $item['href']; ?>">
                    <img src="<?php echo $thumb_src; ?>" loading="lazy" alt="" onerror="this.onerror=null;this.src='<?php echo $product_placeholder; ?>';">
                </a>
                <div class="body">
                    <a class="title" href="<?php echo $item['href']; ?>">
                        <?php echo $item['subject']; ?>
                    </a>
                    <div class="meta">
                        <span><i class="fa-solid fa-user"></i><?php echo get_text(strip_tags($item['name'])); ?></span>
                        <span><i class="fa-solid fa-calendar"></i><?php echo date('Y.m.d', strtotime($item['wr_datetime'])); ?></span>
                        <span><i class="fa-solid fa-eye"></i><?php echo number_format($item['wr_hit']); ?></span>
                    </div>
                </div>
            </li>
            <?php $i++; ?>
            <?php } ?>
        <?php } else { ?>
            <li class="board-empty">등록된 게시물이 없습니다.</li>
        <?php } ?>
    </ul>

    <?php if (!empty($write_pages)) { ?>
    <div class="board-pagination">
        <?php echo $write_pages; ?>
    </div>
    <?php } ?>

    <div class="board-actions" style="justify-content: flex-end;">
        <?php if ($product_allow_write) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
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
            alert((document.pressed || '선택작업') + '할 게시물을 하나 이상 선택하세요.');
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
            if (!confirm('선택한 게시물을 정말 삭제하시겠습니까?')) return false;
            f.removeAttribute('target');
            f.action = bbsUrl + '/board_list_update.php';
        }

        return true;
    };
})();
</script>
<?php } ?>
