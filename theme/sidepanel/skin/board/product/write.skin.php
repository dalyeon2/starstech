<?php
if (!defined('_GNUBOARD_')) exit;

$sidepanel_theme_url = defined('G5_THEME_URL') ? G5_THEME_URL : (G5_URL . '/theme/sidepanel');
if (function_exists('add_stylesheet')) {
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/style.css">', 0);
    add_stylesheet('<link rel="stylesheet" href="' . $sidepanel_theme_url . '/skin/board/product/style.css">', 1);
}

$sidepanel_product_placeholder = 'https://via.placeholder.com/480x360/ededed/1f1f1f?text=No+Image';
$sidepanel_product_file_count = 3;
$sidepanel_product_langs = [
    'ko' => 'KO',
    'en' => 'EN',
    'ja' => 'JA',
    'fr' => 'FR',
    'mn' => 'MN'
];

$sidepanel_product_data = [];
$sidepanel_product_raw = $write['wr_content'] ?? '';
if ($sidepanel_product_raw) {
    $decoded = json_decode($sidepanel_product_raw, true);
    if (is_array($decoded)) {
        $sidepanel_product_data = $decoded;
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

<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/style.css">
<link rel="stylesheet" href="<?php echo $sidepanel_theme_url; ?>/skin/board/product/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="<?php echo $sidepanel_theme_url; ?>/theme.js" defer></script>
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
        <textarea name="wr_content" id="wr_content" style="display:none;"><?php echo htmlspecialchars($sidepanel_product_raw, ENT_QUOTES); ?></textarea>

        <div class="board-form">
            <div class="board-field">
                <label>제품 이미지 (최대 3장)</label>
                <div class="file-field-list">
                <?php for ($i = 0; $i < $sidepanel_product_file_count; $i++) {
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
                                        <img src="<?php echo $existing_src; ?>" alt="<?php echo get_text($existing_file['source']); ?>" onerror="this.src='<?php echo $sidepanel_product_placeholder; ?>';">
                                    <?php } else { ?>
                                        <img src="<?php echo $sidepanel_product_placeholder; ?>" alt="">
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
                        <input type="file" name="bf_file[]" id="bf_file_<?php echo $i; ?>" accept="image/*">
                    </div>
                <?php } ?>
                </div>
            </div>

            <div class="lang-tabs" role="tablist" aria-label="언어">
                <?php $first_lang = true; foreach ($sidepanel_product_langs as $lang_key => $lang_label) { ?>
                    <button type="button" class="lang-tab<?php echo $first_lang ? ' is-active' : ''; ?>" data-lang="<?php echo $lang_key; ?>" role="tab" aria-selected="<?php echo $first_lang ? 'true' : 'false'; ?>" aria-controls="lang-panel-<?php echo $lang_key; ?>" id="lang-tab-<?php echo $lang_key; ?>">
                        <?php echo $lang_label; ?>
                    </button>
                <?php $first_lang = false; } ?>
            </div>

            <?php $first_panel = true; foreach ($sidepanel_product_langs as $lang_key => $lang_label) {
                $lang_data = $sidepanel_product_data['lang'][$lang_key] ?? [];
                $features = [];
                if (!empty($lang_data['features']) && is_array($lang_data['features'])) {
                    $features = $lang_data['features'];
                }
                if (empty($features)) {
                    $features = [[]];
                }
            ?>
            <section class="lang-panel<?php echo $first_panel ? ' is-active' : ''; ?>" data-lang="<?php echo $lang_key; ?>" role="tabpanel" aria-labelledby="lang-tab-<?php echo $lang_key; ?>" id="lang-panel-<?php echo $lang_key; ?>" aria-hidden="<?php echo $first_panel ? 'false' : 'true'; ?>">
                <div class="field-grid">
                    <div class="board-field full">
                        <label for="lang-<?php echo $lang_key; ?>-title">제목</label>
                        <input type="text" id="lang-<?php echo $lang_key; ?>-title" data-field="title" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'title'), ENT_QUOTES); ?>">
                    </div>
                    <div class="board-field full">
                        <label for="lang-<?php echo $lang_key; ?>-desc">설명</label>
                        <textarea id="lang-<?php echo $lang_key; ?>-desc" data-field="desc"><?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'desc'), ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="board-field">
                        <label for="lang-<?php echo $lang_key; ?>-buy">구매방법</label>
                        <input type="text" id="lang-<?php echo $lang_key; ?>-buy" data-field="buy" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'buy'), ENT_QUOTES); ?>">
                    </div>
                    <div class="board-field">
                        <label for="lang-<?php echo $lang_key; ?>-price">판매가</label>
                        <input type="text" id="lang-<?php echo $lang_key; ?>-price" data-field="price" value="<?php echo htmlspecialchars(sidepanel_product_lang_value($lang_data, 'price'), ENT_QUOTES); ?>">
                    </div>
                </div>

                <div class="board-field">
                    <label>제품 특징 세트</label>
                    <div class="feature-table-wrap">
                        <table class="feature-table">
                            <thead>
                                <tr>
                                    <th>세트 제목</th>
                                    <th>항목 (줄바꿈)</th>
                                    <th class="actions">추가/삭제</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($features as $set) {
                                    $set_title = $set['title'] ?? '';
                                    $set_items = $set['items'] ?? [];
                                ?>
                                <tr data-feature-row>
                                    <td>
                                        <input type="text" data-field="feature-title" value="<?php echo htmlspecialchars($set_title, ENT_QUOTES); ?>">
                                    </td>
                                    <td>
                                        <textarea data-field="feature-items"><?php echo htmlspecialchars(sidepanel_product_items_text($set_items), ENT_QUOTES); ?></textarea>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="row-btn" data-action="add" aria-label="세트 추가">+</button>
                                        <button type="button" class="row-btn remove" data-action="remove" aria-label="세트 삭제">-</button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <div class="feature-note">항목은 줄바꿈으로 입력해주세요.</div>
                    </div>
                </div>
            </section>
            <?php $first_panel = false; } ?>

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

    var tabs = root.querySelectorAll('.lang-tab');
    var panels = root.querySelectorAll('.lang-panel');

    function setActive(lang) {
        tabs.forEach(function(tab) {
            var active = tab.dataset.lang === lang;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
        });
        panels.forEach(function(panel) {
            var active = panel.dataset.lang === lang;
            panel.classList.toggle('is-active', active);
            panel.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
    }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            setActive(tab.dataset.lang);
        });
    });

    function getField(scope, name) {
        var el = scope.querySelector('[data-field="' + name + '"]');
        return el ? el.value.trim() : '';
    }

    function splitItems(raw) {
        if (!raw) return [];
        return raw.split(/\r?\n/).map(function(item) {
            return item.trim();
        }).filter(function(item) {
            return item.length > 0;
        });
    }

    function collectLangData(panel) {
        var data = {
            title: getField(panel, 'title'),
            desc: getField(panel, 'desc'),
            buy: getField(panel, 'buy'),
            price: getField(panel, 'price'),
            features: []
        };

        panel.querySelectorAll('[data-feature-row]').forEach(function(row) {
            var titleEl = row.querySelector('[data-field="feature-title"]');
            var itemsEl = row.querySelector('[data-field="feature-items"]');
            var title = titleEl ? titleEl.value.trim() : '';
            var itemsRaw = itemsEl ? itemsEl.value : '';
            var items = splitItems(itemsRaw);
            if (title || items.length) {
                data.features.push({ title: title, items: items });
            }
        });

        return data;
    }

    function clearRowValues(row) {
        row.querySelectorAll('input, textarea').forEach(function(el) {
            el.value = '';
        });
    }

    root.addEventListener('click', function(event) {
        var btn = event.target.closest('[data-action]');
        if (!btn) return;
        var row = btn.closest('[data-feature-row]');
        if (!row) return;
        var tbody = row.parentNode;
        if (!tbody) return;

        if (btn.dataset.action === 'add') {
            var clone = row.cloneNode(true);
            clearRowValues(clone);
            tbody.insertBefore(clone, row.nextSibling);
            return;
        }

        if (btn.dataset.action === 'remove') {
            var rows = tbody.querySelectorAll('[data-feature-row]');
            if (rows.length <= 1) {
                clearRowValues(row);
                return;
            }
            row.remove();
        }
    });

    window.fwrite_submit = function(f) {
        var payload = { version: 1, lang: {} };
        var subject = '';

        panels.forEach(function(panel) {
            var lang = panel.dataset.lang;
            var data = collectLangData(panel);
            payload.lang[lang] = data;
            if (!subject && data.title) {
                subject = data.title;
            }
        });

        if (!subject) {
            alert('언어 중 하나 이상에 제목을 입력해주세요.');
            return false;
        }

        f.wr_subject.value = subject;
        f.wr_content.value = JSON.stringify(payload);
        return true;
    };
})();
</script>
