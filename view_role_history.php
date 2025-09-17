
<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: ../signin.html");
    exit;
}

$user_id = $_GET['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: admin_dashboard.php");
    exit;
}
$user = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT rc.new_role, rc.changed_at, u.username AS changed_by 
                        FROM role_changes rc 
                        JOIN users u ON rc.changed_by = u.id 
                        WHERE rc.user_id = ? 
                        ORDER BY rc.changed_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Change History - Chama Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Role Change History for <?php echo htmlspecialchars($user['username']); ?></h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <table class="w-full border-collapse bg-white shadow rounded">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-3 text-left">New Role</th>
                    <th class="p-3 text-left">Changed By</th>
                    <th class="p-3 text-left">Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($entry['new_role']); ?></td>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($entry['changed_by']); ?></td>
                            <td class="p-3 border-b"><?php echo htmlspecialchars($entry['changed_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="p-3 text-center">No role change history</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="admin_dashboard.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>
