<?php
if (!defined('_GNUBOARD_')) exit;

$sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/skin/board/gallery/style.css">', 1);
}
$sidepanel_gallery_placeholder = 'https://via.placeholder.com/360x240/ededed/1f1f1f?text=No+Image';
$sidepanel_is_video_board = isset($bo_table) && $bo_table === 'video';
$sidepanel_show_source = isset($bo_table) && $bo_table === 'news';
$sidepanel_hide_file_source = isset($bo_table) && in_array($bo_table, ['news', 'pr'], true);

if (!function_exists('sidepanel_gallery_is_video_file')) {
    function sidepanel_gallery_is_video_file($file) {
        $name = '';
        if (is_array($file)) {
            if (!empty($file['file'])) {
                $name = $file['file'];
            } elseif (!empty($file['source'])) {
                $name = $file['source'];
            }
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'mkv', 'mov', 'avi', 'm4v', 'ogv'], true);
    }
}

if (!function_exists('sidepanel_gallery_render_media')) {
    function sidepanel_gallery_render_media($file, $placeholder, $hide_source = false) {
        $media_src = $placeholder;
        if (!empty($file['path']) && !empty($file['file'])) {
            $media_src = rtrim($file['path'], '/') . '/' . rawurlencode($file['file']);
        } elseif (!empty($file['href'])) {
            $media_src = $file['href'];
        }
        $render_video = sidepanel_gallery_is_video_file($file) && $media_src !== $placeholder;
        $caption_text = '';
        $source_text = '';
        $content_raw = '';
        if (!empty($file['bf_content'])) {
            $content_raw = $file['bf_content'];
        } elseif (!empty($file['content'])) {
            $content_raw = $file['content'];
        }
        if ($content_raw !== '') {
            $parts = explode('||', $content_raw, 2);
            $caption_text = trim($parts[0]);
            if (isset($parts[1])) $source_text = trim($parts[1]);
        }
        if (!$caption_text && !$source_text && !empty($file['source'])) {
            $caption_text = $file['source'];
        }
        if ($hide_source) {
            $source_text = '';
        }

        ob_start();
        ?>
        <figure class="content-media">
            <div class="media-frame">
                <?php if ($render_video) { ?>
                    <video class="view-video" src="<?php echo $media_src; ?>" controls preload="metadata">
                        Your browser does not support the video tag.
                    </video>
                <?php } else { ?>
                    <img src="<?php echo $media_src; ?>" alt="<?php echo get_text($file['source']); ?>" onerror="this.onerror=null;this.src='<?php echo $placeholder; ?>';">
                <?php } ?>
            </div>
            <?php if ($caption_text || $source_text) { ?>
            <figcaption>
                <?php if ($caption_text) { ?><div class="title"><?php echo get_text($caption_text); ?></div><?php } ?>
                <?php if ($source_text) { ?><div class="meta">출처: <?php echo get_text($source_text); ?></div><?php } ?>
            </figcaption>
            <?php } ?>
        </figure>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('sidepanel_gallery_inject_media')) {
    function sidepanel_gallery_inject_media($content, $files, $placeholder, &$used, $hide_source = false) {
        $used = [];
        if (!$content || empty($files)) return $content;
        return preg_replace_callback('/\\[(?:media|image):\\s*(\\d+)\\]/i', function ($matches) use ($files, $placeholder, &$used, $hide_source) {
            $index = (int)$matches[1] - 1;
            if ($index < 0 || $index >= count($files)) return '';
            $used[$index] = true;
            return sidepanel_gallery_render_media($files[$index], $placeholder, $hide_source);
        }, $content);
    }
}

function sidepanel_gallery_body($view, $write) {
    global $g5, $bo_table, $write_table;
    if (!empty($view['content'])) return $view['content'];
    if (!empty($view['wr_content'])) return $view['wr_content'];
    if (!empty($write['wr_content'])) return $write['wr_content'];

    if (!function_exists('sql_fetch')) return '';
    $wr_id = (int)($view['wr_id'] ?? ($write['wr_id'] ?? 0));
    if ($wr_id <= 0) return '';

    $table_name = '';
    if (!empty($write_table)) {
        $table_name = $write_table;
    } elseif (!empty($g5['write_prefix']) && !empty($bo_table)) {
        $table_name = $g5['write_prefix'] . $bo_table;
    }
    if ($table_name === '') return '';

    $row = sql_fetch("SELECT wr_content FROM {$table_name} WHERE wr_id = {$wr_id}");
    if ($row && isset($row['wr_content'])) {
        return $row['wr_content'];
    }
    return '';
}
?>

<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/skin/board/gallery/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $sidepanel_theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

<div class="section board board-view gallery-view">
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

    <?php
    $sidepanel_view_files = [];
    if (!empty($view['file']) && is_array($view['file'])) {
        foreach ($view['file'] as $file) {
            if (empty($file['source'])) continue;
            $sidepanel_view_files[] = $file;
        }
    }

    if ($sidepanel_is_video_board && $sidepanel_view_files) {
        $videos_only = array_values(array_filter($sidepanel_view_files, 'sidepanel_gallery_is_video_file'));
        if (!empty($videos_only)) {
            $sidepanel_view_files = [$videos_only[0]]; // only show the actual video
        } else {
            $non_thumb = array_values(array_filter($sidepanel_view_files, function ($f) {
                $name = '';
                if (!empty($f['file'])) $name = $f['file'];
                elseif (!empty($f['source'])) $name = $f['source'];
                return stripos($name, 'thumb-') !== 0;
            }));
            if (!empty($non_thumb)) {
                $sidepanel_view_files = [$non_thumb[0]];
            } else {
                $sidepanel_view_files = [reset($sidepanel_view_files)];
            }
        }
    }

    $sidepanel_content = sidepanel_gallery_body($view, $write);
    $sidepanel_used_files = [];
    $sidepanel_content = sidepanel_gallery_inject_media($sidepanel_content, $sidepanel_view_files, $sidepanel_gallery_placeholder, $sidepanel_used_files, $sidepanel_hide_file_source);
    $sidepanel_inline_media = !empty($sidepanel_used_files);
    $sidepanel_unused_files = [];
    if (!empty($sidepanel_view_files)) {
        foreach ($sidepanel_view_files as $idx => $file) {
            if (!$sidepanel_inline_media || !isset($sidepanel_used_files[$idx])) {
                $sidepanel_unused_files[] = $file;
            }
        }
    }
    ?>
    <?php if (!empty($sidepanel_unused_files)) { ?>
        <div class="view-media">
            <div class="media-head">
                <span class="pill">첨부 이미지</span>
                <?php if ($sidepanel_inline_media) { ?><span class="hint">내용에 삽입되지 않은 이미지</span><?php } ?>
            </div>
            <div class="view-images">
            <?php foreach ($sidepanel_unused_files as $file) {
                $media_src = $sidepanel_gallery_placeholder;
                if (!empty($file['path']) && !empty($file['file'])) {
                    $media_src = rtrim($file['path'], '/') . '/' . rawurlencode($file['file']);
                } elseif (!empty($file['href'])) {
                    $media_src = $file['href'];
                }
                $render_video = sidepanel_gallery_is_video_file($file) && $media_src !== $sidepanel_gallery_placeholder;
                $caption_text = '';
                $source_text = '';
                $content_raw = '';
                if (!empty($file['bf_content'])) {
                    $content_raw = $file['bf_content'];
                } elseif (!empty($file['content'])) {
                    $content_raw = $file['content'];
                }
                if ($content_raw !== '') {
                    $parts = explode('||', $content_raw, 2);
                    $caption_text = trim($parts[0]);
                    if (isset($parts[1])) $source_text = trim($parts[1]);
                }
                if (!$caption_text && !$source_text && !empty($file['source'])) {
                    $caption_text = $file['source'];
                }
                if ($sidepanel_hide_file_source) {
                    $source_text = '';
                }
            ?>
                    <figure class="view-image">
                        <div class="media-frame">
                            <?php if ($render_video) { ?>
                                <video class="view-video" src="<?php echo $media_src; ?>" controls preload="metadata">
                                    Your browser does not support the video tag.
                                </video>
                            <?php } else { ?>
                                <img src="<?php echo $media_src; ?>" alt="<?php echo get_text($file['source']); ?>" onerror="this.onerror=null;this.src='<?php echo $sidepanel_gallery_placeholder; ?>';">
                            <?php } ?>
                        </div>
                        <?php if ($caption_text || $source_text) { ?>
                        <figcaption>
                            <?php if ($caption_text) { ?><div class="title"><?php echo get_text($caption_text); ?></div><?php } ?>
                            <?php if ($source_text) { ?><div class="meta">출처: <?php echo get_text($source_text); ?></div><?php } ?>
                        </figcaption>
                        <?php } ?>
                    </figure>
            <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div class="content panel">
        <div class="panel-head">
            <span class="pill">내용</span>
        </div>
        <div class="content-body"><?php echo $sidepanel_content; ?></div>
        <?php
        $news_source_text = trim($view['wr_1'] ?? ($write['wr_1'] ?? ''));
        $news_source_link = trim($view['wr_2'] ?? ($write['wr_2'] ?? ''));
        ?>
        <?php if ($sidepanel_show_source && ($news_source_text || $news_source_link)) { ?>
            <div class="source-block">
                <div class="label">출처</div>
                <div class="value">
                    <?php if ($news_source_text) { ?><span class="text"><?php echo get_text($news_source_text); ?></span><?php } ?>
                    <?php if ($news_source_link) { ?>
                        <a href="<?php echo htmlspecialchars($news_source_link, ENT_QUOTES); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars($news_source_link, ENT_QUOTES); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php if (!empty($view['link']) && is_array($view['link'])) { ?>
        <div class="link-list">
            <?php for ($i=1; $i<=count($view['link']); $i++) { if (!$view['link'][$i]) continue; ?>
                <div class="row">
                    <a class="t-title" href="<?php echo $view['link'][$i]; ?>" target="_blank" rel="noopener">링크 <?php echo $i; ?></a>
                    <?php if ($view['link_hit'][$i]) { ?><span class="t-meta"><?php echo $view['link_hit'][$i]; ?></span><?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
