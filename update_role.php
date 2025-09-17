
<?php
session_start();
include('db_connect.php');
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin' || !isset($_SESSION['id'])) {
    header("Location: ../signin.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $superadmin_id = $_SESSION['id'];

    // Validate input
    if (!in_array($new_role, ['member', 'chairperson', 'secretary'])) {
        $_SESSION['error'] = "Invalid role selected.";
        header("Location: admin_dashboard.php");
        exit;
    }

    // Fetch user details
    $stmt_user = $conn->prepare("SELECT username, email, first_name, last_name, role FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result = $stmt_user->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $current_role = $user['role'];

        if ($current_role === $new_role) {
            $_SESSION['error'] = "User already has the role: $new_role.";
            $stmt_user->close();
            header("Location: admin_dashboard.php");
            exit;
        }

        // Update user role
        $stmt_update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_role, $user_id);
        $update_success = $stmt_update->execute();
        $stmt_update->close();

        if ($update_success) {
            // Log role change
            $stmt_log = $conn->prepare("INSERT INTO role_changes (user_id, new_role, changed_by) VALUES (?, ?, ?)");
            $stmt_log->bind_param("isi", $user_id, $new_role, $superadmin_id);
            $stmt_log->execute();
            $stmt_log->close();

            // Send email notification
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
                $mail->Subject = 'Your Role Has Been Updated';
                $mail->Body = "Hi {$user['first_name']} {$user['last_name']},<br>Your role has been updated to <strong>$new_role</strong> by the Super Admin.";

                $mail->send();
                $_SESSION['success'] = "Role updated successfully. Notification sent to {$user['email']}.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Role updated, but failed to send email: {$mail->ErrorInfo}";
            }
        } else {
            $_SESSION['error'] = "Failed to update role.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }

    $stmt_user->close();
    header("Location: admin_dashboard.php");
    exit;
}

$conn->close();
?>
