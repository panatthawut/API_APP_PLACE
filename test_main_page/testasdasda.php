<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include '../connection.php'; // ไฟล์เชื่อมต่อฐานข้อมูล>
$sql = "SELECT Location_Id, Location_Name, Type_Id FROM `location`;";
$result = $conn->query($sql);

$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// ปิดการเชื่อมต่อ
$conn->close();

// ส่งข้อมูลเป็น JSON
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>