<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'chairperson' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

$chairperson_id = $_SESSION['id'];

// Fetch all active users (not pending removal)
$users_query = $conn->prepare("
    SELECT u.id, u.username, u.email, u.role, u.approval_status, u.created_at,
           COALESCE(SUM(t.amount), 0) AS total_contributions
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'completed'
    WHERE u.id NOT IN (SELECT user_id FROM removal_requests WHERE status = 'pending')
    GROUP BY u.id
");
$users_query->execute();
$users = $users_query->get_result()->fetch_all(MYSQLI_ASSOC);
$users_query->close();

// Fetch users with pending removal requests
$pending_removal_query = $conn->prepare("
    SELECT u.id, u.username, u.email, u.role, u.approval_status, u.created_at,
           COALESCE(SUM(t.amount), 0) AS total_contributions, rr.reason, rr.requested_at
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'completed'
    JOIN removal_requests rr ON u.id = rr.user_id
    WHERE rr.status = 'pending'
    GROUP BY u.id
");
$pending_removal_query->execute();
$pending_removals = $pending_removal_query->get_result()->fetch_all(MYSQLI_ASSOC);
$pending_removal_query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tooltip { position: relative; }
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
        }
        .tooltip:hover::after { opacity: 1; visibility: visible; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="flex">
        <div class="w-64 h-screen bg-gray-800 text-white p-5 fixed">
            <h1 class="text-xl font-bold mb-6">Chama Chairperson</h1>
            <a href="meetings.php?view=upcoming" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Upcoming Meetings</a>
            <a href="meetings.php?view=past" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Past Meetings</a>
            <a href="make_contribution.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Contribute</a>
            <a href="receipts.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Receipts</a>
            <a href="reports.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Reports</a>
            <a href="loans.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Loans</a>
            <a href="send_apology.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Send Apology</a>
            <a href="manage_apologies.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Manage Apologies</a>
            <a href="pending_members.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Approve Members</a>
            <a href="manage_members.php" class="block py-2 px-4 mb-2 rounded bg-gray-700">Manage Members</a>
            <a href="logout.php" class="block py-2 px-4 mb-2 rounded hover:bg-gray-700">Logout</a>
            <button class="theme-toggle w-full py-2 px-4 rounded bg-blue-600 hover:bg-blue-700" onclick="toggleTheme()">Toggle Theme</button>
        </div>
        <div class="ml-64 p-6 container mx-auto">
            <h2 class="text-2xl font-bold mb-4">Manage Members</h2>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <h3 class="text-xl font-semibold mb-2">Active Users</h3>
            <div class="bg-white shadow rounded p-4 mb-8">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-3 text-left">Username</th>
                            <th class="p-3 text-left">Email</th>
                            <th class="p-3 text-left">Role</th>
                            <th class="p-3 text-left">Approval Status</th>
                            <th class="p-3 text-left">Total Contributions</th>
                            <th class="p-3 text-left">Account Age (Days)</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $account_age_days = (strtotime('now') - strtotime($user['created_at'])) / (60 * 60 * 24);
                                $can_request_removal = $user['total_contributions'] < 5000 || $account_age_days < 90;
                                ?>
                                <tr>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars(ucfirst($user['approval_status'])); ?></td>
                                    <td class="p-3 border-b">Ksh <?php echo number_format($user['total_contributions'], 2); ?></td>
                                    <td class="p-3 border-b"><?php echo number_format($account_age_days, 0); ?></td>
                                    <td class="p-3 border-b">
                                        <?php if ($can_request_removal && $user['role'] === 'member'): ?>
                                            <form action="request_removal.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <div class="mb-2">
                                                    <label for="reason_<?php echo $user['id']; ?>" class="block text-sm font-medium">Reason for Removal</label>
                                                    <textarea name="reason" id="reason_<?php echo $user['id']; ?>" rows="3" class="w-full border rounded p-2" required placeholder="Enter reason for removal (e.g., low contributions, inactivity)"></textarea>
                                                </div>
                                                <button type="submit" class="bg-red-600 text-white py-1 px-3 rounded hover:bg-red-700 tooltip" data-tooltip="Request removal for review by Super Admin">Request Removal</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-500">Not eligible for removal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="p-3 text-center">No active users found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <h3 class="text-xl font-semibold mb-2">Pending Removal Requests</h3>
            <div class="bg-white shadow rounded p-4">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-3 text-left">Username</th>
                            <th class="p-3 text-left">Email</th>
                            <th class="p-3 text-left">Role</th>
                            <th class="p-3 text-left">Approval Status</th>
                            <th class="p-3 text-left">Total Contributions</th>
                            <th class="p-3 text-left">Account Age (Days)</th>
                            <th class="p-3 text-left">Reason</th>
                            <th class="p-3 text-left">Requested At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_removals)): ?>
                            <?php foreach ($pending_removals as $user): ?>
                                <?php
                                $account_age_days = (strtotime('now') - strtotime($user['created_at'])) / (60 * 60 * 24);
                                ?>
                                <tr>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars(ucfirst($user['approval_status'])); ?></td>
                                    <td class="p-3 border-b">Ksh <?php echo number_format($user['total_contributions'], 2); ?></td>
                                    <td class="p-3 border-b"><?php echo number_format($account_age_days, 0); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['reason']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($user['requested_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="p-3 text-center">No pending removal requests</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="chairperson_dashboard.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Back to Dashboard</a>
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
    </script>
</body>
</html>
<?php
$conn->close();
?>