<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member' || !isset($_SESSION['id'])) {
    header("Location: ../signin.html");
    exit;
}

include 'db_connect.php';

$member_id = $_SESSION['id'];

// Validate form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'loan_amount', FILTER_VALIDATE_FLOAT);
    $purpose = filter_input(INPUT_POST, 'purpose', FILTER_SANITIZE_STRING);
    $repayment_period = filter_input(INPUT_POST, 'repayment_period', FILTER_VALIDATE_INT);

    // Fetch eligibility
    $eligibility_query = $conn->prepare("SELECT is_eligible, max_loan_amount FROM loan_eligibility WHERE user_id = ?");
    $eligibility_query->bind_param("i", $member_id);
    $eligibility_query->execute();
    $eligibility = $eligibility_query->get_result()->fetch_assoc();
    $is_eligible = $eligibility['is_eligible'] ?? 0;
    $max_loan_amount = $eligibility['max_loan_amount'] ?? 0;
    $eligibility_query->close();

    // Fetch approval status
    $approval_query = $conn->prepare("SELECT approval_status FROM users WHERE id = ?");
    $approval_query->bind_param("i", $member_id);
    $approval_query->execute();
    $approval_status = $approval_query->get_result()->fetch_assoc()['approval_status'];
    $approval_query->close();

    // Validate inputs
    if ($approval_status !== 'approved') {
        $_SESSION['error'] = "Your membership is not approved.";
    } elseif (!$is_eligible) {
        $_SESSION['error'] = "You are not eligible for a loan.";
    } elseif (!$amount || $amount <= 0 || $amount > $max_loan_amount) {
        $_SESSION['error'] = "Invalid loan amount. Must be between 1 and " . number_format($max_loan_amount, 2) . " Ksh.";
    } elseif (!$purpose) {
        $_SESSION['error'] = "Purpose is required.";
    } elseif (!$repayment_period || $repayment_period < 1 || $repayment_period > 60) {
        $_SESSION['error'] = "Invalid repayment period (1-60 months).";
    } else {
        // Insert loan application
        $stmt = $conn->prepare("INSERT INTO loan_applications (user_id, amount, status, purpose, repayment_period, applied_at) 
                                VALUES (?, ?, 'pending', ?, ?, NOW())");
        $stmt->bind_param("idss", $member_id, $amount, $purpose, $repayment_period);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Loan application submitted successfully.";
        } else {
            $_SESSION['error'] = "Failed to submit loan application. Please try again.";
        }
        $stmt->close();
    }
}

header("Location: member_dashboard.php");
exit;
?>