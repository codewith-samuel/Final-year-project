<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /Chama-management-system/financials.php?status=error&message=" . urlencode("Invalid request"));
    exit;
}

$user_id = $_POST['user_id'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($user_id) || empty($phone_number) || empty($message)) {
    header("Location: /Chama-management-system/financials.php?status=error&message=" . urlencode("Missing required fields"));
    exit;
}

// Log notification (replace with actual SMS gateway integration)
error_log("SMS Notification | UserID: $user_id | Phone: $phone_number | Message: $message");

// TODO: Integrate with SMS gateway (e.g., Africa's Talking, Twilio)
// Example pseudo-code:
// $sms = new AfricasTalkingSMS($apiKey, $username);
// $sms->send($phone_number, $message);

header("Location: /Chama-management-system/financials.php?status=success&message=" . urlencode("Notification logged for user ID $user_id"));
exit;
?>