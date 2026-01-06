<?php
if (!defined('_GNUBOARD_')) exit;

$sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/skin/board/basic/style.css">', 1);
}
$sidepanel_basic_allow_write = $write_href && (!isset($bo_table) || $bo_table !== 'inquiry');

include_once(G5_LIB_PATH . '/thumbnail.lib.php');

function sidepanel_basic_body($view, $write) {
    if (!empty($view['content'])) return get_view_thumbnail($view['content']);
    if (!empty($view['wr_content'])) return get_view_thumbnail($view['wr_content']);
    if (!empty($write['wr_content'])) return get_view_thumbnail($write['wr_content']);
    return '';
}

function sidepanel_basic_files($view) {
    $images = [];
    $downloads = [];
    if (!isset($view['file']) || !is_array($view['file'])) return [$images, $downloads];

    foreach ($view['file'] as $file) {
        if (empty($file['source'])) continue;
        $is_image = !empty($file['view']) || (!empty($file['image']) && $file['image']);
        if ($is_image) $images[] = $file;
        else $downloads[] = $file;
    }
    return [$images, $downloads];
}
?>

<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/skin/board/basic/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $sidepanel_theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

<div class="section board board-view">
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
            <?php if ($sidepanel_basic_allow_write) { ?><a class="btn-minimal" href="<?php echo $write_href; ?>"><i class="fa-solid fa-pen"></i> 글쓰기</a><?php } ?>
            <?php if ($update_href) { ?><a class="btn-minimal" href="<?php echo $update_href; ?>"><i class="fa-solid fa-pen-to-square"></i> 수정</a><?php } ?>
            <?php if ($delete_href) { ?><a class="btn-minimal" href="<?php echo $delete_href; ?>" onclick="del(this.href); return false;"><i class="fa-solid fa-trash"></i> 삭제</a><?php } ?>
        </div>
    </div>

    <?php list($view_images, $download_files) = sidepanel_basic_files($view); ?>
    <?php if (!empty($view_images)) { ?>
        <div class="view-images">
            <?php foreach ($view_images as $file) {
                $img_src = '';
                if (!empty($file['path']) && !empty($file['file'])) {
                    $img_src = rtrim($file['path'], '/') . '/' . rawurlencode($file['file']);
                } elseif (!empty($file['href'])) {
                    $img_src = $file['href'];
                }
                if (!$img_src) continue;
            ?>
                <figure class="view-image">
                    <img src="<?php echo $img_src; ?>" alt="<?php echo get_text($file['source']); ?>">
                    <figcaption>
                        <div class="title"><?php echo get_text($file['source']); ?></div>
                        <?php if (!empty($file['size'])) { ?><div class="meta"><?php echo $file['size']; ?></div><?php } ?>
                    </figcaption>
                </figure>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="content">
        <div class="content-body"><?php echo sidepanel_basic_body($view, $write); ?></div>
    </div>

    <?php if (!empty($download_files)) { ?>
        <div class="link-list">
            <?php foreach ($download_files as $file) { ?>
                <div class="row">
                    <a class="t-title" href="<?php echo $file['href']; ?>" target="_blank" rel="noopener"><?php echo get_text($file['source']); ?></a>
                    <?php if (!empty($file['size'])) { ?><span class="t-meta"><?php echo $file['size']; ?></span><?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
