<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
    exit;
}

$notification_id = $data['notification_id'];

// อัปเดตให้แจ้งเตือนเป็น "อ่านแล้ว"
$query = "UPDATE notifications SET Is_Read = 1 WHERE Notification_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notification_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>
