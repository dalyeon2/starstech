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

$common_loaded = false;
if (is_file(__DIR__ . '/../common.php')) {
    @include_once __DIR__ . '/../common.php';
    $common_loaded = defined('G5_PHPMAILER_PATH');
}
if ($common_loaded && defined('G5_PHPMAILER_PATH') && is_file(G5_PHPMAILER_PATH . '/PHPMailerAutoload.php')) {
    @include_once G5_PHPMAILER_PATH . '/PHPMailerAutoload.php';
}

$input = [];
$input['type'] = isset($_POST['type']) ? trim($_POST['type']) : '';
$input['type_value'] = isset($_POST['type_value']) ? trim($_POST['type_value']) : '';
$input['company'] = isset($_POST['company']) ? trim($_POST['company']) : '';
$input['manager'] = isset($_POST['manager']) ? trim($_POST['manager']) : '';
$input['country'] = isset($_POST['country']) ? trim($_POST['country']) : '';
$input['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
$input['subject'] = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$input['message'] = isset($_POST['message']) ? trim($_POST['message']) : '';
$input['privacy'] = isset($_POST['privacy']) ? trim($_POST['privacy']) : '';

$errors = [];
if ($input['type'] === '') $errors['inquiry'] = '문의 유형을 선택해주세요.';
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

$bo_table = 'inquiry';
$upload_dir = defined('G5_DATA_PATH') ? (G5_DATA_PATH . '/file/' . $bo_table) : (__DIR__ . '/../data/file/' . $bo_table);
if (!is_dir($upload_dir)) {
    $perm = defined('G5_DIR_PERMISSION') ? G5_DIR_PERMISSION : 0755;
    @mkdir($upload_dir, $perm, true);
    @chmod($upload_dir, $perm);
}

$savedFiles = [];
$uploadErrors = [];
$hasUpload = false;
$expectedFiles = isset($_POST['files_expected']) ? (int)$_POST['files_expected'] : 0;
function upload_error_message($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE: return '서버 업로드 최대 용량을 초과했습니다.';
        case UPLOAD_ERR_FORM_SIZE: return '폼에서 지정한 최대 용량을 초과했습니다.';
        case UPLOAD_ERR_PARTIAL: return '파일이 일부만 업로드되었습니다.';
        case UPLOAD_ERR_NO_FILE: return '업로드된 파일이 없습니다.';
        case UPLOAD_ERR_NO_TMP_DIR: return '임시 폴더가 없습니다.';
        case UPLOAD_ERR_CANT_WRITE: return '디스크에 파일을 쓸 수 없습니다.';
        case UPLOAD_ERR_EXTENSION: return '확장자 업로드가 차단되었습니다.';
        default: return '알 수 없는 업로드 오류가 발생했습니다.';
    }
}
if (!empty($_FILES) && isset($_FILES['files'])) {
    $files = $_FILES['files'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $chars_array = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    for ($i = 0; $i < $count; $i++) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        $name = $files['name'][$i] ?? '';
        $tmp = $files['tmp_name'][$i] ?? '';
        $size = isset($files['size'][$i]) ? (int)$files['size'][$i] : 0;
        if ($name !== '') $hasUpload = true;
        if ($error !== UPLOAD_ERR_OK) {
            if ($name !== '') {
                $uploadErrors[] = $name . ' - ' . upload_error_message($error);
            }
            continue;
        }
        if ($name === '' || $tmp === '') {
            $uploadErrors[] = '파일 정보가 비어 있습니다.';
            continue;
        }
        if ($size > 10 * 1024 * 1024) {
            $uploadErrors[] = $name . ' - 파일 용량이 10MB를 초과했습니다.';
            continue;
        }
        $source_name = function_exists('get_safe_filename') ? get_safe_filename($name) : $name;
        $source_name = preg_replace("/\.(php|pht|phtm|htm|cgi|pl|exe|jsp|asp|inc|phar)/i", "$0-x", $source_name);
        shuffle($chars_array);
        $shuffle = implode('', $chars_array);
        $safe_name = function_exists('replace_filename') ? replace_filename($source_name) : $source_name;
        $bf_file = md5(sha1($_SERVER['REMOTE_ADDR'] ?? '')) . '_' . substr($shuffle, 0, 8) . '_' . $safe_name;
        $target = $upload_dir . '/' . $bf_file;
        if (!is_uploaded_file($tmp)) {
            $uploadErrors[] = $name . ' - 업로드 임시파일 확인에 실패했습니다.';
            continue;
        }
        if (@move_uploaded_file($tmp, $target)) {
            @chmod($target, defined('G5_FILE_PERMISSION') ? G5_FILE_PERMISSION : 0644);
            $savedFiles[] = [
                'path' => $target,
                'name' => $name,
                'bf_file' => $bf_file,
                'size' => $size
            ];
        } else {
            $uploadErrors[] = $name . ' - 서버 저장에 실패했습니다. (폴더 권한 확인)';
        }
    }
}
if (($expectedFiles > 0 || $hasUpload) && empty($savedFiles)) {
    $uploadErrors = array_values(array_unique($uploadErrors));
    $errors['files'] = array_merge(['첨부파일 저장에 실패했습니다. 업로드 제한 또는 data/file/inquiry 권한을 확인해주세요.'], $uploadErrors);
    json_exit(400, ['success' => false, 'errors' => $errors]);
}

