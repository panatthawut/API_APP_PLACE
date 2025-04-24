<?php
include '../connection.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// คำสั่ง SQL เพื่อดึง 5 สถานที่ที่มีคะแนนรีวิวเฉลี่ยสูงสุด
$sql = "SELECT image_path FROM images_general WHERE type = 'banner'";

$result = $conn->query($sql);

$banners = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $banners[] = $row['image_path'];
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ส่งข้อมูลกลับเป็น JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'banners' => $banners]);
?>