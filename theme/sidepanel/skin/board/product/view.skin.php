<?php
if (!defined('_GNUBOARD_')) exit;

$theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/skin/board/product/style.css">', 1);
}

$product_placeholder = G5_URL . '/img/default-image.jpg';
$product_image_count = 3;
$product_langs = [
    'ko' => 'KO',
    'en' => 'EN',
    'ja' => 'JA',
    'fr' => 'FR',
    'mn' => 'MN'
];
$product_lang_names = [
    'ko' => '한국어',
    'en' => '영어',
    'ja' => '일본어',
    'fr' => '프랑스어',
    'mn' => '몽골어'
];
$product_manual_langs = array_keys($product_langs);
$product_manual_start = $product_image_count;
$product_manual_map = [];
foreach ($product_manual_langs as $idx => $lang_key) {
    $product_manual_map[$lang_key] = $product_manual_start + $idx;
}

$product_data = [];
$product_raw = $view['wr_content'] ?? ($write['wr_content'] ?? '');
if ($product_raw) {
    $decoded = json_decode($product_raw, true);
    if (is_array($decoded)) {
        $product_data = $decoded;
    }
}

if (!function_exists('sidepanel_product_is_image_ext')) {
    function sidepanel_product_is_image_ext($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp','svg'], true);
    }
}

if (!function_exists('sidepanel_product_image_srcs')) {
    function sidepanel_product_image_srcs($view_files) {
        $images = [];
        if (!empty($view_files) && is_array($view_files)) {
            foreach ($view_files as $file) {
                $fname = $file['file'] ?? ($file['source'] ?? '');
                if (!$fname || !sidepanel_product_is_image_ext($fname)) continue;
                if (!empty($file['path']) && !empty($file['file'])) {
                    $images[] = rtrim($file['path'], '/') . '/' . rawurlencode($fname);
                } elseif (!empty($file['href'])) {
                    $images[] = $file['href'];
                }
            }
        }
        return $images;
    }
}

if (!function_exists('sidepanel_product_manual_file')) {
    function sidepanel_product_manual_file($view_files) {
        if (empty($view_files) || !is_array($view_files)) return null;
        foreach ($view_files as $file) {
            $fname = $file['file'] ?? ($file['source'] ?? '');
            if (!$fname || sidepanel_product_is_image_ext($fname)) continue;
            $src = '';
            if (!empty($file['path']) && !empty($file['file'])) {
                $src = rtrim($file['path'], '/') . '/' . rawurlencode($fname);
            } elseif (!empty($file['href'])) {
                $src = $file['href'];
            }
            if ($src) {
                return ['src' => $src, 'name' => $file['source'] ?? $fname];
            }
        }
        return null;
    }
}

if (!function_exists('sidepanel_product_manual_files')) {
    function sidepanel_product_manual_files($view_files, $manual_map) {
        $manuals = [];
        if (empty($view_files) || !is_array($view_files)) return $manuals;
        foreach ($manual_map as $lang_key => $idx) {
            if (!isset($view_files[$idx])) continue;
            $file = $view_files[$idx];
            $fname = $file['file'] ?? ($file['source'] ?? '');
            if (!$fname || sidepanel_product_is_image_ext($fname)) continue;
            $src = '';
            if (!empty($file['path']) && !empty($file['file'])) {
                $src = rtrim($file['path'], '/') . '/' . rawurlencode($fname);
            } elseif (!empty($file['href'])) {
                $src = $file['href'];
            }
            if ($src) {
                $manuals[$lang_key] = ['src' => $src, 'name' => $file['source'] ?? $fname];
            }
        }
        return $manuals;
    }
}

if (!function_exists('sidepanel_product_lang_has_content')) {
    function sidepanel_product_lang_has_content($lang_data) {
        if (!is_array($lang_data)) return false;
        foreach (['title','desc','buy','price'] as $key) {
            if (!empty($lang_data[$key])) return true;
        }
        if (!empty($lang_data['features']) && is_array($lang_data['features'])) return true;
        return false;
    }
}

$images = sidepanel_product_image_srcs($view['file'] ?? []);
$has_main_image = !empty($images);
$main_image = $has_main_image ? $images[0] : $product_placeholder;
$thumbs = array_slice($images, 1, 2);
$manuals = sidepanel_product_manual_files($view['file'] ?? [], $product_manual_map);
$manual = null;
if (empty($manuals)) {
    $manual = sidepanel_product_manual_file($view['file'] ?? []);
}

$intro_langs = [];
$detail_langs = [];
foreach ($product_langs as $lang_key => $lang_label) {
    $lang_data = $product_data['lang'][$lang_key] ?? [];
    if (!is_array($lang_data)) $lang_data = [];
    $has_info = false;
    foreach (['title', 'price', 'desc', 'buy'] as $key) {
        if (!empty($lang_data[$key])) {
            $has_info = true;
            break;
        }
    }
    if ($has_info) $intro_langs[$lang_key] = $lang_data;
    if (!empty($lang_data['features']) && is_array($lang_data['features'])) {
        $detail_langs[$lang_key] = $lang_data;
    }
}
?>

<link rel="stylesheet" href="<?php echo $theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $theme_url; ?>/skin/board/product/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

