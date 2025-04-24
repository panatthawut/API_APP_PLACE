<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['Location_Id']) || !isset($data['User_Id']) || !isset($data['Rating'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
    exit;
}

$location_id = $data['Location_Id'];
$user_id = $data['User_Id'];
$rating = $data['Rating'];
$comment = isset($data['Comment']) ? $data['Comment'] : NULL;

// ตรวจสอบว่าผู้ใช้เคยรีวิวสถานที่นี้หรือไม่
$check_query = "SELECT * FROM reviews WHERE Location_Id = ? AND User_Id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $location_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ถ้ามีรีวิวอยู่แล้ว ให้แก้ไขรีวิวเดิม
    $update_query = "UPDATE reviews SET Rating = ?, Comment = ?, Updated_At = CURRENT_TIMESTAMP 
                     WHERE Location_Id = ? AND User_Id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("isii", $rating, $comment, $location_id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'อัปเดตรีวิวสำเร็จ']);
} else {
    // ถ้ายังไม่มีรีวิว ให้เพิ่มรีวิวใหม่
    $insert_query = "INSERT INTO reviews (Location_Id, User_Id, Rating, Comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiis", $location_id, $user_id, $rating, $comment);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'เพิ่มรีวิวสำเร็จ']);
}
?>
