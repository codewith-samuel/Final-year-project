<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include "db_connect.php";

// Handle success/error messages
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $status_message = '<div class="bg-green-100 text-green-800 p-4 rounded mb-4">' . htmlspecialchars($_GET['message']) . '</div>';
} elseif (isset($_GET['status']) && $_GET['status'] === 'error') {
    $status_message = '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">' . htmlspecialchars($_GET['message']) . '</div>';
}

// Fetch all users
$users_query = $conn->query("SELECT id, first_name, last_name, phone_number FROM users WHERE approval_status = 'approved' ORDER BY first_name, last_name");
$users = $users_query->fetch_all(MYSQLI_ASSOC);

// Contribution monitoring: Check for missed monthly payments
$expected_payment_date = date('Y-m-01'); // Assume monthly payments due on 1st
$missed_payments = [];
foreach ($users as $user) {
    $user_id = $user['id'];
    $payment_query = $conn->prepare("SELECT COUNT(*) as payment_count 
                                    FROM transactions 
                                    WHERE user_id = ? 
                                    AND type = 'monthly' 
                                    AND YEAR(created_at) = ? 
                                    AND MONTH(created_at) = ?");
    $year = date('Y');
    $month = date('m');
    $payment_query->bind_param("iii", $user_id, $year, $month);
    $payment_query->execute();
    $result = $payment_query->get_result()->fetch_assoc();
    if ($result['payment_count'] == 0) {
        $missed_payments[$user_id] = [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'phone_number' => $user['phone_number'],
            'suggested_fine' => 100.00 // Ksh 100 fine
        ];
    }
    $payment_query->close();
}

// Fetch transactions for a specific user if selected
$selected_user_id = $_GET['user_id'] ?? null;
$transactions = [];
if ($selected_user_id) {
    $transactions_query = $conn->prepare("SELECT amount, type, mpesa_receipt, transaction_desc, status, created_at 
                                         FROM transactions 
                                         WHERE user_id = ? 
                                         ORDER BY created_at DESC");
    $transactions_query->bind_param("i", $selected_user_id);
    $transactions_query->execute();
    $transactions = $transactions_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $transactions_query->close();
}

// Fetch fines for a specific user if selected
$fines = [];
if ($selected_user_id) {
    $fines_query = $conn->prepare("SELECT id, amount, reason, issued_at, status 
                                  FROM fines 
                                  WHERE user_id = ? 
                                  ORDER BY issued_at DESC");
    $fines_query->bind_param("i", $selected_user_id);
    $fines_query->execute();
    $fines = $fines_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $fines_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financials - Chama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        .table-auto th, .table-auto td { padding: 12px; text-align: left; }
        .status-completed { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
        .status-failed { color: red; font-weight: bold; }
        .alert { background-color: #fee2e2; padding: 12px; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Chama Financials</h1>
        <?php echo $status_message; ?>
        <a href="admin_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4 inline-block">Back to Dashboard</a>

        <!-- Missed Payments Alerts -->
        <?php if (!empty($missed_payments)): ?>
            <div class="alert mb-6">
                <h2 class="text-xl font-bold text-red-800 mb-2">Missed Monthly Contributions</h2>
                <ul class="list-disc pl-6">
                    <?php foreach ($missed_payments as $user_id => $info): ?>
                        <li>
                            <?php echo htmlspecialchars($info['name']); ?> (<?php echo htmlspecialchars($info['phone_number']); ?>): 
                            Missed payment for <?php echo date('F Y'); ?>. Suggested fine: Ksh <?php echo number_format($info['suggested_fine'], 2); ?>.
                            <form action="send_notification.php" method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($info['phone_number']); ?>">
                                <input type="hidden" name="message" value="Dear <?php echo htmlspecialchars($info['name']); ?>, you missed your <?php echo date('F Y'); ?> monthly contribution. A fine of Ksh <?php echo number_format($info['suggested_fine'], 2); ?> may apply. Please make your payment soon.">
                                <button type="submit" class="text-blue-500 hover:underline">Send SMS Reminder</button>
                            </form>
                            <form action="issue_fine.php" method="POST" class="inline ml-2">
                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                <input type="hidden" name="amount" value="<?php echo $info['suggested_fine']; ?>">
                                <input type="hidden" name="reason" value="Missed <?php echo date('F Y'); ?> monthly contribution">
                                <button type="submit" class="text-red-500 hover:underline">Issue Fine</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- All Users -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">All Users</h2>
            <table class="table-auto w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th>Full Name</th>
                        <th>Phone Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b">
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td>
                                <a href="financials.php?user_id=<?php echo $user['id']; ?>" 
                                   class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">View Their Finances</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Selected User's Transactions -->
        <?php if ($selected_user_id): ?>
            <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    Transactions for <?php echo htmlspecialchars($users[array_search($selected_user_id, array_column($users, 'id'))]['first_name'] . ' ' . $users[array_search($selected_user_id, array_column($users, 'id'))]['last_name']); ?>
                </h2>
                <?php if (empty($transactions)): ?>
                    <p class="text-gray-600">No transactions found for this user.</p>
                <?php else: ?>
                    <table class="table-auto w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Receipt</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $row): ?>
                                <tr class="border-b">
                                    <td>Ksh <?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['mpesa_receipt'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['transaction_desc'] ?? 'N/A'); ?></td>
                                    <td class="status-<?php echo htmlspecialchars(strtolower($row['status'] ?? 'pending')); ?>">
                                        <?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Pending')); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Selected User's Fines -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Fines for <?php echo htmlspecialchars($users[array_search($selected_user_id, array_column($users, 'id'))]['first_name'] . ' ' . $users[array_search($selected_user_id, array_column($users, 'id'))]['last_name']); ?></h2>
                <?php if (empty($fines)): ?>
                    <p class="text-gray-600">No fines issued for this user.</p>
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
                            <?php foreach ($fines as $fine): ?>
                                <tr class="border-b">
                                    <td>Ksh <?php echo number_format($fine['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($fine['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($fine['issued_at']); ?></td>
                                    <td class="status-<?php echo htmlspecialchars(strtolower($fine['status'])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($fine['status'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>