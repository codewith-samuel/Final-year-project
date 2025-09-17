<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include "db_connect.php";

// Fetch user's phone number
$user_query = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
$user_query->bind_param("i", $_SESSION['id']);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_phone = preg_replace('/^\+?254/', '254', $user['phone_number'] ?? ''); // Normalize phone number
$user_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Contribution</title>
    <link rel="stylesheet" href="contribution.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="contribution-container">
        <div class="card">
            <h2>💰 Make a Contribution</h2>
            <p>Securely contribute to your Chama using M-Pesa.</p>
            <?php if (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
                <div class="error text-red-500 text-center mb-4"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>
            <form action="process_payment.php" method="POST">
                <div class="input-group">
                    <label for="amount">Amount (Ksh)</label>
                    <input type="number" name="amount" id="amount" placeholder="Enter amount (min Ksh 1)" required min="1" step="0.01">
                </div>
                <div class="input-group">
                    <label for="type">Contribution Type</label>
                    <select name="type" id="type" required>
                        <option value="" disabled selected>Select type</option>
                        <option value="monthly">📆 Monthly Contribution</option>
                        <option value="emergency">🚨 Emergency Fund</option>
                        <option value="investment">📈 Investment Contribution</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="phone">M-Pesa Phone Number</label>
                    <input type="tel" name="phone" id="phone" placeholder="254XXXXXXXXX" required pattern="254[0-9]{9}" value="<?php echo htmlspecialchars($user_phone); ?>">
                </div>
                <button type="submit" class="pay-btn">🔗 Pay with M-Pesa</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>