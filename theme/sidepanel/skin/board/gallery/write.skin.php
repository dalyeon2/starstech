<?php
if (!defined('_GNUBOARD_')) exit;

if (function_exists('add_stylesheet') && defined('G5_THEME_URL')) {
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/skin/board/gallery/style.css">', 1);
}

$gallery_placeholder = 'https://via.placeholder.com/360x240/ededed/1f1f1f?text=No+Image';
$file_count = isset($board['bo_upload_count']) ? (int)$board['bo_upload_count'] : 1;
if ($file_count < 3) $file_count = 3;
if ($file_count > 3) $file_count = 3;
$show_link = isset($bo_table) && $bo_table === 'pr';
$show_source = isset($bo_table) && $bo_table === 'news';
$can_sort = false;
$lang_codes = ['ko', 'en', 'ja', 'fr', 'mn'];
$lang_options = [];
if (!empty($board['bo_category_list'])) {
    $raw_categories = array_map('trim', explode('|', $board['bo_category_list']));
    foreach ($raw_categories as $cat) {
        if ($cat === '') continue;
        $lower = strtolower($cat);
        if (in_array($lower, $lang_codes, true)) {
            $lang_options[$lower] = $cat;
        }
    }
}
$use_lang_filter = !empty($lang_options);
$default_category = $ca_name ?? '';
if (!$default_category && $lang_options) {
    $values = array_values($lang_options);
    $default_category = $values[0] ?? '';
}
$has_existing_file = false;
if ($w === 'u' && !empty($file) && is_array($file)) {
    foreach ($file as $file_item) {
        if (!empty($file_item['file'])) {
            $has_existing_file = true;
            break;
        }
    }
}

if (!function_exists('sidepanel_gallery_is_youtube_link')) {
    function sidepanel_gallery_is_youtube_link($url) {
        if (!$url) return false;
        return (bool)preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url);
    }
}

