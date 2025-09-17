<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /Chama-management-system/financials.php?status=error&message=" . urlencode("Invalid request"));
    exit;
}

$user_id = $_POST['user_id'] ?? '';
$amount = $_POST['amount'] ?? '';
$reason = $_POST['reason'] ?? '';

if (empty($user_id) || empty($amount) || empty($reason)) {
    header("Location: /Chama-management-system/financials.php?status=error&message=" . urlencode("Missing required fields"));
    exit;
}

$query = "INSERT INTO fines (user_id, amount, reason, issued_at, status) VALUES (?, ?, ?, NOW(), 'pending')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ids", $user_id, $amount, $reason);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: /Chama-management-system/financials.php?status=success&message=" . urlencode("Fine issued for user ID $user_id"));
    exit;
}

error_log("Fine Insert Error: " . $stmt->error);
$stmt->close();
$conn->close();
header("Location: /Chama-management-system/financials.php?status=error&message=" . urlencode("Failed to issue fine"));
exit;
?>