<?php
session_start();
include 'db_connect.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'];

// Fetch user contribution total for loan eligibility
$contribution_query = $conn->prepare("SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND status = 'completed'");
$contribution_query->bind_param("i", $user_id);
$contribution_query->execute();
$total_contributions = $contribution_query->get_result()->fetch_assoc()['total'] ?? 0;
$contribution_query->close();

// Fetch account creation date for eligibility
$account_query = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$account_query->bind_param("i", $user_id);
$account_query->execute();
$account_created = $account_query->get_result()->fetch_assoc()['created_at'];
$account_query->close();
$account_age_days = (strtotime('now') - strtotime($account_created)) / (60 * 60 * 24);

// Loan criteria
$min_contributions = 2000; // KSh 2,000
$min_account_age_days = 90; // 3 months
$is_eligible = $total_contributions >= $min_contributions && $account_age_days >= $min_account_age_days;

// Handle loan application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_loan'])) {
    $amount = floatval($_POST['amount']);
    $category = $_POST['category'];
    $purpose = trim(strip_tags($_POST['purpose']));
    $repayment_period = intval($_POST['repayment_period']);
    
    // Validate repayment period based on category
    $valid_period = false;
    if ($category === 'Personal' && $repayment_period >= 3 && $repayment_period <= 12) {
        $valid_period = true;
    } elseif ($category === 'Emergency' && $repayment_period >= 1 && $repayment_period <= 6) {
        $valid_period = true;
    } elseif ($category === 'Business' && $repayment_period >= 6 && $repayment_period <= 24) {
        $valid_period = true;
    }
    
    if (!$is_eligible) {
        $_SESSION['error'] = "You are not eligible for a loan. Requirements: minimum KSh 2,000 in contributions and 3 months account age.";
    } elseif ($amount <= 0 || $amount > 50000) {
        $_SESSION['error'] = "Loan amount must be between KSh 1 and KSh 50,000.";
    } elseif (!$valid_period) {
        $_SESSION['error'] = "Invalid repayment period. Personal: 3-12 months, Emergency: 1-6 months, Business: 6-24 months.";
    } elseif (empty($purpose)) {
        $_SESSION['error'] = "Loan purpose is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO loan_applications (user_id, amount, category, purpose, repayment_period, applied_at, status) VALUES (?, ?, ?, ?, ?, NOW(), 'pending')");
        $stmt->bind_param("idssi", $user_id, $amount, $category, $purpose, $repayment_period);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Loan application submitted successfully. Awaiting approval.";
        } else {
            $_SESSION['error'] = "Failed to submit loan application.";
        }
        $stmt->close();
    }
    header("Location: loans.php");
    exit;
}

// Handle loan approval/rejection by chairperson or superadmin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($role, ['chairperson', 'superadmin'])) {
    $loan_id = intval($_POST['loan_id']);
    $action = $_POST['action'];
    $rejection_reason = isset($_POST['rejection_reason']) ? trim(strip_tags($_POST['rejection_reason'])) : null;
    
    // Fetch loan details for email and self-approval check
    $loan_query = $conn->prepare("SELECT la.user_id, la.amount, la.category, la.purpose, u.email, u.first_name, u.last_name 
                                  FROM loan_applications la 
                                  JOIN users u ON la.user_id = u.id 
                                  WHERE la.id = ?");
    $loan_query->bind_param("i", $loan_id);
    $loan_query->execute();
    $loan = $loan_query->get_result()->fetch_assoc();
    $loan_query->close();
    
    if ($loan) {
        // Prevent chairperson from approving/rejecting their own loan
        if ($role === 'chairperson' && $loan['user_id'] === $user_id) {
            $_SESSION['error'] = "You cannot approve or reject your own loan application.";
            header("Location: loans.php");
            exit;
        }
        
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $update_query = "UPDATE loan_applications SET status = ?, approved_by = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sisi", $status, $user_id, $rejection_reason, $loan_id);
        if ($stmt->execute()) {
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
                $mail->addAddress($loan['email'], $loan['first_name'] . ' ' . $loan['last_name']);
                $mail->isHTML(true);
                
                if ($action === 'approve') {
                    $mail->Subject = 'Loan Application Approved';
                    $mail->Body = "Hi {$loan['first_name']} {$loan['last_name']},<br><br>Your loan application for KSh " . number_format($loan['amount'], 2) . 
                                  " ({$loan['category']}) has been approved. Purpose: {$loan['purpose']}.<br>Please contact the treasurer for disbursement details.<br><br>Regards,<br>Chama Management System";
                } else {
                    $mail->Subject = 'Loan Application Rejected';
                    $mail->Body = "Hi {$loan['first_name']} {$loan['last_name']},<br><br>Your loan application for KSh " . number_format($loan['amount'], 2) . 
                                  " ({$loan['category']}) has been rejected. Reason: {$rejection_reason}.<br>Please address the issue and reapply if needed.<br><br>Regards,<br>Chama Management System";
                }
                
                $mail->send();
                $_SESSION['success'] = "Loan $status successfully. Email sent to user.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Loan $status, but email failed: {$mail->ErrorInfo}";
            }
        } else {
            $_SESSION['error'] = "Failed to update loan status.";
        }
        $stmt->close();
    }
    header("Location: loans.php");
    exit;
}

