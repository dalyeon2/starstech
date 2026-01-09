<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_exit($code, $payload) {
    if (!headers_sent()) {
        http_response_code($code);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit(405, ['success' => false, 'error' => 'method_not_allowed']);
}

$input = [];
$input['type'] = isset($_POST['type']) ? trim($_POST['type']) : '';
$input['company'] = isset($_POST['company']) ? trim($_POST['company']) : '';
$input['manager'] = isset($_POST['manager']) ? trim($_POST['manager']) : '';
$input['country'] = isset($_POST['country']) ? trim($_POST['country']) : '';
$input['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
$input['subject'] = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$input['message'] = isset($_POST['message']) ? trim($_POST['message']) : '';
$input['privacy'] = isset($_POST['privacy']) ? trim($_POST['privacy']) : '';

$errors = [];
if ($input['type'] === '') $errors['inquiry'] = '문의 유형을 선택해주세요.';
// length validation rules
function str_between_len($s, $min, $max) {
    $len = mb_strlen($s);
    return $len >= $min && $len <= $max;
}
if ($input['company'] === '') $errors['company'] = '기업 및 소속명을 입력해주세요.';
else if (!str_between_len($input['company'], 2, 100)) $errors['company'] = '기업 및 소속명은 2자 이상 100자 이하여야 합니다.';
if ($input['manager'] === '') $errors['manager'] = '담당자의 성명과 직급을 입력해주세요.';
else if (!str_between_len($input['manager'], 2, 60)) $errors['manager'] = '담당자 정보는 2자 이상 60자 이하여야 합니다.';
if ($input['country'] === '') $errors['country'] = '국가를 입력해주세요.';
else if (!str_between_len($input['country'], 2, 60)) $errors['country'] = '국가는 2자 이상 60자 이하여야 합니다.';
if ($input['email'] === '') $errors['email'] = '이메일을 입력해주세요.';
else if (strlen($input['email']) > 254) $errors['email'] = '이메일이 너무 깁니다.';
else if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = '이메일 형식이 올바르지 않습니다.';
if ($input['subject'] === '') $errors['subject'] = '제목을 입력해주세요.';
else if (!str_between_len($input['subject'], 3, 150)) $errors['subject'] = '제목은 3자 이상 150자 이하여야 합니다.';
if ($input['message'] === '') $errors['message'] = '문의 내용을 입력해주세요.';
else if (!str_between_len($input['message'], 10, 5000)) $errors['message'] = '문의 내용은 10자 이상 5000자 이하여야 합니다.';
if ($input['privacy'] !== '1') $errors['privacy'] = '개인정보 수집 및 이용에 동의해주세요.';

if (!empty($errors)) {
    json_exit(400, ['success' => false, 'errors' => $errors]);
}

// handle file uploads
$savedFiles = [];
if (!empty($_FILES) && isset($_FILES['files'])) {
    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $destDir = __DIR__ . '/../data/contact_files';
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0755, true);
    }
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $name = $files['name'][$i];
        $tmp = $files['tmp_name'][$i];
        if (isset($files['size'][$i]) && $files['size'][$i] > 10 * 1024 * 1024) continue;
        $uniq = time() . '_' . bin2hex(random_bytes(6));
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        $target = $destDir . '/' . $uniq . '_' . $safe;
        if (@move_uploaded_file($tmp, $target)) {
            $savedFiles[] = $target;
        }
    }
}

// prepare e-mail to admin
$adminEmail = 'ubique99@naver.com';
$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'example.com';
$subject = '[웹문의] ' . ($input['subject'] ?: '(무제)');
$body = "문의 유형: " . $input['type'] . "\n";
$body .= "기업/소속: " . $input['company'] . "\n";
$body .= "담당자: " . $input['manager'] . "\n";
$body .= "국가: " . $input['country'] . "\n";
$body .= "이메일: " . $input['email'] . "\n\n";
$body .= "제목: " . $input['subject'] . "\n\n";
$body .= "문의 내용:\n" . $input['message'] . "\n\n";