$pr_youtube = $show_link && !empty($write['wr_link1']) && sidepanel_gallery_is_youtube_link($write['wr_link1']);
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
                <?php if ($use_lang_filter) { ?>
                    <div class="board-field category-tabs">
                        <label>언어</label>
                        <div class="category-list" role="tablist" aria-label="언어 선택">
                            <?php $is_first = true; foreach ($lang_options as $lang_value) {
                                $is_active = ($ca_name === $lang_value) || (!$ca_name && $is_first);
                            ?>
                                <button type="button" class="category-tab<?php echo $is_active ? ' is-active' : ''; ?>" data-category="<?php echo htmlspecialchars($lang_value, ENT_QUOTES); ?>" role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                                    <?php echo htmlspecialchars($lang_value, ENT_QUOTES); ?>
                                </button>
                            <?php $is_first = false; } ?>
                        </div>
                        <input type="hidden" name="ca_name" id="ca_name" value="<?php echo htmlspecialchars($default_category, ENT_QUOTES); ?>" required>
                    </div>
                <?php } else { ?>
                    <div class="board-field">
                        <label for="ca_name">언어</label>
                        <select name="ca_name" id="ca_name" required>
                            <option value="">선택</option>
                            <?php echo $category_option; ?>
                        </select>
                    </div>
                <?php } ?>
            <?php } ?>

            <?php if ($show_link) { ?>
                <div class="board-field pr-type">
                    <label>PR 유형</label>
                    <div class="pr-type-options" role="radiogroup" aria-label="PR 유형 선택">
                        <label class="pr-type-option">
                            <input type="radio" name="pr_type" value="image"<?php echo $pr_youtube ? '' : ' checked'; ?>>
                            <span>이미지</span>
                        </label>
                        <label class="pr-type-option">
                            <input type="radio" name="pr_type" value="youtube"<?php echo $pr_youtube ? ' checked' : ''; ?>>
                            <span>유튜브</span>
                        </label>
                    </div>
                </div>
            <?php } ?>
            <div class="board-field">
                <label for="wr_subject">제목</label>
                <input type="text" name="wr_subject" id="wr_subject" value="<?php echo $subject; ?>" required>
            </div>
            <?php if ($show_link) { ?>
                <div class="board-field link-field">
                    <label for="wr_link1">외부 링크</label>
                    <div class="link-input">
                        <i class="fa-solid fa-link" aria-hidden="true"></i>
                        <input type="url" name="wr_link1" id="wr_link1" value="<?php echo htmlspecialchars($write['wr_link1'] ?? '', ENT_QUOTES); ?>" placeholder="https://">
                    </div>
                    <div class="link-preview" data-link-preview aria-live="polite"></div>
                    <p class="form-help">유튜브 링크를 넣으면 상세 페이지에 썸네일/설명이 표시됩니다.</p>
                </div>
            <?php } ?>
            <?php if ($is_file) { ?>
                <?php
                $visible_slots = 1;
                if ($w === 'u' && !empty($file) && is_array($file)) {
                    foreach ($file as $idx => $file_item) {
                        if (!empty($file_item['file'])) {
                            $visible_slots = max($visible_slots, $idx + 1);
                        }
                    }
                }
                if ($visible_slots > $file_count) {
                    $visible_slots = $file_count;
                }
                $has_file_controls = ($file_count > 1);
                ?>
                <div class="board-field file-group">
                    <label>이미지</label>
                    <div class="form-help">텍스트 사이에 이미지를 넣을 때 아래 삽입 코드를 내용에 추가하세요.</div>
                    <div class="file-field-list<?php echo $has_file_controls ? ' has-controls' : ''; ?>" data-max="<?php echo $file_count; ?>">
                    <?php for ($i=0; $i<$file_count; $i++) {
                        $existing_file = $file[$i] ?? [];
                        $existing_src = '';
                        if (!empty($existing_file['path']) && !empty($existing_file['file'])) {
                            $existing_src = rtrim($existing_file['path'], '/') . '/' . rawurlencode($existing_file['file']);
                    } elseif (!empty($existing_file['href'])) {
                        $existing_src = $existing_file['href'];
                    }
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
                        $is_hidden = ($i >= $visible_slots);
                    ?>
                    <div class="board-field file-field<?php echo $has_existing_file ? ' has-file' : ''; ?><?php echo $is_hidden && !$has_existing_file ? ' is-hidden' : ''; ?>">
                        <div class="file-box">
                            <?php if ($has_existing_file) { ?>
                                <div class="file-existing">
                                    <div class="thumb">
                                        <?php if ($existing_src) { ?>
                                            <img src="<?php echo $existing_src; ?>" alt="<?php echo get_text($existing_file['source']); ?>" onerror="this.src='<?php echo $gallery_placeholder; ?>';">
                                        <?php } else { ?>
                                            <img src="<?php echo $gallery_placeholder; ?>" alt="">
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
                            <input type="file" name="bf_file[]" id="bf_file_<?php echo $i; ?>" class="file-input" accept="image/*" aria-label="이미지 파일">
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
                        <?php if ($has_file_controls) { ?>
                            <div class="file-controls">
                                <button type="button" class="file-add icon-btn" aria-label="이미지 추가" title="이미지 추가"><i class="fa-solid fa-plus"></i></button>
                                <button type="button" class="file-remove icon-btn" aria-label="이미지 삭제" title="이미지 삭제"><i class="fa-solid fa-minus"></i></button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <div class="board-field">
                <label for="wr_content">내용</label>
                <div class="editor-preview" data-preview-wrap>
                    <div class="editor-pane">
                        <div class="editor-wrap">
                            <?php echo $editor_html; ?>
                        </div>
                        <button type="button" class="preview-toggle" data-preview-toggle aria-label="미리보기 열기" title="미리보기 열기">
                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="preview-pane">
                        <div class="preview-box" id="sidepanel_preview" hidden>
                            <div class="preview-empty">미리보기에서 이미지 위치를 확인할 수 있습니다.</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($show_source) { ?>
                <div class="board-field source-field">
                    <label for="wr_1">출처</label>
                    <div class="source-inputs">
                        <input type="text" name="wr_1" id="wr_1" value="<?php echo htmlspecialchars($wr_1 ?? ($write['wr_1'] ?? ''), ENT_QUOTES); ?>" placeholder="출처 텍스트">
                        <div class="source-link">
                            <input type="url" name="wr_2" id="wr_2" value="<?php echo htmlspecialchars($wr_2 ?? ($write['wr_2'] ?? ''), ENT_QUOTES); ?>" placeholder="https://">
                        </div>
                    </div>
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
        var field = blocks[i].closest('.file-field');
        var hiddenInput = field ? field.querySelector("input[name='bf_content[]']") : null;
        if (!hiddenInput) continue;
        var caption = captionInput ? captionInput.value.trim() : '';
        hiddenInput.value = caption;
    }
}

function sidepanelGetEditorContent(form) {
    if (window.oEditors && oEditors.getById && oEditors.getById['wr_content']) {
        return oEditors.getById['wr_content'].getIR();
    }
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.wr_content) {
        return CKEDITOR.instances.wr_content.getData();
    }
    if (typeof get_editor_wr_content === 'function') {
        return get_editor_wr_content();
    }
    return (form && form.wr_content) ? (form.wr_content.value || '') : '';
}

function sidepanelSetEditorContent(form, value) {
    if (window.oEditors && oEditors.getById && oEditors.getById['wr_content']) {
        oEditors.getById['wr_content'].exec('SET_IR', [value]);
        return;
    }
    if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.wr_content) {
        CKEDITOR.instances.wr_content.setData(value);
        return;
    }
    if (typeof put_editor_wr_content === 'function') {
        put_editor_wr_content(value);
        return;
    }
    if (form && form.wr_content) form.wr_content.value = value;
}

function sidepanelCleanMediaTokens(form) {
    var deleteChecks = form.querySelectorAll("input[name^='bf_file_del']");
    if (!deleteChecks.length) return;
    var targets = [];
    for (var i = 0; i < deleteChecks.length; i++) {
        var input = deleteChecks[i];
        if (!input.checked) continue;
        var match = input.name.match(/\[(\d+)\]/);
        if (!match) continue;
        var idx = parseInt(match[1], 10);
        if (!isNaN(idx)) targets.push(idx + 1);
    }
    if (!targets.length) return;

    var content = sidepanelGetEditorContent(form);
    if (!content) return;

    var updated = content;
    for (var j = 0; j < targets.length; j++) {
        var token = targets[j];
        var re = new RegExp('\\[(?:media|image):\\s*' + token + '\\]', 'gi');
        updated = updated.replace(re, '');
    }
    if (updated === content) return;

    sidepanelSetEditorContent(form, updated);
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
    sidepanelSchedulePreviewUpdate();
}

var sidepanelPreviewTimer = null;
var sidepanelPreviewCache = {};

function sidepanelSyncPreviewHeight() {
    var wrap = document.querySelector('[data-preview-wrap]');
    var preview = document.getElementById('sidepanel_preview');
    if (!wrap || !preview || !wrap.classList.contains('is-preview-open')) {
        var editorPane = wrap ? wrap.querySelector('.editor-pane') : null;
        if (editorPane) editorPane.style.minHeight = '';
        if (preview) preview.style.minHeight = '';
        return;
    }
    if (preview.hasAttribute('hidden')) return;
    var editorPane = wrap.querySelector('.editor-pane');
    if (!editorPane) return;
    editorPane.style.minHeight = '';
    preview.style.minHeight = '';
    var target = Math.max(editorPane.offsetHeight, preview.offsetHeight);
    if (target > 0) {
        editorPane.style.minHeight = target + 'px';
        preview.style.minHeight = target + 'px';
    }
}

function sidepanelGetPreviewUrl(input) {
    if (!input || !input.files || !input.files.length) {
        if (input && input.dataset.previewUrl) {
            URL.revokeObjectURL(input.dataset.previewUrl);
            input.dataset.previewUrl = '';
            input.dataset.previewKey = '';
        }
        return '';
    }
    var file = input.files[0];
    var key = [file.name, file.size, file.lastModified].join('_');
    if (input.dataset.previewKey !== key) {
        if (input.dataset.previewUrl) {
            URL.revokeObjectURL(input.dataset.previewUrl);
        }
        input.dataset.previewUrl = URL.createObjectURL(file);
        input.dataset.previewKey = key;
    }
    return input.dataset.previewUrl || '';
}

function sidepanelBuildMediaMap(form) {
    var map = {};
    var fields = form.querySelectorAll('.file-field');
    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var index = i + 1;
        var captionInput = field.querySelector('[data-caption]');
        var caption = captionInput ? captionInput.value.trim() : '';
        var input = field.querySelector("input[type='file'][name='bf_file[]']");
        var src = '';
        if (input && input.files && input.files.length) {
            src = sidepanelGetPreviewUrl(input);
        } else {
            var existing = field.querySelector('.file-existing img');
            if (existing) {
                src = existing.getAttribute('src') || '';
            }
        }
        if (src) {
            map[index] = { src: src, caption: caption };
        }
    }
    return map;
}

function sidepanelUpdatePreview() {
    var preview = document.getElementById('sidepanel_preview');
    var form = document.getElementById('fwrite');
    if (!preview || !form) return;

    var raw = sidepanelGetEditorContent(form) || '';
    var hasToken = /\[(?:media|image)\s*:\s*\d+\]/i.test(raw);
    var plain = raw.replace(/<[^>]+>/g, '').trim();
    if (!plain && !hasToken) {
        preview.innerHTML = '<div class="preview-empty">미리보기에서 이미지 위치를 확인할 수 있습니다.</div>';
        return;
    }

    var html = raw;
    if (!/<[a-z][\s\S]*>/i.test(html)) {
        html = html.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\r?\n/g, '<br>');
    }
    html = html.replace(/\[(?:media|image)\s*:\s*(\d+)\]/gi, function (_, index) {
        return '<span class="media-token" data-index="' + index + '"></span>';
    });

    preview.innerHTML = '<div class="preview-text">' + html + '</div>';
    var container = preview.querySelector('.preview-text');
    var map = sidepanelBuildMediaMap(form);
    var tokens = container.querySelectorAll('[data-index]');
    tokens.forEach(function (token) {
        var index = token.getAttribute('data-index');
        var media = map[index];
        if (!media) {
            token.remove();
            return;
        }
        var fig = document.createElement('figure');
        fig.className = 'preview-media';
        var frame = document.createElement('div');
        frame.className = 'media-frame';
        var img = document.createElement('img');
        img.src = media.src;
        img.alt = '';
        frame.appendChild(img);
        fig.appendChild(frame);
        if (media.caption) {
            var cap = document.createElement('figcaption');
            cap.textContent = media.caption;
            fig.appendChild(cap);
        }
        token.replaceWith(fig);
    });
    sidepanelSyncPreviewHeight();
}

