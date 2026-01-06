<?php
if (!defined('_GNUBOARD_')) exit;

$sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/skin/board/product/style.css">', 1);
}

$sidepanel_product_placeholder = 'https://via.placeholder.com/480x360/ededed/1f1f1f?text=No+Image';
$sidepanel_product_langs = [
    'ko' => 'KO',
    'en' => 'EN',
    'ja' => 'JA',
    'fr' => 'FR',
    'mn' => 'MN'
];

$sidepanel_product_data = [];
$sidepanel_product_raw = $view['wr_content'] ?? ($write['wr_content'] ?? '');
if ($sidepanel_product_raw) {
    $decoded = json_decode($sidepanel_product_raw, true);
    if (is_array($decoded)) {
        $sidepanel_product_data = $decoded;
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

$sidepanel_images = sidepanel_product_image_srcs($view['file'] ?? []);
$sidepanel_main_image = $sidepanel_images[0] ?? $sidepanel_product_placeholder;
$sidepanel_thumbs = array_slice($sidepanel_images, 1, 2);
?>

<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/skin/board/product/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $sidepanel_theme_url; ?>/theme.js" defer></script>
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

    <div class="product-gallery">
        <div class="main">
            <img src="<?php echo $sidepanel_main_image; ?>" alt="">
        </div>
        <div class="thumbs">
            <?php if ($sidepanel_thumbs) { ?>
                <?php foreach ($sidepanel_thumbs as $thumb) { ?>
                    <div class="thumb"><img src="<?php echo $thumb; ?>" alt=""></div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

    <?php foreach ($sidepanel_product_langs as $lang_key => $lang_label) {
        $lang_data = $sidepanel_product_data['lang'][$lang_key] ?? [];
        if (!sidepanel_product_lang_has_content($lang_data)) continue;
        $features = [];
        if (!empty($lang_data['features']) && is_array($lang_data['features'])) {
            $features = $lang_data['features'];
        }
    ?>
        <div class="lang-block">
            <div class="lang-title"><?php echo $lang_label; ?></div>
            <div class="info-grid">
                <div>
                    <div class="label">제목</div>
                    <div class="value"><?php echo htmlspecialchars($lang_data['title'] ?? '', ENT_QUOTES); ?></div>
                </div>
                <div>
                    <div class="label">판매가</div>
                    <div class="value"><?php echo htmlspecialchars($lang_data['price'] ?? '', ENT_QUOTES); ?></div>
                </div>
                <div class="full">
                    <div class="label">설명</div>
                    <div class="value"><?php echo nl2br(htmlspecialchars($lang_data['desc'] ?? '', ENT_QUOTES)); ?></div>
                </div>
                <div class="full">
                    <div class="label">구매방법</div>
                    <div class="value"><?php echo htmlspecialchars($lang_data['buy'] ?? '', ENT_QUOTES); ?></div>
                </div>
            </div>

            <?php if (!empty($features)) { ?>
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
            <?php } ?>
        </div>
    <?php } ?>
</div>