$inquiryKey = strtolower($input['type_value']);
if ($inquiryKey === '' && $input['type'] !== '') {
    $typeLower = mb_strtolower($input['type'], 'UTF-8');
    if (strpos($typeLower, 'product') !== false || strpos($typeLower, '제품') !== false) {
        $inquiryKey = 'product';
    }
}
$adminEmail = ($inquiryKey === 'product') ? 'ubique99@naver.com' : 'ubique2@naver.com';
$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'example.com';
$subjectCore = ($input['subject'] ?: '(무제)');
$subject = '[STARSTECH] 문의가 등록되었습니다.';
$typeLabel = $input['type'];
$typeValue = $input['type_value'];
$typeLine = $typeLabel;
if ($typeValue !== '' && $typeValue !== $typeLabel) {
    $typeLine .= ' (' . $typeValue . ')';
}
$esc = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$summaryMap = [
    '문의 유형' => $typeLine,
    '기업/소속' => $input['company'],
    '담당자' => $input['manager'],
    '국가' => $input['country'],
    '이메일' => $input['email'],
    '제목' => $subjectCore,
    '접수 일시' => date('Y-m-d H:i:s')
];
$summaryRows = '';
$rowIndex = 0;
foreach ($summaryMap as $label => $value) {
    $rowIndex++;
    $borderStyle = ($rowIndex > 1) ? 'border-top:1px solid #f0f0f0;' : '';
    $summaryRows .= '<tr><th style="text-align:left;color:#374151;padding:8px 0;width:120px;vertical-align:top;' . $borderStyle . '">' . $esc($label) . '</th><td style="padding:8px 0;color:#111827;' . $borderStyle . '">' . $esc($value) . '</td></tr>';
}

$messageHtml = nl2br($esc($input['message']));
$summaryRows .= '<tr><th style="text-align:left;color:#374151;padding:8px 0;width:120px;vertical-align:top;border-top:1px solid #f0f0f0;">문의 내용</th><td style="padding:8px 0;color:#111827;border-top:1px solid #f0f0f0;line-height:1.6;">' . $messageHtml . '</td></tr>';

$attachFiles = [];
$attachTotal = 0;
$attachLimit = 8 * 1024 * 1024;
$attachSkipped = false;
$fileUrls = [];
$attachmentList = '';
if (!empty($savedFiles)) {
    foreach ($savedFiles as $file) {
        $filePath = $file['path'] ?? '';
        if ($filePath === '') continue;
        $fileSize = !empty($file['size']) ? (int)$file['size'] : (is_file($filePath) ? filesize($filePath) : 0);
        $originalName = $file['name'] ?? basename($filePath);
        $bf_file = $file['bf_file'] ?? basename($filePath);
        $url = defined('G5_DATA_URL') ? (G5_DATA_URL . '/file/' . $bo_table . '/' . rawurlencode($bf_file)) : null;
        if ($fileSize && ($attachTotal + $fileSize) <= $attachLimit) {
            $attachFiles[] = ['path' => $filePath, 'name' => $originalName];
            $attachTotal += $fileSize;
        } elseif ($fileSize) {
            $attachSkipped = true;
        }
        $label = $esc($originalName);
        $liStyle = 'margin:6px 0;font-size:14px;';
        if ($url) {
            $safeUrl = $esc($url);
            $attachmentList .= '<li style="' . $liStyle . '"><a href="' . $safeUrl . '" style="color:#2563eb;text-decoration:underline;">' . $label . '</a></li>';
            $fileUrls[] = $url;
        } else {
            $attachmentList .= '<li style="' . $liStyle . '">' . $label . '</li>';
        }
    }
}

