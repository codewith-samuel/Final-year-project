
<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include 'db_connect.php';

$meeting_id = $_GET['id'] ?? '';
$user_id = $_SESSION['id'];

if (!$meeting_id) {
    $_SESSION['error'] = "Invalid meeting.";
    header("Location: /Chama-management-system/meetings.php");
    exit;
}

// Validate meeting is online and ongoing
$stmt = $conn->prepare("SELECT online_link, meeting_date FROM meetings WHERE id = ? AND online_link IS NOT NULL AND meeting_date <= NOW() AND DATE_ADD(meeting_date, INTERVAL 1 HOUR) >= NOW()");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Meeting is not online or not currently active.";
    header("Location: /Chama-management-system/meetings.php");
    exit;
}
$meeting = $result->fetch_assoc();
$online_link = $meeting['online_link'];
$stmt->close();

// Check if user is approved
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND approval_status = 'approved' AND approved_by_chairperson = 1 AND approved_by_superadmin = 1 AND created_at < ?");
$stmt->bind_param("is", $user_id, $meeting['meeting_date']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = "You are not authorized to join this meeting.";
    header("Location: /Chama-management-system/meetings.php");
    exit;
}
$stmt->close();

// Check if attendance already recorded
$stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND meeting_id = ?");
$stmt->bind_param("ii", $user_id, $meeting_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Attendance already recorded.";
    header("Location: $online_link");
    exit;
}
$stmt->close();

// Record attendance as present
$stmt = $conn->prepare("INSERT INTO attendance (user_id, meeting_id, attended_at, status) VALUES (?, ?, NOW(), 'present')");
$stmt->bind_param("ii", $user_id, $meeting_id);
if ($stmt->execute()) {
    $_SESSION['success'] = "Successfully joined meeting.";
} else {
    $_SESSION['error'] = "Error recording attendance: " . $stmt->error;
    header("Location: /Chama-management-system/meetings.php");
    exit;
}
$stmt->close();

$conn->close();
header("Location: $online_link");
exit;
?>
