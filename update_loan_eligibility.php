<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['id'])) {
    header("Location: ../signin.html");
    exit;
}

include 'db_connect.php';

$user_id = $_POST['user_id'] ?? 0;
$is_eligible = isset($_POST['is_eligible']) ? 1 : 0;
$max_loan_amount = $_POST['max_loan_amount'] ?? 0;
$admin_id = $_SESSION['id'];

if ($user_id <= 0 || $max_loan_amount < 0) {
    header("Location: admin_dashboard.php?status=error&message=" . urlencode("Invalid input"));
    exit;
}

$query = $conn->prepare("INSERT INTO loan_eligibility (user_id, is_eligible, max_loan_amount, updated_by) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE is_eligible = ?, max_loan_amount = ?, updated_by = ?");
$query->bind_param("iidiidi", $user_id, $is_eligible, $max_loan_amount, $admin_id, $is_eligible, $max_loan_amount, $admin_id);
$query->execute();
$query->close();

header("Location: admin_dashboard.php?status=success&message=" . urlencode("Eligibility updated successfully"));
$conn->close();
exit;
?>