<?php
session_start();
include 'db_connect.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = trim($_POST['phone_number']);
    $national_id = trim($_POST['national_id']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $role = $_POST['role'];

    $errors = [];

    // Server-side validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || 
        empty($confirm_password) || empty($phone_number) || empty($national_id) || empty($address) || 
        empty($date_of_birth) || empty($gender)) {
        $errors[] = "All fields are required.";
    }

    if (!preg_match("/^[A-Za-z]{2,50}$/", $first_name)) {
        $errors[] = "First name must be 2–50 letters only.";
    }

    if (!preg_match("/^[A-Za-z]{2,50}$/", $last_name)) {
        $errors[] = "Last name must be 2–50 letters only.";
    }

    if (!preg_match("/^[A-Za-z0-9]{3,30}$/", $username)) {
        $errors[] = "Username must be 3–30 alphanumeric characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $password)) {
        $errors[] = "Password must be at least 8 characters, including a letter and a number.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!preg_match("/^254[0-9]{9}$/", $phone_number)) {
        $errors[] = "Phone number must be in format 254 followed by 9 digits.";
    }

    if (!preg_match("/^[0-9]{6,20}$/", $national_id)) {
        $errors[] = "National ID must be 6–20 alphanumeric characters.";
    }
// string length
    if (strlen($address) < 10 || strlen($address) > 255) {
        $errors[] = "Address must be 10–255 characters.";
    }

    $dob = new DateTime($date_of_birth);
    $today = new DateTime();
    $min_age_date = (new DateTime())->modify('-18 years');
    if ($dob >= $today || $dob > $min_age_date) {
        $errors[] = "You must be at least 18 years old and born in the past.";
    }

    if (!in_array($gender, ['male', 'female', 'other'])) {
        $errors[] = "Please select a valid gender.";
    }

    // Check for duplicate email or username
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    if (!$stmt) {
        $errors[] = "Database prepare error: " . $conn->error;
    } else {
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email or username already exists.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));

        // Insert all fields into the database
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password, phone_number, national_id, address, date_of_birth, gender, verification_token, role, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            $errors[] = "Database prepare error: " . $conn->error;
        } else {
            // Fixed: Use 12 's' for 12 variables
            $stmt->bind_param("ssssssssssss", $first_name, $last_name, $username, $email, $hashed_password, $phone_number, $national_id, $address, $date_of_birth, $gender, $verification_token, $role);
            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'waithakas2003@gmail.com';
                    $mail->Password = 'xaba hxxm aywg nufg'; // This is a valid app-specific password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('waithakas2003@gmail.com', 'Chama Management System');
                    $mail->addAddress($email, "$first_name $last_name");

                    $mail->isHTML(true);
                    $mail->Subject = 'Verify Your Account';
                    $mail->Body = "Hi $first_name $last_name,<br>Please verify your account by clicking the link below:<br><a href='http://localhost/Chama-management-system/verify_email.php?token=$verification_token'>Verify Account</a>";

                    $mail->send();
                    $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
                    header("Location: signin.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = "Registration successful, but failed to send verification email: {$mail->ErrorInfo}";
                    header("Location: signup.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Registration failed: " . $stmt->error;
                header("Location: signup.php");
                exit;
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: signup.php");
        exit;
    }
}

$conn->close();
?>