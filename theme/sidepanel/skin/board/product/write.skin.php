<?php
if (!defined('_GNUBOARD_')) exit;

$theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/skin/board/product/style.css">', 1);
}

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
} elseif ($is_category && !$category_default) {
    $category_default = 'ko';
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
foreach ($product_langs as $lang_key => $lang_label) {
    $lang_features = $product_data['lang'][$lang_key]['features'] ?? [];
    if (is_array($lang_features)) {
        $count = count($lang_features);
        if ($count > $feature_rows) $feature_rows = $count;
    }
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
<link rel="stylesheet" href="<?php echo $theme_url; ?>/skin/board/product/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $theme_url; ?>/theme.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('theme-sidepanel'); });</script>

<div class="section board board-write product-write">
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
        <input type="hidden" name="wr_subject" id="wr_subject" value="<?php echo htmlspecialchars($subject ?? '', ENT_QUOTES); ?>">
        <textarea name="wr_content" id="wr_content" style="display:none;"><?php echo htmlspecialchars($product_raw, ENT_QUOTES); ?></textarea>
        <?php if ($is_category && $category_default !== '') { ?>
            <input type="hidden" name="ca_name" value="<?php echo htmlspecialchars($category_default, ENT_QUOTES); ?>">
        <?php } ?>

        <div class="board-form">
            <div class="board-field product-info">
                <label>제품 소개</label>
                <div class="product-info-grid">
                    <?php foreach ($product_langs as $lang_key => $lang_label) {
                        $lang_data = $product_data['lang'][$lang_key] ?? [];
                        $lang_name = $product_lang_names[$lang_key] ?? $lang_label;
                    ?>
                        <div class="product-info-card" data-lang="<?php echo $lang_key; ?>">
                            <div class="card-head">
                                <span class="lang-icon" aria-hidden="true"><?php echo strtoupper($lang_key); ?></span>
                                <span class="lang-text"><?php echo $lang_name; ?></span>
                            </div>
                            <button type="button" class="card-zoom" aria-label="확대/축소" title="확대/축소">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            </button>
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
                    <?php } ?>
                </div>
            </div>

            <div class="board-field manual-field manual-grid-wrap">
                <label>제품 설명서 파일</label>
                <div class="manual-grid">
                    <?php foreach ($product_langs as $lang_key => $lang_label) {
                        $lang_name = $product_lang_names[$lang_key] ?? $lang_label;
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
                    <?php } ?>
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
                                <div class="feature-title">카테고리 <?php echo $i + 1; ?></div>
                                <button type="button" class="feature-remove" data-action="feature-remove" aria-label="카테고리 삭제"></button>
                            </div>
                            <div class="feature-grid">
                                <?php foreach ($product_langs as $lang_key => $lang_label) {
                                    $lang_data = $product_data['lang'][$lang_key] ?? [];
                                    $lang_features = $lang_data['features'] ?? [];
                                    $set = $lang_features[$i] ?? [];
                                    $set_title = $set['title'] ?? '';
                                    $set_items = $set['items'] ?? [];
                                    $lang_name = $product_lang_names[$lang_key] ?? $lang_label;
                                ?>
                                    <div class="feature-lang-card" data-lang="<?php echo $lang_key; ?>">
                                        <div class="lang-label">
                                            <span class="lang-icon" aria-hidden="true"><?php echo strtoupper($lang_key); ?></span>
                                            <span class="lang-text"><?php echo $lang_name; ?></span>
                                        </div>
                                        <button type="button" class="card-zoom" aria-label="확대/축소" title="확대/축소">
                                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                        </button>
                                        <input type="text" data-field="feature-title" data-lang="<?php echo $lang_key; ?>" value="<?php echo htmlspecialchars($set_title, ENT_QUOTES); ?>" placeholder="카테고리명을 입력해 주세요.">
                                        <textarea data-field="feature-items" data-lang="<?php echo $lang_key; ?>" placeholder="내용을 줄바꿈으로 구분해 입력해 주세요."><?php echo htmlspecialchars(sidepanel_product_items_text($set_items), ENT_QUOTES); ?></textarea>
                                    </div>
                                <?php } ?>
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
    var langs = <?php echo json_encode(array_keys($product_langs)); ?>;
    // TODO: Restore multi-language required fields after testing.
    // var requiredLangs = ['ko', 'en', 'fr'];
    var requiredLangs = ['ko'];
    // var fallbackLang = 'en';
    var fallbackLang = 'ko';
    var langLabels = {
        ko: '한국어',
        en: '영어',
        ja: '일본어',
        fr: '프랑스어',
        mn: '몽골어'
    };

    function getInfoValue(lang, field) {
        var section = root.querySelector('.product-info');
        if (!section) return '';
        var el = section.querySelector('[data-field="' + field + '"][data-lang="' + lang + '"]');
        return el ? el.value.trim() : '';
    }

    function isRequiredLang(lang) {
        return requiredLangs.indexOf(lang) !== -1;
    }

    function buildInfoData(lang) {
        return {
            title: getInfoValue(lang, 'title'),
            desc: getInfoValue(lang, 'desc'),
            buy: getInfoValue(lang, 'buy'),
            price: getInfoValue(lang, 'price')
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

    function getFeatureData(card, lang) {
        var titleEl = card.querySelector('[data-field="feature-title"][data-lang="' + lang + '"]');
        var itemsEl = card.querySelector('[data-field="feature-items"][data-lang="' + lang + '"]');
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
            if (title) title.textContent = '카테고리 ' + (idx + 1);
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

    function initCardZoom() {
        var selector = '.product-info-card, .feature-lang-card';
        var cards = root.querySelectorAll(selector);
        if (!cards.length) return;

        function collapseAll() {
            var expanded = root.querySelectorAll(selector + '.is-expanded');
            expanded.forEach(function(card) {
                card.classList.remove('is-expanded');
            });
            var collapsed = root.querySelectorAll(selector + '.is-collapsed');
            collapsed.forEach(function(card) {
                card.classList.remove('is-collapsed');
            });
            var grids = root.querySelectorAll('.product-info-grid.is-expanded, .feature-grid.is-expanded');
            grids.forEach(function(grid) {
                grid.classList.remove('is-expanded');
            });
        }

        function expandWithinGrid(card) {
            var grid = card.closest('.product-info-grid, .feature-grid');
            if (!grid) return;
            var cardSelector = grid.classList.contains('product-info-grid') ? '.product-info-card' : '.feature-lang-card';
            var cards = grid.querySelectorAll(cardSelector);
            cards.forEach(function(item) {
                if (item === card) {
                    item.classList.add('is-expanded');
                    item.classList.remove('is-collapsed');
                } else {
                    item.classList.remove('is-expanded');
                    item.classList.add('is-collapsed');
                }
            });
            grid.classList.add('is-expanded');
        }

        root.addEventListener('click', function(event) {
            var zoomBtn = event.target.closest('.card-zoom');
            if (!zoomBtn) return;
            var card = zoomBtn.closest(selector);
            if (!card) return;
            if (card.classList.contains('is-expanded')) {
                collapseAll();
                return;
            }
            collapseAll();
            expandWithinGrid(card);
        });

        document.addEventListener('click', function(event) {
            if (event.target.closest(selector)) return;
            collapseAll();
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                collapseAll();
            }
        });
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
    initCardZoom();

    window.fwrite_submit = function(f) {
        var payload = { version: 1, lang: {} };
        var subject = '';
        var infoMap = {};
        var missingInfo = [];

        langs.forEach(function(lang) {
            infoMap[lang] = buildInfoData(lang);
        });

        requiredLangs.forEach(function(lang) {
            if (!isInfoComplete(infoMap[lang])) {
                missingInfo.push(langLabels[lang] || lang.toUpperCase());
            }
        });

        if (missingInfo.length) {
            alert('제품 소개는 ' + missingInfo.join(', ') + ' 항목을 모두 입력해 주세요.');
            return false;
        }

        var fallbackInfo = infoMap[fallbackLang] || { title: '', desc: '', buy: '', price: '' };

        langs.forEach(function(lang) {
            var data = infoMap[lang];
            if (!isRequiredLang(lang)) {
                if (!data.title) data.title = fallbackInfo.title;
                if (!data.desc) data.desc = fallbackInfo.desc;
                if (!data.buy) data.buy = fallbackInfo.buy;
                if (!data.price) data.price = fallbackInfo.price;
            }
            payload.lang[lang] = {
                title: data.title,
                desc: data.desc,
                buy: data.buy,
                price: data.price,
                features: []
            };
            if (!subject && data.title) {
                subject = data.title;
            }
        });

        var missingFeature = {};
        var cards = root.querySelectorAll('[data-feature]');
        cards.forEach(function(card) {
            var perLang = {};
            var hasAny = false;
            langs.forEach(function(lang) {
                var data = getFeatureData(card, lang);
                perLang[lang] = data;
                if (hasFeatureData(data)) {
                    hasAny = true;
                }
            });
            if (!hasAny) return;

            requiredLangs.forEach(function(lang) {
                var data = perLang[lang];
                if (!data.title || !data.items.length) {
                    missingFeature[lang] = true;
                }
            });

            var fallbackFeature = perLang[fallbackLang] || { title: '', items: [] };

            langs.forEach(function(lang) {
                var data = perLang[lang];
                if (!isRequiredLang(lang)) {
                    if (!data.title) data.title = fallbackFeature.title;
                    if (!data.items.length) data.items = fallbackFeature.items.slice();
                }
                if (hasFeatureData(data)) {
                    payload.lang[lang].features.push({
                        title: data.title,
                        items: data.items
                    });
                }
            });
        });

        var missingFeatureLangs = Object.keys(missingFeature).map(function(lang) {
            return langLabels[lang] || lang.toUpperCase();
        });
        if (missingFeatureLangs.length) {
            alert('제품 상세정보는 ' + missingFeatureLangs.join(', ') + ' 항목을 모두 입력해 주세요.');
            return false;
        }

        if (!subject) {
            alert('제품명을 최소 1개 이상 입력해 주세요.');
            return false;
        }

        f.wr_subject.value = subject;
        f.wr_content.value = JSON.stringify(payload);
        return true;
    };
})();
</script>
