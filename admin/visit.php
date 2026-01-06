<?php
include_once('./_common.php');
include_once(G5_LIB_PATH . '/visit.lib.php');

if (!defined('G5_IS_ADMIN')) {
    define('G5_IS_ADMIN', true);
}

$g5['title'] = '방문자 통계';
include_once(G5_PATH . '/theme/sidepanel/head.php');

$today = G5_TIME_YMD;
$fr_date = isset($_GET['fr_date']) && preg_match("/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/", $_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-29 days', strtotime($today)));
$to_date = isset($_GET['to_date']) && preg_match("/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/", $_GET['to_date']) ? $_GET['to_date'] : $today;
if (strtotime($fr_date) > strtotime($to_date)) {
    $t = $fr_date;
    $fr_date = $to_date;
    $to_date = $t;
}

$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

function mask_ip($ip)
{
    if (strpos($ip, ':') !== false)
        return preg_replace('/^(([0-9a-f]{1,4}:){2}).*$/i', '$1****:****', $ip);
    return preg_replace('/^(\d{1,3}\.\d{1,3})\.\d{1,3}\.\d{1,3}$/', '$1.*.*', $ip);
}

$cf_today = $cf_total = $cf_max = 0;
if (!empty($config['cf_visit']) && preg_match("/오늘:(.*),어제:(.*),최대:(.*),전체:(.*)/", $config['cf_visit'], $m)) {
    $cf_today = (int) $m[1];
    $cf_max = (int) $m[3];
    $cf_total = (int) $m[4];
}
$row = sql_fetch("SELECT vs_count AS c FROM {$g5['visit_sum_table']} WHERE vs_date=CURDATE()");
$kpi_today = max((int) ($row['c'] ?? 0), $cf_today);
$row = sql_fetch("SELECT SUM(vs_count) AS c FROM {$g5['visit_sum_table']}");
$kpi_total = max((int) ($row['c'] ?? 0), $cf_total);
$kpi_max = $cf_max;

$daily = [];
$rs = sql_query("SELECT vs_date d, vs_count c FROM {$g5['visit_sum_table']} WHERE vs_date BETWEEN '{$fr_date}' AND '{$to_date}' ORDER BY vs_date ASC");
while ($r = sql_fetch_array($rs)) {
    $daily[] = ['d' => $r['d'], 'c' => (int) $r['c']];
}
$daily_total = array_sum(array_column($daily, 'c'));
$daily_days = max(1, count($daily));
$daily_avg = round($daily_total / $daily_days, 1);
$daily_max_v = 0;
$daily_max_d = '-';
$daily_min_v = null;
$daily_min_d = '-';
foreach ($daily as $it) {
    if ($it['c'] >= $daily_max_v) {
        $daily_max_v = $it['c'];
        $daily_max_d = $it['d'];
    }
    if ($daily_min_v === null || $it['c'] <= $daily_min_v) {
        $daily_min_v = $it['c'];
        $daily_min_d = $it['d'];
    }
}

$hours = array_fill(0, 24, 0);
$rs = sql_query("SELECT HOUR(vi_time) h, COUNT(*) c FROM {$g5['visit_table']} WHERE vi_date=CURDATE() GROUP BY HOUR(vi_time) ORDER BY h");
while ($r = sql_fetch_array($rs)) {
    $hours[(int) $r['h']] = (int) $r['c'];
}

$dow = array_fill(0, 7, 0);
$rs = sql_query("SELECT DAYOFWEEK(vi_date) dw, COUNT(*) c FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}' GROUP BY DAYOFWEEK(vi_date)");
while ($r = sql_fetch_array($rs)) {
    $idx = ((int) $r['dw'] + 5) % 7;
    $dow[$idx] = (int) $r['c'];
}

$browser_labels = [];
$browser_counts = [];
$rs = sql_query("SELECT vi_browser nm, COUNT(*) c FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}' AND IFNULL(TRIM(vi_browser),'')<>'' GROUP BY vi_browser ORDER BY c DESC LIMIT 8");
while ($r = sql_fetch_array($rs)) {
    $browser_labels[] = $r['nm'];
    $browser_counts[] = (int) $r['c'];
}
if (!count($browser_labels)) {
    $tmp = [];
    $rs = sql_query("SELECT vi_agent FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}'");
    while ($r = sql_fetch_array($rs)) {
        $b = get_brow($r['vi_agent']) ?: '기타/미확인';
        $tmp[$b] = ($tmp[$b] ?? 0) + 1;
    }
    arsort($tmp);
    $tmp = array_slice($tmp, 0, 8, true);
    $browser_labels = array_keys($tmp);
    $browser_counts = array_values($tmp);
}
$os_labels = [];
$os_counts = [];
$rs = sql_query("SELECT vi_os nm, COUNT(*) c FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}' AND IFNULL(TRIM(vi_os),'')<>'' GROUP BY vi_os ORDER BY c DESC LIMIT 8");
while ($r = sql_fetch_array($rs)) {
    $os_labels[] = $r['nm'];
    $os_counts[] = (int) $r['c'];
}
if (!count($os_labels)) {
    $tmp = [];
    $rs = sql_query("SELECT vi_agent FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}'");
    while ($r = sql_fetch_array($rs)) {
        $o = get_os($r['vi_agent']) ?: '기타/미확인';
        $tmp[$o] = ($tmp[$o] ?? 0) + 1;
    }
    arsort($tmp);
    $tmp = array_slice($tmp, 0, 8, true);
    $os_labels = array_keys($tmp);
    $os_counts = array_values($tmp);
}

$host = sql_real_escape_string($_SERVER['HTTP_HOST']);
$domain_labels = [];
$domain_counts = [];
$map = [];
$rs = sql_query("SELECT vi_referer FROM {$g5['visit_table']} WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}' AND IFNULL(TRIM(vi_referer),'')<>''");
while ($r = sql_fetch_array($rs)) {
    $ref = trim($r['vi_referer']);
    if (!$ref)
        continue;
    if (strpos($ref, $host) !== false) {
        $key = '내부 페이지';
    } else {
        $h = parse_url($ref, PHP_URL_HOST);
        if (!$h) {
            $ref2 = (strpos($ref, '://') === false) ? 'http://' . $ref : $ref;
            $h = parse_url($ref2, PHP_URL_HOST);
        }
        $key = $h ?: '직접 접속';
    }
    $map[$key] = ($map[$key] ?? 0) + 1;
}
if (!$map)
    $map = ['데이터 없음' => 0];
arsort($map);
$domain_labels = array_slice(array_keys($map), 0, 10);
$domain_counts = array_slice(array_values($map), 0, 10);

$sql_common = " FROM {$g5['visit_table']} ";
$sql_search = " WHERE vi_date BETWEEN '{$fr_date}' AND '{$to_date}' ";
$row = sql_fetch("SELECT COUNT(*) cnt {$sql_common} {$sql_search}");
$total_count = (int) $row['cnt'];
$rs = sql_query("SELECT * {$sql_common} {$sql_search} ORDER BY vi_id DESC LIMIT {$offset}, {$per_page}");
$visit_rows = [];
while ($r = sql_fetch_array($rs)) {
    $visit_rows[] = $r;
}
$total_page = max(1, ceil($total_count / $per_page));
$qstr = "fr_date={$fr_date}&to_date={$to_date}&page=";
?>
<style>
.card h3 {font-family:Pretendard,"Noto Sans KR",system-ui,sans-serif!important;font-weight:800;margin:0 0 10px;color:var(--title);font-size:16px}
html,body,input,button,textarea,select {font-family:Pretendard,"Noto Sans KR",system-ui,sans-serif}
.dashboard {max-width:1200px;margin:0 auto}
.filter-row {display:flex;gap:8px;align-items:end;margin:10px 0 18px}
.filter-row .frm {display:flex;gap:12px;flex-wrap:wrap;align-items:end}
.filter-row .frm label {display:flex;flex-direction:column;font-size:13px;color:var(--muted)}
.filter-row input[type="date"] {padding:8px 12px;border:1px solid var(--line);border-radius:10px;background:#fff}
.filter-row button {padding:10px 16px;border:0;border-radius:10px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer}
.kpis {display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:6px 0 18px}
.kpi {background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;min-height:132px;box-shadow:0 2px 6px rgba(15,23,42,.04);display:flex;flex-direction:column;justify-content:center}
.kpi .lbl {font-size:13px;color:var(--muted)}
.kpi .val {font-size:30px;font-weight:800;color:var(--primary);line-height:1.1;margin-top:6px}
.kpi .sub {font-size:12px;color:#64748B;margin-top:8px}
.grid-2 {display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px}
.grid-3 {display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px}
.card {background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;box-shadow:0 2px 6px rgba(15,23,42,.04);min-width:0;}
.card.log{margin-top: 14px;}
.canvas-wrap {height:320px;position:relative}
.note {color:#64748B;font-size:12px;margin-top:8px}
.table-wrap{overflow:auto;margin-bottom:14px;padding-bottom:6px}
.tbl {width:100%;border-collapse:collapse}
.tbl th,.tbl td {padding:12px 14px;border:1px solid #EEF2F6;white-space:nowrap;font-size:14px}
.tbl thead th {background:#F7F8FB;color:var(--title);font-weight:800}
.badge {display:inline-block;padding:2px 8px;background:#EEF2FF;color:#3F56CF;border-radius:999px;font-size:12px}
code {font-family:inherit;background:#F3F5F9;border:1px solid #E5EAF3;border-radius:8px;padding:2px 6px}
.mini-metrics {display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.mini-metrics .chip {background:#F3F5F9;border:1px solid #E6EBF4;border-radius:999px;padding:6px 10px;font-size:12px;color:#334155}
.mini-metrics .chip b {font-weight:800;color:#0F172A;margin-left:6px}
.pg_wrap{width:100%;display:flex;justify-content:center;margin-top:30px}
.pg{display:flex;gap:8px;align-items:center}
.pg a,
.pg strong.pg_current,
.pg span.pg_page{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:#334155;font-size:14px;box-shadow:0 2px 6px rgba(15,23,42,.04);text-decoration:none}
.pg a:hover{border-color:var(--primary);color:var(--primary)}
.pg strong.pg_current{background:var(--primary);border-color:var(--primary);color:#fff;font-weight:800}
.pg .pg_start, .pg .pg_prev, .pg .pg_next, .pg .pg_end{min-width:32px;padding:0 10px;color:#475569;}
.pg .disabled{opacity:.4;pointer-events:none}
.pg .pg_start{background:url('../img/btn_first.gif') no-repeat 50% 50% #fff;border:1px solid #e0e6f0;border-radius:10px;min-width:32px;height:36px;color:transparent;text-indent:-9999px}
.pg .pg_prev{background:url('../img/btn_prev.gif') no-repeat 50% 50% #fff;border:1px solid #e0e6f0;border-radius:10px;min-width:32px;height:36px;color:transparent;text-indent:-9999px}
.pg .pg_next{background:url('../img/btn_next.gif') no-repeat 50% 50% #fff;border:1px solid #e0e6f0;border-radius:10px;min-width:32px;height:36px;color:transparent;text-indent:-9999px}
.pg .pg_end{background:url('../img/btn_end.gif') no-repeat 50% 50% #fff;border:1px solid #e0e6f0;border-radius:10px;min-width:32px;height:36px;color:transparent;text-indent:-9999px}


@media(max-width:1100px){.kpis{grid-template-columns:1fr 1fr}.grid-2{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr}}
</style>

<div class="dashboard">
    <div class="section-header" style="margin: 0 0 12px;">
        <h2 style="margin:0 0 30px;font-size:32px;font-weight:800;">방문자 통계</h2>
    </div>
    <div class="filter-row">
        <form class="frm" method="get">
            <label>시작일<input type="date" name="fr_date" value="<?php echo $fr_date; ?>"></label>
            <label>종료일<input type="date" name="to_date" value="<?php echo $to_date; ?>"></label>
            <button type="submit">적용</button>
        </form>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="lbl">오늘 방문</div>
            <div class="val"><?php echo number_format($kpi_today); ?></div>
            <div class="sub"><?php echo $to_date; ?></div>
        </div>
        <div class="kpi">
            <div class="lbl">전체 방문(누적)</div>
            <div class="val"><?php echo number_format($kpi_total); ?></div>
            <div class="sub">기간 <?php echo $fr_date; ?> ~ <?php echo $to_date; ?></div>
        </div>
        <div class="kpi">
            <div class="lbl">최대 방문(일)</div>
            <div class="val"><?php echo number_format($kpi_max); ?></div>
            <div class="sub">기간 <?php echo $fr_date; ?> ~ <?php echo $to_date; ?></div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3>일자별 방문 추이</h3>
            <div class="canvas-wrap"><canvas id="dailyChart"></canvas></div>
            <div class="mini-metrics">
                <span class="chip">합계 <b><?php echo number_format($daily_total); ?></b></span>
                <span class="chip">평균 <b><?php echo number_format($daily_avg, 1); ?></b></span>
                <span class="chip">최대 <b><?php echo number_format($daily_max_v); ?></b> <span style="color:#64748B">/
                        <?php echo $daily_max_d; ?></span></span>
                <span class="chip">최소 <b><?php echo number_format($daily_min_v); ?></b> <span style="color:#64748B">/
                        <?php echo $daily_min_d; ?></span></span>
            </div>
            <div class="note">기간 <?php echo $fr_date; ?> ~ <?php echo $to_date; ?></div>
        </div>

        <div class="card">
            <h3>시간대별 방문(오늘)</h3>
            <div class="canvas-wrap"><canvas id="hourlyChart"></canvas></div>
            <div class="note"><?php echo $today; ?></div>
        </div>
    </div>

    <div class="grid-3">
        <div class="card">
            <h3>요일별 분포</h3>
            <div class="canvas-wrap"><canvas id="dowChart"></canvas></div>
        </div>
        <div class="card">
            <h3>브라우저 TOP</h3>
            <div class="canvas-wrap"><canvas id="browserChart"></canvas></div>
        </div>
        <div class="card">
            <h3>운영체제 TOP</h3>
            <div class="canvas-wrap"><canvas id="osChart"></canvas></div>
        </div>
    </div>

    <div class="card">
        <h3>유입 도메인 TOP</h3>
        <div class="canvas-wrap"><canvas id="domainChart"></canvas></div>
    </div>

    <div class="card log">
        <h3>최근 방문 로그</h3>
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>IP</th>
                        <th>방문일시</th>
                        <th>브라우저</th>
                        <th>OS</th>
                        <th>리퍼러(유입)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($visit_rows)):
                        foreach ($visit_rows as $i => $row):
                            $b = $row['vi_browser'] ?: get_brow($row['vi_agent']);
                            $o = $row['vi_os'] ?: get_os($row['vi_agent']);
                            $ip_disp = mask_ip($row['vi_ip']);
                            $title = '직접 접속';
                            $link_open = '';
                            $link_close = '';
                            if (!empty($row['vi_referer'])) {
                                $title = cut_str(urldecode(get_text($row['vi_referer'])), 80, '');
                                $link_open = '<a href="' . get_text($row['vi_referer']) . '" target="_blank" rel="noreferrer noopener">';
                                $link_close = '</a>';
                            }
                            ?>
                            <tr>
                                <td><?php echo number_format($total_count - $offset - $i); ?></td>
                                <td><code><?php echo htmlspecialchars($ip_disp); ?></code></td>
                                <td><?php echo $row['vi_date'] . ' ' . $row['vi_time']; ?></td>
                                <td><span class="badge"><?php echo get_text($b); ?></span></td>
                                <td><span class="badge"><?php echo get_text($o); ?></span></td>
                                <td><?php echo $link_open . htmlspecialchars($title) . $link_close; ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--muted);padding:24px">데이터가 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_page > 1) {
            $pagelist = get_paging($config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?{$qstr}");
            echo $pagelist;
        } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const P = { line: '#506BE1', fill: 'rgba(80,107,225,0.12)', bar: '#5AE1C3', bar2: '#5ED9FC', bar3: '#25354C', grid: 'rgba(15,23,42,.06)' };

    const daily = <?php echo json_encode($daily, JSON_UNESCAPED_UNICODE); ?>;
    const dailyLabelsFull = daily.map(x => x.d);
    const dailyLabelsFmt = dailyLabelsFull.map(d => d ? d.slice(5).replace('-', '.') : '');
    const dailyCounts = daily.map(x => parseInt(x.c || 0, 10));

    const hourData = <?php echo json_encode(array_values($hours)); ?>;
    const hourLabels = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0') + ':00');

    const dowData = <?php echo json_encode(array_values($dow)); ?>;
    const dowLabels = ['월', '화', '수', '목', '금', '토', '일'];

    const browserLabels = <?php echo json_encode($browser_labels, JSON_UNESCAPED_UNICODE); ?>;
    const browserCounts = <?php echo json_encode($browser_counts); ?>;

    const osLabels = <?php echo json_encode($os_labels, JSON_UNESCAPED_UNICODE); ?>;
    const osCounts = <?php echo json_encode($os_counts); ?>;

    const domainLabels = <?php echo json_encode($domain_labels, JSON_UNESCAPED_UNICODE); ?>;
    const domainCounts = <?php echo json_encode($domain_counts); ?>;

    function makeLineDaily(id, labels, data) {
        const el = document.getElementById(id); if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'line',
            data: { labels, datasets: [{ data, borderColor: P.line, backgroundColor: P.fill, borderWidth: 2, tension: .25, pointRadius: 2, fill: true }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: P.grid } },
                    x: { type: 'category', grid: { display: false }, ticks: { autoSkip: false, maxRotation: 0, minRotation: 0, padding: 6 } }
                }
            }
        });
    }
    function makeBar(id, labels, data, color) {
        const el = document.getElementById(id); if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: color, borderColor: color, borderWidth: 1, borderRadius: 6, maxBarThickness: 42 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: P.grid } }, x: { grid: { display: false }, ticks: { maxRotation: 0, minRotation: 0 } } } }
        });
    }
    function makeBarCompact(id, labels, data, color) {
        const el = document.getElementById(id); if (!el) return;
        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: color, borderColor: color, borderWidth: 1, borderRadius: 6, maxBarThickness: 48, categoryPercentage: 0.6, barPercentage: 0.6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: P.grid } }, x: { grid: { display: false }, ticks: { maxRotation: 0, minRotation: 0 } } } }
        });
    }

    makeLineDaily('dailyChart', dailyLabelsFmt, dailyCounts);
    makeBar('hourlyChart', hourLabels, hourData, P.bar2);
    makeBar('dowChart', dowLabels, dowData, P.bar3);
    makeBar('browserChart', browserLabels, browserCounts, P.bar);
    makeBar('osChart', osLabels, osCounts, P.line);
    makeBarCompact('domainChart', domainLabels, domainCounts, '#3559F7');
</script>

<?php include_once(G5_PATH . '/theme/sidepanel/tail.php'); ?>
