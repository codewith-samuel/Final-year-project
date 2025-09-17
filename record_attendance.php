<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.html");
    exit;
}

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_id'], $_POST['attendance'])) {
    $meeting_id = $_POST['meeting_id'];
    $attendance = $_POST['attendance'];

    // Validate meeting exists, is in-person, and is today or in the future
    $stmt = $conn->prepare("SELECT id FROM meetings WHERE id = ? AND online_link IS NULL AND DATE(meeting_date) >= CURDATE()");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['error'] = "Invalid or past meeting selected.";
        header("Location: /Chama-management-system/meetings.php");
        exit;
    }
    $stmt->close();

    // Check if attendance already recorded
    $stmt = $conn->prepare("SELECT id FROM meeting_attendance WHERE meeting_id = ? LIMIT 1");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Attendance for this meeting has already been recorded.";
        header("Location: /Chama-management-system/meetings.php");
        exit;
    }
    $stmt->close();

    // Fetch approved apologies for this meeting
    $stmt = $conn->prepare("SELECT user_id FROM apologies WHERE meeting_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $approved_apologies = array_column($result->fetch_all(MYSQLI_ASSOC), 'user_id');
    $stmt->close();

    // Begin transaction
    $conn->begin_transaction();
    try {
        foreach ($attendance as $user_id => $status) {
            $is_present = isset($status['present']) ? 1 : 0;
            $is_absent = isset($status['absent']) ? 1 : 0;
            $has_apology = isset($status['has_apology']) && in_array($user_id, $approved_apologies) ? 1 : 0;

            // Validate: only one state allowed
            $checked_count = $is_present + $is_absent + $has_apology;
            if ($checked_count > 1) {
                throw new Exception("Invalid attendance: multiple states selected for user $user_id.");
            }

            // Set defaults
            if ($has_apology) {
                $is_present = 0; // Absent with apology implies not present
            } elseif ($is_absent) {
                $is_present = 0; // Absent implies not present
            }

            // Determine status
            $status_value = $has_apology ? 'absent_with_apology' : ($is_present ? 'present' : 'absent');

            // Insert record
            $stmt = $conn->prepare("INSERT INTO meeting_attendance (meeting_id, user_id, is_present, has_apology, recorded_at, status) VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iiiis", $meeting_id, $user_id, $is_present, $has_apology, $status_value);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        $_SESSION['success'] = "Attendance recorded successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to record attendance: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

$conn->close();
header("Location: /Chama-management-system/meetings.php");
exit;
?>