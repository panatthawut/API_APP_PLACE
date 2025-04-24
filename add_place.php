<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล


// รับข้อมูลจาก Flutter
$place_name = $_POST['name'];
$place_description = $_POST['description'];
$place_image = "default_image_path.jpg"; // หากยังไม่มีภาพให้ใช้ default

// เพิ่มข้อมูลสถานที่ใหม่
$sql = "INSERT INTO places (name, description, image_path) VALUES ('$place_name', '$place_description', '$place_image')";
if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => 'Place added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding place']);
}

$conn->close();
?>
