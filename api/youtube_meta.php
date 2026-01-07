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

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if ($url === '') {
    json_exit(400, ['success' => false, 'error' => 'missing_url']);
}

$parts = parse_url($url);
$host = strtolower($parts['host'] ?? '');
if (!$host || !preg_match('~(^|\\.)youtube\\.com$|(^|\\.)youtu\\.be$~', $host)) {
    json_exit(400, ['success' => false, 'error' => 'invalid_host']);
}

function sidepanel_fetch_url($target) {
    if (!$target) return '';
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $data = curl_exec($ch);
        curl_close($ch);
        return is_string($data) ? $data : '';
    }
    $context = stream_context_create([
        'http' => [
            'timeout' => 4,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    $data = @file_get_contents($target, false, $context);
    return is_string($data) ? $data : '';
}

$video_id = '';
if (preg_match('~(?:youtu\\.be/|youtube\\.com/(?:watch\\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $match)) {
    $video_id = $match[1];
}

$meta = [
    'title' => '',
    'description' => '',
    'thumbnail' => ''
];

$oembed_url = 'https://www.youtube.com/oembed?format=json&url=' . urlencode($url);
$oembed = sidepanel_fetch_url($oembed_url);
if ($oembed) {
    $data = json_decode($oembed, true);
    if (is_array($data)) {
        if (!empty($data['title'])) $meta['title'] = $data['title'];
        if (!empty($data['thumbnail_url'])) $meta['thumbnail'] = $data['thumbnail_url'];
    }
}

if ($meta['thumbnail'] === '' && $video_id) {
    $meta['thumbnail'] = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
}

$watch_url = $video_id ? ('https://www.youtube.com/watch?v=' . $video_id) : $url;
$html = sidepanel_fetch_url($watch_url);
if ($html) {
    if (preg_match('~\"shortDescription\":\"([^\"]*)\"~', $html, $m)) {
        $decoded = json_decode('"' . $m[1] . '"');
        if (is_string($decoded)) $meta['description'] = $decoded;
    }
    if ($meta['description'] === '' && preg_match('~<meta name=\"description\" content=\"([^\"]*)\"~i', $html, $m)) {
        $meta['description'] = html_entity_decode($m[1], ENT_QUOTES);
    }
}

json_exit(200, array_merge(['success' => true], $meta));
