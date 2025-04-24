<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // อนุญาตให้เข้าถึงจากทุกที่
header("Access-Control-Allow-Methods: GET");

// เชื่อมต่อฐานข้อมูล
include '../connection.php';


// ตรวจสอบว่ามี userId ถูกส่งมาหรือไม่
$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

if ($userId > 0) {
    // ดึงข้อมูลของผู้ใช้คนเดียว
    $stmt = $conn->prepare("SELECT * FROM users WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);
} else {
    // ดึงข้อมูลผู้ใช้ทั้งหมด
    $stmt = $conn->prepare("SELECT * FROM users");
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(["status" => "success", "data" => $users]);

$stmt->close();
$conn->close();
?>
