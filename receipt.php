<?php
session_start();
include "config.php";

// Check if user is logged in and has a valid role
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary', 'superadmin'])) {
    header("Location: $baseUrl/signin.php");
    exit;
}

$checkoutRequestID = $_GET['checkout'] ?? '';
if (empty($checkoutRequestID)) {
    header("Location: $baseUrl/member_dashboard.php?status=error&message=" . urlencode("Invalid receipt request"));
    exit;
}

// Prepare query based on user role
$query = "SELECT t.user_id, t.phone_number, t.amount, t.type, t.checkout_request_id, t.transaction_desc, t.created_at, t.mpesa_receipt, t.status, u.username 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          WHERE t.checkout_request_id = ?";
$params = ["s", $checkoutRequestID];

// Add user_id restriction for non-superadmin roles
if ($_SESSION['role'] !== 'superadmin') {
    $query .= " AND t.user_id = ?";
    $params[0] .= "i";
    $params[] = $_SESSION['id'];
}

$stmt = $conn->prepare($query);
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

if (!$transaction) {
    header("Location: $baseUrl/member_dashboard.php?status=error&message=" . urlencode("Transaction not found or unauthorized"));
    exit;
}

$qrData = "Transaction ID: {$transaction['checkout_request_id']} | Amount: KES {$transaction['amount']} | Type: {$transaction['type']} | Date: {$transaction['created_at']}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Receipt - Chama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
            .receipt { border: 1px dashed #000; box-shadow: none; }
        }
    </style>
</head>
<body class="bg-gray-100 font-roboto">
    <div class="max-w-md mx-auto my-8 p-6 bg-white rounded-lg shadow-lg receipt">
        <div class="text-center mb-6">
            <img src="https://via.placeholder.com/100x50?text=Chama+Logo" alt="Chama Logo" class="mx-auto mb-2">
            <h1 class="text-2xl font-bold text-green-600">M-Pesa Receipt</h1>
            <p class="text-sm text-gray-500">Chama Management System</p>
        </div>
        <div class="border-t border-b border-gray-200 py-4 mb-4">
            <div class="flex justify-between">
                <span class="font-medium">Transaction ID:</span>
                <span><?php echo htmlspecialchars($transaction['checkout_request_id']); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Member:</span>
                <span><?php echo htmlspecialchars($transaction['username']); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Amount:</span>
                <span class="text-green-600 font-bold">KES <?php echo number_format($transaction['amount'], 2); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Type:</span>
                <span><?php echo htmlspecialchars(ucfirst($transaction['type'])); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Phone Number:</span>
                <span><?php echo htmlspecialchars($transaction['phone_number']); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Description:</span>
                <span><?php echo htmlspecialchars($transaction['transaction_desc']); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Status:</span>
                <span><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></span>
            </div>
            <div class="flex justify-between mt-2">
                <span class="font-medium">Date:</span>
                <span><?php echo htmlspecialchars($transaction['created_at']); ?></span>
            </div>
            <?php if ($transaction['mpesa_receipt']): ?>
                <div class="flex justify-between mt-2">
                    <span class="font-medium">M-Pesa Receipt:</span>
                    <span><?php echo htmlspecialchars($transaction['mpesa_receipt']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mb-6">
            <div id="qrcode" class="inline-block"></div>
            <p class="text-xs text-gray-500 mt-2">Scan to verify transaction</p>
        </div>
        <div class="text-center text-sm text-gray-600 border-t pt-4">
            <p>Thank you for your contribution!</p>
            <p>Contact: support@chama.co.ke | +254 700 123 456</p>
        </div>
    </div>
    <div class="text-center no-print">
        <button onclick="printReceipt()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">Print Receipt</button>
        <button onclick="downloadPDF()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 ml-4">Download PDF</button>
        <a href="<?php echo $baseUrl; ?>/member_dashboard.php" class="block mt-4 text-blue-600 hover:underline">Back to Dashboard</a>
    </div>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo htmlspecialchars($qrData); ?>",
            width: 100,
            height: 100
        });
        function printReceipt() {
            window.print();
        }
        function downloadPDF() {
            const element = document.querySelector('.receipt');
            html2pdf()
                .set({
                    filename: 'Transaction_Receipt_<?php echo $transaction['checkout_request_id']; ?>.pdf',
                    margin: 10,
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                })
                .from(element)
                .save();
        }
    </script>
</body>
</html>