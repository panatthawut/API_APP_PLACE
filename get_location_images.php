<?php
// get_location_images.php

include 'connection.php'; // เชื่อมต่อฐานข้อมูล

$location_id = $_GET['location_id'];
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 50; // แสดงทีละ 50 รูป

// Query สำหรับดึงรูปภาพ
$sql = "SELECT * FROM location_image WHERE Location_Id = ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $location_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$images = [];

while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Query สำหรับนับจำนวนรูปภาพทั้งหมด
$sql_count = "SELECT COUNT(*) AS total_images FROM location_image WHERE Location_Id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $location_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_images = $result_count->fetch_assoc()['total_images'];

// ส่งข้อมูลในรูปแบบ JSON
echo json_encode([
    'images' => $images,
    'total_images' => $total_images
]);

$stmt->close();
$stmt_count->close();
$conn->close();

?>
