<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.html");
    exit;
}

include('db_connect.php');
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['loan_id'], $_GET['status']) && in_array($_GET['status'], ['approved', 'rejected'])) {
    $loan_id = $_GET['loan_id'];
    $status = $_GET['status'];
    $admin_id = $_SESSION['id'];

    $conn->begin_transaction();
    try {
        // Fetch loan and user details
        $stmt = $conn->prepare("SELECT la.user_id, la.amount, u.email, u.first_name, u.last_name 
                                FROM loan_applications la 
                                JOIN users u ON la.user_id = u.id 
                                WHERE la.id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$loan) {
            throw new Exception("Loan not found.");
        }

        // Update loan status
        $stmt = $conn->prepare("UPDATE loan_applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $loan_id);
        $stmt->execute();
        $stmt->close();

        // Log action
        $stmt = $conn->prepare("INSERT INTO loan_audit_log (user_id, action, loan_id, details) VALUES (?, ?, ?, ?)");
        $action = $status === 'approved' ? 'approve' : 'reject';
        $details = ucfirst($status) . " loan of Ksh " . number_format($loan['amount'], 2) . " by Superadmin ID $admin_id.";
        $stmt->bind_param("isis", $admin_id, $action, $loan_id, $details);
        $stmt->execute();
        $stmt->close();

        // Send email notification
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'waithakas2003@gmail.com';
        $mail->Password = 'xaba hxxm aywg nufg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('waithakas2003@gmail.com', 'Chama Management System');
        $mail->addAddress($loan['email'], "{$loan['first_name']} {$loan['last_name']}");

        $mail->isHTML(true);
        $mail->Subject = 'Loan Application Status';
        $mail->Body = "Hi {$loan['first_name']} {$loan['last_name']},<br>Your loan application for Ksh " . number_format($loan['amount'], 2) . " has been $status.";
        $mail->send();

        $conn->commit();
        $_SESSION['success'] = "Loan $status successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to update loan status: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: /Chama-management-system/loans.php");
exit;
?>