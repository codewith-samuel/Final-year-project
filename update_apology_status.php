<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'chairperson']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include 'db_connect.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_POST['apology_id']) || !isset($_POST['status']) || !in_array($_POST['status'], ['approved', 'rejected'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: /Chama-management-system/manage_apologies.php");
    exit;
}

$apology_id = $_POST['apology_id'];
$status = $_POST['status'];
$user_id = $_SESSION['id'];
$rejection_reason = ($status === 'rejected' && isset($_POST['rejection_reason'])) ? trim($_POST['rejection_reason']) : null;

if ($status === 'rejected' && empty($rejection_reason)) {
    $_SESSION['error'] = "A rejection reason is required.";
    header("Location: /Chama-management-system/manage_apologies.php");
    exit;
}

// Determine which approval column to update
$approval_column = $_SESSION['role'] === 'superadmin' ? 'approved_by_superadmin' : 'approved_by_chairperson';
$approval_value = $status === 'approved' ? 1 : -1;

// Update the appropriate approval column and rejection reason
$stmt = $conn->prepare("UPDATE apologies SET $approval_column = ?, rejection_reason = COALESCE(rejection_reason, ?) WHERE id = ? AND status = 'pending'");
$stmt->bind_param("isi", $approval_value, $rejection_reason, $apology_id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Check if both officials have acted
    $stmt = $conn->prepare("SELECT a.approved_by_chairperson, a.approved_by_superadmin, a.user_id, u.email, u.username, a.meeting_id, a.reason, a.rejection_reason 
                            FROM apologies a 
                            JOIN users u ON a.user_id = u.id 
                            WHERE a.id = ?");
    $stmt->bind_param("i", $apology_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $apology = $result->fetch_assoc();

    // Only update status and send email if both officials have acted
    if ($apology['approved_by_chairperson'] != 0 && $apology['approved_by_superadmin'] != 0) {
        $new_status = ($apology['approved_by_chairperson'] == 1 && $apology['approved_by_superadmin'] == 1) ? 'approved' : 'rejected';

        // Update status and email_sent
        $stmt = $conn->prepare("UPDATE apologies SET status = ?, email_sent = 1 WHERE id = ?");
        $stmt->bind_param("si", $new_status, $apology_id);
        $stmt->execute();

        // Send email
        $stmt = $conn->prepare("SELECT m.title FROM meetings m WHERE m.id = ?");
        $stmt->bind_param("i", $apology['meeting_id']);
        $stmt->execute();
        $meeting_title = $stmt->get_result()->fetch_assoc()['title'];

        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'waithakas2003@gmail.com';
            $mail->Password = 'xaba hxxm aywg nufg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email content
            $mail->setFrom('waithakas2003@gmail.com', 'Chama Management System');
            $mail->addAddress($apology['email'], $apology['username']);
            $mail->Subject = 'Apology Status for ' . $meeting_title;
            $body = "Dear {$apology['username']},\n\nYour apology for the meeting '$meeting_title' has been $new_status.";
            if ($new_status === 'rejected' && !empty($apology['rejection_reason'])) {
                $body .= "\n\nReason for rejection: {$apology['rejection_reason']}";
            }
            $body .= "\n\nThank you,\nChama Management Team";
            $mail->Body = $body;
            $mail->AltBody = $body;

            $mail->send();
        } catch (Exception $e) {
            error_log("Email failed: {$mail->ErrorInfo}");
            $_SESSION['error'] = "Apology status updated, but email notification failed.";
        }
    }

    $_SESSION['success'] = "Apology action recorded successfully.";
} else {
    $_SESSION['error'] = "Failed to update apology or already processed.";
}
$stmt->close();
$conn->close();

header("Location: /Chama-management-system/manage_apologies.php");
exit;
?>