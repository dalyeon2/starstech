<?php
if (!defined('_GNUBOARD_')) exit;

if (function_exists('add_stylesheet') && defined('G5_THEME_URL')) {
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/skin/board/basic/style.css">', 1);
}
?>

<div class="section board board-basic board-write">
    <div class="section-header">
        <h2><?php echo $board['bo_subject']; ?></h2>
        <div class="top-actions">
            <?php if ($list_href) { ?><a class="btn-minimal" href="<?php echo $list_href; ?>"><i class="fa-solid fa-list"></i> 목록</a><?php } ?>
        </div>
    </div>

    <form name="fwrite" id="fwrite" action="<?php echo $action_url; ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
        <input type="hidden" name="w" value="<?php echo $w; ?>">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
        <input type="hidden" name="wr_id" value="<?php echo $wr_id; ?>">
        <input type="hidden" name="sca" value="<?php echo $sca; ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
        <input type="hidden" name="stx" value="<?php echo $stx; ?>">
        <input type="hidden" name="spt" value="<?php echo $spt; ?>">
        <input type="hidden" name="sst" value="<?php echo $sst; ?>">
        <input type="hidden" name="sod" value="<?php echo $sod; ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">

        <div class="board-form">
            <div class="board-field">
                <label for="wr_subject">제목</label>
                <input type="text" name="wr_subject" value="<?php echo $subject; ?>" id="wr_subject" required>
            </div>

            <div class="board-field">
                <label for="wr_content">내용</label>
                <div class="editor-wrap">
                    <?php echo $editor_html; ?>
                </div>
            </div>

            <?php if ($is_secret) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" name="secret" value="secret" <?php echo $secret_checked; ?>> 비밀글</label>
            </div>
            <?php } ?>

            <?php if ($is_mail) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" id="mail" name="mail" value="mail" <?php echo $recv_email_checked; ?>> 답변메일 받기</label>
            </div>
            <?php } ?>
        </div>

        <?php $cancel_href = $list_href ?: 'javascript:history.back();'; ?>
        <div class="board-actions">
            <a class="btn-minimal btn-cancel" href="<?php echo $cancel_href; ?>">취소</a>
            <button type="submit" class="btn-minimal btn-submit">작성완료</button>
        </div>
    </form>
</div>

<?php if ($is_dhtml_editor) echo $editor_js; ?>
<?php echo $captcha_js; ?>
<script>
function fwrite_submit(f) {
    <?php if ($is_dhtml_editor) echo chk_editor_js('wr_content'); ?>
    return true;
}
</script>
