<?php
if (!defined('_GNUBOARD_')) exit;

if (function_exists('add_stylesheet') && defined('G5_THEME_URL')) {
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/skin/board/gallery/style.css">', 1);
}

$sidepanel_gallery_placeholder = 'https://via.placeholder.com/360x240/ededed/1f1f1f?text=No+Image';
$sidepanel_is_video_board = isset($bo_table) && $bo_table === 'video';
$sidepanel_media_label = $sidepanel_is_video_board ? '비디오' : '이미지';
$sidepanel_accept_types = $sidepanel_is_video_board ? 'video/*' : 'image/*';
$sidepanel_file_count = 1;
if (!$sidepanel_is_video_board) {
    $sidepanel_file_count = isset($board['bo_upload_count']) ? (int)$board['bo_upload_count'] : 1;
    if ($sidepanel_file_count < 1) $sidepanel_file_count = 1;
    if ($sidepanel_file_count > 3) $sidepanel_file_count = 3;
}
$sidepanel_show_link = isset($bo_table) && $bo_table === 'pr';
$sidepanel_show_source = isset($bo_table) && $bo_table === 'news';
$sidepanel_can_sort = false;
$sidepanel_lang_codes = ['ko', 'en', 'ja', 'fr', 'mn'];
$sidepanel_lang_options = [];
if (!empty($board['bo_category_list'])) {
    $raw_categories = array_map('trim', explode('|', $board['bo_category_list']));
    foreach ($raw_categories as $cat) {
        if ($cat === '') continue;
        $lower = strtolower($cat);
        if (in_array($lower, $sidepanel_lang_codes, true)) {
            $sidepanel_lang_options[$lower] = $cat;
        }
    }
}
$sidepanel_use_lang_filter = !empty($sidepanel_lang_options);
$sidepanel_has_existing_file = false;
if ($w === 'u' && !empty($file) && is_array($file)) {
    foreach ($file as $file_item) {
        if (!empty($file_item['file'])) {
            $sidepanel_has_existing_file = true;
            break;
        }
    }
}

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
?>