function sidepanelSchedulePreviewUpdate() {
    if (sidepanelPreviewTimer) return;
    sidepanelPreviewTimer = setTimeout(function () {
        sidepanelPreviewTimer = null;
        sidepanelUpdatePreview();
    }, 200);
}

function sidepanelStartPreviewWatch() {
    var form = document.getElementById('fwrite');
    var preview = document.getElementById('sidepanel_preview');
    if (!form || !preview) return;
    var last = '';
    setInterval(function () {
        var current = sidepanelGetEditorContent(form) || '';
        if (current !== last) {
            last = current;
            sidepanelSchedulePreviewUpdate();
        }
    }, 800);
}

document.addEventListener('click', function (event) {
    var btn = event.target.closest('.insert-btn');
    if (!btn) return;
    event.preventDefault();
    sidepanelInsertToken(btn.getAttribute('data-insert'));
    sidepanelSchedulePreviewUpdate();
});

function sidepanelInitCategoryTabs() {
    var wrap = document.querySelector('.category-tabs');
    if (!wrap) return;
    var input = document.getElementById('ca_name');
    var tabs = wrap.querySelectorAll('.category-tab');
    if (!tabs.length || !input) return;

    function setActive(value) {
        tabs.forEach(function (tab) {
            var active = tab.getAttribute('data-category') === value;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        input.value = value;
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            setActive(tab.getAttribute('data-category'));
        });
    });

    if (!input.value) {
        setActive(tabs[0].getAttribute('data-category'));
    }
}

