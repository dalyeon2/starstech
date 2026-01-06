<?php
define('_ADMIN_', true);
include_once('../common.php');

$config['cf_admin_level'] = 10; // 관리자 레벨

if (!$is_member) {
    alert('로그인 하십시오.', G5_BBS_URL . '/login.php?url=' . urlencode(correct_goto_url(G5_URL.'/admin')));
} elseif ($member['mb_level'] < $config['cf_admin_level']) {
    alert('관리자만 접근 가능합니다.');
}