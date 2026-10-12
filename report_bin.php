<?php
include 'config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $location = $conn->real_escape_string($_POST['location']);
    $description = $conn->real_escape_string($_POST['description']);
    $user_id = 1; // Temporary placeholder user ID until login session is fully active

    $sql = "INSERT INTO complaints (user_id, location, description) VALUES ('$user_id', '$location', '$description')";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Complaint submitted successfully!";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Waste Issue</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Report Overflowing Bin or Waste Issue</h2>
        <?php if($message != "") { echo "<p style='color:green;'>$message</p>"; } ?>
        <form action="report_bin.php" method="POST">
            <label for="location">Location / Area:</label>
            <input type="text" id="location" name="location" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" required></textarea>

            <button type="submit">Submit Report</button>
        </form>
        <br>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>