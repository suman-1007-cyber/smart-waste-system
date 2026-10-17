<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect(is_officer() ? 'admin_panel.php' : 'dashboard.php');
}
if (isset($_GET['out'])) {
    flash('success', 'You have been signed out.');
}

page_head('Sign in', 'auth');
auth_open('See a full bin? Report it in under a minute.');
?>
    <h2>Sign in</h2>
    <p class="muted">Welcome back. Sign in to report issues and track their progress.</p>

    <?php flash_messages(); ?>

    <form action="login_process.php" method="POST">
        <?= csrf_field() ?>
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autocomplete="email"
               placeholder="name@example.com" value="<?= h(old('email')) ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password" placeholder="Your password">

        <button class="btn" type="submit">Sign in</button>
    </form>

    <p class="auth-footer">Don't have an account? <a href="register.php">Create one</a></p>
<?php auth_close();