<div class="section board board-gallery board-write">
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
            <?php if ($is_category) { ?>
                <div class="board-field">
                    <label for="ca_name">언어</label>
                    <select name="ca_name" id="ca_name" required>
                        <option value="">선택</option>
                        <?php if ($sidepanel_use_lang_filter) { ?>
                            <?php foreach ($sidepanel_lang_options as $lang_value) {
                                $selected = ($ca_name === $lang_value) ? ' selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($lang_value, ENT_QUOTES); ?>"<?php echo $selected; ?>><?php echo htmlspecialchars($lang_value, ENT_QUOTES); ?></option>
                            <?php } ?>
                        <?php } else { ?>
                            <?php echo $category_option; ?>
                        <?php } ?>
                    </select>
                </div>
            <?php } ?>

            <div class="board-field">
                <label for="wr_subject">제목</label>
                <input type="text" name="wr_subject" id="wr_subject" value="<?php echo $subject; ?>" required>
            </div>
            <?php if ($sidepanel_show_link) { ?>
                <div class="board-field link-field">
                    <label for="wr_link1">외부 링크</label>
                    <div class="link-input">
                        <i class="fa-solid fa-link" aria-hidden="true"></i>
                        <input type="url" name="wr_link1" id="wr_link1" value="<?php echo htmlspecialchars($write['wr_link1'] ?? '', ENT_QUOTES); ?>" placeholder="https://">
                    </div>
                    <p class="link-help">입력하면 리스트에서 바로 외부 링크로 이동합니다.</p>
                </div>
            <?php } ?>
            <?php if ($is_file) { ?>
                <?php
                $sidepanel_visible_slots = 1;
                if ($w === 'u' && !empty($file) && is_array($file)) {
                    foreach ($file as $idx => $file_item) {
                        if (!empty($file_item['file'])) {
                            $sidepanel_visible_slots = max($sidepanel_visible_slots, $idx + 1);
                        }
                    }
                }
                if ($sidepanel_visible_slots > $sidepanel_file_count) {
                    $sidepanel_visible_slots = $sidepanel_file_count;
                }
                ?>
                <div class="board-field file-group">
                    <label>이미지</label>
                    <div class="file-field-list" data-max="<?php echo $sidepanel_file_count; ?>">
                        <div class="file-insert-note">텍스트 사이에 이미지를 넣을 때 아래 삽입 코드를 내용에 추가하세요.</div>
                    <?php for ($i=0; $i<$sidepanel_file_count; $i++) {
                        $existing_file = $file[$i] ?? [];
                        $existing_src = '';
                        if (!empty($existing_file['path']) && !empty($existing_file['file'])) {
                            $existing_src = rtrim($existing_file['path'], '/') . '/' . rawurlencode($existing_file['file']);
                    } elseif (!empty($existing_file['href'])) {
                        $existing_src = $existing_file['href'];
                    }
                    $existing_is_video = $sidepanel_is_video_board && sidepanel_gallery_is_video_file($existing_file);
                    $existing_content_raw = '';
                    $existing_caption = '';
                    if (!empty($existing_file['bf_content'])) {
                        $existing_content_raw = $existing_file['bf_content'];
                    } elseif (!empty($existing_file['content'])) {
                        $existing_content_raw = $existing_file['content'];
                    }
                        if ($existing_content_raw !== '') {
                            $parts = explode('||', $existing_content_raw, 2);
                            $existing_caption = trim($parts[0]);
                        }
                        $has_existing_file = ($w == 'u' && !empty($existing_file['file']));
                        $is_hidden = ($i >= $sidepanel_visible_slots);
                    ?>
                    <div class="board-field file-field<?php echo $has_existing_file ? ' has-file' : ''; ?><?php echo $is_hidden && !$has_existing_file ? ' is-hidden' : ''; ?>">
                        <div class="file-box">
                            <?php if ($has_existing_file) { ?>
                                <div class="file-existing">
                                    <div class="thumb">
                                        <?php if ($existing_src) { ?>
                                            <?php if ($existing_is_video) { ?>
                                                <video src="<?php echo $existing_src; ?>" controls preload="metadata" style="width:100%;height:100%;object-fit:cover;display:block;background:#000;"></video>
                                            <?php } else { ?>
                                                <img src="<?php echo $existing_src; ?>" alt="<?php echo get_text($existing_file['source']); ?>" onerror="this.src='<?php echo $sidepanel_gallery_placeholder; ?>';">
                                            <?php } ?>
                                        <?php } else { ?>
                                            <img src="<?php echo $sidepanel_gallery_placeholder; ?>" alt="">
                                        <?php } ?>
                                    </div>
                                    <div class="file-meta">
                                        <div class="name"><?php echo get_text($existing_file['source']); ?></div>
                                        <?php if (!empty($existing_file['filesize'])) { ?><div class="size"><?php echo $existing_file['filesize']; ?></div><?php } ?>
                                    </div>
                                    <label class="file-del">
                                        <input type="checkbox" name="bf_file_del[<?php echo $i; ?>]" value="1">
                                        <span class="box" aria-hidden="true"></span>
                                        <span class="text">삭제</span>
                                    </label>
                                </div>
                            <?php } ?>
                            <input type="file" name="bf_file[]" id="bf_file_<?php echo $i; ?>" class="file-input" accept="<?php echo $sidepanel_accept_types; ?>"<?php echo $sidepanel_is_video_board ? ' multiple' : ''; ?> aria-label="이미지 파일">
                            <div class="file-meta-fields">
                                <input type="text" id="bf_caption_<?php echo $i; ?>" data-caption value="<?php echo htmlspecialchars($existing_caption, ENT_QUOTES); ?>" placeholder="캡션 (선택)">
                                <div class="file-insert">
                                    <button type="button" class="insert-btn" data-insert="[media:<?php echo $i + 1; ?>]">내용에 삽입</button>
                                    <span class="insert-code">[media:<?php echo $i + 1; ?>]</span>
                                </div>
                            </div>
                            <input type="hidden" name="bf_content[]" id="bf_content_<?php echo $i; ?>" value="<?php echo htmlspecialchars($existing_content_raw, ENT_QUOTES); ?>">
                        </div>
                    </div>
                    <?php } ?>
                    </div>
                    <?php if ($sidepanel_file_count > 1 && !$sidepanel_is_video_board) { ?>
                        <div class="file-controls">
                            <button type="button" class="file-add">이미지 추가</button>
                            <button type="button" class="file-remove">이미지 삭제</button>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="board-field">
                <label for="wr_content">내용</label>
                <div class="editor-wrap">
                    <?php echo $editor_html; ?>
                </div>
            </div>

            <?php if ($sidepanel_show_source) { ?>
                <div class="board-field source-field">
                    <label for="wr_1">출처</label>
                    <div class="source-inputs">
                        <input type="text" name="wr_1" id="wr_1" value="<?php echo htmlspecialchars($wr_1 ?? ($write['wr_1'] ?? ''), ENT_QUOTES); ?>" placeholder="출처 텍스트">
                        <div class="source-link">
                            <input type="url" name="wr_2" id="wr_2" value="<?php echo htmlspecialchars($wr_2 ?? ($write['wr_2'] ?? ''), ENT_QUOTES); ?>" placeholder="https://">
                        </div>
                    </div>
                    <p class="source-help">텍스트만, 링크만, 텍스트+링크 모두 가능합니다.</p>
                </div>
            <?php } ?>

            <?php if ($is_secret) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" name="secret" value="secret" <?php echo $secret_checked; ?> 비밀글</label>
            </div>
            <?php } ?>

            <?php if ($is_mail) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" id="mail" name="mail" value="mail" <?php echo $recv_email_checked; ?> 메일 받기</label>
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
var sidepanelVideoBoard = <?php echo $sidepanel_is_video_board ? 'true' : 'false'; ?>;
var sidepanelVideoThumbSize = { width: 1170, height: 780 };

function sidepanelBuildThumbName(filename) {
    var base = (filename || 'video').replace(/\.[^.]+$/, '');
    return 'thumb-' + base + '_1170x780.jpg';
}

function sidepanelCalcCoverRect(srcW, srcH, destW, destH) {
    if (!srcW || !srcH || !destW || !destH) {
        return { sx: 0, sy: 0, sw: srcW, sh: srcH };
    }
    var srcRatio = srcW / srcH;
    var destRatio = destW / destH;
    var sw = srcW;
    var sh = srcH;
    var sx = 0;
    var sy = 0;
    if (srcRatio > destRatio) {
        sh = srcH;
        sw = srcH * destRatio;
        sx = (srcW - sw) / 2;
    } else {
        sw = srcW;
        sh = srcW / destRatio;
        sy = (srcH - sh) / 2;
    }
    return { sx: sx, sy: sy, sw: sw, sh: sh };
}

function sidepanelCanvasToBlob(canvas) {
    return new Promise(function (resolve, reject) {
        if (canvas.toBlob) {
            canvas.toBlob(function (blob) {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('blob_failed'));
                }
            }, 'image/jpeg', 0.9);
            return;
        }
        try {
            var dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            var parts = dataUrl.split(',');
            var mimeMatch = parts[0] ? parts[0].match(/:(.*?);/) : null;
            var mime = (mimeMatch && mimeMatch[1]) ? mimeMatch[1] : 'image/jpeg';
            var binary = atob(parts[1] || '');
            var len = binary.length;
            var arr = new Uint8Array(len);
            for (var i = 0; i < len; i++) {
                arr[i] = binary.charCodeAt(i);
            }
            resolve(new Blob([arr], { type: mime }));
        } catch (e) {
            reject(e);
        }
    });
}

