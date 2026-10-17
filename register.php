<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect('dashboard.php');
}

page_head('Create account', 'auth');
auth_open('Help keep your neighbourhood clean.');
?>
    <h2>Create an account</h2>
    <p class="muted">It takes a minute. You will use it to report issues and follow their status.</p>

    <?php flash_messages(); ?>

    <form action="register_process.php" method="POST">
        <?= csrf_field() ?>
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" required maxlength="100"
               autocomplete="name" placeholder="Your full name" value="<?= h(old('name')) ?>">

        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required maxlength="100"
               autocomplete="email" placeholder="name@example.com" value="<?= h(old('email')) ?>">

        <label for="password">Password <span class="muted small">(at least 8 characters)</span></label>
        <input type="password" id="password" name="password" required minlength="8"
               autocomplete="new-password">

        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
               autocomplete="new-password">

        <button class="btn" type="submit">Create account</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="index.php">Sign in</a></p>
<?php auth_close();
