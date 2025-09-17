<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include 'db_connect.php';
$user_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meeting_id = $_POST['meeting_id'];
    $reason = trim($_POST['reason']);

    // Validate inputs
    if (empty($meeting_id) || empty($reason)) {
        $_SESSION['error'] = "Please select a meeting and provide a reason.";
    } else {
        // Check if meeting is upcoming and exists
        $stmt = $conn->prepare("SELECT id FROM meetings WHERE id = ? AND meeting_date >= CURDATE()");
        $stmt->bind_param("i", $meeting_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $_SESSION['error'] = "Invalid or past meeting selected.";
        } else {
            // Check if apology already exists
            $stmt = $conn->prepare("SELECT id FROM apologies WHERE user_id = ? AND meeting_id = ?");
            $stmt->bind_param("ii", $user_id, $meeting_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $_SESSION['error'] = "You have already submitted an apology for this meeting.";
            } else {
                // Insert apology
                $stmt = $conn->prepare("INSERT INTO apologies (user_id, meeting_id, reason, status, approved_by_chairperson, approved_by_superadmin, rejection_reason) VALUES (?, ?, ?, 'pending', 0, 0, NULL)");
                $stmt->bind_param("iis", $user_id, $meeting_id, $reason);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Apology submitted successfully.";
                    $redirect = $_SESSION['role'] === 'member' ? 'member_dashboard.php' : 
                               ($_SESSION['role'] === 'chairperson' ? 'chairperson_dashboard.php' : 
                               ($_SESSION['role'] === 'secretary' ? 'secretary_dashboard.php' : 'superadmin_dashboard.php'));
                    header("Location: /Chama-management-system/$redirect");
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to submit apology.";
                }
            }
        }
        $stmt->close();
    }
}

// Fetch upcoming meetings for dropdown
$meetings_query = $conn->prepare("SELECT id, title, meeting_date FROM meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC");
$meetings_query->execute();
$meetings = $meetings_query->get_result()->fetch_all(MYSQLI_ASSOC);
$meetings_query->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Apology - Chama <?php echo ucfirst($_SESSION['role']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7fa; color: #333; transition: all 0.3s; }
        .sidebar {
            width: 250px; height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0;
            padding: 20px; color: #ecf0f1; box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar h1 { font-size: 24px; margin-bottom: 30px; }
        .sidebar a, .sidebar button {
            display: block; color: #ecf0f1; text-decoration: none; padding: 10px; margin: 5px 0;
            border-radius: 5px; background: none; border: none; cursor: pointer; font-size: 16px;
        }
        .sidebar a:hover, .sidebar button:hover { background: #34495e; }
        .container {
            margin-left: 270px; padding: 40px; min-height: 100vh;
        }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-size: 28px; color: #2c3e50; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px;
        }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; color: white; display: inline-block; transition: background 0.3s; }
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        .alert-success { margin-bottom: 20px; padding: 10px; background: #dff0d8; color: #3c763d; border-radius: 5px; }
        .alert-error { margin-bottom: 20px; padding: 10px; background: #f2dede; color: #a94442; border-radius: 5px; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .sidebar { background: #121212; }
        body.dark .container { background: #1a1a1a; }
        body.dark .header h2 { color: #ecf0f1; }
        body.dark .btn-primary { background: #e74c3c; }
        body.dark .btn-primary:hover { background: #c0392b; }
        body.dark .form-group input, body.dark .form-group textarea, body.dark .form-group select {
            background: #333; color: #ecf0f1; border-color: #444;
        }
        body.dark .alert-success { background: #3c763d; color: #dff0d8; }
        body.dark .alert-error { background: #a94442; color: #f2dede; }
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .container { margin-left: 220px; }
        }
        @media (max-width: 480px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .container { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Chama <?php echo ucfirst($_SESSION['role']); ?></h1>
        <?php if ($_SESSION['role'] === 'member'): ?>
            <a href="/Chama-management-system/member_dashboard.php">Dashboard</a>
        <?php elseif ($_SESSION['role'] === 'chairperson'): ?>
            <a href="/Chama-management-system/chairperson_dashboard.php">Dashboard</a>
        <?php elseif ($_SESSION['role'] === 'secretary'): ?>
            <a href="/Chama-management-system/secretary_dashboard.php">Dashboard</a>
        <?php else: ?>
            <a href="/Chama-management-system/superadmin_dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a href="/Chama-management-system/meetings.php">Meetings</a>
        <a href="/Chama-management-system/make_contribution.php">Contribute</a>
        <a href="/Chama-management-system/view_receipts.php">Receipts</a>
        <a href="/Chama-management-system/view_reports.php">Reports</a>
        <a href="/Chama-management-system/send_apology.php">Send Apology</a>
        <a href="/Chama-management-system/member_dashboard.php#loans-section">Loans</a>
        <a href="/Chama-management-system/logout.php">Logout</a>
    </div>
    <div class="container">
        <div class="header">
            <h2>Send Apology for Absence</h2>
        </div>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <form action="/Chama-management-system/send_apology.php" method="POST" class="space-y-4">
            <div class="form-group">
                <label for="meeting_id">Select Meeting</label>
                <select name="meeting_id" id="meeting_id" required>
                    <option value="">-- Select a Meeting --</option>
                    <?php foreach ($meetings as $meeting): ?>
                        <option value="<?php echo $meeting['id']; ?>">
                            <?php echo htmlspecialchars($meeting['title'] . ' (' . $meeting['meeting_date'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Reason for Absence</label>
                <textarea name="reason" id="reason" required placeholder="e.g., I have a conflicting appointment." rows="5"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Apology</button>
            <a href="/Chama-management-system/<?php echo $_SESSION['role'] === 'member' ? 'member_dashboard.php' : ($_SESSION['role'] === 'chairperson' ? 'chairperson_dashboard.php' : ($_SESSION['role'] === 'secretary' ? 'secretary_dashboard.php' : 'superadmin_dashboard.php')); ?>" class="btn btn-primary ml-4">Cancel</a>
        </form>
    </div>
</body>
</html>