function sidepanelCaptureFirstFrame(file) {
    return new Promise(function (resolve, reject) {
        var video = document.createElement('video');
        var objectUrl = URL.createObjectURL(file);
        var cleaned = false;
        var timeoutId = setTimeout(function () {
            fail('capture_timeout');
        }, 12000);
        var cleanup = function () {
            if (cleaned) return;
            cleaned = true;
            clearTimeout(timeoutId);
            try { URL.revokeObjectURL(objectUrl); } catch (e) {}
            video.removeAttribute('src');
            video.load();
            video.remove();
        };

        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.src = objectUrl;

        var fail = function (msg) {
            cleanup();
            reject(new Error(msg || 'capture_failed'));
        };

        video.addEventListener('error', function () { fail('video_error'); });

        video.addEventListener('loadedmetadata', function () {
            try {
                var seekTo = Math.min(Math.max(0.05, (video.duration || 1) * 0.02), Math.max(0.05, (video.duration || 1) - 0.1));
                video.currentTime = seekTo;
            } catch (e) {
                fail('metadata_seek_failed');
            }
        });

        video.addEventListener('seeked', function () {
            try {
                var targetW = sidepanelVideoThumbSize.width;
                var targetH = sidepanelVideoThumbSize.height;
                var canvas = document.createElement('canvas');
                canvas.width = targetW;
                canvas.height = targetH;
                var ctx = canvas.getContext('2d');
                var rect = sidepanelCalcCoverRect(video.videoWidth, video.videoHeight, targetW, targetH);
                ctx.drawImage(video, rect.sx, rect.sy, rect.sw, rect.sh, 0, 0, targetW, targetH);
                sidepanelCanvasToBlob(canvas).then(function (blob) {
                    cleanup();
                    resolve(blob);
                }).catch(function () {
                    fail('blob_failed');
                });
            } catch (e) {
                fail('draw_failed');
            }
        });
    });
}

