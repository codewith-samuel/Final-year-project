<?php
// Start a new session or resume an existing one to manage user session data.
session_start();

// Check if the user is authenticated and has a valid role and user ID.
// - `!isset($_SESSION['role'])`: Checks if the 'role' key is not set in the session.
// - `!in_array($_SESSION['role'], ['member', 'chairperson', 'secretary'])`: Ensures the user's role is one of 'member', 'chairperson', or 'secretary'.
// - `!isset($_SESSION['id'])`: Checks if the 'id' key is not set in the session.
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary']) || !isset($_SESSION['id'])) {
    // Redirect the user to the sign-in page if the session check fails.
    // The Location header sends the user to '/Chama-management-system/signin.html'.
    header("Location: /Chama-management-system/signin.html");
    // Terminate the script to prevent further execution after the redirect.
    exit;
}

// Include the configuration file, which likely contains database credentials, API keys, or other settings.
include('config.php');

// Store the user's ID from the session in a variable for easy access.
// $_SESSION['id'] holds the unique identifier for the authenticated user.
$user_id = $_SESSION['id'];

// Store the user's role from the session in a variable for easy access.
// $_SESSION['role'] indicates whether the user is a 'member', 'chairperson', or 'secretary'.
$role = $_SESSION['role'];

// Fetch user's phone number,   variable that references an object of the mysqli_stmt class.
// executes prepared SQL queries to fetch user data, contributions, transactions, loans, and other records from the database.
$stmt = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: /Chama-management-system/{$role}_dashboard.php");
    exit;
}

$phone_number = $user['phone_number'];

// Fetch completed transactions
$stmt = $conn->prepare("SELECT id, amount, checkout_request_id, transaction_desc, created_at, mpesa_receipt 
                        FROM transactions 
                        WHERE phone_number = ? AND status = 'completed' 
                        ORDER BY created_at DESC");
$stmt->bind_param("s", $phone_number);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts - Chama <?php echo ucfirst($role); ?></title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0; padding: 20px; color: #ecf0f1; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar h1 { font-size: 24px; margin-bottom: 30px; }
        .sidebar a { display: block; color: #ecf0f1; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .sidebar a:hover { background: #34495e; }
        .container { margin-left: 270px; padding: 40px; min-height: 100vh; }
        .header h2 { font-size: 28px; color: #2c3e50; }
        .section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; color: white; display: inline-block; }
        .btn-primary { background: #3498db; }
        .btn-primary:hover { background: #2980b9; }
        .alert-error { margin-bottom: 20px; padding: 10px; background: #f2dede; color: #a94442; border-radius: 5px; }
        .tooltip { position: relative; display: inline-block; }
        .tooltip::after {
            content: attr(data-tooltip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            background: #333; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; white-space: nowrap;
            opacity: 0; visibility: hidden; transition: opacity 0.3s;
        }
        .tooltip:hover::after { opacity: 1; visibility: visible; }
        @media (max-width: 768px) { .sidebar { width: 200px; } .container { margin-left: 220px; } }
        @media (max-width: 480px) { .sidebar { position: static; width: 100%; height: auto; } .container { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body class="bg-gray-100">
    <div class="sidebar">
        <h1>Chama <?php echo ucfirst($role); ?></h1>
        <?php if ($role === 'member'): ?>
            <a href="/Chama-management-system/member_dashboard.php">Dashboard</a>
            <a href="/Chama-management-system/meetings.php">Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/loans.php">Loans</a>
            <a href="/Chama-management-system/receipts.php">Receipts</a>
        <?php elseif ($role === 'secretary'): ?>
            <a href="/Chama-management-system/secretary_dashboard.php">Secretary Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=join">Meetings</a>
            <a href="/Chama-management-system/meetings.php">Manage Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/loans.php">Loans</a>
            <a href="/Chama-management-system/receipts.php">Receipts</a>
        <?php elseif ($role === 'chairperson'): ?>
            <a href="/Chama-management-system/chairperson_dashboard.php">Chairperson Dashboard</a>
            <a href="/Chama-management-system/meetings.php">Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/manage_apologies.php">Manage Apologies</a>
            <a href="/Chama-management-system/loans.php">Loans</a>
            <a href="/Chama-management-system/receipts.php">Receipts</a>
        <?php endif; ?>
        <a href="/Chama-management-system/logout.php">Logout</a>
    </div>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <div class="header">
            <h2>Your Transaction Receipts</h2>
        </div>
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Amount (Ksh)</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>M-Pesa Receipt</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['checkout_request_id']); ?></td>
                                <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['transaction_desc']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['mpesa_receipt'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="/Chama-management-system/receipt.php?checkout=<?php echo urlencode($transaction['checkout_request_id']); ?>" 
                                       class="btn btn-primary tooltip" data-tooltip="View receipt">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No completed transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>