<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access Control: Verify user is logged in and has admin/officer privileges
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'officer'])) {
    // If regular user tries to access, redirect to regular dashboard
    header("Location: dashboard.php");
    exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Officer';
$message = '';
$message_type = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $new_status   = trim($_POST['status'] ?? 'Pending');

    // Allowed status options for validation
    $allowed_statuses = ['Pending', 'Assigned', 'Collected'];

    if ($complaint_id > 0 && in_array($new_status, $allowed_statuses)) {
        $updateStmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $new_status, $complaint_id);

        if ($updateStmt->execute()) {
            $message = "Complaint #{$complaint_id} status updated to '{$new_status}' successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to update complaint status.";
            $message_type = "error";
        }
        $updateStmt->close();
    } else {
        $message = "Invalid status update request.";
        $message_type = "error";
    }
}

// Fetch All Citizen Reports with User Details
$reports = [];
$query = "SELECT complaints.id, complaints.location, complaints.waste_type, complaints.description, 
                 complaints.status, complaints.created_at, users.name AS citizen_name, users.email AS citizen_email 
          FROM complaints 
          JOIN users ON complaints.user_id = users.id 
          ORDER BY complaints.created_at DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - Smart Waste Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: block;
            min-height: 100vh;
            background-color: var(--background);
            padding: 0;
        }

        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .navbar h1 {
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logout-btn {
            background-color: #d32f2f;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .logout-btn:hover {
            background-color: #b71c1c;
        }

        .main-container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .card h3 {
            margin-bottom: 16px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--background);
            padding-bottom: 8px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 0.875rem;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--background);
            color: var(--text-muted);
            font-weight: 600;
        }

        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-form select {
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
        }

        .update-btn {
            padding: 6px 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
        }

        .update-btn:hover {
            background-color: var(--primary-hover);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-pending { background-color: #fff3e0; color: #e65100; }
        .badge-assigned { background-color: #e3f2fd; color: #1565c0; }
        .badge-collected { background-color: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="navbar">
        <h1>Smart Waste Management - Municipal Control Center</h1>
        <div class="user-info">
            <span>Officer: <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <div class="card">
            <h3>City-Wide Waste Collection Reports</h3>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($reports)): ?>
                <p style="color: var(--text-muted); padding: 20px 0;">No citizen collection requests submitted in the system yet.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Citizen</th>
                                <th>Location</th>
                                <th>Waste Type</th>
                                <th>Notes</th>
                                <th>Current Status</th>
                                <th>Date</th>
                                <th>Action / Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>#<?php echo $report['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($report['citizen_name']); ?></strong><br>
                                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($report['citizen_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($report['location']); ?></td>
                                    <td><?php echo htmlspecialchars($report['waste_type']); ?></td>
                                    <td><?php echo htmlspecialchars($report['description'] ?: 'None'); ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'badge-pending';
                                            if (strtolower($report['status']) === 'assigned') {
                                                $statusClass = 'badge-assigned';
                                            } elseif (strtolower($report['status']) === 'collected') {
                                                $statusClass = 'badge-collected';
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($report['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <form action="admin_dashboard.php" method="POST" class="status-form">
                                            <input type="hidden" name="complaint_id" value="<?php echo $report['id']; ?>">
                                            <select name="status">
                                                <option value="Pending" <?php echo ($report['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Assigned" <?php echo ($report['status'] === 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                                                <option value="Collected" <?php echo ($report['status'] === 'Collected') ? 'selected' : ''; ?>>Collected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="update-btn">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>