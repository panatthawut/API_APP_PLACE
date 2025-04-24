<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
include '../connection.php'; // เชื่อมต่อฐานข้อมูล

// รับค่าพารามิเตอร์จากการค้นหา
$search = isset($_GET['query']) ? "%" . $_GET['query'] . "%" : "%";

$conn->set_charset("utf8");

// ค้นหาข้อมูลสถานที่ที่มีชื่อคล้ายกับคำค้นหา
$sql = "SELECT l.Location_Id, l.Location_Name, l.Location_Lat, l.Location_Long, l.Description, 
               (SELECT li.Image_Path FROM location_image li WHERE li.Location_Id = l.Location_Id LIMIT 1) AS Image_Path
        FROM location l
        WHERE l.Location_Name LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$stmt->close();
$conn->close();

// ส่งข้อมูลเป็น JSON
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