function sidepanelCreateThumbFile(videoFile) {
    return sidepanelCaptureFirstFrame(videoFile).then(function (blob) {
        var name = sidepanelBuildThumbName(videoFile && videoFile.name ? videoFile.name : 'video');
        try {
            return new File([blob], name, { type: blob.type || 'image/jpeg' });
        } catch (e) {
            blob.name = name;
            return blob;
        }
    });
}

function sidepanelAttachThumbFiles(form, originalFiles, thumbFile) {
    var fileInput = form.querySelector("input[type='file'][name='bf_file[]']");
    if (!fileInput || typeof DataTransfer === 'undefined') return false;
    if (!fileInput.multiple) fileInput.multiple = true;

    var dt = new DataTransfer();
    for (var i = 0; i < originalFiles.length; i++) {
        dt.items.add(originalFiles[i]);
    }
    dt.items.add(thumbFile);
    fileInput.files = dt.files;
    return true;
}

function sidepanelToggleSubmitState(form, disabled) {
    var submitBtn = form.querySelector('.btn-submit');
    if (!submitBtn) return;
    if (disabled) {
        submitBtn.dataset.originalText = submitBtn.dataset.originalText || submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Generating thumbnail...';
    } else {
        if (submitBtn.dataset.originalText) {
            submitBtn.textContent = submitBtn.dataset.originalText;
        }
        submitBtn.disabled = false;
    }
}

function sidepanelCollectSelectedFiles(form) {
    var inputs = form.querySelectorAll("input[type='file'][name='bf_file[]']");
    var files = [];
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].files && inputs[i].files.length) {
            files = files.concat(Array.prototype.slice.call(inputs[i].files));
        }
    }
    return files;
}

function sidepanelSyncFileMeta(form) {
    var blocks = form.querySelectorAll('.file-meta-fields');
    for (var i = 0; i < blocks.length; i++) {
        var captionInput = blocks[i].querySelector('[data-caption]');
        var hiddenInput = blocks[i].querySelector("input[name='bf_content[]']");
        if (!hiddenInput) continue;
        var caption = captionInput ? captionInput.value.trim() : '';
        hiddenInput.value = caption;
    }
}

function sidepanelInsertToken(token) {
    if (!token) return;

    if (window.oEditors && oEditors.getById && oEditors.getById['wr_content']) {
        oEditors.getById['wr_content'].exec('PASTE_HTML', [token]);
        return;
    }

    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.wr_content) {
        CKEDITOR.instances.wr_content.insertText(token);
        return;
    }

    if (typeof get_editor_wr_content === 'function' && typeof put_editor_wr_content === 'function') {
        var current = get_editor_wr_content();
        put_editor_wr_content(current + token);
        return;
    }

    var textarea = document.getElementById('wr_content');
    if (!textarea) return;
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value || '';
    if (typeof start === 'number' && typeof end === 'number') {
        textarea.value = value.slice(0, start) + token + value.slice(end);
        textarea.selectionStart = textarea.selectionEnd = start + token.length;
    } else {
        textarea.value = value + token;
    }
    textarea.focus();
}

document.addEventListener('click', function (event) {
    var btn = event.target.closest('.insert-btn');
    if (!btn) return;
    event.preventDefault();
    sidepanelInsertToken(btn.getAttribute('data-insert'));
});

document.addEventListener('change', function (event) {
    var input = event.target;
    if (!input || input.type !== 'file') return;
    var field = input.closest('.file-field');
    if (!field) return;
    if (input.files && input.files.length) {
        field.classList.add('has-file');
        if (field.classList.contains('is-hidden')) {
            field.classList.remove('is-hidden');
        }
        return;
    }
    if (!field.querySelector('.file-existing')) {
        field.classList.remove('has-file');
    }
});

