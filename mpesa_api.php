<?php
// Include the configuration file, which likely contains sensitive data like API keys, secrets, and database connection details.
include "config.php";

// Define a function to obtain an M-Pesa access token for API authentication.
function getMpesaAccessToken() {
    // Use global variables for consumer key and secret, defined in config.php.
    global $consumerKey, $consumerSecret;
    // Set the URL for the M-Pesa OAuth token endpoint (sandbox environment).
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    // Encode consumer key and secret in Base64 for Basic Authentication.
    $credentials = base64_encode("$consumerKey:$consumerSecret");
    // Initialize a cURL session for making the HTTP request.
    $curl = curl_init();
    // Configure cURL options for the request.
    curl_setopt_array($curl, [
        // Set the request URL.
        CURLOPT_URL => $url,
        // Set headers for Basic Authentication and JSON content type.
        CURLOPT_HTTPHEADER => ["Authorization: Basic $credentials", "Content-Type: application/json"],
        // Return the response as a string instead of outputting it.
        CURLOPT_RETURNTRANSFER => true,
        // Enable SSL verification for secure communication.
        CURLOPT_SSL_VERIFYPEER => true,
        // Set a timeout of 10 seconds for the request.
        CURLOPT_TIMEOUT => 10,
        // Treat HTTP 4xx/5xx status codes as errors.
        CURLOPT_FAILONERROR => true
    ]);
    // Attempt the request up to 3 times in case of failure.
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        // Execute the cURL request and store the response.
        $response = curl_exec($curl);
        // Get the HTTP status code of the response.
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Get any cURL error message if the request fails.
        $curl_error = curl_error($curl);
        // Check if the request was successful (HTTP 200 and no errors).
        if ($response !== false && $http_code == 200) {
            // Decode the JSON response into an associative array.
            $result = json_decode($response, true);
            // Check if the access token is present in the response.
            if (isset($result['access_token'])) {
                // Close the cURL session to free resources.
                curl_close($curl);
                // Return the access token.
                return $result['access_token'];
            }
        }
        // Log the failure details for debugging.
        error_log("Access Token Attempt $attempt Failed: HTTP $http_code | cURL: $curl_error | Response: " . ($response ?: 'No response'));
        // Wait 2 seconds before retrying.
        sleep(2); // Wait before retry
    }
    // Close the cURL session if all attempts fail.
    curl_close($curl);
    // Throw an exception if authentication fails after all attempts.
    throw new Exception("Unable to authenticate with M-Pesa API after $attempt attempts");
}

// Define a function to initiate an M-Pesa STK Push transaction (prompts user to enter PIN on their phone).
function stkPushRequest($phone, $amount, $desc, $user_id, $type) {
    // Use global variables for passkey, callback URL, and database connection.
    global $passKey, $callbackURL, $conn;
    // Check if the database connection is active; attempt to reopen if closed.
    if (!$conn || $conn->connect_error) {
        // Include the database connection script to re-establish the connection.
        include "db_connect.php"; // Reopen connection
        // Verify if the connection was successful.
        if (!$conn || $conn->connect_error) {
            // Log the database connection failure.
            error_log("DB Connection Failed: " . ($conn ? $conn->connect_error : "null"));
            // Throw an exception if the connection cannot be established.
            throw new Exception("Database connection failed");
        }
    }
    // Define the business short code (PayBill number) for the transaction.
    $shortCode = 174379;
    // Obtain an access token for API authentication.
    $accessToken = getMpesaAccessToken();
    // Generate a timestamp in the format YYYYMMDDHHMMSS.
    $timestamp = date("YmdHis");
    // Create the password by encoding the short code, passkey, and timestamp in Base64.
    $password = base64_encode($shortCode . $passKey . $timestamp);
    // Prepare the data payload for the STK Push request.
    $data = [
        // Business short code (PayBill number).
        "BusinessShortCode" => $shortCode,
        // Password for authentication.
        "Password" => $password,
        // Timestamp of the request.
        "Timestamp" => $timestamp,
        // Transaction type for STK Push.
        "TransactionType" => "CustomerPayBillOnline",
        // Amount to be paid.
        "Amount" => $amount,
        // Payer's phone number (customer).
        "PartyA" => $phone,
        // Payee's short code (same as BusinessShortCode).
        "PartyB" => $shortCode,
        // Phone number to receive the STK Push prompt.
        "PhoneNumber" => $phone,
        // URL to receive transaction status updates.
        "CallBackURL" => $callbackURL,
        // Reference for the transaction, includes user ID.
        "AccountReference" => "ChamaContrib_$user_id",
        // Description of the transaction.
        "TransactionDesc" => $desc
    ];
    // Initialize a cURL session for the STK Push API request.
    $curl = curl_init("https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest");
    // Configure cURL options for the request.
    curl_setopt_array($curl, [
        // Set headers for Bearer Authentication and JSON content type.
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken", "Content-Type: application/json"],
        // Set the request method to POST.
        CURLOPT_POST => true,
        // Encode the data payload as JSON.
        CURLOPT_POSTFIELDS => json_encode($data),
        // Return the response as a string.
        CURLOPT_RETURNTRANSFER => true,
        // Enable SSL verification.
        CURLOPT_SSL_VERIFYPEER => true,
        // Set a timeout of 10 seconds.
        CURLOPT_TIMEOUT => 10
    ]);
    // Execute the cURL request.
    $response = curl_exec($curl);
    // Get the HTTP status code.
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // Get any cURL error message.
    $curl_error = curl_error($curl);
    // Close the cURL session.
    curl_close($curl);
    // Log the request details and response for debugging.
    error_log("STK Push | UserID: $user_id | Phone: $phone | Type: $type | Payload: " . json_encode($data) . " | Response: " . ($response ?: 'No response'));
    // Check if the request failed (no response or non-200 status code).
    if ($response === false || $http_code !== 200) {
        // Throw an exception with failure details.
        throw new Exception("STK Push failed. HTTP Code: $http_code | cURL Error: $curl_error | Response: " . ($response ?: 'No response'));
    }
    // Decode the JSON response.
    $result = json_decode($response, true);
    // Check if the response indicates success (ResponseCode = 0).
    if (isset($result['ResponseCode']) && $result['ResponseCode'] === "0") {
        // Extract MerchantRequestID and CheckoutRequestID from the response.
        $merchantRequestID = $result['MerchantRequestID'];
        $checkoutRequestID = $result['CheckoutRequestID'];
        // Prepare an SQL query to insert transaction details into the database.
        $query = "INSERT INTO transactions (user_id, phone_number, amount, type, merchant_request_id, checkout_request_id, status, transaction_desc, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        // Prepare the SQL statement to prevent SQL injection.
        $stmt = $conn->prepare($query);
        // Check if the statement preparation was successful.
        if (!$stmt) {
            // Log the database error.
            error_log("DB Prepare Error: " . $conn->error);
            // Throw an exception if preparation fails.
            throw new Exception("DB Prepare Failed: " . $conn->error);
        }
        // Bind parameters to the SQL query (user_id, phone, amount, type, merchantRequestID, checkoutRequestID, desc).
        $stmt->bind_param("isdsdss", $user_id, $phone, $amount, $type, $merchantRequestID, $checkoutRequestID, $desc);
        // Execute the query and check for success.
        if (!$stmt->execute()) {
            // Log the database error.
            error_log("DB Insert Error: " . $stmt->error);
            // Throw an exception if insertion fails.
            throw new Exception("DB Insert Failed: " . $stmt->error);
        }
        // Close the prepared statement.
        $stmt->close();
        // Return the API response.
        return $result;
    }
    // Throw an exception if the response is unexpected.
    throw new Exception("Unexpected STK Push response: " . json_encode($result));
}