if ($attachSkipped) {
    $summaryRows .= '<tr><th style="text-align:left;color:#374151;padding:8px 0;width:120px;vertical-align:top;border-top:1px solid #f0f0f0;">첨부 안내</th><td style="padding:8px 0;color:#111827;border-top:1px solid #f0f0f0;line-height:1.6;">첨부파일 용량이 커서 메일에 포함하지 못했습니다. 관리자에서 확인해주세요.</td></tr>';
}

$body = <<<HTML
<!doctype html>
<html>
<head>
<meta charset="utf-8">
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#111827;font-family:-apple-system,BlinkMacSystemFont,'Apple SD Gothic Neo','Noto Sans KR','Malgun Gothic',Arial,sans-serif;">
<div style="width:100%;padding:0;background:#ffffff;">
  <div style="width:680px;max-width:100%;margin:0;">
    <div style="background:#111827;color:#ffffff;padding:14px 16px;font-size:16px;font-weight:600;text-align:left;">STARSTECH 문의 접수</div>
    <div style="padding:16px 16px 12px;">
      <div style="font-size:12px;color:#6b7280;letter-spacing:.04em;margin:0 0 6px;">문의 정보</div>
      <table style="width:100%;border-collapse:collapse;font-size:14px;text-align:left;">
        {$summaryRows}
      </table>
    </div>
HTML;

if ($attachmentList !== '') {
    $body .= '<div style="padding:8px 16px 16px;"><div style="font-size:12px;color:#6b7280;letter-spacing:.04em;margin:0 0 6px;">첨부 파일</div><ul style="margin:0;padding-left:18px;">' . $attachmentList . '</ul></div>';
}

$body .= <<<HTML
  </div>
