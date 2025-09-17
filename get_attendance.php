
<?php
include 'db_connect.php';

if (!isset($_GET['meeting_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Meeting ID not provided']);
    exit;
}

$meeting_id = intval($_GET['meeting_id']);

// Check if the meeting is online or physical
$meeting_query = $conn->prepare("SELECT online_link FROM meetings WHERE id = ?");
$meeting_query->bind_param("i", $meeting_id);
$meeting_query->execute();
$meeting = $meeting_query->get_result()->fetch_assoc();
$is_online = !empty($meeting['online_link']);
$meeting_query->close();

// Fetch approved users and their attendance status
$attendance_query = $conn->prepare("
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name,
           COALESCE(
               (SELECT status FROM " . ($is_online ? "attendance" : "meeting_attendance") . " a WHERE a.meeting_id = ? AND a.user_id = u.id),
               (SELECT CASE 
                   WHEN ap.approved_by_superadmin = 1 AND ap.approved_by_chairperson = 1 THEN 'absent_with_apology'
                   ELSE NULL
               END
               FROM apologies ap
               WHERE ap.meeting_id = ? AND ap.user_id = u.id)
           ) AS status
    FROM users u
    WHERE u.approval_status = 'Approved'
    ORDER BY name
");
$attendance_query->bind_param("ii", $meeting_id, $meeting_id);
$attendance_query->execute();
$attendance_result = $attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);
$attendance_query->close();

header('Content-Type: application/json');
echo json_encode($attendance_result);
?>
