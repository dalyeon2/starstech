<?php
if (!defined('_GNUBOARD_')) exit;

$theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/skin/board/product_lang/style.css">', 1);
}

$list_href = $list_href ?? '';

$product_placeholder = G5_URL . '/img/default-image.jpg';
$product_image_count = 3;
$product_manual_accept = '.pdf,.hwp,.hwpx,.doc,.docx';
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
$product_manual_count = count($product_manual_langs);
$product_file_count = $product_image_count + $product_manual_count;
$product_manual_map = [];
foreach ($product_manual_langs as $idx => $lang_key) {
    $product_manual_map[$lang_key] = $product_manual_start + $idx;
}

$lang_codes = array_keys($product_langs);
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

$category_default = '';
if (!empty($board['bo_category_list'])) {
    $raw_categories = array_map('trim', explode('|', $board['bo_category_list']));
    $category_list = array_values(array_filter($raw_categories, 'strlen'));
    $category_default = $category_list[0] ?? '';
    foreach ($category_list as $cat) {
        $lower = strtolower($cat);
        if ($lower === 'ko' || $cat === '한국어') {
            $category_default = $cat;
            break;
        }
    }
}
if (!empty($ca_name)) {
    $category_default = $ca_name;
} elseif (!empty($sca)) {
    $category_default = $sca;
} elseif ($is_category && !$category_default) {
    $category_default = 'ko';
}

$default_lang = 'ko';
$selected_lang = '';
if (!empty($write['wr_1'])) {
    $selected_lang = strtolower(trim($write['wr_1']));
}
if (!$selected_lang && !empty($ca_name)) {
    $selected_lang = strtolower(trim($ca_name));
}
if (!$selected_lang && !empty($sca)) {
    $selected_lang = strtolower(trim($sca));
}
if (!$selected_lang && $category_default) {
    $selected_lang = strtolower(trim($category_default));
}
if (!$selected_lang && isset($_GET['lang'])) {
    $selected_lang = strtolower(trim($_GET['lang']));
}
if (!isset($product_langs[$selected_lang])) {
    $selected_lang = $default_lang;
}
$lang_locked = false;

if ($use_lang_filter) {
    if (isset($lang_options[$selected_lang])) {
        $category_default = $lang_options[$selected_lang];
    } elseif (!$category_default) {
        $first_lang = reset($lang_options);
        if ($first_lang) $category_default = $first_lang;
    }
}

$product_data = [];
$product_raw = $write['wr_content'] ?? '';
if ($product_raw) {
    $decoded = json_decode($product_raw, true);
    if (is_array($decoded)) {
        $product_data = $decoded;
    }
}
$feature_rows = 1;
$selected_lang_data = [];
if (!empty($product_data['lang'][$selected_lang]) && is_array($product_data['lang'][$selected_lang])) {
    $selected_lang_data = $product_data['lang'][$selected_lang];
}
$lang_features = $selected_lang_data['features'] ?? [];
if (is_array($lang_features)) {
    $count = count($lang_features);
    if ($count > $feature_rows) $feature_rows = $count;
}

if (!function_exists('sidepanel_product_lang_value')) {
    function sidepanel_product_lang_value($lang_data, $key) {
        return isset($lang_data[$key]) ? $lang_data[$key] : '';
    }
}

if (!function_exists('sidepanel_product_items_text')) {
    function sidepanel_product_items_text($items) {
        if (!is_array($items)) return '';
        $clean = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== '') $clean[] = $item;
        }
        return implode("\n", $clean);
    }
}
?>

<link rel="stylesheet" href="<?php echo $theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $theme_url; ?>/skin/board/product_lang/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

