
<?php
session_start();
if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header("Location: /Chama-management-system/signin.html");
    exit;
}
include 'db_connect.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

// Verify users table schema
$schema_check = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_name'");
$has_last_name = $schema_check->num_rows > 0;

// Personal Reports for All Users
// Attendance (Online and Physical)
$personal_attendance = $conn->prepare("
    SELECT m.id, m.title, m.meeting_date, m.online_link, COALESCE(a.status, ma.status) AS status, COALESCE(a.attended_at, ma.recorded_at) AS attended_at
    FROM meetings m
    LEFT JOIN attendance a ON m.id = a.meeting_id AND a.user_id = ?
    LEFT JOIN meeting_attendance ma ON m.id = ma.meeting_id AND ma.user_id = ?
    WHERE m.meeting_date <= NOW()
    ORDER BY m.meeting_date DESC
");
$personal_attendance->bind_param("ii", $user_id, $user_id);
$personal_attendance->execute();
$personal_attendance_result = $personal_attendance->get_result()->fetch_all(MYSQLI_ASSOC);
$personal_attendance->close();

// Apologies
$personal_apologies = $conn->prepare("
    SELECT m.title, m.meeting_date, ap.reason, ap.submitted_at, ap.status
    FROM apologies ap
    JOIN meetings m ON ap.meeting_id = m.id
    WHERE ap.user_id = ?
    ORDER BY m.meeting_date DESC
");
$personal_apologies->bind_param("i", $user_id);
$personal_apologies->execute();
$personal_apologies_result = $personal_apologies->get_result()->fetch_all(MYSQLI_ASSOC);
$personal_apologies->close();

// Loans
$personal_loans = $conn->prepare("
    SELECT la.id, la.amount, la.category, la.purpose, la.repayment_period, la.status, la.applied_at, la.disbursement_status
    FROM loan_applications la
    WHERE la.user_id = ?
    ORDER BY la.applied_at DESC
");
$personal_loans->bind_param("i", $user_id);
$personal_loans->execute();
$personal_loans_result = $personal_loans->get_result()->fetch_all(MYSQLI_ASSOC);
$personal_loans->close();

// Transactions
$personal_transactions = $conn->prepare("
    SELECT t.amount, t.type, t.status, t.created_at, t.transaction_desc
    FROM transactions t
    WHERE t.user_id = ? AND t.type IN ('monthly', 'investment')
    ORDER BY t.created_at DESC
");
$personal_transactions->bind_param("i", $user_id);
$personal_transactions->execute();
$personal_transactions_result = $personal_transactions->get_result()->fetch_all(MYSQLI_ASSOC);
$personal_transactions->close();

// Chairperson and Superadmin Reports
if (in_array($role, ['chairperson', 'superadmin'])) {
    // Approved Members Report (Attendance and Contributions)
    $approved_members_report = $conn->prepare("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name,
               COUNT(DISTINCT a.id) AS online_attended,
               SUM(CASE WHEN ma.status = 'present' THEN 1 ELSE 0 END) AS physical_attended,
               COALESCE((
                   SELECT SUM(t.amount)
                   FROM transactions t
                   WHERE t.user_id = u.id
                   AND t.type IN ('monthly', 'investment')
                   AND t.status = 'completed'
               ), 0) AS total_contributions
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id
        LEFT JOIN meeting_attendance ma ON u.id = ma.user_id
        WHERE u.role = 'member' AND u.approval_status = 'approved'
        GROUP BY u.id
        ORDER BY name
    ");
    $approved_members_report->execute();
    $approved_members_report_result = $approved_members_report->get_result()->fetch_all(MYSQLI_ASSOC);
    $approved_members_report->close();

    // Pending Members Report
    $pending_members_report = $conn->prepare("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name,
               COUNT(DISTINCT a.id) AS online_attended,
               SUM(CASE WHEN ma.status = 'present' THEN 1 ELSE 0 END) AS physical_attended,
               COALESCE((
                   SELECT SUM(t.amount)
                   FROM transactions t
                   WHERE t.user_id = u.id
                   AND t.type IN ('monthly', 'investment')
                   AND t.status = 'completed'
               ), 0) AS total_contributions
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id
        LEFT JOIN meeting_attendance ma ON u.id = ma.user_id
        WHERE u.role = 'member' AND u.approval_status = 'pending'
        GROUP BY u.id
        ORDER BY name
    ");
    $pending_members_report->execute();
    $pending_members_report_result = $pending_members_report->get_result()->fetch_all(MYSQLI_ASSOC);
    $pending_members_report->close();

    // Loans Report
    $loans_report = $conn->query("
        SELECT la.id, CONCAT(u.first_name, ' ', u.last_name) AS name, la.amount, la.status, la.applied_at, la.disbursement_status
        FROM loan_applications la
        JOIN users u ON la.user_id = u.id
        ORDER BY la.applied_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Superadmin Reports
if ($role === 'superadmin') {
    // Approved Users
    $approved_users = $conn->query("
        SELECT id, CONCAT(first_name, ' ', last_name) AS name, role, approval_status, created_at
        FROM users
        WHERE approval_status = 'approved'
        ORDER BY name
    ")->fetch_all(MYSQLI_ASSOC);

    // Pending Users
    $pending_users = $conn->query("
        SELECT id, CONCAT(first_name, ' ', last_name) AS name, role, approval_status, created_at
        FROM users
        WHERE approval_status = 'pending'
        ORDER BY name
    ")->fetch_all(MYSQLI_ASSOC);

    // All Transactions
    $all_transactions = $conn->query("
        SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) AS name
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.type IN ('monthly', 'investment')
        ORDER BY t.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // All Attendance
    $all_attendance = $conn->query("
        SELECT m.id, m.title, m.meeting_date, COUNT(a.id) AS online_attended, COUNT(ma.id) AS physical_attended
        FROM meetings m
        LEFT JOIN attendance a ON m.id = a.meeting_id
        LEFT JOIN meeting_attendance ma ON m.id = ma.meeting_id AND ma.status = 'present'
        GROUP BY m.id
        ORDER BY m.meeting_date DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // All Apologies
    $all_apologies = $conn->query("
        SELECT ap.*, CONCAT(u.first_name, ' ', u.last_name) AS name, m.title
        FROM apologies ap
        JOIN users u ON ap.user_id = u.id
        JOIN meetings m ON ap.meeting_id = m.id
        ORDER BY ap.submitted_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // All Fines
    $all_fines = $conn->query("
        SELECT f.*, CONCAT(u.first_name, ' ', u.last_name) AS name
        FROM fines f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.issued_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // All Removal Requests
    $all_removals = $conn->query("
        SELECT rr.*, CONCAT(u.first_name, ' ', u.last_name) AS name, CONCAT(ru.first_name, ' ', ru.last_name) AS requested_by_name
        FROM removal_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN users ru ON rr.requested_by = ru.id
        ORDER BY rr.requested_at DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // All Role Changes
    $all_role_changes = $conn->query("
        SELECT rc.*, CONCAT(u.first_name, ' ', u.last_name) AS name, CONCAT(cu.first_name, ' ', cu.last_name) AS changed_by_name
        FROM role_changes rc
        JOIN users u ON rc.user_id = u.id
        JOIN users cu ON rc.changed_by = cu.id
        ORDER BY rc.changed_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .table-auto { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f4f4f4; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .bg-white { background: #2c2c2c; }
        body.dark .text-gray-800 { color: #ecf0f1; }
        body.dark .bg-gray-100 { background: #333; }
        #calendar { max-width: 900px; margin: 0 auto 20px; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Reports</h1>
            <div>
                <a href="/Chama-management-system/<?php echo $role === 'superadmin' ? 'admin' : $role; ?>_dashboard.php" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 rounded">Dashboard</a>
                <a href="/Chama-management-system/logout.php" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6">
        <?php if (!$has_last_name): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Error:</strong> The 'last_name' column is missing in the 'users' table. Please update your database schema.
            </div>
        <?php endif; ?>
        <!-- Personal Reports -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Personal Reports</h2>

            <!-- Attendance Calendar -->
            <h3 class="text-xl font-semibold mb-4">Attendance Calendar</h3>
            <div id="calendar"></div>

            <!-- Personal Attendance -->
            <h3 class="text-xl font-semibold mb-4">Your Attendance</h3>
            <?php if (empty($personal_attendance_result)): ?>
                <p class="text-gray-600">No attendance records found.</p>
            <?php else: ?>
                <table class="table-auto mb-6">
                    <thead>
                        <tr>
                            <th>Meeting Title</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Attended At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal_attendance_result as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['title']); ?></td>
                                <td><?php echo $record['meeting_date']; ?></td>
                                <td><?php echo $record['online_link'] ? 'Online' : 'Physical'; ?></td>
                                <td><?php echo $record['status'] ?? 'Not Attended'; ?></td>
                                <td><?php echo $record['attended_at'] ?? '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Personal Apologies -->
            <h3 class="text-xl font-semibold mb-4">Your Apologies</h3>
            <?php if (empty($personal_apologies_result)): ?>
                <p class="text-gray-600">No apologies found.</p>
            <?php else: ?>
                <table class="table-auto mb-6">
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal_apologies_result as $apology): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apology['title']); ?></td>
                                <td><?php echo $apology['meeting_date']; ?></td>
                                <td><?php echo htmlspecialchars($apology['reason']); ?></td>
                                <td><?php echo $apology['submitted_at']; ?></td>
                                <td><?php echo $apology['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Personal Loans -->
            <h3 class="text-xl font-semibold mb-4">Your Loans</h3>
            <?php if (empty($personal_loans_result)): ?>
                <p class="text-gray-600">No loan applications found.</p>
            <?php else: ?>
                <table class="table-auto mb-6">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Purpose</th>
                            <th>Repayment Period</th>
                            <th>Status</th>
                            <th>Applied At</th>
                            <th>Disbursement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal_loans_result as $loan): ?>
                            <tr>
                                <td><?php echo number_format($loan['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                <td><?php echo $loan['repayment_period']; ?> months</td>
                                <td><?php echo $loan['status']; ?></td>
                                <td><?php echo $loan['applied_at']; ?></td>
                                <td><?php echo $loan['disbursement_status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Personal Transactions -->
            <h3 class="text-xl font-semibold mb-4">Your Contributions</h3>
            <?php if (empty($personal_transactions_result)): ?>
                <p class="text-gray-600">No contributions found.</p>
            <?php else: ?>
                <table class="table-auto mb-6">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal_transactions_result as $transaction): ?>
                            <tr>
                                <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                <td><?php echo $transaction['type']; ?></td>
                                <td><?php echo $transaction['status']; ?></td>
                                <td><?php echo $transaction['created_at']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['transaction_desc']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Chairperson and Superadmin Reports -->
        <?php if (in_array($role, ['chairperson', 'superadmin'])): ?>
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Member Reports</h2>

                <!-- Approved Members -->
                <h3 class="text-xl font-semibold mb-4">Approved Members</h3>
                <?php if (empty($approved_members_report_result)): ?>
                    <p class="text-gray-600">No approved members found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Online Attended</th>
                                <th>Physical Attended</th>
                                <th>Total Contributions (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_members_report_result as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['name']); ?></td>
                                    <td><?php echo $report['online_attended']; ?></td>
                                    <td><?php echo $report['physical_attended']; ?></td>
                                    <td><?php echo number_format($report['total_contributions'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Pending Members -->
                <h3 class="text-xl font-semibold mb-4">Pending Members</h3>
                <?php if (empty($pending_members_report_result)): ?>
                    <p class="text-gray-600">No pending members found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Online Attended</th>
                                <th>Physical Attended</th>
                                <th>Total Contributions (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_members_report_result as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['name']); ?></td>
                                    <td><?php echo $report['online_attended']; ?></td>
                                    <td><?php echo $report['physical_attended']; ?></td>
                                    <td><?php echo number_format($report['total_contributions'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Loans Overview -->
                <h3 class="text-xl font-semibold mb-4">Loans Overview</h3>
                <?php if (empty($loans_report)): ?>
                    <p class="text-gray-600">No loans found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Applied At</th>
                                <th>Disbursement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans_report as $loan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loan['name']); ?></td>
                                    <td><?php echo number_format($loan['amount'], 2); ?></td>
                                    <td><?php echo $loan['status']; ?></td>
                                    <td><?php echo $loan['applied_at']; ?></td>
                                    <td><?php echo $loan['disbursement_status']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Superadmin System Reports -->
        <?php if ($role === 'superadmin'): ?>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">System Reports</h2>

                <!-- Approved Users -->
                <h3 class="text-xl font-semibold mb-4">Approved Users</h3>
                <?php if (empty($approved_users)): ?>
                    <p class="text-gray-600">No approved users found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td><?php echo $user['approval_status']; ?></td>
                                    <td><?php echo $user['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Pending Users -->
                <h3 class="text-xl font-semibold mb-4">Pending Users</h3>
                <?php if (empty($pending_users)): ?>
                    <p class="text-gray-600">No pending users found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td><?php echo $user['approval_status']; ?></td>
                                    <td><?php echo $user['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Transactions -->
                <h3 class="text-xl font-semibold mb-4">All Contributions</h3>
                <?php if (empty($all_transactions)): ?>
                    <p class="text-gray-600">No contributions found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                                    <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo $transaction['type']; ?></td>
                                    <td><?php echo $transaction['status']; ?></td>
                                    <td><?php echo $transaction['created_at']; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['transaction_desc']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Attendance -->
                <h3 class="text-xl font-semibold mb-4">All Attendance</h3>
                <?php if (empty($all_attendance)): ?>
                    <p class="text-gray-600">No attendance records found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Meeting</th>
                                <th>Date</th>
                                <th>Online Attended</th>
                                <th>Physical Attended</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_attendance as $att): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($att['title']); ?></td>
                                    <td><?php echo $att['meeting_date']; ?></td>
                                    <td><?php echo $att['online_attended']; ?></td>
                                    <td><?php echo $att['physical_attended']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Apologies -->
                <h3 class="text-xl font-semibold mb-4">All Apologies</h3>
                <?php if (empty($all_apologies)): ?>
                    <p class="text-gray-600">No apologies found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Meeting</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_apologies as $ap): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ap['name']); ?></td>
                                    <td><?php echo htmlspecialchars($ap['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ap['reason']); ?></td>
                                    <td><?php echo $ap['status']; ?></td>
                                    <td><?php echo $ap['submitted_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Fines -->
                <h3 class="text-xl font-semibold mb-4">All Fines</h3>
                <?php if (empty($all_fines)): ?>
                    <p class="text-gray-600">No fines found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Issued At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_fines as $fine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fine['name']); ?></td>
                                    <td><?php echo number_format($fine['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($fine['reason']); ?></td>
                                    <td><?php echo $fine['status']; ?></td>
                                    <td><?php echo $fine['issued_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Removal Requests -->
                <h3 class="text-xl font-semibold mb-4">All Removal Requests</h3>
                <?php if (empty($all_removals)): ?>
                    <p class="text-gray-600">No removal requests found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Requested By</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Requested At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_removals as $rem): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rem['name']); ?></td>
                                    <td><?php echo htmlspecialchars($rem['requested_by_name']); ?></td>
                                    <td><?php echo $rem['status']; ?></td>
                                    <td><?php echo htmlspecialchars($rem['reason']); ?></td>
                                    <td><?php echo $rem['requested_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- All Role Changes -->
                <h3 class="text-xl font-semibold mb-4">All Role Changes</h3>
                <?php if (empty($all_role_changes)): ?>
                    <p class="text-gray-600">No role changes found.</p>
                <?php else: ?>
                    <table class="table-auto mb-6">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>New Role</th>
                                <th>Changed By</th>
                                <th>Changed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_role_changes as $change): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($change['name']); ?></td>
                                    <td><?php echo $change['new_role']; ?></td>
                                    <td><?php echo htmlspecialchars($change['changed_by_name']); ?></td>
                                    <td><?php echo $change['changed_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Meetings Attendance Calendar -->
                <h3 class="text-xl font-semibold mb-4">Meetings Attendance Calendar</h3>
                <div id="meetings-calendar" class="max-w-900 mx-auto mb-6"></div>

                <!-- Modal for Attendance Details -->
                <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="attendanceModalLabel">Attendance Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Member</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="attendanceTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var calendarEl = document.getElementById('meetings-calendar');
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        events: [
                            <?php
                            $meetings_query = $conn->query("SELECT id, title, meeting_date, online_link FROM meetings ORDER BY meeting_date");
                            while ($meeting = $meetings_query->fetch_assoc()) {
                                echo "{";
                                echo "id: '" . $meeting['id'] . "',";
                                echo "title: '" . htmlspecialchars($meeting['title']) . "',";
                                echo "start: '" . $meeting['meeting_date'] . "',";
                                echo "color: '" . ($meeting['online_link'] ? 'green' : 'blue') . "'";
                                echo "},";
                            }
                            ?>
                        ],
                        eventClick: function(info) {
                            var meetingId = info.event.id;
                            fetch('get_attendance.php?meeting_id=' + meetingId)
                                .then(response => response.json())
                                .then(data => {
                                    var modalTitle = document.getElementById('attendanceModalLabel');
                                    modalTitle.textContent = 'Attendance for ' + info.event.title;
                                    var tbody = document.getElementById('attendanceTableBody');
                                    tbody.innerHTML = '';
                                    data.forEach(user => {
                                        var row = `<tr>
                                            <td>${user.name}</td>
                                            <td>${user.status || 'Absent'}</td>
                                        </tr>`;
                                        tbody.innerHTML += row;
                                    });
                                    var modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
                                    modal.show();
                                })
                                .catch(error => {
                                    console.error('Error fetching attendance:', error);
                                    alert('Failed to load attendance details.');
                                });
                        }
                    });
                    calendar.render();
                });
                </script>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php foreach ($personal_attendance_result as $event): ?>
                    {
                        id: '<?php echo $event['id']; ?>',
                        title: '<?php echo htmlspecialchars($event['title']); ?> (<?php echo $event['status'] ?? 'Not Attended'; ?>)',
                        start: '<?php echo $event['meeting_date']; ?>',
                        color: '<?php echo ($event['status'] == 'present' ? 'green' : ($event['status'] == 'absent_with_apology' ? 'yellow' : 'red')); ?>'
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function (info) {
                    alert('Meeting: ' + info.event.title + '\nDate: ' + info.event.start.toISOString().split('T')[0]);
                }
            });
            calendar.render();
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>