function sidepanelInitFileControls() {
    var list = document.querySelector('.file-field-list');
    if (!list) return;
    var addBtn = document.querySelector('.file-controls .file-add');
    var removeBtn = document.querySelector('.file-controls .file-remove');
    if (!addBtn && !removeBtn) return;
    var max = parseInt(list.getAttribute('data-max'), 10) || 0;

    function rows() {
        return Array.prototype.slice.call(list.querySelectorAll('.file-field'));
    }

    function visibleRows() {
        return rows().filter(function (row) {
            return !row.classList.contains('is-hidden');
        });
    }

    function updateControls() {
        var visible = visibleRows().length;
        if (addBtn) addBtn.disabled = max > 0 ? visible >= max : false;
        if (removeBtn) removeBtn.disabled = visible <= 1;
    }

    function showNext() {
        var next = rows().find(function (row) {
            return row.classList.contains('is-hidden');
        });
        if (!next) return;
        next.classList.remove('is-hidden');
        updateControls();
        var input = next.querySelector('input[type="file"]');
        if (input) input.focus();
    }

    function canHideRow(row) {
        if (row.querySelector('.file-existing')) return false;
        var input = row.querySelector('input[type="file"]');
        var hasNew = input && input.files && input.files.length;
        var caption = row.querySelector('[data-caption]');
        var hasCaption = caption && caption.value.trim();
        if (hasNew || hasCaption) return false;
        return true;
    }

    function hideLast() {
        var visible = visibleRows();
        if (visible.length <= 1) return;
        var row = visible[visible.length - 1];
        if (!canHideRow(row)) {
            alert('삭제하려면 파일을 비우거나 기존 파일은 삭제 체크하세요.');
            return;
        }
        row.classList.add('is-hidden');
        row.classList.remove('has-file');
        updateControls();
    }

    if (addBtn) {
        addBtn.addEventListener('click', showNext);
    }
    if (removeBtn) {
        removeBtn.addEventListener('click', hideLast);
    }

    updateControls();
}

document.addEventListener('DOMContentLoaded', sidepanelInitFileControls);

function fwrite_submit(f) {
    sidepanelSyncFileMeta(f);
    var autoSubject = "<?php echo addslashes($auto_subject); ?>";
    if (!f.wr_subject.value.trim()) {
        f.wr_subject.value = autoSubject;
    }
    var isNew = "<?php echo $w; ?>" === '';
    var hasExistingFile = <?php echo $sidepanel_has_existing_file ? 'true' : 'false'; ?>;
    var selectedFiles = sidepanelCollectSelectedFiles(f);
    var hasSelectedFile = selectedFiles.length > 0;
    var fileInput = f.querySelector("input[type='file'][name='bf_file[]']");

    if (isNew && !hasSelectedFile) {
        alert('이미지를 반드시 첨부해주세요.');
        if (fileInput) fileInput.focus();
        return false;
    }
    if (!isNew && !hasExistingFile && !hasSelectedFile) {
        alert('이미지를 첨부해주세요.');
        if (fileInput) fileInput.focus();
        return false;
    }
    var plainContent = (f.wr_content && f.wr_content.value) ? f.wr_content.value.replace(/<[^>]+>/g, '').trim() : '';
    if (!plainContent) {
        alert('내용을 입력해주세요.');
        return false;
    }
    <?php if ($is_dhtml_editor) echo chk_editor_js('wr_content'); ?>

    if (sidepanelVideoBoard && hasSelectedFile) {
        var nonVideoFiles = selectedFiles.filter(function (file) {
            return !(file && file.type && file.type.indexOf('video/') === 0);
        });
        if (nonVideoFiles.length) {
            alert('프로모션 비디오는 동영상 파일만 등록할 수 있습니다.');
            return false;
        }

        sidepanelToggleSubmitState(f, true);
        sidepanelCreateThumbFile(selectedFiles[0]).then(function (thumbFile) {
            var attached = sidepanelAttachThumbFiles(f, selectedFiles, thumbFile);
            sidepanelToggleSubmitState(f, false);
            if (!attached) {
                alert('Unable to attach the generated thumbnail automatically in this browser.');
                return;
            }
            f.submit();
        }).catch(function (err) {
            console.error('[sidepanel] video thumbnail generation failed', err);
            sidepanelToggleSubmitState(f, false);
            alert('Failed to generate a thumbnail from the video. Please try again.');
        });
        return false;
    }

    return true;
}
</script>