$fileUrls = [];
if (!empty($savedFiles)) {
    $body .= "첨부파일:\n";
    foreach ($savedFiles as $f) {
        $basename = basename($f);
        $url = null;
        if (is_file(__DIR__ . '/../common.php')) {
            if (!defined('G5_DATA_URL')) {
                @include_once __DIR__ . '/../common.php';
            }
            if (defined('G5_DATA_URL')) {
                $url = G5_DATA_URL . '/contact_files/' . rawurlencode($basename);
            }
        }
        $body .= $basename . " -> " . ($url ? $url : $f) . "\n";
        if ($url) $fileUrls[] = $url;
    }
    $body .= "\n";
}

$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/plain; charset=\"UTF-8\"";
$headers[] = "From: noreply@" . $serverName;
$headers[] = "Reply-To: " . $input['email'];
$headers[] = "X-Mailer: PHP/" . phpversion();

$sent = @mail($adminEmail, $subject, $body, implode("\r\n", $headers), '-fnoreply@' . $serverName);

$gnuboard_inserted = false;
$wr_id = null;
$logEntry = [
    'time' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'subject' => $input['subject'],
    'email' => $input['email'],
    'manager' => $input['manager'],
    'company' => $input['company'],
    'saved_files' => array_map('basename', $savedFiles),
    'file_urls' => $fileUrls,
    'mail_sent' => (bool)$sent,
];

if (!defined('G5_DATA_URL') && is_file(__DIR__ . '/../common.php')) {
    @include_once __DIR__ . '/../common.php';
}

if (defined('G5_DATA_URL') && isset($g5['write_prefix'])) {
    $write_table = $g5['write_prefix'] . 'inquiry';
    $link1 = isset($fileUrls[0]) ? $fileUrls[0] : '';

    // build a concise post content for sidepanel 'basic' view (avoid dumping full email body into the board)
    $postSubject = '[웹문의] ' . ($input['subject'] ?: '(무제)');
    $shortMsg = (mb_strlen($input['message']) > 400) ? mb_substr($input['message'], 0, 400) . '...' : $input['message'];
    $postContent = "문의 유형: " . $input['type'] . "\n";
    $postContent .= "기업/소속: " . $input['company'] . "\n";
    $postContent .= "담당자: " . $input['manager'] . "\n";
    $postContent .= "국가: " . $input['country'] . "\n";
    $postContent .= "이메일: " . $input['email'] . "\n\n";
    $postContent .= "문의(요약):\n" . $shortMsg . "\n";
    if (!empty($fileUrls)) {
        $postContent .= "\n첨부: " . implode(', ', $fileUrls) . "\n";
    }

    $sql = "INSERT INTO `{$write_table}` (wr_subject, wr_content, wr_name, wr_email, wr_link1, wr_datetime, wr_ip, wr_is_comment) VALUES ('" . sql_escape_string($postSubject) . "', '" . sql_escape_string($postContent) . "', '" . sql_escape_string($input['manager']) . "', '" . sql_escape_string($input['email']) . "', '" . sql_escape_string($link1) . "', NOW(), '" . sql_escape_string($_SERVER['REMOTE_ADDR']) . "', 0)";
    $res = @sql_query($sql);
    if ($res !== false) {
        $wr_id = @sql_insert_id();
        $gnuboard_inserted = (bool)$wr_id;
        $logEntry['wr_id'] = $wr_id;
        $logEntry['post_subject'] = $postSubject;
    } else {
        $logEntry['gnuboard_error'] = 'insert_failed';
    }
}

$logPath = __DIR__ . '/../data/contact_submissions.log';
@file_put_contents($logPath, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

json_exit(200, ['success' => true, 'mail_sent' => (bool)$sent, 'gnuboard' => ['inserted' => $gnuboard_inserted, 'wr_id' => $wr_id]]);
