<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

include('db_connect.php');

$admin_id = $_SESSION['id'];

$member_count = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'member'")->fetch_assoc()['count'];
$total_contributions = $conn->query("SELECT SUM(amount) AS total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;
$total_loans = $conn->query("SELECT SUM(amount) AS total FROM loan_applications WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$pending_loans = $conn->query("SELECT COUNT(*) AS count FROM loan_applications WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_loans = $conn->query("SELECT COUNT(*) AS count FROM loan_applications WHERE status = 'approved'")->fetch_assoc()['count'];
$disbursed_loans = $conn->query("SELECT COUNT(*) AS count FROM loan_applications WHERE disbursement_status = 'Disbursed'")->fetch_assoc()['count'];
$pending_members = $conn->query("SELECT COUNT(*) AS count FROM users WHERE approval_status = 'pending'")->fetch_assoc()['count'];

// Fetch members for role management
$role_members_query = $conn->query("SELECT id, username, first_name, last_name, role FROM users WHERE role IN ('member', 'chairperson', 'secretary')");
$role_members = $role_members_query->fetch_all(MYSQLI_ASSOC);

// Fetch admin's personal contributions (last 5)
$personal_transactions_query = $conn->prepare("SELECT amount, type, transaction_desc, created_at, mpesa_receipt, updated_at, checkout_request_id 
                                              FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$personal_transactions_query->bind_param("i", $admin_id);
$personal_transactions_query->execute();
$personal_transactions = $personal_transactions_query->get_result()->fetch_all(MYSQLI_ASSOC);
$personal_transactions_query->close();

// Fetch attendance records (online and physical)
$attendance_query = $conn->prepare("
    SELECT m.title, a.attended_at, 'Online' AS attendance_type, status 
    FROM attendance a 
    JOIN meetings m ON a.meeting_id = m.id 
    WHERE a.user_id = ?
    UNION
    SELECT m.title, ma.recorded_at AS attended_at, 'Physical' AS attendance_type, 
           CASE 
               WHEN ma.has_apology = 1 THEN 'Absent with Apology' 
               WHEN ma.is_present = 1 THEN 'Present' 
               ELSE 'Absent' 
           END AS status 
    FROM meeting_attendance ma 
    JOIN meetings m ON ma.meeting_id = m.id 
    WHERE ma.user_id = ?
    ORDER BY attended_at DESC");
$attendance_query->bind_param("ii", $admin_id, $admin_id);
$attendance_query->execute();
$attendance = $attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);
$attendance_query->close();

// Fetch user's apologies
$apologies_query = $conn->prepare("
    SELECT a.id, m.title, m.meeting_date, a.reason, a.status, a.submitted_at, a.rejection_reason 
    FROM apologies a 
    JOIN meetings m ON a.meeting_id = m.id 
    WHERE a.user_id = ? 
    ORDER BY a.submitted_at DESC");
$apologies_query->bind_param("i", $admin_id);
$apologies_query->execute();
$apologies = $apologies_query->get_result()->fetch_all(MYSQLI_ASSOC);
$apologies_query->close();

$all_transactions_query = $conn->prepare("
    SELECT t.user_id, t.amount, t.type, t.transaction_desc, t.created_at, t.mpesa_receipt, t.status, t.checkout_request_id, u.username, u.first_name, u.last_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC LIMIT 50");
$all_transactions_query->execute();
$all_transactions = $all_transactions_query->get_result()->fetch_all(MYSQLI_ASSOC);
$all_transactions_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard</title>
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
        .theme-toggle { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .theme-toggle:hover { background: #2980b9; }
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center; transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        .stat-card h3 { font-size: 18px; color: #7f8c8d; margin-bottom: 10px; }
        .stat-card p { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 40px; }
        .section h3 { font-size: 20px; margin-bottom: 20px; }
        .section-content { transition: max-height 0.3s ease-out; overflow: hidden; }
        .section.collapsed .section-content { max-height: 0; }
        .section h3::after { content: '▼'; font-size: 14px; transition: transform 0.3s; }
        .section.collapsed h3::after { transform: rotate(180deg); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; color: white; display: inline-block; transition: background 0.3s; }
        .btn-approve { background: #27ae60; }
        .btn-approve:hover { background: #219653; }
        .btn-reject { background: #c0392b; }
        .btn-reject:hover { background: #a93226; }
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        .quick-actions { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .tooltip { position: relative; }
        .tooltip::after {
            content: attr(data-tooltip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            background: #333; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; white-space: nowrap;
            opacity: 0; visibility: hidden; transition: opacity 0.3s;
        }
        .tooltip:hover::after { opacity: 1; visibility: visible; }
        .hidden { display: none; }
        .alert-success { margin-bottom: 20px; padding: 10px; background: #dff0d8; color: #3c763d; border-radius: 5px; }
        .alert-error { margin-bottom: 20px; padding: 10px; background: #f2dede; color: #a94442; border-radius: 5px; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .sidebar { background: #121212; }
        body.dark .container { background: #1a1a1a; }
        body.dark .stat-card, body.dark .section { background: #2c2c2c; }
        body.dark .header h2 { color: #ecf0f1; }
        body.dark .theme-toggle { background: #e74c3c; }
        body.dark .theme-toggle:hover { background: #c0392b; }
        body.dark th { background: #e74c3c; }
        body.dark th:hover { background: #c0392b; }
        body.dark td { border-color: #444; }
        body.dark .btn-primary { background: #e74c3c; }
        body.dark .btn-primary:hover { background: #c0392b; }
        body.dark .alert-success { background: #3c763d; color: #dff0d8; }
        body.dark .alert-error { background: #a94442; color: #f2dede; }
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .container { margin-left: 220px; }
            .stats { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        }
        @media (max-width: 480px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .container { margin-left: 0; padding: 20px; }
            .quick-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Chama Secretary</h1>
        <a href="meetings.php?view=upcoming">Upcoming Meetings</a>
        <a href="meetings.php?view=past">Past Meetings</a>
        <a href="meetings.php">Manage Meetings</a>
        <a href="make_contribution.php">Contribute</a>
        <a href="manage_members.php">Manage Members</a>
        <a href="manage_removal.php">Manage Removals</a>
        <a href="users_report.php">Users</a>
        <a href="financials.php">Financials</a>
        <a href="reports.php">Reports</a>
        <a href="pending_members.php">Pending Members</a>
        <a href="manage_apologies.php">Manage Apologies</a>
        <a href="loans.php">Loans</a>
        <a href="logout.php">Logout</a>
        <button class="theme-toggle" onclick="toggleTheme()">Toggle Theme</button>
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
            <h2>Welcome, Secretary</h2>
        </div>
        <div class="stats">
            <div class="stat-card">
                <h3>Total Members</h3>
                <p><?php echo $member_count; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Contributions</h3>
                <p>KSh <?php echo number_format($total_contributions, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Loans Issued</h3>
                <p>KSh <?php echo number_format($total_loans, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Loans</h3>
                <p><?php echo $pending_loans; ?></p>
            </div>
            <div class="stat-card">
                <h3>Approved Loans</h3>
                <p><?php echo $approved_loans; ?></p>
            </div>
            <div class="stat-card">
                <h3>Disbursed Loans</h3>
                <p><?php echo $disbursed_loans; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Members</h3>
                <p><?php echo $pending_members; ?></p>
            </div>
        </div>
        <div class="section">
            <h3>Quick Actions</h3>
            <div class="quick-actions">
                <a href="meetings.php" class="btn btn-primary tooltip" data-tooltip="Schedule or manage meetings">Schedule Meeting</a>
                <a href="pending_members.php" class="btn btn-primary tooltip" data-tooltip="Approve or reject new members">Approve Members</a>
                <a href="manage_removal.php" class="btn btn-primary tooltip" data-tooltip="Review removal requests">Manage Removals</a>
                <a href="financials.php" class="btn btn-primary tooltip" data-tooltip="View financial reports">View Financials</a>
                <a href="reports.php" class="btn btn-primary tooltip" data-tooltip="Generate detailed reports">Generate Reports</a>
            </div>
        </div>
        <div class="section">
            <h3>My Contributions</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Receipt</th>
                            <th>Description</th>
                            
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($personal_transactions)): ?>
                            <?php foreach ($personal_transactions as $txn): ?>
                                <tr>
                                    <td>KSh <?php echo number_format($txn['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($txn['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['mpesa_receipt'] ?? 'Pending'); ?></td>
                                    <td><?php echo htmlspecialchars($txn['transaction_desc']); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($txn['updated_at'] ?? $txn['created_at']); ?></td>
                                    <td>
                                        <a href="receipt.php?checkout=<?php echo urlencode($txn['checkout_request_id']); ?>" class="btn btn-primary">View Receipt</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No personal contributions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section">
            <h3>Manage Roles</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Current Role</th>
                        <th>New Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($role_members as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['username'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($member['role']); ?></td>
                            <td>
                                <form action="update_role.php" method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                    <select name="new_role" required>
                                        <option value="member" <?php echo $member['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                        <option value="chairperson" <?php echo $member['role'] === 'chairperson' ? 'selected' : ''; ?>>Chairperson</option>
                                        <option value="secretary" <?php echo $member['role'] === 'secretary' ? 'selected' : ''; ?>>Secretary</option>
                                    </select>
                                    <button type="submit" class="btn btn-approve">Update</button>
                                </form>
                            </td>
                            <td>
                                <a href="view_role_history.php?user_id=<?php echo $member['id']; ?>" class="btn btn-primary">View History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="loans-section" class="section hidden">
            <!-- Existing content unchanged -->
        </div>
        <div class="section">
            <h3>My Attendance</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Attendance Type</th>
                            <th>Status</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance)): ?>
                            <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['title']); ?></td>
                                    <td><?php echo htmlspecialchars($record['attendance_type']); ?></td>
                                    <td><?php echo htmlspecialchars($record['status']); ?></td>
                                    <td><?php echo htmlspecialchars($record['attended_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #7f8c8d;">No attendance records</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section">
            <h3>My Apologies</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($apologies)): ?>
                            <?php foreach ($apologies as $apology): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                    <td><?php echo htmlspecialchars($apology['meeting_date']); ?></td>
                                    <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($apology['status'])); ?></td>
                                    <td><?php echo htmlspecialchars($apology['submitted_at']); ?></td>
                                    <td><?php echo htmlspecialchars($apology['rejection_reason'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #7f8c8d;">No apologies submitted</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section">
            <h3>All Transactions</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Receipt</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_transactions)): ?>
                            <?php foreach ($all_transactions as $txn): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($txn['first_name'] . ' ' . $txn['last_name'] . ' (' . $txn['username'] . ')'); ?></td>
                                    <td>KSh <?php echo number_format($txn['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($txn['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['mpesa_receipt'] ?? 'Pending'); ?></td>
                                    <td><?php echo htmlspecialchars($txn['transaction_desc']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($txn['status'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['updated_at'] ?? $txn['created_at']); ?></td>
                                    <td>
                                        <a href="receipt.php?checkout=<?php echo urlencode($txn['checkout_request_id']); ?>" class="btn btn-primary">View Receipt</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center; color: #7f8c8d;">No transactions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
        const loansToggle = document.getElementById('loans-toggle');
        const loansSection = document.getElementById('loans-section');
        if (loansToggle && loansSection) {
            loansToggle.addEventListener('click', () => {
                loansSection.classList.toggle('hidden');
            });
        }
        document.querySelectorAll('.section h3').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.parentElement;
                section.classList.toggle('collapsed');
                const content = section.querySelector('.section-content');
                content.style.maxHeight = section.classList.contains('collapsed') ? '0' : `${content.scrollHeight}px`;
            });
        });
        document.querySelectorAll('.section-content').forEach(content => {
            content.style.maxHeight = `${content.scrollHeight}px`;
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>