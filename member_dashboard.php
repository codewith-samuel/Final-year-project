<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

include 'db_connect.php';

$id = $_SESSION['id'];

// Fetch user details
$user_query = $conn->prepare("SELECT username, approval_status, created_at FROM users WHERE id = ?");
$user_query->bind_param("i", $id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_name = $user['username'] ?? 'Unknown';
$approval_status = $user['approval_status'] ?? 'pending';
$account_created = $user['created_at'];
$user_query->close();

// Fetch total contributions and calculate eligibility
$total_contributions_query = $conn->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND status = 'completed'");
$total_contributions_query->bind_param("i", $id);
$total_contributions_query->execute();
$total_contributions = $total_contributions_query->get_result()->fetch_assoc()['total'] ?? 0;
$total_contributions_query->close();
$account_age_days = (strtotime('now') - strtotime($account_created)) / (60 * 60 * 24);
$is_eligible = $total_contributions >= 5000 && $account_age_days >= 90;
$max_loan_amount = $is_eligible ? min($total_contributions * 2, 50000) : 0;

// Fetch report count
$report_count_query = $conn->prepare("SELECT COUNT(*) AS count FROM transactions WHERE user_id = ? AND status = 'completed'");
$report_count_query->bind_param("i", $id);
$report_count_query->execute();
$report_count = $report_count_query->get_result()->fetch_assoc()['count'] ?? 0;
$report_count_query->close();

// Fetch recent transactions (last 5)
$recent_transactions_query = $conn->prepare("SELECT amount, type, transaction_desc, created_at, mpesa_receipt, updated_at, checkout_request_id, status 
                                            FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent_transactions_query->bind_param("i", $id);
$recent_transactions_query->execute();
$recent_transactions = $recent_transactions_query->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_transactions_query->close();

// Fetch user's loan applications
$user_loans_query = $conn->prepare("SELECT id, amount, category, purpose, repayment_period, applied_at, status, rejection_reason, disbursement_status 
                                   FROM loan_applications 
                                   WHERE user_id = ? 
                                   ORDER BY applied_at DESC");
$user_loans_query->bind_param("i", $id);
$user_loans_query->execute();
$user_loans = $user_loans_query->get_result()->fetch_all(MYSQLI_ASSOC);
$user_loans_query->close();

// Fetch upcoming meetings
$meetings_query = $conn->prepare("SELECT id, title, meeting_date, online_link FROM meetings WHERE meeting_date >= NOW() ORDER BY meeting_date ASC");
$meetings_query->execute();
$meetings = $meetings_query->get_result()->fetch_all(MYSQLI_ASSOC);
$meetings_query->close();

// Fetch attendance records
$attendance_query = $conn->prepare("
    SELECT m.title, a.attended_at, a.status, 'Online' AS attendance_type 
    FROM attendance a 
    JOIN meetings m ON a.meeting_id = m.id 
    WHERE a.user_id = ?
    UNION
    SELECT m.title, ma.recorded_at AS attended_at, 
           CASE 
               WHEN ma.has_apology = 1 THEN 'Absent with Apology' 
               WHEN ma.is_present = 1 THEN 'Present' 
               ELSE 'Absent' 
           END AS status, 'Physical' AS attendance_type 
    FROM meeting_attendance ma 
    JOIN meetings m ON ma.meeting_id = m.id 
    WHERE ma.user_id = ?
    ORDER BY attended_at DESC");
$attendance_query->bind_param("ii", $id, $id);
$attendance_query->execute();
$attendance = $attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);
$attendance_query->close();

// Fetch member's apologies
$apologies_query = $conn->prepare("
    SELECT a.id, m.title, m.meeting_date, a.reason, a.status, a.submitted_at, a.rejection_reason 
    FROM apologies a 
    JOIN meetings m ON a.meeting_id = m.id 
    WHERE a.user_id = ? 
    ORDER BY a.submitted_at DESC");
$apologies_query->bind_param("i", $id);
$apologies_query->execute();
$apologies = $apologies_query->get_result()->fetch_all(MYSQLI_ASSOC);
$apologies_query->close();

// Fetch fines
$fines_query = $conn->prepare("SELECT id, amount, reason, issued_at, status 
                              FROM fines 
                              WHERE user_id = ? 
                              ORDER BY issued_at DESC");
$fines_query->bind_param("i", $id);
$fines_query->execute();
$fines = $fines_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Chama</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .section h3 {
            font-size: 20px; margin-bottom: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .section h3::after { content: '▼'; font-size: 14px; transition: transform 0.3s; }
        .section.collapsed h3::after { transform: rotate(180deg); }
        .section-content { transition: max-height 0.3s ease-out; overflow: hidden; }
        .section.collapsed .section-content { max-height: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        th:hover { background: #2980b9; }
        tr:hover { background: #f9f9f9; }
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
        .tooltip { position: relative; }
        .tooltip::after {
            content: attr(data-tooltip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            background: #333; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; white-space: nowrap;
            opacity: 0; visibility: hidden; transition: opacity 0.3s;
        }
        .tooltip:hover::after { opacity: 1; visibility: visible; }
        canvas { max-width: 100%; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .sidebar { background: #121212; }
        body.dark .container { background: #1a1a1a; }
        body.dark .stat-card, body.dark .section { background: #2c2c2c; }
        body.dark .header h2 { color: #ecf0f1; }
        body.dark .theme-toggle { background: #e74c3c; }
        body.dark .theme-toggle:hover { background: #c0392b; }
        body.dark th { background: #e74c3c; }
        body.dark th:hover { background: #c0392b; }
        body.dark tr:hover { background: #333; }
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
            .stats { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        }
        @media (max-width: 480px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .container { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Chama Member</h1>
        <a href="meetings.php?view=upcoming">Upcoming Meetings</a>
        <a href="meetings.php?view=past">Past Meetings</a>
        <a href="make_contribution.php">Contribute</a>
        <a href="receipts.php">Receipts</a>
        <a href="reports.php">Reports (<?php echo $report_count; ?>)</a>
        <a href="loans.php">Loans</a>
        <a href="send_apology.php">Send Apology</a>
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
            <h2>Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
        </div>
        <div class="stats">
            <div class="stat-card">
                <h3>Total Contributions</h3>
                <p>Ksh <?php echo number_format($total_contributions, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Reports Available</h3>
                <p><?php echo $report_count; ?></p>
            </div>
            <div class="stat-card">
                
                <p class="<?php echo $is_eligible ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $is_eligible ? 'Eligible (Max: Ksh ' . number_format($max_loan_amount, 2) . ')' : ''; ?>
                    <a href="loans.php" class="btn btn-primary mt-2 inline-block">Apply for Loan</a>
                </p>
            </div>
        </div>
        <div class="section">
            <h3>Contribution Overview</h3>
            <div class="section-content">
                <canvas id="contributionChart" height="200"></canvas>
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
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_transactions)): ?>
                            <?php foreach ($recent_transactions as $txn): ?>
                                <tr>
                                    <td>Ksh <?php echo number_format($txn['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($txn['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['mpesa_receipt'] ?? 'Pending'); ?></td>
                                    <td><?php echo htmlspecialchars($txn['transaction_desc']); ?></td>
                                    <td class="status-<?php echo htmlspecialchars(strtolower($txn['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($txn['status'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($txn['updated_at'] ?? $txn['created_at']); ?></td>
                                    <td>
                                        <a href="receipt.php?checkout=<?php echo urlencode($txn['checkout_request_id']); ?>" class="btn btn-primary">View Receipt</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #7f8c8d;">No contributions found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="section">
            <h3>My Loans</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Purpose</th>
                            <th>Repayment Period</th>
                            <th>Applied At</th>
                            <th>Status</th>
                            <th>Rejection Reason</th>
                            <th>Disbursement Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($user_loans)): ?>
                            <?php foreach ($user_loans as $loan): ?>
                                <tr>
                                    <td>Ksh <?php echo number_format($loan['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                    <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($loan['status'])); ?></td>
                                    <td><?php echo htmlspecialchars($loan['rejection_reason'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($loan['disbursement_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center; color: #7f8c8d;">No loan applications found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">My Fines</h2>
            <?php if ($fines->num_rows === 0): ?>
                <p class="text-gray-600">No fines issued.</p>
            <?php else: ?>
                <table class="table-auto w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Issued At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $fines->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td>Ksh <?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['issued_at']); ?></td>
                                <td class="status-<?php echo htmlspecialchars(strtolower($row['status'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <div class="section">
            <h3>Upcoming Meetings</h3>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Join</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($meetings)): ?>
                            <?php foreach ($meetings as $meeting): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($meeting['title']); ?></td>
                                    <td><?php echo htmlspecialchars($meeting['meeting_date']); ?></td>
                                    <td>
                                        <?php if ($meeting['online_link']): ?>
                                            <a href="join_meeting.php?id=<?php echo $meeting['id']; ?>" target="_blank" class="btn btn-primary tooltip" data-tooltip="Join meeting online">Join Online</a>
                                        <?php else: ?>
                                            In-person
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #7f8c8d;">No upcoming meetings</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
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
        //const ctx, (declares constant variable ctx), rendering a 2d chart diagram = document.getElementById('contributionChart').getContext('2d'): 
        // Gets the 2D rendering context of the canvas element with the ID contributionChart to draw the chart.


        const ctx = document.getElementById('contributionChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Contributions (Ksh)',
                    data: [500, 1000, 750, 2000, <?php echo $total_contributions; ?>],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>