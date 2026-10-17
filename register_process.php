<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirm  = (string) ($_POST['confirm_password'] ?? '');

function register_fail(string $message, string $name, string $email)
{
    remember_input(['name' => $name, 'email' => $email]);
    flash('error', $message);
    redirect('register.php');
}

if (!csrf_valid()) {
    register_fail('Your session expired. Please try again.', $name, $email);
}
if ($name === '' || $email === '' || $password === '') {
    register_fail('Please fill in all fields.', $name, $email);
}
if (mb_strlen($name) > 100) {
    register_fail('Name must be 100 characters or fewer.', $name, $email);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    register_fail('Enter a valid email address.', $name, $email);
}
if (strlen($password) < 8) {
    register_fail('Password must be at least 8 characters.', $name, $email);
}
if ($password !== $confirm) {
    register_fail('Passwords do not match.', $name, $email);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    try {
        // New accounts never choose their role. The database default applies.
        db_query('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', 'sss', [$name, $email, $hash]);
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() !== 1364) { // 1364 = "role has no default value"
            throw $e;
        }
        db_query('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)', 'ssss', [$name, $email, $hash, 'citizen']);
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) { // duplicate email
        register_fail('This email is already registered. Try signing in instead.', $name, $email);
    }
    error_log($e->getMessage());
    register_fail('Registration failed. Please try again.', $name, $email);
}

flash('success', 'Account created. Sign in to continue.');
redirect('index.php');
