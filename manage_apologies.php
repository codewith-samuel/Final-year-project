<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'chairperson']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include 'db_connect.php';
$admin_id = $_SESSION['id'];

// Fetch pending apologies
$pending_apologies_query = $conn->query("SELECT a.id, a.user_id, u.username, u.email, m.title, m.meeting_date, a.reason, a.submitted_at, a.approved_by_chairperson, a.approved_by_superadmin
                                         FROM apologies a
                                         JOIN users u ON a.user_id = u.id
                                         JOIN meetings m ON a.meeting_id = m.id
                                         WHERE a.status = 'pending'
                                         ORDER BY m.meeting_date ASC");
$pending_apologies = $pending_apologies_query->fetch_all(MYSQLI_ASSOC);

// Fetch approved and rejected apologies, split by upcoming and past
$processed_apologies_query = $conn->query("SELECT a.id, a.user_id, u.username, u.email, m.title, m.meeting_date, a.reason, a.submitted_at, a.status, a.rejection_reason
                                          FROM apologies a
                                          JOIN users u ON a.user_id = u.id
                                          JOIN meetings m ON a.meeting_id = m.id
                                          WHERE a.status IN ('approved', 'rejected')
                                          ORDER BY m.meeting_date ASC");
$processed_apologies = $processed_apologies_query->fetch_all(MYSQLI_ASSOC);
$upcoming_approved = [];
$past_approved = [];
$rejected_apologies = [];
$current_time = date('Y-m-d H:i:s');
foreach ($processed_apologies as $apology) {
    if ($apology['status'] === 'approved') {
        if ($apology['meeting_date'] >= $current_time) {
            $upcoming_approved[] = $apology;
        } else {
            $past_approved[] = $apology;
        }
    } else {
        $rejected_apologies[] = $apology;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Apologies - Chama <?php echo ucfirst($_SESSION['role']); ?></title>
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
        .section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .section h3 { font-size: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; color: white; display: inline-block; transition: background 0.3s; }
        .btn-approve { background: #27ae60; }
        .btn-approve:hover { background: #219653; }
        .btn-reject { background: #c0392b; }
        .btn-reject:hover { background: #a93226; }
        .form-group { margin-bottom: 15px; }
        .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .alert-success { margin-bottom: 20px; padding: 10px; background: #dff0d8; color: #3c763d; border-radius: 5px; }
        .alert-error { margin-bottom: 20px; padding: 10px; background: #f2dede; color: #a94442; border-radius: 5px; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .sidebar { background: #121212; }
        body.dark .container { background: #1a1a1a; }
        body.dark .section { background: #2c2c2c; }
        body.dark .header h2 { color: #ecf0f1; }
        body.dark th { background: #e74c3c; }
        body.dark th:hover { background: #c0392b; }
        body.dark td { border-color: #444; }
        body.dark .form-group select { background: #333; color: #ecf0f1; border-color: #444; }
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
        <?php if ($_SESSION['role'] === 'superadmin'): ?>
            <a href="/Chama-management-system/admin_dashboard.php">Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=join">Meetings</a>
            <a href="/Chama-management-system/meetings.php">Manage Meetings</a>
            <a href="/Chama-management-system/manage_members.php">Manage Members</a>
            <a href="/Chama-management-system/manage_removal.php">Manage Removals</a>
            <a href="/Chama-management-system/financials.php">Financials</a>
            <a href="/Chama-management-system/reports.php">Reports</a>
            <a href="/Chama-management-system/pending_members.php">Pending Members</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/manage_apologies.php">Manage Apologies</a>
        <?php else: ?>
            <a href="/Chama-management-system/chairperson_dashboard.php">Dashboard</a>
            <a href="/Chama-management-system/meetings.php">Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/manage_apologies.php">Manage Apologies</a>
        <?php endif; ?>
        <a href="/Chama-management-system/logout.php">Logout</a>
    </div>
    <div class="container">
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
        <div class="header">
            <h2>Manage Apologies</h2>
        </div>
        <!-- Pending Apologies -->
        <div class="section">
            <h3>Pending Apologies</h3>
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                        <th>Chairperson</th>
                        <th>Superadmin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pending_apologies)): ?>
                        <?php foreach ($pending_apologies as $apology): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apology['username']); ?></td>
                                <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                <td><?php echo htmlspecialchars($apology['meeting_date']); ?></td>
                                <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                <td><?php echo htmlspecialchars($apology['submitted_at']); ?></td>
                                <td>
                                    <?php 
                                    if ($apology['approved_by_chairperson'] == 1) echo 'Approved';
                                    elseif ($apology['approved_by_chairperson'] == -1) echo 'Rejected';
                                    else echo 'Pending';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($apology['approved_by_superadmin'] == 1) echo 'Approved';
                                    elseif ($apology['approved_by_superadmin'] == -1) echo 'Rejected';
                                    else echo 'Pending';
                                    ?>
                                </td>
                                <td>
                                    <?php if (
                                        ($_SESSION['role'] === 'chairperson' && $apology['approved_by_chairperson'] == 0) ||
                                        ($_SESSION['role'] === 'superadmin' && $apology['approved_by_superadmin'] == 0)
                                    ): ?>
                                        <form action="/Chama-management-system/update_apology_status.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="apology_id" value="<?php echo $apology['id']; ?>">
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($apology['email']); ?>">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($apology['username']); ?>">
                                            <input type="hidden" name="meeting_title" value="<?php echo htmlspecialchars($apology['title']); ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-approve">Approve</button>
                                        </form>
                                        <form action="/Chama-management-system/update_apology_status.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="apology_id" value="<?php echo $apology['id']; ?>">
                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($apology['email']); ?>">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($apology['username']); ?>">
                                            <input type="hidden" name="meeting_title" value="<?php echo htmlspecialchars($apology['title']); ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <div class="form-group">
                                                <select name="rejection_reason" required>
                                                    <option value="">Select Rejection Reason</option>
                                                    <option value="Vague or insufficient reason">Vague or insufficient reason</option>
                                                    <option value="Repeated apologies for similar reasons">Repeated apologies for similar reasons</option>
                                                    <option value="Late submission without emergency">Late submission without emergency</option>
                                                    <option value="Conflicts with chama responsibilities">Conflicts with chama responsibilities</option>
                                                    <option value="Poor attendance history">Poor attendance history</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-reject">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Action Taken</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No pending apologies</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Approved Apologies (Upcoming) -->
        <div class="section">
            <h3>Approved Apologies for Upcoming Meetings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($upcoming_approved)): ?>
                        <?php foreach ($upcoming_approved as $apology): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apology['username']); ?></td>
                                <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                <td><?php echo htmlspecialchars($apology['meeting_date']); ?></td>
                                <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                <td><?php echo htmlspecialchars($apology['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No approved apologies for upcoming meetings</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Approved Apologies (Past) -->
        <div class="section">
            <h3>Approved Apologies for Past Meetings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($past_approved)): ?>
                        <?php foreach ($past_approved as $apology): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apology['username']); ?></td>
                                <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                <td><?php echo htmlspecialchars($apology['meeting_date']); ?></td>
                                <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                <td><?php echo htmlspecialchars($apology['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No approved apologies for past meetings</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Rejected Apologies -->
        <div class="section">
            <h3>Rejected Apologies</h3>
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Meeting</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                        <th>Rejection Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rejected_apologies)): ?>
                        <?php foreach ($rejected_apologies as $apology): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apology['username']); ?></td>
                                <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                <td><?php echo htmlspecialchars($apology['meeting_date']); ?></td>
                                <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                <td><?php echo htmlspecialchars($apology['submitted_at']); ?></td>
                                <td><?php echo htmlspecialchars($apology['rejection_reason'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No rejected apologies</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>