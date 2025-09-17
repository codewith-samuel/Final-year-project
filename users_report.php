<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

// Fetch all users with basic details
$users_query = $conn->prepare("
    SELECT u.id, u.username, u.email, u.role, u.approval_status, u.created_at, u.first_name, u.last_name,
           COALESCE(SUM(t.amount), 0) AS total_contributions
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'completed'
    GROUP BY u.id
");
$users_query->execute();
$users = $users_query->get_result()->fetch_all(MYSQLI_ASSOC);
$users_query->close();

// Fetch transactions for each user if needed (we'll display them in expandable sections)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reports - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .section { margin-bottom: 20px; }
        .section-header { cursor: pointer; background: #f3f4f6; padding: 10px; border-radius: 5px; }
        .section-content { display: none; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">User Reports</h2>
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
        <?php foreach ($users as $user): ?>
            <div class="section bg-white shadow rounded mb-4">
                <div class="section-header flex justify-between items-center">
                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars(ucfirst($user['role'])); ?>)</h3>
                    <span>▼</span>
                </div>
                <div class="section-content">
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Approval Status:</strong> <?php echo htmlspecialchars(ucfirst($user['approval_status'])); ?></p>
                    <p><strong>Date Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                    <p><strong>Total Contributions:</strong> Ksh <?php echo number_format($user['total_contributions'], 2); ?></p>
                    <h4 class="text-md font-semibold mt-4 mb-2">Transactions</h4>
                    <?php
                    // Fetch transactions for this user
                    $txn_query = $conn->prepare("SELECT amount, transaction_desc, created_at, mpesa_receipt, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
                    $txn_query->bind_param("i", $user['id']);
                    $txn_query->execute();
                    $transactions = $txn_query->get_result()->fetch_all(MYSQLI_ASSOC);
                    $txn_query->close();
                    ?>
                    <?php if (!empty($transactions)): ?>
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-2 text-left">Amount</th>
                                    <th class="p-2 text-left">Description</th>
                                    <th class="p-2 text-left">Date</th>
                                    <th class="p-2 text-left">Receipt</th>
                                    <th class="p-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td class="p-2 border-b">Ksh <?php echo number_format($txn['amount'], 2); ?></td>
                                        <td class="p-2 border-b"><?php echo htmlspecialchars($txn['transaction_desc']); ?></td>
                                        <td class="p-2 border-b"><?php echo htmlspecialchars($txn['created_at']); ?></td>
                                        <td class="p-2 border-b"><?php echo htmlspecialchars($txn['mpesa_receipt'] ?? 'N/A'); ?></td>
                                        <td class="p-2 border-b"><?php echo htmlspecialchars(ucfirst($txn['status'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No transactions found for this user.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <a href="admin_dashboard.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Back to Dashboard</a>
    </div>
    <script>
        document.querySelectorAll('.section-header').forEach(header => {
            header.addEventListener('click', () => {
                const content = header.nextElementSibling;
                content.style.display = content.style.display === 'block' ? 'none' : 'block';
                header.querySelector('span').textContent = content.style.display === 'block' ? '▲' : '▼';
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>