<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_officer();   // citizens and signed-out visitors are turned away

/* ---------- Update a status ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The row's dropdown is "status" (the new value); the active filter travels as "filter_status"
    $back = list_params([
        'status' => $_POST['filter_status'] ?? '',
        'type'   => $_POST['filter_type'] ?? '',
        'q'      => $_POST['q'] ?? '',
        'page'   => $_POST['page'] ?? 1,
    ]);
    $backUrl = 'admin_panel.php' . (($qs = http_build_query(array_filter($back))) !== '' ? '?' . $qs : '');

    $id  = (int) ($_POST['complaint_id'] ?? 0);
    $new = (string) ($_POST['status'] ?? '');

    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
    } elseif ($id <= 0 || !in_array($new, COMPLAINT_STATUSES, true)) {
        flash('error', 'That update was not valid.');
    } else {
        $current = db_one('SELECT status FROM complaints WHERE id = ?', 'i', [$id]);
        if (!$current) {
            flash('error', "Report #$id no longer exists.");
        } elseif ($current['status'] === $new) {
            flash('success', "Report #$id is already $new.");
        } else {
            db_query('UPDATE complaints SET status = ? WHERE id = ?', 'si', [$new, $id]);
            flash('success', "Report #$id moved to $new.");
        }
    }
    redirect($backUrl);
}

$f     = list_params($_GET);
$stats = complaint_stats(null);
$overdue = overdue_count();

/* ---------- CSV export of the current filter ---------- */
if (isset($_GET['export'])) {
    $all = fetch_complaints(null, $f['status'], $f['q'], 1, 100000, $f['type']);
    $safe = function ($v) {
        $v = (string) $v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; // stop spreadsheet formula injection
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="complaints-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Reported by', 'Location', 'Waste type', 'Description', 'Status', 'Reported at']);
    foreach ($all['rows'] as $r) {
        fputcsv($out, [$r['id'], $safe($r['name'] ?? 'Citizen'), $safe($r['location']), $safe($r['waste_type']), $safe($r['description']),
                       $r['status'], fmt_dt($r['created_at'], 'Y-m-d H:i')]);
    }
    exit;
}

$list      = fetch_complaints(null, $f['status'], $f['q'], $f['page'], PER_PAGE, $f['type']);
$filtered  = $f['status'] !== '' || $f['q'] !== '' || $f['type'] !== '';
$baseQuery = ['status' => $f['status'], 'type' => $f['type'], 'q' => $f['q']];

page_head('Officer panel');
app_header('admin');
?>
<main>
  <section class="page-title">
    <div>
      <h1>Officer panel</h1>
      <p class="muted" style="margin:0">Review reports and move them through to resolved.</p>
    </div>
    <div class="actions">
      <a class="btn secondary" href="?<?= h(http_build_query(array_filter($baseQuery) + ['export' => 1])) ?>">Download CSV</a>
      <a class="btn secondary" href="dashboard.php?view=all">Public dashboard</a>
    </div>
  </section>

  <?php flash_messages(); ?>

  <?php if ($overdue > 0): ?>
    <p class="msg warn">
      <strong><?= $overdue ?> pending <?= $overdue === 1 ? 'report has' : 'reports have' ?></strong>
      waited more than <?= OVERDUE_HOURS / 24 ?> days.
      <a href="?status=Pending">Show pending reports</a>
    </p>
  <?php endif; ?>

  <div class="stack">
    <section class="grid">
      <a class="card stat-card" href="admin_panel.php"><div class="stat-label">Total reports</div><div class="stat"><?= $stats['total'] ?></div></a>
      <a class="card stat-card pending" href="?status=Pending"><div class="stat-label">Pending</div><div class="stat"><?= $stats['Pending'] ?></div></a>
      <a class="card stat-card progress" href="?status=In+Progress"><div class="stat-label">In progress</div><div class="stat"><?= $stats['In Progress'] ?></div></a>
      <a class="card stat-card resolved" href="?status=Resolved"><div class="stat-label">Resolved</div><div class="stat"><?= $stats['Resolved'] ?></div>
        <div class="muted small"><?= $stats['rate'] ?>% resolution rate</div></a>
    </section>

    <section class="card">
      <form class="filters" method="GET">
        <div class="grow">
          <label for="q" class="sr-only">Search</label>
          <input id="q" type="search" name="q" value="<?= h($f['q']) ?>" placeholder="Search location or description">
        </div>
        <div>
          <label for="status" class="sr-only">Status</label>
          <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (COMPLAINT_STATUSES as $s): ?>
              <option value="<?= h($s) ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (has_waste_type()): ?>
        <div>
          <label for="type" class="sr-only">Waste type</label>
          <select id="type" name="type">
            <option value="">All waste types</option>
            <?php foreach (WASTE_TYPES as $t): ?>
              <option value="<?= h($t) ?>" <?= $f['type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <button class="btn" type="submit">Filter</button>
        <?php if ($filtered): ?><a class="btn secondary" href="admin_panel.php">Clear</a><?php endif; ?>
      </form>

      <?php if (!$list['rows']): ?>
        <div class="empty-state">
          <h3><?= $filtered ? 'No reports match those filters' : 'No reports to manage yet' ?></h3>
          <p class="muted"><?= $filtered ? 'Try a different search or clear the filters.' : 'New citizen reports will show up here.' ?></p>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="wide">
            <thead><tr>
              <th>Report</th><th>Reported by</th><th>Reported</th><th>Status</th><th>Update</th>
            </tr></thead>
            <tbody>
            <?php foreach ($list['rows'] as $r):
                $late = $r['status'] === 'Pending' && (int) $r['age_hours'] >= OVERDUE_HOURS; ?>
              <tr>
                <td class="report-cell">
                  <div><span class="muted">#<?= (int) $r['id'] ?></span> <strong><?= h($r['location']) ?></strong> <?= waste_tag($r['waste_type']) ?></div>
                  <div class="muted desc"><?= h($r['description']) ?></div>
                </td>
                <td><?= h(display_name($r['name'], true)) ?></td>
                <td class="nowrap"><?= h(fmt_dt($r['created_at'], 'd M Y, g:i a')) ?>
                  <div class="small <?= $late ? 'late' : 'muted' ?>"><?= h(age_text((int) $r['age_hours'])) ?><?= $late ? ' · overdue' : '' ?></div></td>
                <td><?= status_badge((string) $r['status']) ?></td>
                <td>
                  <form class="inline-update" method="POST" action="admin_panel.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="complaint_id" value="<?= (int) $r['id'] ?>">
                    <input type="hidden" name="q" value="<?= h($f['q']) ?>">
                    <input type="hidden" name="page" value="<?= (int) $list['page'] ?>">
                    <input type="hidden" name="filter_status" value="<?= h($f['status']) ?>">
                    <input type="hidden" name="filter_type" value="<?= h($f['type']) ?>">
                    <label class="sr-only" for="s<?= (int) $r['id'] ?>">Status for report <?= (int) $r['id'] ?></label>
                    <select id="s<?= (int) $r['id'] ?>" name="status">
                      <?php foreach (COMPLAINT_STATUSES as $s): ?>
                        <option value="<?= h($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn small-btn" type="submit">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?= pager($list['page'], $list['pages'], $baseQuery) ?>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php page_foot();