function sidepanelIsYoutubeLink(url) {
    if (!url) return false;
    return /(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i.test(url);
}

var sidepanelYoutubeMetaEndpoint = "<?php echo G5_URL; ?>/api/youtube_meta.php?url=";

function sidepanelFetchYoutubeMeta(url, done) {
    if (!url || !window.fetch) {
        if (done) done(null);
        return;
    }
    var endpoint = sidepanelYoutubeMetaEndpoint + encodeURIComponent(url);
    fetch(endpoint).then(function (res) {
        if (!res.ok) throw new Error('youtube_meta_failed');
        return res.json();
    }).then(function (data) {
        if (!data || !data.success) {
            if (done) done(null);
            return;
        }
        if (done) done(data);
    }).catch(function () {
        if (done) done(null);
    });
}

function sidepanelInitPrControls() {
    var isPr = <?php echo $show_link ? 'true' : 'false'; ?>;
    if (!isPr) return;
    var form = document.getElementById('fwrite');
    var wrap = form ? form.closest('.board-write') : null;
    var linkInput = document.getElementById('wr_link1');
    var subjectInput = document.getElementById('wr_subject');
    var preview = document.querySelector('[data-link-preview]');
    var previewWrap = document.querySelector('[data-preview-wrap]');
    var previewBox = document.getElementById('sidepanel_preview');
    var previewToggle = document.querySelector('[data-preview-toggle]');
    var radios = document.querySelectorAll('input[name="pr_type"]');
    var currentMode = null;
    var youtubeRequestId = 0;
    var isInit = true;

    function getSelectedMode() {
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return 'image';
    }

    function getPlainContent() {
        var raw = sidepanelGetEditorContent(form) || '';
        return raw.replace(/<[^>]+>/g, '').trim();
    }

    function hasDirtyState() {
        if (getPlainContent()) return true;
        if (subjectInput && subjectInput.value.trim()) return true;
        if (linkInput && linkInput.value.trim()) return true;
        var fields = form.querySelectorAll('.file-field');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            var input = field.querySelector("input[type='file'][name='bf_file[]']");
            if (input && input.files && input.files.length) return true;
            var caption = field.querySelector('[data-caption]');
            if (caption && caption.value.trim()) return true;
            var existing = field.querySelector('.file-existing');
            if (existing) {
                var delInput = field.querySelector("input[name^='bf_file_del']");
                if (!delInput || !delInput.checked) return true;
            }
        }
        return false;
    }

    function confirmTabChange(nextMode) {
        if (currentMode && nextMode === currentMode) return true;
        if (!hasDirtyState()) return true;
        return confirm('작성 중인 내용이 있습니다. \n탭을 변경하면 내용/제목/첨부 이미지가 초기화됩니다. \n계속하시겠습니까?');
    }

    function clearContent() {
        sidepanelSetEditorContent(form, '');
        if (subjectInput) {
            subjectInput.value = '';
            subjectInput.dataset.auto = '0';
        }
        if (linkInput) {
            linkInput.value = '';
            updatePreview(null);
        }
        var fileInputs = form.querySelectorAll("input[type='file'][name='bf_file[]']");
        for (var fi = 0; fi < fileInputs.length; fi++) {
            fileInputs[fi].value = '';
        }
        var captions = form.querySelectorAll('[data-caption]');
        for (var ci = 0; ci < captions.length; ci++) {
            captions[ci].value = '';
        }
        var hiddenContents = form.querySelectorAll("input[name='bf_content[]']");
        for (var hi = 0; hi < hiddenContents.length; hi++) {
            hiddenContents[hi].value = '';
        }
        var deleteChecks = form.querySelectorAll("input[name^='bf_file_del']");
        for (var di = 0; di < deleteChecks.length; di++) {
            deleteChecks[di].checked = true;
        }
        var fileFields = form.querySelectorAll('.file-field');
        for (var ff = 0; ff < fileFields.length; ff++) {
            fileFields[ff].classList.remove('has-file');
        }
        var existingBlocks = form.querySelectorAll('.file-existing');
        for (var eb = 0; eb < existingBlocks.length; eb++) {
            existingBlocks[eb].style.display = 'none';
        }
        sidepanelSchedulePreviewUpdate();
    }

    function setMode(mode, options) {
        var opts = options || {};
        if (!opts.skipConfirm && !confirmTabChange(mode)) {
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].value === currentMode) {
                    radios[i].checked = true;
                }
            }
            return false;
        }
        if (!opts.skipConfirm && currentMode && mode !== currentMode && hasDirtyState()) {
            clearContent();
        }
        if (wrap) {
            wrap.classList.toggle('is-pr-youtube', mode === 'youtube');
            wrap.classList.toggle('is-pr-image', mode === 'image');
        }
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].value === mode) {
                radios[i].checked = true;
            }
        }
        if (mode === 'image' && linkInput && linkInput.value) {
            linkInput.value = '';
            updatePreview(null);
        }
        if (mode === 'youtube') {
            closeEditorPreview();
        }
        if (mode !== 'youtube') {
            youtubeRequestId += 1;
        }
        currentMode = mode;
        return true;
    }

    function closeEditorPreview() {
        if (!previewBox) return;
        previewBox.setAttribute('hidden', '');
        if (previewWrap) previewWrap.classList.remove('is-preview-open');
        if (previewToggle) {
            var icon = previewToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        sidepanelSyncPreviewHeight();
    }

    function updatePreview(meta) {
        if (!preview) return;
        if (!meta || !meta.title) {
            preview.textContent = '';
            return;
        }
        preview.innerHTML = '<div class="link-title">' + meta.title + '</div><div class="link-sub">YouTube 자동 제목</div>';
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[char] || char;
        });
    }

    function updateContent(desc) {
        if (!desc) return;
        if (getPlainContent()) return;
        var safe = escapeHtml(desc);
        sidepanelSetEditorContent(form, safe);
    }

    function updateSubject(title) {
        if (!subjectInput || !title) return;
        if (!subjectInput.value.trim() || subjectInput.dataset.auto === '1') {
            subjectInput.value = title;
            subjectInput.dataset.auto = '1';
        }
    }

    function syncFromLink() {
        if (!linkInput) return;
        var url = linkInput.value.trim();
        var selected = getSelectedMode();
        if (selected === 'youtube' && sidepanelIsYoutubeLink(url)) {
            if (!setMode('youtube', { skipConfirm: isInit })) return;
            var requestId = ++youtubeRequestId;
            sidepanelFetchYoutubeMeta(url, function (meta) {
                if (requestId !== youtubeRequestId) return;
                if (getSelectedMode() !== 'youtube') return;
                if (!linkInput || linkInput.value.trim() !== url) return;
                if (meta && meta.title) {
                    updatePreview(meta);
                    updateSubject(meta.title);
                    updateContent(meta.description || '');
                }
            });
            return;
        }
        updatePreview(null);
        setMode(selected, { skipConfirm: isInit });
    }

    if (subjectInput) {
        subjectInput.addEventListener('input', function () {
            subjectInput.dataset.auto = '0';
        });
    }

    for (var j = 0; j < radios.length; j++) {
        radios[j].addEventListener('change', function () {
            setMode(this.value);
        });
    }

    if (linkInput) {
        linkInput.addEventListener('blur', syncFromLink);
        linkInput.addEventListener('change', syncFromLink);
    }

    currentMode = getSelectedMode();
    syncFromLink();
    isInit = false;
}

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
    sidepanelSchedulePreviewUpdate();
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

