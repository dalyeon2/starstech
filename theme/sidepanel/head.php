<?php
$standalone = !defined('_GNUBOARD_') || (defined('G5_IS_ADMIN') && G5_IS_ADMIN);

$theme_url = '/theme/sidepanel';
if (defined('G5_URL') && G5_URL) {
    $theme_url = rtrim(G5_URL, '/') . '/theme/sidepanel';
}

if (!$standalone) {
    if (function_exists('add_stylesheet')) {
        add_stylesheet('<link rel="stylesheet" href="' . $theme_url . '/style.css">', 0);
        add_stylesheet('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">', 1);
    } else {
        echo '<link rel="stylesheet" href="' . $theme_url . '/style.css">' . PHP_EOL;
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">' . PHP_EOL;
    }
    if (function_exists('add_javascript')) {
        add_javascript('<script src="' . $theme_url . '/theme.js" defer></script>', 0);
        add_javascript('<script>document.addEventListener("DOMContentLoaded",function(){document.body.classList.add("theme-sidepanel");});</script>', 0);
    }
    if (defined('G5_THEME_PATH') && file_exists(G5_THEME_PATH . '/head.sub.php')) {
        include_once(G5_THEME_PATH . '/head.sub.php');
    }
} else {
    ?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STARSTECH</title>
    <link rel="stylesheet" href="<?php echo $theme_url; ?>/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>document.addEventListener("DOMContentLoaded",function(){document.body.classList.add("theme-sidepanel");});</script>
    <script src="<?php echo $theme_url; ?>/theme.js" defer></script>
</head>
<body class="theme-sidepanel">
<?php
}
?>

<div id="wrap" class="theme-sidepanel">
    <div class="panel-layout">
        <aside class="side-nav" aria-label="Main navigation">
            <div class="brand">
                <span class="logo">ADMIN</span>
            </div>

            <div class="nav-group">
                <div class="nav-title">관리</div>
                <ul>
                    <li><a href="/admin/"><span class="icon"><i class="fa-solid fa-gauge-high"></i></span><span class="label">대시보드</span><span class="pill stats">Dashboard</span></a></li>
                    <li><a href="/admin/visit.php"><span class="icon"><i class="fa-solid fa-chart-line"></i></span><span class="label">방문자 통계</span></a></li>
                </ul>
            </div>

            <div class="nav-group">
                <div class="nav-title">제품</div>
                <ul>
                    <li><a href="/bbs/board.php?bo_table=eco_st"><span class="icon"><i class="fa-solid fa-snowflake"></i></span><span class="label">ECO-ST</span></a></li>
                    <li><a href="/bbs/board.php?bo_table=fertilizer"><span class="icon"><i class="fa-solid fa-seedling"></i></span><span class="label">Fertilizer</span></a></li>
                    <li><a href="/bbs/board.php?bo_table=labope"><span class="icon"><i class="fa-solid fa-flask"></i></span><span class="label">Labope</span></a></li>
                </ul>
            </div>

            <div class="nav-group">
                <div class="nav-title">홍보센터</div>
                <ul>
                    <li><a href="/bbs/board.php?bo_table=news"><span class="icon"><i class="fa-solid fa-newspaper"></i></span><span class="label">Newsroom</span></a></li>
                    <li><a href="/bbs/board.php?bo_table=pr"><span class="icon"><i class="fa-solid fa-bullhorn"></i></span><span class="label">PR</span></a></li>
                </ul>
            </div>

            <div class="nav-group">
                <div class="nav-title">고객지원</div>
                <ul>
                    <li><a href="/bbs/board.php?bo_table=inquiry"><span class="icon"><i class="fa-solid fa-envelope-open-text"></i></span><span class="label">Contact</span></a></li>
                </ul>
            </div>

            <div class="nav-footer">
                <?php
                $nav_is_member = isset($is_member) && $is_member;
                if ($nav_is_member) {
                    if (isset($is_admin) && $is_admin === 'super') {
                        ?>
                        <a href="/adm/"><span class="icon"><i class="fa-solid fa-user-gear"></i></span>관리자</a>
                        <?php
                    }
                    ?>
                    <a href="/bbs/logout.php"><span class="icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>로그아웃</a>
                    <?php
                } else {
                    ?>
                    <a href="/bbs/login.php"><span class="icon"><i class="fa-solid fa-right-to-bracket"></i></span>로그인</a>
                    <?php
                }
                ?>
            </div>
        </aside>

        <main class="main">
