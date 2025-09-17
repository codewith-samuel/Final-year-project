<?php
session_start();
include('db_connect.php');
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    // Fetch user details
    $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch chairperson details
    $stmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name 
                            FROM removal_requests rr 
                            JOIN users u ON rr.requested_by = u.id 
                            WHERE rr.id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $chairperson = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Update removal request
    $stmt = $conn->prepare("UPDATE removal_requests SET status = ?, approved_by_superadmin = ?, rejection_reason = ? WHERE id = ?");
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $approved = $action === 'approve' ? 1 : 0;
    $stmt->bind_param("sisi", $status, $approved, $rejection_reason, $request_id);
    $stmt->execute();
    $stmt->close();

    // Delete user if approved
    if ($action === 'approve') {
        $conn->begin_transaction();
        try {
            // Delete related records from all tables with foreign key constraints
            $tables = ['apologies', 'meeting_attendance', 'attendance', 'transactions', 'loan_applications', 'fines', 'removal_requests'];
            foreach ($tables as $table) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
            header("Location: manage_removal.php");
            exit;
        }
    }

    // Send email notifications
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'waithakas2003@gmail.com';
        $mail->Password = 'xaba hxxm aywg nufg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('waithakas2003@gmail.com', 'Chama Management System');

        // Email to chairperson
        $mail->addAddress($chairperson['email'], "{$chairperson['first_name']} {$chairperson['last_name']}");
        $mail->isHTML(true);
        $mail->Subject = 'Removal Request Status';
        $mail->Body = "Hi {$chairperson['first_name']} {$chairperson['last_name']},<br>Your request to remove {$user['first_name']} {$user['last_name']} has been $status by the Super Admin.";
        if ($action === 'reject' && !empty($rejection_reason)) {
            $mail->Body .= "<br>Rejection Reason: " . htmlspecialchars($rejection_reason);
        }
        $mail->send();
        $mail->clearAddresses();

        // Email to member (if approved)
        if ($action === 'approve') {
            $stmt = $conn->prepare("SELECT reason FROM removal_requests WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $reason = $stmt->get_result()->fetch_assoc()['reason'];
            $stmt->close();

            $mail->addAddress($user['email'], "{$user['first_name']} {$user['last_name']}");
            $mail->Subject = 'Membership Removal Notification';
            $mail->Body = "Hi {$user['first_name']} {$user['last_name']},<br>Your membership in the Chama has been terminated.<br>Reason: " . htmlspecialchars($reason);
            $mail->send();
        }

        $_SESSION['success'] = "Removal request $status successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Removal request $status, but failed to send email: {$mail->ErrorInfo}";
    }

    header("Location: manage_removal.php");
    exit;
}

$conn->close();
?>