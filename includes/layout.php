<?php
/** Page chrome shared by every screen. */

function page_head(string $title, string $bodyClass = ''): void
{
    $css = 'assets/css/style.css';
    $ver = @filemtime(__DIR__ . '/../' . $css) ?: 1;
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . h($title) . ' | Smart Waste</title>'
       . '<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Ctext y=%27.9em%27 font-size=%2790%27%3E%E2%99%BB%EF%B8%8F%3C/text%3E%3C/svg%3E">'
       . '<link rel="stylesheet" href="' . $css . '?v=' . $ver . '"></head><body'
       . ($bodyClass !== '' ? ' class="' . h($bodyClass) . '"' : '') . '>';
}

function app_header(string $active): void
{
    $user = current_user();
    $links = [
        'dashboard' => ['dashboard.php', 'Dashboard'],
        'report'    => ['report_bin.php', 'Report an issue'],
    ];
    if (is_officer()) {
        $links['admin'] = ['admin_panel.php', 'Officer panel'];
    }

    echo '<header class="site-header"><a class="logo" href="dashboard.php">♻️ Smart Waste</a>'
       . '<nav class="nav" aria-label="Main">';
    foreach ($links as $key => [$href, $label]) {
        $cls = $key === $active ? ' class="active" aria-current="page"' : '';
        echo '<a' . $cls . ' href="' . $href . '">' . h($label) . '</a>';
    }
    echo '</nav><div class="header-right">'
       . '<span class="hello">' . h($user['name'] ?? '')
       . (is_officer() ? ' <span class="role-tag">Officer</span>' : '') . '</span>'
       . '<a class="logout" href="logout.php">Log out</a></div></header>';
}

function flash_messages(): void
{
    foreach (take_flashes() as [$type, $msg]) {
        $role = $type === 'error' ? 'alert' : 'status';
        echo '<p class="msg ' . h($type) . '" role="' . $role . '">' . h($msg) . '</p>';
    }
}

/** Pagination links that keep the current filters. */
function pager(int $page, int $pages, array $query): string
{
    if ($pages <= 1) {
        return '';
    }
    $link = function (int $p, string $label, bool $disabled = false, bool $current = false) use ($query) {
        if ($disabled) {
            return '<span class="disabled">' . $label . '</span>';
        }
        $q = array_filter($query, fn($v) => $v !== '' && $v !== null);
        $q['page'] = $p;
        $cls = $current ? ' class="current" aria-current="page"' : '';
        return '<a' . $cls . ' href="?' . h(http_build_query($q)) . '">' . $label . '</a>';
    };

    $out = '<nav class="pager" aria-label="Pages">' . $link($page - 1, '← Prev', $page <= 1);
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $out .= $link($i, (string) $i, false, $i === $page);
    }
    return $out . $link($page + 1, 'Next →', $page >= $pages) . '</nav>';
}

function page_foot(): void
{
    echo '<footer class="site-footer">© ' . date('Y') . ' Smart Waste Management System — report it, track it, see it fixed</footer></body></html>';
}

/** Two-panel layout used by sign-in and register. */
function auth_open(string $headline): void
{
    echo '<div class="auth-shell"><aside class="auth-side">'
       . '<div class="logo">♻️ Smart Waste</div>'
       . '<h1>' . h($headline) . '</h1>'
       . '<ol class="steps">'
       . '<li><strong>Report</strong><span>Tell us where the problem is and what you see.</span></li>'
       . '<li><strong>Review</strong><span>A municipal officer picks it up and starts work.</span></li>'
       . '<li><strong>Resolved</strong><span>Follow the status until it is fixed.</span></li>'
       . '</ol></aside><main class="auth-main"><div class="card auth-card">';
}

function auth_close(): void
{
    echo '</div></main></div></body></html>';
}
