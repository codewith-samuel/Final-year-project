
<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'chairperson']) || !isset($_SESSION['id'])) {
    header("Location: signin.html");
    exit;
}

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone_number, created_at, approved_by_chairperson, approved_by_superadmin 
                        FROM users 
                        WHERE approval_status = 'pending'");
$stmt->execute();
$pending_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Members - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Pending Member Approvals</h2>
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
        <table class="w-full border-collapse bg-white shadow rounded">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Phone</th>
                    <th class="p-3 text-left">Signup Date</th>
                    <th class="p-3 text-left">Chairperson Approval</th>
                    <th class="p-3 text-left">Super Admin Approval</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pending_members)): ?>
                    <?php foreach ($pending_members as $member): ?>
                        <tr>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($member['phone_number']); ?></td>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($member['created_at']); ?></td>
                            <td class="p-3 border-b"><?php echo $member['approved_by_chairperson'] ? 'Approved' : 'Pending'; ?></td>
                            <td class="p-3 border-b"><?php echo $member['approved_by_superadmin'] ? 'Approved' : 'Pending'; ?></td>
                            <td class="p-3 border-b">
                                <?php if (($role === 'chairperson' && !$member['approved_by_chairperson']) || ($role === 'superadmin' && !$member['approved_by_superadmin'])): ?>
                                    <form action="approve_member.php" method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="bg-green-600 text-white py-1 px-3 rounded hover:bg-green-700 mr-2">Approve</button>
                                        <button type="submit" name="action" value="reject" class="bg-red-600 text-white py-1 px-3 rounded hover:bg-red-700">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-500">Action Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="p-3 text-center">No pending members</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo $role === 'superadmin' ? 'admin_dashboard.php' : 'chairperson_dashboard.php'; ?>" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>
