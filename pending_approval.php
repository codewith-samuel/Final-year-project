<?php
session_start();
include "config.php";

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_GET['user_id'] ?? '';
if (!$user_id) {
    header("Location: signup.php?error=Invalid request");
    exit;
}

$stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ? AND approval_status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: signin.php?error=Account not found or already approved");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $admin_email = "1046031@cuea.edu"; // Your admin email

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'waithakas2003@gmail.com'; // Your Gmail
        $mail->Password = 'xaba hxxm aywg nufg'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('waithakas2003@gmail.com', 'Chama Management System');
        $mail->addAddress($admin_email, 'Admin');

        $mail->isHTML(true);
        $mail->Subject = 'Approval Delay - Chama System';
        $mail->Body = "User: {$user['first_name']} {$user['last_name']} ({$user['email']}) says:<br>" . nl2br(htmlspecialchars($message));

        $mail->send();
        $contact_success = "Message sent to admin!";
    } catch (Exception $e) {
        $contact_error = "Failed to send message: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f4f7fa; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 100px; margin: 10px 0; }
        button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Pending Approval</h2>
        <p>Hi <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>,</p>
        <p>Your account is awaiting admin approval. This usually takes less than 24 hours.</p>
        <p>If it’s been over 24 hours, contact the admin below:</p>
        <?php 
        if (isset($contact_success)) echo "<p style='color: green;'>$contact_success</p>"; 
        if (isset($contact_error)) echo "<p style='color: red;'>$contact_error</p>"; 
        ?>
        <form method="POST">
            <textarea name="message" placeholder="Your message to the admin" required></textarea>
            <button type="submit">Send Message</button>
        </form>
    </div>
</body>
</html>