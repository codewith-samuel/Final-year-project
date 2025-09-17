<?php
session_start();
include "config.php";
include "mpesa_api.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $baseUrl/make_contribution.php?status=error&message=" . urlencode("Invalid request method"));
    exit;
}

$user_id = $_SESSION['id'];
$phone = preg_replace('/^\+?254/', '254', $_POST['phone'] ?? ''); // Normalize phone number
$type = $_POST['type'] ?? '';
$amount = $_POST['amount'] ?? '';
$desc = "Chama Payment - " . ucfirst($type);

// Validate inputs
if (empty($phone) || empty($type) || empty($amount) || !preg_match("/^254[0-9]{9}$/", $phone) || !in_array($type, ['monthly', 'emergency', 'investment']) || $amount < 1) {
    header("Location: $baseUrl/make_contribution.php?status=error&message=" . urlencode("Invalid input: Ensure phone is 254XXXXXXXXX, amount >= 1, and type is valid"));
    exit;
}

// Verify phone number matches user's phone_number
include "db_connect.php";
$user_query = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
$user_phone = preg_replace('/^\+?254/', '254', $user['phone_number'] ?? '');
$user_query->close();

if ($phone !== $user_phone) {
    $conn->close();
    header("Location: $baseUrl/make_contribution.php?status=error&message=" . urlencode("Phone number must match your registered number"));
    exit;
}

try {
    $response = stkPushRequest($phone, $amount, $desc, $user_id, $type);
    $checkoutRequestID = $response['CheckoutRequestID'];
    $conn->close(); // Close connection after successful STK Push
    header("Location: $baseUrl/processing.php?checkout=" . urlencode($checkoutRequestID));
    exit;
} catch (Exception $e) {
    error_log("Process Payment Error: " . $e->getMessage());
    $conn->close(); // Close connection on error
    header("Location: $baseUrl/make_contribution.php?status=error&message=" . urlencode($e->getMessage()));
    exit;
}
?>