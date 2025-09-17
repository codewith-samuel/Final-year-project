<?php
session_start();
include('db_connect.php');
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT rr.id, rr.user_id, u.username, u.email, rr.reason, rr.requested_at 
    FROM removal_requests rr 
    JOIN users u ON rr.user_id = u.id 
    WHERE rr.status = 'pending'
");
$stmt->execute();
$removal_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Removal Requests - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Manage Removal Requests</h2>
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
        <div class="bg-white shadow rounded p-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-3 text-left">Username</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Reason for Removal</th>
                        <th class="p-3 text-left">Requested At</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($removal_requests)): ?>
                        <?php foreach ($removal_requests as $request): ?>
                            <tr>
                                <td class="p-3 border-b"><?php echo htmlspecialchars($request['username']); ?></td>
                                <td class="p-3 border-b"><?php echo htmlspecialchars($request['email']); ?></td>
                                <td class="p-3 border-b"><?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></td>
                                <td class="p-3 border-b"><?php echo htmlspecialchars($request['requested_at']); ?></td>
                                <td class="p-3 border-b">
                                    <form action="process_removal.php" method="POST">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <div class="mb-2">
                                            <label for="rejection_reason_<?php echo $request['id']; ?>" class="block text-sm font-medium">Rejection Reason (if rejecting)</label>
                                            <textarea name="rejection_reason" id="rejection_reason_<?php echo $request['id']; ?>" rows="3" class="w-full border rounded p-2" placeholder="Enter reason for rejection (optional)"></textarea>
                                        </div>
                                        <button type="submit" name="action" value="approve" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700">Approve</button>
                                        <button type="submit" name="action" value="reject" class="bg-red-600 text-white py-1 px-3 rounded hover:bg-red-700">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-3 text-center">No pending removal requests</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="admin_dashboard.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>