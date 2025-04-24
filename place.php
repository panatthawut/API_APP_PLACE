<?php
// เปิดการแสดงข้อผิดพลาดสำหรับการดีบัก (สามารถปิดหลังจากแก้ไขเสร็จแล้ว)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// รับพารามิเตอร์การแบ่งหน้า (pagination) จาก URL
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20; // เปลี่ยนเป็น 20 เพื่อโหลดมากขึ้น
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// ปรับปรุง SQL Query เพื่อดึงข้อมูลสถานที่และรูปภาพ
$sql = "SELECT 
            location.Location_Id,
            location.Location_Name,
            location.Description,
            location.Location_Lat,
            location.Location_Long,
            location_image.Image_Path
        FROM 
            location
        LEFT JOIN 
            location_image 
        ON 
            location.Location_Id = location_image.Location_Id
        WHERE location.Status_Location = 1 
        GROUP BY location.Location_Id
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$place = array();

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $place[] = $row;
        }
    }
} else {
    // ถ้ามีข้อผิดพลาดใน SQL
    echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
    exit();
}

// ดึงจำนวนทั้งหมดของสถานที่
$count_sql = "SELECT COUNT(*) as total FROM location";
$count_result = $conn->query($count_sql);
$total = 0;
if ($count_result) {
    $count_row = $count_result->fetch_assoc();
    $total = $count_row['total'];
} else {
    echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
    exit();
}

$response = array(
    'places' => $place,
    'total' => $total
);

// ตั้งค่า header เป็น JSON
header('Content-Type: application/json');
echo json_encode($response);

// ปิดการเชื่อมต่อ
$conn->close();
?>
