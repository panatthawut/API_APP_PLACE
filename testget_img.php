<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล


$sql = "SELECT * FROM location_image";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $imgs = array();
    while($row = $result->fetch_assoc()) {
        $imgs[] = $row;
    }
    echo json_encode($imgs); // แปลงข้อมูลเป็น JSON
} else {
    echo json_encode([]); // ถ้าไม่มีข้อมูล ให้ส่ง array ว่าง
}
$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>