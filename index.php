<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Waste Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Smart Waste Segregation & Collection Tracker</h2>
        <form action="login_process.php" method="POST">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <!-- Link for new users to register -->
        <div style="margin-top: 15px; text-align: center;">
            <p>Don't have an account yet? <a href="register.php">Register / Create Account</a></p>
        </div>
    </div>
</body>
</html>