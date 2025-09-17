<?php
include "config.php";

$data = file_get_contents('php://input');
$log_file = 'C:/xampp/htdocs/Chama-management-system/mpesa_log.txt';
file_put_contents($log_file, "Callback Received: " . $data . PHP_EOL, FILE_APPEND);

if (empty($data)) {
    file_put_contents($log_file, "Empty callback received" . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

$mpesaResponse = json_decode($data, true);
if ($mpesaResponse === null) {
    file_put_contents($log_file, "Invalid JSON: " . $data . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

if (!isset($mpesaResponse['Body']['stkCallback'])) {
    file_put_contents($log_file, "Missing stkCallback: " . json_encode($mpesaResponse) . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

$callback = $mpesaResponse['Body']['stkCallback'];
$resultCode = $callback['ResultCode'] ?? null;
$resultDesc = $callback['ResultDesc'] ?? 'Unknown error';
$merchantRequestID = $callback['MerchantRequestID'] ?? '';
$checkoutRequestID = $callback['CheckoutRequestID'] ?? '';

if ($resultCode === null) {
    file_put_contents($log_file, "Missing ResultCode: " . json_encode($callback) . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

if ($resultCode == 0) {
    $metadata = $callback['CallbackMetadata']['Item'] ?? [];
    $amount = null;
    $mpesaReceiptNumber = null;
    $phoneNumber = null;

    foreach ($metadata as $item) {
        switch ($item['Name']) {
            case 'Amount': $amount = $item['Value']; break;
            case 'MpesaReceiptNumber': $mpesaReceiptNumber = $item['Value']; break;
            case 'PhoneNumber': $phoneNumber = $item['Value']; break;
        }
    }

    if ($amount && $mpesaReceiptNumber && $phoneNumber) {
        $query = "UPDATE transactions 
                  SET phone_number = ?, amount = ?, mpesa_receipt = ?, status = 'completed', updated_at = NOW() 
                  WHERE checkout_request_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sdss", $phoneNumber, $amount, $mpesaReceiptNumber, $checkoutRequestID);
        if ($stmt->execute()) {
            file_put_contents($log_file, "Success: $checkoutRequestID | Receipt: $mpesaReceiptNumber" . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents($log_file, "DB Update Error: " . $stmt->error . " | Data: " . json_encode($callback) . PHP_EOL, FILE_APPEND);
        }
        $stmt->close();
    } else {
        file_put_contents($log_file, "Incomplete metadata: " . json_encode($callback) . PHP_EOL, FILE_APPEND);
    }
} else {
    $query = "UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE checkout_request_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $checkoutRequestID);
    $stmt->execute();
    file_put_contents($log_file, "Failed: $checkoutRequestID | $resultCode - $resultDesc" . PHP_EOL, FILE_APPEND);
    $stmt->close();
}

http_response_code(200);
exit;
?>