<div class="section board board-view product-view">
    <div class="view-header">
        <div>
            <div class="eyebrow"><?php echo $board['bo_subject']; ?></div>
            <h1 class="title"><?php echo get_text($view['subject'] ?? $write['wr_subject']); ?></h1>
            <div class="meta">
                <span><i class="fa-solid fa-user"></i><?php echo $view['name'] ?? $write['wr_name']; ?></span>
                <span><i class="fa-solid fa-eye"></i><?php echo number_format($view['wr_hit'] ?? $write['wr_hit']); ?></span>
                <span><i class="fa-solid fa-clock"></i><?php echo date('Y.m.d H:i', strtotime($view['wr_datetime'] ?? $write['wr_datetime'])); ?></span>
            </div>
        </div>
        <div class="view-actions top-actions">
            <?php if ($list_href) { ?><a class="btn-minimal" href="<?php echo $list_href; ?>"><i class="fa-solid fa-list"></i> 목록</a><?php } ?>
            <?php if ($write_href) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
            <?php if ($update_href) { ?><a class="btn-minimal" href="<?php echo $update_href; ?>"><i class="fa-solid fa-pen-to-square"></i> 수정</a><?php } ?>
            <?php if ($delete_href) { ?><a class="btn-minimal" href="<?php echo $delete_href; ?>" onclick="del(this.href); return false;"><i class="fa-solid fa-trash"></i> 삭제</a><?php } ?>
        </div>
    </div>

    <div class="product-top">
        <div class="product-main<?php echo $has_main_image ? '' : ' is-empty'; ?>">
            <?php if ($has_main_image) { ?>
                <img src="<?php echo $main_image; ?>" alt="" onerror="this.onerror=null;this.closest('.product-main').classList.add('is-empty');this.style.display='none';">
            <?php } ?>
        </div>
        <div class="product-intro">
            <div class="section-title">제품 소개</div>
            <div class="intro-grid">
                <?php foreach ($intro_langs as $lang_key => $lang_data) {
                    $lang_title = $product_lang_names[$lang_key] ?? ($product_langs[$lang_key] ?? strtoupper($lang_key));
                    $title = trim($lang_data['title'] ?? '');
                    $price = trim($lang_data['price'] ?? '');
                    $desc = trim($lang_data['desc'] ?? '');
                    $buy = trim($lang_data['buy'] ?? '');
                ?>
                    <div class="intro-card">
                        <div class="lang-title"><?php echo $lang_title; ?></div>
                        <div class="info-grid">
                            <?php if ($title !== '') { ?>
                                <div class="info-row">
                                    <div class="label">제품명</div>
                                    <div class="value"><?php echo htmlspecialchars($title, ENT_QUOTES); ?></div>
                                </div>
                            <?php } ?>
                            <?php if ($price !== '') { ?>
                                <div class="info-row">
                                    <div class="label">판매가</div>
                                    <div class="value"><?php echo htmlspecialchars($price, ENT_QUOTES); ?></div>
                                </div>
                            <?php } ?>
                            <?php if ($desc !== '') { ?>
                                <div class="info-row full">
                                    <div class="label">설명</div>
                                    <div class="value"><?php echo nl2br(htmlspecialchars($desc, ENT_QUOTES)); ?></div>
                                </div>
                            <?php } ?>
                            <?php if ($buy !== '') { ?>
                                <div class="info-row full">
                                    <div class="label">구매방법</div>
                                    <div class="value"><?php echo htmlspecialchars($buy, ENT_QUOTES); ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if (!empty($manuals)) { ?>
                <div class="manual-download">
                    <?php foreach ($manuals as $lang_key => $manual) {
                        $lang_label = $product_lang_names[$lang_key] ?? strtoupper($lang_key);
                    ?>
                        <a class="btn-minimal" href="<?php echo $manual['src']; ?>" target="_blank" rel="noopener">제품 설명서(<?php echo $lang_label; ?>)</a>
                    <?php } ?>
                </div>
            <?php } elseif (!empty($manual)) { ?>
                <div class="manual-download">
                    <a class="btn-minimal" href="<?php echo $manual['src']; ?>" target="_blank" rel="noopener">제품 설명서 보기</a>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php if ($thumbs) { ?>
        <div class="detail-images">
            <div class="section-title">제품 상세이미지</div>
            <div class="detail-grid">
                <?php foreach ($thumbs as $thumb) { ?>
                    <div class="detail-image">
                        <img src="<?php echo $thumb; ?>" alt="" onerror="this.onerror=null;this.closest('.detail-image').classList.add('is-empty');this.style.display='none';">
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($detail_langs)) { ?>
        <div class="detail-section">
            <div class="section-title">제품 상세정보</div>
            <div class="detail-lang-list">
                <?php foreach ($detail_langs as $lang_key => $lang_data) {
                    $features = [];
                    if (!empty($lang_data['features']) && is_array($lang_data['features'])) {
                        $features = $lang_data['features'];
                    }
                    if (empty($features)) continue;
                    $lang_title = $product_lang_names[$lang_key] ?? ($product_langs[$lang_key] ?? strtoupper($lang_key));
                ?>
                    <div class="lang-block">
                        <div class="lang-title"><?php echo $lang_title; ?></div>
                        <div class="features">
                            <?php foreach ($features as $feature) {
                                $title = $feature['title'] ?? '';
                                $items = $feature['items'] ?? [];
                                if (!is_array($items)) $items = [];
                                if ($title === '' && empty($items)) continue;
                            ?>
                            <div class="feature-block">
                                <div class="feature-title"><?php echo htmlspecialchars($title, ENT_QUOTES); ?></div>
                                <?php if (!empty($items)) { ?>
                                <ul>
                                    <?php foreach ($items as $item) { ?>
                                        <li><?php echo htmlspecialchars($item, ENT_QUOTES); ?></li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>
