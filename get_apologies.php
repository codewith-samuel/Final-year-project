<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'db_connect.php';

if (!isset($_GET['meeting_id']) || !is_numeric($_GET['meeting_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid meeting ID']);
    exit;
}

$meeting_id = $_GET['meeting_id'];
$stmt = $conn->prepare("SELECT user_id FROM apologies WHERE meeting_id = ? AND status = 'approved'");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$result = $stmt->get_result();
$apologies = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($apologies);
?>