<?php
include 'config/db.php';

// Fetch all reported complaints from the database
$sql = "SELECT complaints.id, complaints.location, complaints.description, complaints.status, complaints.created_at, users.name 
        FROM complaints 
        LEFT JOIN users ON complaints.user_id = users.id 
        ORDER BY complaints.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Waste Management - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Smart Waste Collection - Complaint Dashboard</h2>
        <a href="report_bin.php">+ Report New Issue</a> | 
        <a href="admin_panel.php">Officer Panel</a> | 
        <a href="logout.php" style="color: red;">Logout</a>
        <br><br>

        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; text-align:left;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reported By</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date Reported</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . ($row['name'] ? $row['name'] : 'Citizen') . "</td>";
                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['status']) . "</strong></td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No complaints reported yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>