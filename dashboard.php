<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Access Control: Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle Waste Report Submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $location    = trim($_POST['location'] ?? '');
    $waste_type  = trim($_POST['waste_type'] ?? 'Organic');
    $description = trim($_POST['description'] ?? '');

    if (!empty($location)) {
        $stmt = $conn->prepare("INSERT INTO complaints (user_id, location, waste_type, description, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("isss", $user_id, $location, $waste_type, $description);

        if ($stmt->execute()) {
            $message = "Waste collection report submitted successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to submit report. Please try again.";
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Please provide the collection location.";
        $message_type = "error";
    }
}

// Fetch Previous Reports Submitted by this User
$reports = [];
$reportStmt = $conn->prepare("SELECT id, location, waste_type, description, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC");
$reportStmt->bind_param("i", $user_id);
$reportStmt->execute();
$result = $reportStmt->get_result();

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$reportStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Waste Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Dashboard-specific Layout Extensions */
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
            text-decoration: none;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 32px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .dashboard-card h3 {
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

        /* Report Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 0.875rem;
        }

        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--background);
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Status Badge Styling */
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
        <h1>Smart Waste Management</h1>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Main Dashboard Layout -->
    <main class="dashboard-container">

        <!-- Report Submission Form Card -->
        <section class="dashboard-card">
            <h3>Report Waste Collection</h3>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="dashboard.php" method="POST">
                <div>
                    <label for="location">Pickup Location / Address</label>
                    <input type="text" id="location" name="location" required placeholder="e.g. Block C, Sector 12, Main Road">
                </div>

                <div>
                    <label for="waste_type">Waste Category</label>
                    <select id="waste_type" name="waste_type">
                        <option value="Organic">Organic (Food / Green Waste)</option>
                        <option value="Recyclable">Recyclable (Paper, Plastic, Glass)</option>
                        <option value="Hazardous">Hazardous / E-Waste</option>
                        <option value="Mixed">Mixed / Bulk Waste</option>
                    </select>
                </div>

                <div>
                    <label for="description">Additional Notes / Landmarks</label>
                    <textarea id="description" name="description" rows="3" placeholder="e.g. Near the main park entrance..."></textarea>
                </div>

                <button type="submit" name="submit_report">Submit Collection Request</button>
            </form>
        </section>

        <!-- Previous Reports History Card -->
        <section class="dashboard-card">
            <h3>My Collection Requests</h3>

            <?php if (empty($reports)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">You haven't submitted any waste reports yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['location']); ?></td>
                                <td><?php echo htmlspecialchars($report['waste_type']); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'badge-pending';
                                        if (strtolower($report['status']) === 'assigned') {
                                            $statusClass = 'badge-assigned';
                                        } elseif (strtolower($report['status']) === 'collected' || strtolower($report['status']) === 'completed') {
                                            $statusClass = 'badge-collected';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($report['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>