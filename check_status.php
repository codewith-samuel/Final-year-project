<?php
include "config.php";

$checkoutRequestID = $_GET['checkout'] ?? '';
if (empty($checkoutRequestID)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid checkout ID']);
    exit();
}

$query = "SELECT status, transaction_desc FROM transactions WHERE checkout_request_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $checkoutRequestID);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
if ($transaction) {
    echo json_encode(['status' => $transaction['status'], 'message' => $transaction['transaction_desc']]);
} else {
    echo json_encode(['status' => 'pending', 'message' => 'Transaction not found']);
}
exit();
?>