
<?php
include 'db_connect.php';

// Fetch online meetings that ended within the last 5 minutes
$meetings = $conn->query("SELECT id, meeting_date FROM meetings WHERE online_link IS NOT NULL AND meeting_date <= NOW() AND DATE_ADD(meeting_date, INTERVAL 1 HOUR) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

while ($meeting = $meetings->fetch_assoc()) {
    $meeting_id = $meeting['id'];
    $meeting_end = date('Y-m-d H:i:s', strtotime($meeting['meeting_date'] . ' +1 hour'));

    // Fetch approved users
    $users = $conn->query("SELECT id FROM users WHERE approval_status = 'approved' AND approved_by_chairperson = 1 AND approved_by_superadmin = 1 AND created_at < '{$meeting['meeting_date']}'");
    $user_ids = array_column($users->fetch_all(MYSQLI_ASSOC), 'id');

    // Fetch approved apologies
    $stmt = $conn->prepare("SELECT user_id FROM apologies WHERE meeting_id = ? AND status = 'approved' AND approved_by_chairperson = 1 AND approved_by_superadmin = 1");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    $apologies = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');
    $stmt->close();

    // Fetch already recorded attendance
    $stmt = $conn->prepare("SELECT user_id FROM attendance WHERE meeting_id = ?");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    $recorded = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');
    $stmt->close();

    // Mark absent or absent_with_apology for unrecorded users
    $conn->begin_transaction();
    try {
        foreach ($user_ids as $user_id) {
            if (!in_array($user_id, $recorded)) {
                $status = in_array($user_id, $apologies) ? 'absent_with_apology' : 'absent';
                $stmt = $conn->prepare("INSERT INTO attendance (user_id, meeting_id, attended_at, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $meeting_id, $meeting_end, $status);
                $stmt->execute();
                $stmt->close();
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error marking absent users for meeting $meeting_id: " . $e->getMessage());
    }
}

$conn->close();
?>