// Handle disbursement marking by chairperson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_disbursed']) && $role === 'chairperson') {
    $loan_id = intval($_POST['loan_id']);
    
    // Fetch loan details for email
    $loan_query = $conn->prepare("SELECT la.user_id, la.amount, la.category, u.email, u.first_name, u.last_name 
                                  FROM loan_applications la 
                                  JOIN users u ON la.user_id = u.id 
                                  WHERE la.id = ? AND la.status = 'approved'");
    $loan_query->bind_param("i", $loan_id);
    $loan_query->execute();
    $loan = $loan_query->get_result()->fetch_assoc();
    $loan_query->close();
    
    if ($loan) {
        $stmt = $conn->prepare("UPDATE loan_applications SET disbursement_status = 'Disbursed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        if ($stmt->execute()) {
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
                $mail->addAddress($loan['email'], $loan['first_name'] . ' ' . $loan['last_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Loan Disbursement Notification';
                $mail->Body = "Hi {$loan['first_name']} {$loan['last_name']},<br><br>Your loan of KSh " . number_format($loan['amount'], 2) . 
                              " ({$loan['category']}) has been marked as disbursed. Please contact the treasurer to collect your funds or confirm receipt.<br><br>Regards,<br>Chama Management System";
                
                $mail->send();
                $_SESSION['success'] = "Loan marked as disbursed. Email sent to user.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Loan marked as disbursed, but email failed: {$mail->ErrorInfo}";
            }
        } else {
            $_SESSION['error'] = "Failed to mark loan as disbursed.";
        }
        $stmt->close();
    }
    header("Location: loans.php");
    exit;
}

// Fetch user's loan applications
$user_loans_query = $conn->prepare("SELECT la.id, la.amount, la.category, la.purpose, la.repayment_period, la.applied_at, la.status, la.rejection_reason, la.disbursement_status 
                                   FROM loan_applications la 
                                   WHERE la.user_id = ? 
                                   ORDER BY la.applied_at DESC");
$user_loans_query->bind_param("i", $user_id);
$user_loans_query->execute();
$user_loans = $user_loans_query->get_result()->fetch_all(MYSQLI_ASSOC);
$user_loans_query->close();

// Fetch loans for chairperson: split into pending, approved (pending disbursement), and disbursed
$pending_loans = $approved_pending_loans = $disbursed_loans = [];
if ($role === 'chairperson') {
    $all_loans_query = $conn->prepare("SELECT la.id, la.user_id, la.amount, la.category, la.purpose, la.repayment_period, la.applied_at, la.status, la.rejection_reason, la.disbursement_status, 
                                      u.first_name, u.last_name, u.username 
                                      FROM loan_applications la 
                                      JOIN users u ON la.user_id = u.id 
                                      WHERE la.user_id != ? 
                                      ORDER BY la.applied_at DESC");
    $all_loans_query->bind_param("i", $user_id);
    $all_loans_query->execute();
    $all_loans = $all_loans_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $all_loans_query->close();
    
    // Categorize loans
    foreach ($all_loans as $loan) {
        if ($loan['status'] === 'pending') {
            $pending_loans[] = $loan;
        } elseif ($loan['status'] === 'approved' && $loan['disbursement_status'] === 'Pending') {
            $approved_pending_loans[] = $loan;
        } elseif ($loan['disbursement_status'] === 'Disbursed') {
            $disbursed_loans[] = $loan;
        }
    }
}

// Fetch all pending and approved loans for superadmin
$superadmin_loans = [];
if ($role === 'superadmin') {
    $superadmin_loans_query = $conn->prepare("SELECT la.id, la.user_id, la.amount, la.category, la.purpose, la.repayment_period, la.applied_at, la.status, la.rejection_reason, la.disbursement_status, 
                                             u.first_name, u.last_name, u.username 
                                             FROM loan_applications la 
                                             JOIN users u ON la.user_id = u.id 
                                             WHERE la.status IN ('pending', 'approved') 
                                             ORDER BY la.applied_at DESC");
    $superadmin_loans_query->execute();
    $superadmin_loans = $superadmin_loans_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $superadmin_loans_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - Chama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background: #f4f7fa; }
        .sidebar {
            width: 250px; height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0;
            padding: 20px; color: #ecf0f1; box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 5px; display: block; }
        .sidebar a:hover { background: #34495e; }
        .container { margin-left: 270px; padding: 40px; min-height: 100vh; }
        .section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        textarea { resize: vertical; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1 class="text-white mb-4">Chama Management</h1>
        <a href="<?php echo $role === 'superadmin' ? 'admin_dashboard.php' : ($role === 'chairperson' ? 'chairperson_dashboard.php' : 'member_dashboard.php'); ?>">Dashboard</a>
        <a href="meetings.php?view=upcoming">Upcoming Meetings</a>
        <a href="meetings.php?view=past">Past Meetings</a>
        <?php if ($role === 'superadmin'): ?>
            <a href="meetings.php">Manage Meetings</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_removal.php">Manage Removals</a>
            <a href="pending_members.php">Pending Members</a>
            <a href="manage_apologies.php">Manage Apologies</a>
        <?php endif; ?>
        <a href="make_contribution.php">Contribute</a>
        <a href="financials.php">Financials</a>
        <a href="reports.php">Reports</a>
        <a href="loans.php">Loans</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="header mb-4">
            <h2>Loan Management</h2>
        </div>
        <div class="section">
            <h3 class="mb-3">Apply for a Loan</h3>
            <div class="section-content">
                <?php if ($is_eligible): ?>
                    <form method="POST" action="loans.php" class="row g-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Loan Amount (KSh):</label>
                            <input type="number" name="amount" id="amount" class="form-control" min="1" max="50000" required>
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Loan Category:</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="Personal">Personal (3-12 months)</option>
                                <option value="Emergency">Emergency (1-6 months)</option>
                                <option value="Business">Business (6-24 months)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="purpose" class="form-label">Purpose:</label>
                            <textarea name="purpose" id="purpose" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="repayment_period" class="form-label">Repayment Period (Months):</label>
                            <input type="number" name="repayment_period" id="repayment_period" class="form-control" min="1" max="24" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="apply_loan" class="btn btn-primary">Submit Application</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        You are not eligible to apply for a loan. Requirements: minimum KSh 2,000 in contributions and 3 months account age.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="section">
            <h3 class="mb-3">My Loan Applications</h3>
            <div class="section-content">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-primary">
                            <tr>
                                <th>Amount</th>
                                <th>Category</th>
                                <th>Purpose</th>
                                <th>Repayment Period</th>
                                <th>Applied At</th>
                                <th>Status</th>
                                <th>Rejection Reason</th>
                                <th>Disbursement Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($user_loans)): ?>
                                <?php foreach ($user_loans as $loan): ?>
                                    <tr>
                                        <td>KSh <?php echo number_format($loan['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                        <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($loan['status'])); ?></td>
                                        <td><?php echo htmlspecialchars($loan['rejection_reason'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($loan['disbursement_status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No loan applications found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if ($role === 'chairperson'): ?>
            <div class="section">
                <h3 class="mb-3">Pending Loan Applications</h3>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Purpose</th>
                                    <th>Repayment Period</th>
                                    <th>Applied At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pending_loans)): ?>
                                    <?php foreach ($pending_loans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'] . ' (' . $loan['username'] . ')'); ?></td>
                                            <td>KSh <?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                            <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                            <td>
                                                <form method="POST" action="loans.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this loan?');">
                                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <form method="POST" action="loans.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this loan?');">
                                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="text" name="rejection_reason" class="form-control d-inline-block w-auto" placeholder="Reason for rejection" required>
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No pending loan applications</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="section">
                <h3 class="mb-3">Approved Loans (Pending Disbursement)</h3>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Purpose</th>
                                    <th>Repayment Period</th>
                                    <th>Applied At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($approved_pending_loans)): ?>
                                    <?php foreach ($approved_pending_loans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'] . ' (' . $loan['username'] . ')'); ?></td>
                                            <td>KSh <?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                            <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                            <td>
                                                <form method="POST" action="loans.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to mark this loan as disbursed?');">
                                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                    <button type="submit" name="mark_disbursed" class="btn btn-primary btn-sm">Mark as Disbursed</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No approved loans pending disbursement</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="section">
                <h3 class="mb-3">Disbursed Loans</h3>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Purpose</th>
                                    <th>Repayment Period</th>
                                    <th>Applied At</th>
                                    <th>Disbursement Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($disbursed_loans)): ?>
                                    <?php foreach ($disbursed_loans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'] . ' (' . $loan['username'] . ')'); ?></td>
                                            <td>KSh <?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                            <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['disbursement_status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">No disbursed loans</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($role === 'superadmin'): ?>
            <div class="section">
                <h3 class="mb-3">Admin Loan Management</h3>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Purpose</th>
                                    <th>Repayment Period</th>
                                    <th>Applied At</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($superadmin_loans)): ?>
                                    <?php foreach ($superadmin_loans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'] . ' (' . $loan['username'] . ')'); ?></td>
                                            <td>KSh <?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['category']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['repayment_period']); ?> months</td>
                                            <td><?php echo htmlspecialchars($loan['applied_at']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($loan['status'])); ?></td>
                                            <td>
                                                <?php if ($loan['status'] === 'pending'): ?>
                                                    <form method="POST" action="loans.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this loan?');">
                                                        <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <form method="POST" action="loans.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this loan?');">
                                                        <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="text" name="rejection_reason" class="form-control d-inline-block w-auto" placeholder="Reason for rejection" required>
                                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Action completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No loan applications to manage</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php
$conn->close();
?>