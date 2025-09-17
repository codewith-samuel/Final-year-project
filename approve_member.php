
<?php
session_start();
include('db_connect.php');
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['superadmin', 'chairperson']) || !isset($_SESSION['id'])) {
    header("Location: ../signin.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $role = $_SESSION['role'];

    $column = $role === 'superadmin' ? 'approved_by_superadmin' : 'approved_by_chairperson';
    $status = $action === 'approve' ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET $column = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    $stmt->execute();
    $stmt->close();

    // Fetch user details
    $stmt = $conn->prepare("SELECT email, first_name, last_name, approved_by_chairperson, approved_by_superadmin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Update approval_status
    if ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET approval_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Send rejection email
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
            $mail->addAddress($user['email'], "{$user['first_name']} {$user['last_name']}");

            $mail->isHTML(true);
            $mail->Subject = 'Membership Application Status';
            $mail->Body = "Hi {$user['first_name']} {$user['last_name']},<br>Your membership application has been rejected by the $role.";

            $mail->send();
            $_SESSION['success'] = "Membership rejected successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Membership rejected, but failed to send email: {$mail->ErrorInfo}";
        }
    } elseif ($user['approved_by_chairperson'] && $user['approved_by_superadmin']) {
        $stmt = $conn->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Send approval email
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
            $mail->addAddress($user['email'], "{$user['first_name']} {$user['last_name']}");

            $mail->isHTML(true);
            $mail->Subject = 'Membership Application Approved';
            $mail->Body = "Hi {$user['first_name']} {$user['last_name']},<br>Your membership application has been approved. You can now log in to the Chama Management System.";

            $mail->send();
            $_SESSION['success'] = "Membership approved successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Membership approved, but failed to send email: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['success'] = "Action recorded. Awaiting further approval.";
    }

    header("Location: pending_members.php");
    exit;
}

$conn->close();
?>