document.addEventListener('DOMContentLoaded', sidepanelInitCategoryTabs);
document.addEventListener('DOMContentLoaded', sidepanelInitFileControls);
document.addEventListener('DOMContentLoaded', sidepanelInitPrControls);
document.addEventListener('DOMContentLoaded', sidepanelUpdatePreview);
document.addEventListener('DOMContentLoaded', sidepanelStartPreviewWatch);

document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-preview-toggle]');
    if (!btn) return;
    var preview = document.getElementById('sidepanel_preview');
    if (!preview) return;
    var wrap = btn.closest('[data-preview-wrap]');
    var isHidden = preview.hasAttribute('hidden');
    if (isHidden) {
        preview.removeAttribute('hidden');
        if (wrap) wrap.classList.add('is-preview-open');
        btn.setAttribute('aria-label', '미리보기 닫기');
        btn.setAttribute('title', '미리보기 닫기');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        sidepanelUpdatePreview();
    } else {
        preview.setAttribute('hidden', '');
        if (wrap) wrap.classList.remove('is-preview-open');
        btn.setAttribute('aria-label', '미리보기 열기');
        btn.setAttribute('title', '미리보기 열기');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
        sidepanelSyncPreviewHeight();
    }
});
document.addEventListener('input', function (event) {
    if (!event.target) return;
    if (event.target.matches('[data-caption]') || event.target.id === 'wr_content') {
        sidepanelSchedulePreviewUpdate();
    }
});

