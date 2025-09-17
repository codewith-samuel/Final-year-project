<?php
include('db_connect.php'); // Include the database connection file

if (isset($_GET['token'])) {
    $verification_token = $_GET['token'];

    // Check if the verification token exists in the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $verification_token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update the user's verification status
        $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE verification_token = ?");
        $stmt->bind_param("s", $verification_token);
        if ($stmt->execute()) {
            echo "Your email has been verified successfully!";
            header("Location: signin.php"); // Redirect to login page
            exit; // Ensure no further code is executed after redirection
        } else {
            echo "Error: Could not verify email.";
        }
    } else {
        echo "Invalid verification token.";
    }

    $stmt->close();
} else {
    echo "No verification token provided.";
}

$conn->close();
?>