<div class="section board board-write product-write">
    <div class="section-header">
        <h2><?php echo $board['bo_subject']; ?></h2>
        <div class="top-actions">
            <?php if (!empty($list_href)) { ?><a class="btn-minimal" href="<?php echo $list_href; ?>"><i class="fa-solid fa-list"></i> 목록</a><?php } ?>
        </div>
    </div>

    <form name="fwrite" id="fwrite" action="<?php echo $action_url; ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
        <input type="hidden" name="w" value="<?php echo $w; ?>">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
        <input type="hidden" name="wr_id" value="<?php echo $wr_id; ?>">
        <input type="hidden" name="sca" value="<?php echo htmlspecialchars($category_default, ENT_QUOTES); ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
        <input type="hidden" name="stx" value="<?php echo $stx; ?>">
        <input type="hidden" name="spt" value="<?php echo $spt; ?>">
        <input type="hidden" name="sst" value="<?php echo $sst; ?>">
        <input type="hidden" name="sod" value="<?php echo $sod; ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        <input type="hidden" name="wr_subject" id="wr_subject" value="<?php echo htmlspecialchars($subject ?? '', ENT_QUOTES); ?>">
        <input type="hidden" name="wr_1" id="wr_1" value="<?php echo htmlspecialchars($selected_lang, ENT_QUOTES); ?>">
        <textarea name="wr_content" id="wr_content" style="display:none;"><?php echo htmlspecialchars($product_raw, ENT_QUOTES); ?></textarea>

        <div class="board-form">
            <div class="board-field category-tabs">
                <label>언어</label>
                <div class="category-list" role="tablist" aria-label="언어 선택">
                    <?php foreach ($product_langs as $lang_key => $lang_label) {
                        $is_active = ($lang_key === $selected_lang);
                        $category_value = $use_lang_filter ? ($lang_options[$lang_key] ?? '') : '';
                    ?>
                        <button type="button" class="category-tab<?php echo $is_active ? ' is-active' : ''; ?>" data-lang-tab data-lang="<?php echo $lang_key; ?>"<?php if ($category_value !== '') { ?> data-category="<?php echo htmlspecialchars($category_value, ENT_QUOTES); ?>"<?php } ?> role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"<?php echo $lang_locked ? ' disabled' : ''; ?>>
                            <?php echo htmlspecialchars($lang_label, ENT_QUOTES); ?>
                        </button>
                    <?php } ?>
                </div>
                <?php if ($is_category && $use_lang_filter) { ?>
                    <input type="hidden" name="ca_name" id="ca_name" value="<?php echo htmlspecialchars($category_default, ENT_QUOTES); ?>" required>
                <?php } ?>
            </div>
            <div class="board-field product-info">
                <label>제품 소개</label>
                <div class="product-info-grid">
                                        <?php
                        $lang_key = $selected_lang;
                        $lang_data = $selected_lang_data;
                        $lang_name = $product_lang_names[$lang_key] ?? ($product_langs[$lang_key] ?? strtoupper($lang_key));
                    ?>
                        <div class="product-info-card" data-lang="<?php echo $lang_key; ?>">
                        <div class="card-head">
                            <span class="lang-icon" aria-hidden="true"><?php echo strtoupper($lang_key); ?></span>
                            <span class="lang-text"><?php echo $lang_name; ?></span>
                        </div>
                            <div class="card-row">
                                <span class="row-label">제품명</span>
                                <input type="text" data-field="title" data-lang="<?php echo $lang_key; ?>" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'title'), ENT_QUOTES); ?>" placeholder="제품명을 입력해 주세요.">
                            </div>
                            <div class="card-row">
                                <span class="row-label">구매방법</span>
                                <input type="text" data-field="buy" data-lang="<?php echo $lang_key; ?>" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'buy'), ENT_QUOTES); ?>" placeholder="구매방법을 입력해 주세요.">
                            </div>
                            <div class="card-row">
                                <span class="row-label">판매가</span>
                                <input type="text" data-field="price" data-lang="<?php echo $lang_key; ?>" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'price'), ENT_QUOTES); ?>" placeholder="판매가를 입력해 주세요.">
                            </div>
                            <div class="card-row is-block">
                                <span class="row-label">설명</span>
                                <textarea data-field="desc" data-lang="<?php echo $lang_key; ?>" placeholder="설명을 입력해 주세요."><?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'desc'), ENT_QUOTES); ?></textarea>
                            </div>
                        </div>
                    
                </div>
            </div>

            <div class="board-field manual-field manual-grid-wrap">
                <label>제품 설명서 파일</label>
                <div class="manual-grid">
                                        <?php
                        $lang_key = $selected_lang;
                        $lang_name = $product_lang_names[$lang_key] ?? ($product_langs[$lang_key] ?? strtoupper($lang_key));
                        $manual_index = $product_manual_map[$lang_key] ?? null;
                        $manual_file = $manual_index !== null ? ($file[$manual_index] ?? []) : [];
                        $manual_src = '';
                        if (!empty($manual_file['path']) && !empty($manual_file['file'])) {
                            $manual_src = rtrim($manual_file['path'], '/') . '/' . rawurlencode($manual_file['file']);
                        } elseif (!empty($manual_file['href'])) {
                            $manual_src = $manual_file['href'];
                        }
                    ?>
                        <div class="manual-card">
                            <div class="manual-head"><?php echo $lang_name; ?></div>
                            <?php if ($w == 'u' && !empty($manual_file['file'])) { ?>
                                <div class="file-existing">
                                    <div class="file-meta">
                                        <div class="name"><?php echo get_text($manual_file['source']); ?></div>
                                        <?php if (!empty($manual_file['filesize'])) { ?><div class="size"><?php echo $manual_file['filesize']; ?></div><?php } ?>
                                        <?php if ($manual_src) { ?><a class="file-link" href="<?php echo $manual_src; ?>" target="_blank" rel="noopener">파일 보기</a><?php } ?>
                                    </div>
                                    <label class="file-del">
                                        <input type="checkbox" name="bf_file_del[<?php echo $manual_index; ?>]" value="1">
                                        <span class="box" aria-hidden="true"></span>
                                        <span class="text">삭제</span>
                                    </label>
                                </div>
                            <?php } ?>
                            <input type="file" name="bf_file[<?php echo $manual_index; ?>]" id="bf_file_<?php echo $manual_index; ?>" class="file-input" accept="<?php echo $product_manual_accept; ?>">
                        </div>
                </div>
            </div>

            <div class="board-field">
                <label>제품 이미지</label>
                <div class="file-field-list image-field-list has-controls" data-max="<?php echo $product_image_count; ?>">
                <?php for ($i = 0; $i < $product_image_count; $i++) {
                    $existing_file = $file[$i] ?? [];
                    $existing_src = '';
                    if (!empty($existing_file['path']) && !empty($existing_file['file'])) {
                        $existing_src = rtrim($existing_file['path'], '/') . '/' . rawurlencode($existing_file['file']);
                    } elseif (!empty($existing_file['href'])) {
                        $existing_src = $existing_file['href'];
                    }
                ?>
                    <div class="board-field file-field">
                        <label for="bf_file_<?php echo $i; ?>">이미지 <?php echo $i + 1; ?></label>
                        <?php if ($w == 'u' && !empty($existing_file['file'])) { ?>
                            <div class="file-existing">
                                <div class="thumb">
                                    <?php if ($existing_src) { ?>
                                        <img src="<?php echo $existing_src; ?>" alt="<?php echo get_text($existing_file['source']); ?>" onerror="this.src='<?php echo $product_placeholder; ?>';">
                                    <?php } else { ?>
                                        <img src="<?php echo $product_placeholder; ?>" alt="">
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
                        <input type="file" name="bf_file[<?php echo $i; ?>]" id="bf_file_<?php echo $i; ?>" class="file-input" accept="image/*" aria-label="Product image upload">
                    </div>
                <?php } ?>
                </div>
            </div>

            <div class="board-field feature-section">
                <label>제품 상세정보</label>
                <div class="feature-list" data-feature-list>
                    <?php for ($i = 0; $i < $feature_rows; $i++) { ?>
                        <div class="feature-card" data-feature>
                            <div class="feature-head">
                                <div class="feature-title">&#52852;&#53580;&#44256;&#47532; <?php echo $i + 1; ?></div>
                                <button type="button" class="feature-remove" data-action="feature-remove" aria-label="카테고리 삭제"></button>
                            </div>
                            <div class="feature-grid">
                                                                <?php
                                    $lang_key = $selected_lang;
                                    $lang_data = $selected_lang_data;
                                    $lang_features = $lang_data['features'] ?? [];
                                    $set = $lang_features[$i] ?? [];
                                    $set_title = $set['title'] ?? '';
                                    $set_items = $set['items'] ?? [];
                                    $lang_name = $product_lang_names[$lang_key] ?? ($product_langs[$lang_key] ?? strtoupper($lang_key));
                                ?>
                                    <div class="feature-lang-card" data-lang="<?php echo $lang_key; ?>">
                                        <div class="lang-label">
                                            <span class="lang-icon" aria-hidden="true"><?php echo strtoupper($lang_key); ?></span>
                                            <span class="lang-text"><?php echo $lang_name; ?></span>
                                        </div>
                                        <input type="text" data-field="feature-title" data-lang="<?php echo $lang_key; ?>" value="<?php echo htmlspecialchars($set_title, ENT_QUOTES); ?>" placeholder="카테고리명을 입력해 주세요.">
                                        <textarea data-field="feature-items" data-lang="<?php echo $lang_key; ?>" placeholder="내용을 줄바꿈으로 구분해 입력해 주세요."><?php echo htmlspecialchars(sidepanel_product_items_text($set_items), ENT_QUOTES); ?></textarea>
                                    </div>
                                
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <button type="button" class="feature-add" data-action="feature-add">+ 카테고리 추가</button>
            </div>

            <?php if ($is_secret) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" name="secret" value="secret" <?php echo $secret_checked; ?>> 비밀글</label>
            </div>
            <?php } ?>

            <?php if ($is_mail) { ?>
            <div class="board-field inline">
                <label><input type="checkbox" id="mail" name="mail" value="mail" <?php echo $recv_email_checked; ?>> 메일 받기</label>
            </div>
            <?php } ?>
        </div>

        <?php $cancel_href = $list_href ?: 'javascript:history.back();'; ?>
        <div class="board-actions">
            <a class="btn-minimal btn-cancel" href="<?php echo $cancel_href; ?>">취소</a>
            <button type="submit" class="btn-minimal btn-submit">저장</button>
        </div>
    </form>
</div>

<?php echo $captcha_js; ?>
<script>
(function() {
    var root = document.querySelector('.product-write');
    if (!root) return;
    var langTabs = Array.prototype.slice.call(root.querySelectorAll('[data-lang-tab]'));
    var defaultLang = <?php echo json_encode($selected_lang); ?>;
    var manualIndexMap = <?php echo json_encode($product_manual_map); ?>;

    function getActiveTab() {
        for (var i = 0; i < langTabs.length; i++) {
            if (langTabs[i].classList.contains('is-active')) return langTabs[i];
        }
        return langTabs.length ? langTabs[0] : null;
    }

    function setActiveTab(target) {
        if (!target) return;
        langTabs.forEach(function(tab) {
            var active = tab === target;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function currentLang() {
        var active = getActiveTab();
        var value = active ? active.getAttribute('data-lang') : '';
        return value || defaultLang || 'ko';
    }

    function syncLangUi() {
        var active = getActiveTab();
        if (active && !active.classList.contains('is-active')) {
            setActiveTab(active);
        }
        var lang = currentLang();
        var label = active ? active.textContent.trim() : '';

        var infoCard = root.querySelector('.product-info-card');
        if (infoCard) {
            infoCard.setAttribute('data-lang', lang);
            var infoIcon = infoCard.querySelector('.lang-icon');
            if (infoIcon) infoIcon.textContent = lang.toUpperCase();
            var infoText = infoCard.querySelector('.lang-text');
            if (infoText && label) infoText.textContent = label;
            infoCard.querySelectorAll('[data-lang]').forEach(function(el) {
                el.setAttribute('data-lang', lang);
            });
        }

        root.querySelectorAll('.feature-lang-card').forEach(function(card) {
            card.setAttribute('data-lang', lang);
            var cardIcon = card.querySelector('.lang-icon');
            if (cardIcon) cardIcon.textContent = lang.toUpperCase();
            var cardText = card.querySelector('.lang-text');
            if (cardText && label) cardText.textContent = label;
            card.querySelectorAll('[data-lang]').forEach(function(el) {
                el.setAttribute('data-lang', lang);
            });
        });

        var manualHead = root.querySelector('.manual-card .manual-head');
        if (manualHead && label) manualHead.textContent = label;
        var manualInput = root.querySelector('.manual-card input[type="file"]');
            if (manualInput && manualIndexMap && manualIndexMap[lang] !== undefined) {
            var idx = manualIndexMap[lang];
            manualInput.name = 'bf_file[' + idx + ']';
            manualInput.id = 'bf_file_' + idx;
        }

        var categoryInput = document.getElementById('ca_name');
        if (categoryInput && active) {
            var categoryValue = active.getAttribute('data-category') || '';
            if (categoryValue) categoryInput.value = categoryValue;
        }

        var langField = document.getElementById('wr_1');
        if (langField) langField.value = lang;
    }

    function getInfoValue(field) {
        var section = root.querySelector('.product-info');
        if (!section) return '';
        var el = section.querySelector('[data-field="' + field + '"]');
        return el ? el.value.trim() : '';
    }

    function buildInfoData() {
        return {
            title: getInfoValue('title'),
            desc: getInfoValue('desc'),
            buy: getInfoValue('buy'),
            price: getInfoValue('price')
        };
    }

    function isInfoComplete(data) {
        return !!(data && data.title && data.desc && data.buy && data.price);
    }

    function splitItems(raw) {
        if (!raw) return [];
        return raw.split(/\r?\n/).map(function(item) {
            return item.trim();
        }).filter(function(item) {
            return item.length > 0;
        });
    }

    function getFeatureData(card) {
        var titleEl = card.querySelector('[data-field="feature-title"]');
        var itemsEl = card.querySelector('[data-field="feature-items"]');
        var title = titleEl ? titleEl.value.trim() : '';
        var itemsRaw = itemsEl ? itemsEl.value : '';
        return { title: title, items: splitItems(itemsRaw) };
    }

    function hasFeatureData(data) {
        return !!(data && (data.title || (data.items && data.items.length)));
    }

    function clearFeatureCard(card) {
        card.querySelectorAll('input, textarea').forEach(function(el) {
            el.value = '';
        });
    }

    function updateFeatureTitles() {
        var cards = root.querySelectorAll('[data-feature]');
        cards.forEach(function(card, idx) {
            var title = card.querySelector('.feature-title');
            if (title) title.textContent = '\uCE74\uD14C\uACE0\uB9AC ' + (idx + 1);
        });
    }

    function initImageControls() {
        var list = root.querySelector('.image-field-list');
        if (!list) return;

        var controls = list.querySelector('.file-controls');
        if (!controls) {
            controls = document.createElement('div');
            controls.className = 'file-controls';
            controls.innerHTML = '<button type="button" class="file-add icon-btn" aria-label="Add image" title="Add image"><i class="fa-solid fa-plus"></i></button><button type="button" class="file-remove icon-btn" aria-label="Remove image" title="Remove image"><i class="fa-solid fa-minus"></i></button>';
            list.appendChild(controls);
        }

        var addBtn = controls.querySelector('.file-add');
        var removeBtn = controls.querySelector('.file-remove');
        var max = parseInt(list.getAttribute('data-max'), 10) || 0;

        function rows() {
            return Array.prototype.slice.call(list.querySelectorAll('.file-field'));
        }

        function visibleRows() {
            return rows().filter(function(row) {
                return !row.classList.contains('is-hidden');
            });
        }

        function rowHasFile(row) {
            if (row.querySelector('.file-existing')) return true;
            var input = row.querySelector('input[type="file"]');
            return !!(input && input.files && input.files.length);
        }

        function updateInitialVisibility() {
            var allRows = rows();
            var lastVisible = 1;
            allRows.forEach(function(row, idx) {
                if (rowHasFile(row)) {
                    lastVisible = idx + 1;
                }
            });
            allRows.forEach(function(row, idx) {
                if (idx < lastVisible) {
                    row.classList.remove('is-hidden');
                } else {
                    row.classList.add('is-hidden');
                }
                if (rowHasFile(row)) {
                    row.classList.add('has-file');
                } else {
                    row.classList.remove('has-file');
                }
            });
        }

        function updateControls() {
            var visible = visibleRows().length;
            if (addBtn) addBtn.disabled = max > 0 ? visible >= max : false;
            if (removeBtn) removeBtn.disabled = visible <= 1;
        }

        function showNext() {
            var next = rows().find(function(row) {
                return row.classList.contains('is-hidden');
            });
            if (!next) return;
            next.classList.remove('is-hidden');
            updateControls();
            var input = next.querySelector('input[type="file"]');
            if (input) input.focus();
        }

        function canHideRow(row) {
            if (rowHasFile(row)) return false;
            return true;
        }

        function hideLast() {
            var visible = visibleRows();
            if (visible.length <= 1) return;
            var row = visible[visible.length - 1];
            if (!canHideRow(row)) {
                alert('파일을 비우거나 기존 파일 삭제를 체크해 주세요.');
                return;
            }
            var input = row.querySelector('input[type="file"]');
            if (input) input.value = '';
            row.classList.add('is-hidden');
            row.classList.remove('has-file');
            updateControls();
        }

        list.addEventListener('change', function(event) {
            var input = event.target;
            if (!input || input.type !== 'file') return;
            var row = input.closest('.file-field');
            if (!row) return;
            if (input.files && input.files.length) {
                row.classList.add('has-file');
                row.classList.remove('is-hidden');
            } else {
                row.classList.remove('has-file');
            }
            updateControls();
        });

        if (addBtn) addBtn.addEventListener('click', showNext);
        if (removeBtn) removeBtn.addEventListener('click', hideLast);

        updateInitialVisibility();
        updateControls();
    }

    root.addEventListener('click', function(event) {
        var btn = event.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var list = root.querySelector('[data-feature-list]');
        if (!list) return;
        var cards = list.querySelectorAll('[data-feature]');

        if (action === 'feature-add') {
            var last = cards[cards.length - 1];
            if (!last) return;
            var clone = last.cloneNode(true);
            clearFeatureCard(clone);
            list.appendChild(clone);
            updateFeatureTitles();
            syncLangUi();
            return;
        }

        if (action === 'feature-remove') {
            var card = btn.closest('[data-feature]');
            if (!card) return;
            if (cards.length <= 1) {
                clearFeatureCard(card);
                return;
            }
            card.remove();
            updateFeatureTitles();
        }
    });

    initImageControls();
    syncLangUi();
    if (langTabs.length) {
        langTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                if (tab.disabled) return;
                setActiveTab(tab);
                syncLangUi();
            });
        });
    }

    window.fwrite_submit = function(f) {
        var lang = currentLang();
        var info = buildInfoData();
        if (!isInfoComplete(info)) {
            alert('필수 항목을 모두 입력해 주세요.');
            return false;
        }

        var payload = {};
        var raw = f.wr_content ? f.wr_content.value : '';
        if (raw) {
            try {
                payload = JSON.parse(raw);
            } catch (err) {
                payload = {};
            }
        }

        if (!payload || typeof payload !== 'object') payload = {};
        if (!payload.lang || typeof payload.lang !== 'object') payload.lang = {};
        payload.version = 1;
        payload.lang[lang] = {
            title: info.title,
            desc: info.desc,
            buy: info.buy,
            price: info.price,
            features: []
        };

        var missingFeature = false;
        var cards = root.querySelectorAll('[data-feature]');
        cards.forEach(function(card) {
            var data = getFeatureData(card);
            if (!hasFeatureData(data)) return;
            if (!data.title || !data.items.length) {
                missingFeature = true;
                return;
            }
            payload.lang[lang].features.push({
                title: data.title,
                items: data.items
            });
        });

        if (missingFeature) {
            alert('제품 상세정보 항목을 모두 입력해 주세요.');
            return false;
        }

        f.wr_subject.value = info.title;
        if (f.wr_1) f.wr_1.value = lang;
        f.wr_content.value = JSON.stringify(payload);
        return true;
    };
})();
</script>
