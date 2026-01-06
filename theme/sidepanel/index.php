<?php
$theme_path = __DIR__;
$theme_head = $theme_path . '/head.php';
$theme_tail = $theme_path . '/tail.php';

$use_local_theme = !defined('_GNUBOARD_') || (defined('G5_IS_ADMIN') && G5_IS_ADMIN);

if (!$use_local_theme && defined('_GNUBOARD_') && defined('G5_THEME_PATH') && realpath(G5_THEME_PATH) === realpath($theme_path)) {
    include_once(G5_THEME_PATH . '/head.php');
} else {
    include_once($theme_head);
}

$quick_links = [
    [
        'label' => 'ECO-ST',
        'desc' => 'ECO-ST 제품 게시판',
        'href' => '/bbs/board.php?bo_table=eco_st',
        'icon' => 'fa-snowflake'
    ],
    [
        'label' => 'Fertilizer',
        'desc' => 'Fertilizer 제품 게시판',
        'href' => '/bbs/board.php?bo_table=fertilizer',
        'icon' => 'fa-seedling'
    ],
    [
        'label' => 'Labope',
        'desc' => 'Labope 제품 게시판',
        'href' => '/bbs/board.php?bo_table=labope',
        'icon' => 'fa-flask'
    ],
    [
        'label' => '뉴스룸',
        'desc' => '뉴스룸 게시판 관리',
        'href' => '/bbs/board.php?bo_table=news',
        'icon' => 'fa-newspaper'
    ],
    [
        'label' => 'PR',
        'desc' => 'PR 게시판 관리',
        'href' => '/bbs/board.php?bo_table=pr',
        'icon' => 'fa-bullhorn'
    ],
    [
        'label' => '문의',
        'desc' => '문의 게시판 관리',
        'href' => '/bbs/board.php?bo_table=inquiry',
        'icon' => 'fa-envelope-open-text'
    ],
];
?>

<section class="hero dash-hero">
    <div class="hero-left">
        <p class="eyebrow">관리자 콘솔</p>
        <h1>프로젝트 관리</h1>
        <p>필요한 섹션을 선택해서 콘텐츠를 추가하거나 수정하세요.</p>
        <div class="quick-links">
            <?php foreach ($quick_links as $link) { ?>
                <a class="quick-link" href="<?php echo $link['href']; ?>">
                    <i class="fa-solid <?php echo $link['icon']; ?>"></i>
                    <span><?php echo $link['label']; ?></span>
                </a>
            <?php } ?>
        </div>
    </div>
    <div class="hero-right">
        <div class="pulse-label">오늘 <?php echo date('m.d'); ?></div>
        <div class="mini-stats">
            <div class="mini-card">
                <span class="label">메뉴</span>
                <strong class="value"><?php echo count($quick_links); ?></strong>
            </div>
            <div class="mini-card">
                <span class="label">업데이트</span>
                <strong class="value"><?php echo date('H:i'); ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>빠른 관리</h2>
    </div>
    <div class="latest-grid">
        <?php foreach ($quick_links as $link) { ?>
            <a class="latest-card" href="<?php echo $link['href']; ?>">
                <div class="head">
                    <span class="title"><?php echo $link['label']; ?></span>
                    <span class="pill">관리</span>
                </div>
                <p class="excerpt"><?php echo $link['desc']; ?></p>
            </a>
        <?php } ?>
    </div>
</section>

<?php
if (!$use_local_theme && defined('_GNUBOARD_') && defined('G5_THEME_PATH') && realpath(G5_THEME_PATH) === realpath($theme_path)) {
    include_once(G5_THEME_PATH . '/tail.php');
} else {
    include_once($theme_tail);
}
?>