// Define a function to query the status of an STK Push transaction.
function stkPushQuery($checkoutRequestID) {
    // Use global variables for short code, passkey, and database connection.
    global $shortCode, $passKey, $conn;
    // Check if the database connection is active; attempt to reopen if closed.
    if (!$conn || $conn->connect_error) {
        // Include the database connection script.
        include "db_connect.php"; // Reopen connection
        // Verify if the connection was successful.
        if (!$conn || $conn->connect_error) {
            // Log the database connection failure.
            error_log("DB Connection Failed: " . ($conn ? $conn->connect_error : "null"));
            // Throw an exception if the connection cannot be established.
            throw new Exception("Database connection failed");
        }
    }
    // Obtain an access token for API authentication.
    $accessToken = getMpesaAccessToken();
    // Generate a timestamp in the format YYYYMMDDHHMMSS.
    $timestamp = date("YmdHis");
    // Create the password by encoding the short code, passkey, and timestamp in Base64.
    $password = base64_encode($shortCode . $passKey . $timestamp);
    // Prepare the data payload for the STK Push query request.
    $data = [
        // Business short code.
        "BusinessShortCode" => $shortCode,
        // Password for authentication.
        "Password" => $password,
        // Timestamp of the request.
        "Timestamp" => $timestamp,
        // The CheckoutRequestID to query.
        "CheckoutRequestID" => $checkoutRequestID
    ];
    // Initialize a cURL session for the STK Push query API.
    $curl = curl_init("https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query");
    // Configure cURL options for the request.
    curl_setopt_array($curl, [
        // Set headers for Bearer Authentication and JSON content type.
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken", "Content-Type: application/json"],
        // Set the request method to POST.
        CURLOPT_POST => true,
        // Encode the data payload as JSON.
        CURLOPT_POSTFIELDS => json_encode($data),
        // Return the response as a string.
        CURLOPT_RETURNTRANSFER => true,
        // Enable SSL verification.
        CURLOPT_SSL_VERIFYPEER => true,
        // Set a timeout of 10 seconds.
        CURLOPT_TIMEOUT => 10
    ]);
    // Execute the cURL request.
    $response = curl_exec($curl);
    // Get the HTTP status code.
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // Get any cURL error message.
    $curl_error = curl_error($curl);
    // Close the cURL session.
    curl_close($curl);
    // Log the request details and response for debugging.
    error_log("STK Push Query | CheckoutID: $checkoutRequestID | Payload: " . json_encode($data) . " | Response: " . ($response ?: 'No response'));
    // Check if the request failed (no response or non-200 status code).
    if ($response === false || $http_code !== 200) {
        // Return an error status with details.
        return ['status' => 'error', 'message' => "Query failed. HTTP Code: $http_code | cURL Error: $curl_error"];
    }
    // Decode the JSON response.
    $result = json_decode($response, true);
    // Check if the response indicates success (ResponseCode = 0).
    if (isset($result['ResponseCode']) && $result['ResponseCode'] === "0") {
        // Return a completed status with the full result.
        return ['status' => 'completed', 'result' => $result];
    } elseif (isset($result['errorCode'])) {
        // Return a failed status with the error message.
        return ['status' => 'failed', 'message' => $result['errorMessage']];
    }
    // Return a pending status if the transaction is still processing.
    return ['status' => 'pending', 'message' => 'Transaction still processing'];
}
?>