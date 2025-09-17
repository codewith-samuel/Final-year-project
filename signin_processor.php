<?php
session_start();
include 'db_connect.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : 'login';
    if ($action === 'forgot_password') {
        $email = trim($_POST['email']);
        $errors = [];
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $token, $expires_at);
                if ($stmt->execute()) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->SMTPDebug = 2; // Enable verbose debug output
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'waithakas2003@gmail.com'; // Replace with your Gmail address
                        $mail->Password = 'xaba hxxm aywg nufg'; // Replace with your Gmail App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->setFrom('your-email@gmail.com', 'Chama Management System');
                        $mail->addAddress($email);
                        $reset_link = "http://localhost:8000/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request';
                        $mail->Body = "Hello,<br><br>You requested a password reset. Click the link below to set a new password:<br><a href='$reset_link'>Reset Password</a><br><br>This link expires in 1 hour.<br><br>If you didn’t request this, ignore this email.<br><br>Best regards,<br>Chama Management System";
                        $mail->AltBody = "You requested a password reset. Copy and paste this link into your browser: $reset_link\nThis link expires in 1 hour.\nIf you didn’t request this, ignore this email.";
                        if ($mail->send()) {
                            $_SESSION['success'] = "A password reset link has been sent to your email.";
                        } else {
                            $errors[] = "Failed to send reset email. Please try again later.";
                            error_log("PHPMailer Error: {$mail->ErrorInfo}");
                        }
                    } catch (Exception $e) {
                        $errors[] = "Failed to send reset email. Please try again later.";
                        error_log("PHPMailer Exception: {$e->getMessage()}");
                    }
                } else {
                    $errors[] = "Failed to process reset request. Please try again.";
                }
                $stmt->close();
            } else {
                $errors[] = "No account found with that email.";
            }
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
        }
        header("Location: forgot_password.php");
        exit;
    } elseif ($action === 'reset_password') {
        $email = trim($_POST['email']);
        $token = trim($_POST['token']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $errors = [];
        if (empty($email) || empty($token) || empty($password) || empty($confirm_password)) {
            $errors[] = "All fields are required.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
            $stmt->bind_param("ss", $email, $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);
                if ($stmt->execute()) {
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND token = ?");
                    $stmt->bind_param("ss", $email, $token);
                    $stmt->execute();
                    $_SESSION['success'] = "Password reset successfully. Please sign in.";
                    header("Location: signin.php");
                } else {
                    $errors[] = "Failed to update password. Please try again.";
                }
            } else {
                $errors[] = "Invalid or expired reset link.";
            }
            $stmt->close();
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
        }
        exit;
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $errors = [];
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, password, role, approval_status, email_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    if ($user['email_verified'] != 1) {
                        $errors[] = "Please verify your email before signing in.";
                    } elseif ($user['approval_status'] !== 'approved') {
                        $_SESSION['error'] = "Your account is pending approval.";
                        header("Location: pending_approval.php?user_id=" . $user['id']);
                        exit;
                    } else {
                        $_SESSION['id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['success'] = "Signed in successfully!";
                        switch ($user['role']) {
                            case 'superadmin':
                                header("Location: admin_dashboard.php");
                                break;
                            case 'chairperson':
                                header("Location: chairperson_dashboard.php");
                                break;
                            case 'secretary':
                                header("Location: secretary_dashboard.php");
                                break;
                            case 'member':
                                header("Location: member_dashboard.php");
                                break;
                            default:
                                $errors[] = "Invalid user role.";
                        }
                        exit;
                    }
                } else {
                    $errors[] = "Invalid login credentials.";
                }
            } else {
                $errors[] = "Invalid login credentials.";
            }
            $stmt->close();
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: signin.php");
            exit;
        }
    }
}
$conn->close();
?>