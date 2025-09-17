<?php
session_start();
include "config.php";

if (!isset($_GET['checkout']) || empty($_GET['checkout'])) {
    header("Location: $baseUrl/make_contribution.php?status=error&message=" . urlencode("Invalid checkout ID"));
    exit;
}

$checkoutRequestID = $_GET['checkout'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Chama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100 font-roboto flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white p-6 rounded-lg shadow-lg text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Processing Your Payment</h2>
        <p class="text-gray-600 mb-4">Please confirm the STK Push on your phone...</p>
        <div class="spinner"></div>
        <p id="status-message" class="text-gray-600 mt-4">Checking transaction status...</p>
    </div>
    <script>
        const checkoutRequestID = <?php echo json_encode($checkoutRequestID); ?>;
        const baseUrl = <?php echo json_encode($baseUrl); ?>;
        let attempts = 0;
        const maxAttempts = 30; // 30 attempts x 5 seconds = 150 seconds (2.5 minutes)

        function checkStatus() {
            fetch(`${baseUrl}/check_status.php?checkout=${encodeURIComponent(checkoutRequestID)}`)
                .then(response => response.json())
                .then(data => {
                    const statusMessage = document.getElementById('status-message');
                    if (data.status === 'completed') {
                        window.location.href = `${baseUrl}/receipt.php?checkout=${encodeURIComponent(checkoutRequestID)}`;
                    } else if (data.status === 'failed') {
                        statusMessage.textContent = `Transaction failed: ${data.message}`;
                        setTimeout(() => {
                            window.location.href = `${baseUrl}/make_contribution.php?status=error&message=${encodeURIComponent(data.message)}`;
                        }, 3000);
                    } else if (data.status === 'error') {
                        statusMessage.textContent = `Error: ${data.message}`;
                        setTimeout(() => {
                            window.location.href = `${baseUrl}/make_contribution.php?status=error&message=${encodeURIComponent(data.message)}`;
                        }, 3000);
                    } else {
                        attempts++;
                        if (attempts < maxAttempts) {
                            statusMessage.textContent = 'Transaction still processing...';
                            setTimeout(checkStatus, 5000); // Poll every 5 seconds
                        } else {
                            statusMessage.textContent = 'Transaction timed out';
                            setTimeout(() => {
                                window.location.href = `${baseUrl}/make_contribution.php?status=error&message=${encodeURIComponent('Transaction timed out')}`;
                            }, 3000);
                        }
                    }
                })
                .catch(error => {
                    document.getElementById('status-message').textContent = 'Error checking status';
                    console.error('Fetch error:', error);
                    setTimeout(() => {
                        window.location.href = `${baseUrl}/make_contribution.php?status=error&message=${encodeURIComponent('Error checking transaction status')}`;
                    }, 3000);
                });
        }

        // Start polling
        checkStatus();
    </script>
</body>
</html>