function fwrite_submit(f) {
    sidepanelSyncFileMeta(f);
    sidepanelCleanMediaTokens(f);
    var categoryInput = f.querySelector('#ca_name');
    if (categoryInput && !categoryInput.value) {
        alert('언어를 선택해주세요.');
        return false;
    }
    var autoSubject = "<?php echo addslashes($auto_subject); ?>";
    if (!f.wr_subject.value.trim()) {
        f.wr_subject.value = autoSubject;
    }
    var isNew = "<?php echo $w; ?>" === '';
    var hasExistingFile = <?php echo $has_existing_file ? 'true' : 'false'; ?>;
    var existingNodes = f.querySelectorAll('.file-existing');
    if (!hasExistingFile && existingNodes.length) {
        hasExistingFile = true;
    }
    if (hasExistingFile && existingNodes.length) {
        var remaining = 0;
        for (var r = 0; r < existingNodes.length; r++) {
            var delInput = existingNodes[r].closest('.file-field').querySelector("input[name^='bf_file_del']");
            if (!delInput || !delInput.checked) {
                remaining++;
            }
        }
        if (!remaining) {
            hasExistingFile = false;
        }
    }
    var selectedFiles = sidepanelCollectSelectedFiles(f);
    var hasSelectedFile = selectedFiles.length > 0;
    var fileInput = f.querySelector("input[type='file'][name='bf_file[]']");
    var linkInput = f.querySelector('#wr_link1');
    var isPr = <?php echo $show_link ? 'true' : 'false'; ?>;
    var isYoutube = isPr && linkInput && sidepanelIsYoutubeLink(linkInput.value.trim());
    var prTypeInput = f.querySelector('input[name="pr_type"]:checked');
    var prType = prTypeInput ? prTypeInput.value : '';

    if (isPr && prType === 'youtube' && !isYoutube) {
        alert('유튜브 링크를 입력해주세요.');
        if (linkInput) linkInput.focus();
        return false;
    }

    var rawContent = sidepanelGetEditorContent(f) || '';
    var hasMediaToken = /\[(?:media|image)\s*:\s*\d+\]/i.test(rawContent);
    var discardFiles = false;
    if (!isYoutube && (hasSelectedFile || hasExistingFile) && !hasMediaToken) {
        if (!confirm('내용에 이미지 삽입 코드가 없습니다. 이대로 작성하면 첨부한 이미지 파일이 저장되지 않습니다. 계속하시겠습니까?')) {
            return false;
        }
        discardFiles = true;
        var fileInputs = f.querySelectorAll("input[type='file'][name='bf_file[]']");
        for (var fi = 0; fi < fileInputs.length; fi++) {
            fileInputs[fi].value = '';
        }
        var deleteInputs = f.querySelectorAll("input[name^='bf_file_del']");
        for (var di = 0; di < deleteInputs.length; di++) {
            deleteInputs[di].checked = true;
        }
        var fileFields = f.querySelectorAll('.file-field');
        for (var ff = 0; ff < fileFields.length; ff++) {
            fileFields[ff].classList.remove('has-file');
        }
        hasSelectedFile = false;
        hasExistingFile = false;
    }

    var hasDeleteChecked = !!f.querySelector("input[name^='bf_file_del']:checked");
    if (!discardFiles) {
        if (isNew && !hasSelectedFile && !isYoutube) {
            alert('이미지를 반드시 첨부해주세요.');
            if (fileInput) fileInput.focus();
            return false;
        }
        if (!isNew && !hasExistingFile && !hasSelectedFile && !isYoutube && !hasDeleteChecked) {
            alert('이미지를 첨부해주세요.');
            if (fileInput) fileInput.focus();
            return false;
        }
    }

    var plainContent = rawContent ? rawContent.replace(/<[^>]+>/g, '').trim() : '';
    if (!plainContent && !isYoutube) {
        alert('내용을 입력해주세요.');
        return false;
    }
    <?php if ($is_dhtml_editor) echo chk_editor_js('wr_content'); ?>
    return true;
}
</script>
