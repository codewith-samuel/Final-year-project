<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'chairperson' || !isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $reason = trim($_POST['reason']);
    $chairperson_id = $_SESSION['id'];

    // Validate reason
    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a reason for the removal request.";
        header("Location: manage_members.php");
        exit;
    }

    // Check if user exists and is a member
    $stmt = $conn->prepare("SELECT created_at, (SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND status = 'completed') AS total_contributions 
                            FROM users u WHERE id = ? AND role = 'member'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Member not found or invalid.";
        header("Location: manage_members.php");
        exit;
    }

    // Check removal criteria
    $user = $result->fetch_assoc();
    $account_age_days = (strtotime('now') - strtotime($user['created_at'])) / (60 * 60 * 24);
    $total_contributions = $user['total_contributions'] ?? 0;
    if ($total_contributions >= 5000 && $account_age_days >= 90) {
        $_SESSION['error'] = "Member does not meet removal criteria (contributions >= 5000 Ksh or account age >= 90 days).";
        header("Location: manage_members.php");
        exit;
    }
    $stmt->close();

    // Insert removal request
    $stmt = $conn->prepare("INSERT INTO removal_requests (user_id, requested_by, reason) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $chairperson_id, $reason);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Removal request submitted. Awaiting Super Admin approval.";
    } else {
        $_SESSION['error'] = "Error submitting removal request: " . $stmt->error;
    }
    $stmt->close();
    header("Location: manage_members.php");
    exit;
}

$conn->close();
?>