</div>
</body>
</html>
HTML;
$fromEmail = (isset($config['cf_admin_email']) && $config['cf_admin_email']) ? $config['cf_admin_email'] : (defined('G5_SMTP_USER') && G5_SMTP_USER ? G5_SMTP_USER : ('noreply@' . $serverName));
$fromName = (isset($config['cf_admin_email_name']) && $config['cf_admin_email_name']) ? $config['cf_admin_email_name'] : 'STARSTECH';
$encodedSubject = $subject;
if (function_exists('mb_encode_mimeheader')) {
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
} else {
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
}
$encodedBody = chunk_split(base64_encode($body));
$sent = false;
$mail_error = '';
if (class_exists('PHPMailer')) {
    try {
        $mail = new PHPMailer();
        if (defined('G5_SMTP') && G5_SMTP) {
            $mail->IsSMTP();
            $mail->Host = G5_SMTP;
            if (defined('G5_SMTP_PORT') && G5_SMTP_PORT) {
                $mail->Port = G5_SMTP_PORT;
            }
            if (defined('G5_SMTP_SECURE') && G5_SMTP_SECURE) {
                $mail->SMTPSecure = G5_SMTP_SECURE;
                if (G5_SMTP_SECURE === 'ssl') {
                    $mail->SMTPAutoTLS = false;
                }
            }
            if (defined('G5_SMTP_USER') && G5_SMTP_USER) {
                $mail->SMTPAuth = true;
                $mail->Username = G5_SMTP_USER;
                if (defined('G5_SMTP_PASS')) {
                    $mail->Password = G5_SMTP_PASS;
                }
            }
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($adminEmail);
        if (!empty($input['email'])) {
            $mail->addReplyTo($input['email']);
        }
        $mail->Subject = $subject;
        $mail->msgHTML($body);
        foreach ($attachFiles as $file) {
            if (!empty($file['path']) && is_file($file['path'])) {
                $mail->addAttachment($file['path'], $file['name'] ?? basename($file['path']));
            }
        }
        $sent = $mail->send();
        if (!$sent) {
            $mail_error = $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        $mail_error = $e->getMessage();
    }
} else {
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=\"UTF-8\"";
    $headers[] = "Content-Transfer-Encoding: base64";
    $headers[] = "From: " . $fromEmail;
    $headers[] = "Reply-To: " . $input['email'];
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $sent = @mail($adminEmail, $encodedSubject, $encodedBody, implode("\r\n", $headers), '-fnoreply@' . $serverName);
}

$gnuboard_inserted = false;
$wr_id = null;
$logEntry = [
    'time' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'subject' => $input['subject'],
    'email' => $input['email'],
    'manager' => $input['manager'],
    'company' => $input['company'],
    'saved_files' => array_map(function ($file) {
        return basename($file['path'] ?? '');
    }, $savedFiles),
    'file_urls' => $fileUrls,
    'mail_sent' => (bool)$sent,
];

if (!defined('G5_DATA_URL') && is_file(__DIR__ . '/../common.php')) {
    @include_once __DIR__ . '/../common.php';
}

if (defined('G5_DATA_URL') && isset($g5['write_prefix'])) {
    $write_table = $g5['write_prefix'] . 'inquiry';
    $link1 = isset($fileUrls[0]) ? $fileUrls[0] : '';

    $postSubject = ($input['subject'] ?: '(무제)');
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

    $wr_num_sql = "(SELECT IFNULL(MIN(wr_num) - 1, -1) FROM `{$write_table}` as sq)";
    $sql = "INSERT INTO `{$write_table}` (wr_num, wr_reply, wr_parent, wr_subject, wr_content, wr_name, wr_email, wr_link1, wr_datetime, wr_last, wr_ip, wr_is_comment) VALUES (" . $wr_num_sql . ", '', 0, '" . sql_escape_string($postSubject) . "', '" . sql_escape_string($postContent) . "', '" . sql_escape_string($input['manager']) . "', '" . sql_escape_string($input['email']) . "', '" . sql_escape_string($link1) . "', NOW(), NOW(), '" . sql_escape_string($_SERVER['REMOTE_ADDR']) . "', 0)";
    $res = @sql_query($sql);
    if ($res !== false) {
        $wr_id = @sql_insert_id();
        $gnuboard_inserted = (bool)$wr_id;
        if ($wr_id) {
            @sql_query("UPDATE `{$write_table}` SET wr_parent = '{$wr_id}' WHERE wr_id = '{$wr_id}'");
            if (isset($g5['board_new_table'])) {
                $bn_datetime = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');
                @sql_query("INSERT INTO {$g5['board_new_table']} (bo_table, wr_id, wr_parent, bn_datetime, mb_id) VALUES ('{$bo_table}', '{$wr_id}', '{$wr_id}', '{$bn_datetime}', '')");
            }
            if (isset($g5['board_table'])) {
                $count_row = @sql_fetch("SELECT COUNT(*) AS cnt FROM `{$write_table}` WHERE wr_is_comment = 0");
                if ($count_row && isset($count_row['cnt'])) {
                    @sql_query("UPDATE {$g5['board_table']} SET bo_count_write = '" . (int)$count_row['cnt'] . "' WHERE bo_table = '{$bo_table}'");
                }
            }

            if (!empty($savedFiles) && isset($g5['board_file_table'])) {
                $bf_no = 0;
                foreach ($savedFiles as $file) {
                    $src = $file['path'] ?? '';
                    if ($src === '' || !is_file($src)) continue;
                    $source_name = $file['name'] ?? basename($src);
                    $source_name = function_exists('get_safe_filename') ? get_safe_filename($source_name) : $source_name;
                    $filesize = !empty($file['size']) ? (int)$file['size'] : (int)@filesize($src);
                    $timg = @getimagesize($src);
                    $bf_width = isset($timg[0]) ? (int)$timg[0] : 0;
                    $bf_height = isset($timg[1]) ? (int)$timg[1] : 0;
                    $bf_type = isset($timg[2]) ? (int)$timg[2] : 0;
                    $bf_file = $file['bf_file'] ?? basename($src);

                    $sql = "INSERT INTO {$g5['board_file_table']}
                            SET bo_table = '{$bo_table}',
                                wr_id = '{$wr_id}',
                                bf_no = '{$bf_no}',
                                bf_source = '" . sql_escape_string($source_name) . "',
                                bf_file = '" . sql_escape_string($bf_file) . "',
                                bf_content = '',
                                bf_fileurl = '',
                                bf_thumburl = '',
                                bf_storage = '',
                                bf_download = 0,
                                bf_filesize = '" . (int)$filesize . "',
                                bf_width = '" . $bf_width . "',
                                bf_height = '" . $bf_height . "',
                                bf_type = '" . $bf_type . "',
                                bf_datetime = '" . G5_TIME_YMDHIS . "'";
                    @sql_query($sql);
                    $bf_no++;
                }

                if ($bf_no > 0) {
                    $row2 = @sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$wr_id}'");
                    if ($row2 && isset($row2['cnt'])) {
                        @sql_query("UPDATE {$write_table} SET wr_file = '" . (int)$row2['cnt'] . "' WHERE wr_id = '{$wr_id}'");
                    }
                }
            }
        }
        $logEntry['wr_id'] = $wr_id;
        $logEntry['post_subject'] = $postSubject;
    } else {
        $logEntry['gnuboard_error'] = 'insert_failed';
    }
}

$logPath = __DIR__ . '/../data/contact_submissions.log';
@file_put_contents($logPath, json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

json_exit(200, ['success' => true, 'mail_sent' => (bool)$sent, 'gnuboard' => ['inserted' => $gnuboard_inserted, 'wr_id' => $wr_id]]);
