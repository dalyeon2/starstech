<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$must_login_use = 0;  // 로그인 필수 사용 (1:사용, 0:사용안함)
$must_login_urls = array(G5_URL."/index.php", G5_BBS_URL."/board.php", G5_BBS_URL."/write.php",
                            G5_BBS_URL."/content.php", G5_BBS_URL."/current_connect.php", G5_BBS_URL."/faq.php",
                            G5_BBS_URL."/qalist.php", G5_BBS_URL."/group.php", G5_BBS_URL."/new.php",
                            G5_BBS_URL."/search.php"
);

if ($is_guest && $must_login_use) {
	$must_login_redirect = false;

	foreach ($must_login_urls as $i) {
		if (stripos($i, $_SERVER['SCRIPT_NAME']) !== false) {
			$must_login_redirect = true;
			break;
		}
	}

	if ($must_login_redirect) {
		goto_url(G5_BBS_URL.'/login.php');
	}
}