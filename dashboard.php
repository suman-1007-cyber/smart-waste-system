<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user    = require_login();
$officer = is_officer();

// "mine" = this person's reports, "all" = everything reported in the city
$view = $_GET['view'] ?? ($officer ? 'all' : 'mine');
if (!in_array($view, ['mine', 'all'], true)) {
    $view = 'mine';
}

$f       = list_params($_GET);
$scope   = $view === 'mine' ? $user['id'] : null;
$stats   = complaint_stats($scope);
$list    = fetch_complaints($scope, $f['status'], $f['q'], $f['page'], PER_PAGE, $f['type']);
$filtered = $f['status'] !== '' || $f['q'] !== '' || $f['type'] !== '';
$baseQuery = ['view' => $view, 'status' => $f['status'], 'type' => $f['type'], 'q' => $f['q']];

page_head('Dashboard');
app_header('dashboard');
?>
<main>
  <section class="page-title">
    <div>
      <h1>Hello, <?= h(explode(' ', trim($user['name']))[0] ?: 'there') ?></h1>
      <p class="muted" style="margin:0">
        <?= $view === 'mine' ? 'Here is what is happening with your reports.' : 'Every report across the city.' ?>
      </p>
    </div>
    <div class="actions">
      <a class="btn" href="report_bin.php">+ Report an issue</a>
      <?php if ($officer): ?><a class="btn secondary" href="admin_panel.php">Open officer panel</a><?php endif; ?>
    </div>
  </section>

  <?php flash_messages(); ?>

  <div class="tabs" role="tablist">
    <a href="?view=mine" class="<?= $view === 'mine' ? 'active' : '' ?>">My reports</a>
    <a href="?view=all"  class="<?= $view === 'all'  ? 'active' : '' ?>">All city reports</a>
  </div>

  <div class="stack">
    <section class="grid">
      <a class="card stat-card" href="?<?= h(http_build_query(['view' => $view])) ?>">
        <div class="stat-label">Total reports</div>
        <div class="stat"><?= $stats['total'] ?></div>
      </a>
      <a class="card stat-card pending" href="?<?= h(http_build_query(['view' => $view, 'status' => 'Pending'])) ?>">
        <div class="stat-label">Pending</div>
        <div class="stat"><?= $stats['Pending'] ?></div>
      </a>
      <a class="card stat-card progress" href="?<?= h(http_build_query(['view' => $view, 'status' => 'In Progress'])) ?>">
        <div class="stat-label">In progress</div>
        <div class="stat"><?= $stats['In Progress'] ?></div>
      </a>
      <a class="card stat-card resolved" href="?<?= h(http_build_query(['view' => $view, 'status' => 'Resolved'])) ?>">
        <div class="stat-label">Resolved</div>
        <div class="stat"><?= $stats['Resolved'] ?></div>
        <div class="muted small"><?= $stats['rate'] ?>% of reports</div>
      </a>
    </section>

    <section class="card">
      <form class="filters" method="GET">
        <input type="hidden" name="view" value="<?= h($view) ?>">
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
        <?php if ($filtered): ?><a class="btn secondary" href="?view=<?= h($view) ?>">Clear</a><?php endif; ?>
      </form>

      <?php if (!$list['rows']): ?>
        <div class="empty-state">
          <?php if ($filtered): ?>
            <h3>No reports match those filters</h3>
            <p class="muted">Try a different search or clear the filters.</p>
          <?php elseif ($view === 'mine'): ?>
            <h3>You haven't reported anything yet</h3>
            <p class="muted">Spotted an overflowing bin or a missed collection? Tell the municipality.</p>
            <a class="btn" href="report_bin.php">Report your first issue</a>
          <?php else: ?>
            <h3>No reports yet</h3>
            <p class="muted">Reports from across the city will appear here.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr>
              <th>Report</th><th>Status</th>
              <?php if ($view === 'all'): ?><th>Reported by</th><?php endif; ?>
              <th>Reported</th>
            </tr></thead>
            <tbody>
            <?php foreach ($list['rows'] as $r): ?>
              <tr>
                <td class="report-cell">
                  <div><span class="muted">#<?= (int) $r['id'] ?></span> <strong><?= h($r['location']) ?></strong> <?= waste_tag($r['waste_type']) ?></div>
                  <div class="muted desc" title="<?= h($r['description']) ?>"><?= h(mb_strimwidth((string) $r['description'], 0, 140, '…')) ?></div>
                </td>
                <td><?= status_badge((string) $r['status']) ?></td>
                <?php if ($view === 'all'): ?><td><?= h(display_name($r['name'], $officer)) ?></td><?php endif; ?>
                <td class="nowrap"><?= h(fmt_dt($r['created_at'], 'd M Y')) ?><div class="muted small"><?= h(age_text((int) $r['age_hours'])) ?></div></td>
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
