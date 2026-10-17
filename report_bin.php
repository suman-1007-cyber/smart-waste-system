<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$error = '';
$location = '';
$description = '';
$wasteType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $wasteType   = (string) ($_POST['waste_type'] ?? '');

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (has_waste_type() && !in_array($wasteType, WASTE_TYPES, true)) {
        $error = 'Please choose the type of waste.';
    } elseif ($location === '' || $description === '') {
        $error = 'Please fill in both the location and the description.';
    } elseif (mb_strlen($location) > 150) {
        $error = 'Location must be 150 characters or fewer.';
    } elseif (mb_strlen($description) < 10) {
        $error = 'Add a little more detail (at least 10 characters) so officers know what to look for.';
    } elseif (mb_strlen($description) > 1000) {
        $error = 'Description must be 1000 characters or fewer.';
    } else {
        try {
            global $conn;
            // Status falls back to the table default (Pending)
            if (has_waste_type()) {
                db_query(
                    'INSERT INTO complaints (user_id, location, waste_type, description) VALUES (?, ?, ?, ?)',
                    'isss',
                    [$user['id'], $location, $wasteType, $description]
                );
            } else {
                db_query(
                    'INSERT INTO complaints (user_id, location, description) VALUES (?, ?, ?)',
                    'iss',
                    [$user['id'], $location, $description]
                );
            }
            flash('success', 'Report #' . $conn->insert_id . ' submitted. An officer will review it soon.');
            redirect('dashboard.php?view=mine');
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $error = 'We could not save your report. Please try again.';
        }
    }
}

page_head('Report an issue');
app_header('report');
?>
<main>
  <div class="card form-card">
    <h1>Report a waste issue</h1>
    <p class="muted">Pick the closest issue type, then add where it is. Photos are not needed. A clear location helps most.</p>

    <?php if ($error): ?><p class="msg error" role="alert"><?= h($error) ?></p><?php endif; ?>

    <form method="POST" action="report_bin.php">
      <?= csrf_field() ?>

      <label>What kind of issue is it?</label>
      <div class="chips" id="chips">
        <button type="button" class="chip" data-text="Overflowing bin: ">Overflowing bin</button>
        <button type="button" class="chip" data-text="Missed collection: ">Missed collection</button>
        <button type="button" class="chip" data-text="Illegal dumping: ">Illegal dumping</button>
        <button type="button" class="chip" data-text="Damaged bin: ">Damaged bin</button>
        <button type="button" class="chip" data-text="Bad smell or hygiene issue: ">Smell / hygiene</button>
      </div>

      <?php if (has_waste_type()): ?>
      <label for="waste_type">What type of waste is it?</label>
      <select id="waste_type" name="waste_type" required>
        <option value="">Select a waste type</option>
        <?php foreach (WASTE_TYPES as $t): ?>
          <option value="<?= h($t) ?>" <?= $wasteType === $t ? 'selected' : '' ?>><?= h($t) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>

      <label for="location">Location or area</label>
      <input id="location" name="location" required maxlength="150"
             placeholder="Street, landmark or area, e.g. Near Andheri station east exit"
             value="<?= h($location) ?>">

      <label for="description">Description</label>
      <textarea id="description" name="description" rows="5" required maxlength="1000"
                placeholder="What do you see? How long has it been like this?"><?= h($description) ?></textarea>
      <div class="muted small counter"><span id="count">0</span> / 1000</div>

      <button class="btn" type="submit">Submit report</button>
    </form>

    <p style="margin:18px 0 0"><a href="dashboard.php">← Back to dashboard</a></p>
  </div>
</main>

<script>
(function () {
    const desc  = document.getElementById('description');
    const count = document.getElementById('count');
    const chips = document.querySelectorAll('.chip');

    function update() {
        count.textContent = desc.value.length;
        chips.forEach(c => c.classList.toggle('on', desc.value.startsWith(c.dataset.text)));
    }

    chips.forEach(chip => chip.addEventListener('click', () => {
        // Replace an existing issue-type prefix instead of stacking them
        let text = desc.value;
        chips.forEach(c => { if (text.startsWith(c.dataset.text)) text = text.slice(c.dataset.text.length); });
        desc.value = chip.dataset.text + text;
        desc.focus();
        desc.setSelectionRange(desc.value.length, desc.value.length);
        update();
    }));

    desc.addEventListener('input', update);
    update();
})();
</script>
<?php page_foot();
