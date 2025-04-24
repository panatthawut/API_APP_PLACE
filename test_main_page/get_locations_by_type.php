<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
include '../connection.php'; // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่ามีการส่ง type_id มาหรือไม่
$type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;

if ($type_id > 0) {
    // ตั้งค่าการเชื่อมต่อฐานข้อมูลให้ใช้ UTF-8
    $conn->set_charset("utf8");

    // ดึงข้อมูลสถานที่ตาม type_id พร้อมกับ image_path
    $sql = "SELECT l.Location_Id, l.Location_Name, l.Location_Lat, l.Location_Long, l.Description, l.Location_Class, l.Status_Location, 
                   (SELECT li.Image_Path FROM location_image li WHERE li.Location_Id = l.Location_Id LIMIT 1) AS Image_Path
            FROM location l
            WHERE l.Type_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $type_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();

    // ส่งข้อมูลเป็น JSON
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "Invalid type_id"]);
}
?>