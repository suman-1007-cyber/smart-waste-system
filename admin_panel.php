<?php
include 'config/db.php';

$message = "";

// Handle status updates from officers
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    $update_sql = "UPDATE complaints SET status = '$new_status' WHERE id = $complaint_id";
    if ($conn->query($update_sql) === TRUE) {
        $message = "Status updated successfully for Report #$complaint_id!";
    } else {
        $message = "Error updating status: " . $conn->error;
    }
}

// Fetch all complaints for administrative view
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
    <title>Municipal Officer Panel - Waste Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Municipal Officer Management Panel</h2>
        <a href="dashboard.php">← Back to Public Dashboard</a>
        <br><br>

        <?php if($message != "") { echo "<p style='color:green;'><strong>$message</strong></p>"; } ?>

        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; text-align:left;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reported By</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Current Status</th>
                    <th>Update Status</th>
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
                        echo "<td>";
                        echo "<form action='admin_panel.php' method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='complaint_id' value='" . $row['id'] . "'>";
                        echo "<select name='status'>";
                        echo "<option value='Pending' " . ($row['status'] == 'Pending' ? 'selected' : '') . ">Pending</option>";
                        echo "<option value='In Progress' " . ($row['status'] == 'In Progress' ? 'selected' : '') . ">In Progress</option>";
                        echo "<option value='Resolved' " . ($row['status'] == 'Resolved' ? 'selected' : '') . ">Resolved</option>";
                        echo "</select> ";
                        echo "<button type='submit' name='update_status'>Update</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No complaints available to manage.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>