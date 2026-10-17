<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if (!csrf_valid()) {
    flash('error', 'Your session expired. Please try again.');
    redirect('index.php');
}

if ($email === '' || $password === '') {
    remember_input(['email' => $email]);
    flash('error', 'Enter your email and password.');
    redirect('index.php');
}

$user = db_one(
    'SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1',
    's',
    [$email]
);

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role']      = $user['role'];

    redirect($user['role'] === 'officer' ? 'admin_panel.php' : 'dashboard.php');
}

// Same message for "no such email" and "wrong password"
remember_input(['email' => $email]);
flash('error', 'Invalid email or password.');
redirect('index.php');
