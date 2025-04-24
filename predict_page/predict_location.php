<?php
include '../connection.php'; // เชื่อมต่อฐานข้อมูล

// ฟังก์ชันสำหรับรับภาพจากผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $image_path = $_FILES['image']['tmp_name'];

    // จำลองผลลัพธ์จากการวิเคราะห์ภาพ (สมมติว่าคุณมีโมเดล AI)
    $predicted_lat = 13.736717;  // Latitude ที่ทำนายได้จากภาพ
    $predicted_long = 100.523186; // Longitude ที่ทำนายได้จากภาพ

    // ค้นหาสถานที่ใกล้เคียงในฐานข้อมูลจาก latitude และ longitude ที่ทำนายได้
    $sql = "SELECT l.Location_Id, l.Location_Name, l.Location_Lat, l.Location_Long, l.Description, a.Street, a.District, a.Province
            FROM location l
            JOIN address a ON l.Address_Id = a.Address_Id
            WHERE ST_Distance_Sphere(point(l.Location_Long, l.Location_Lat), point(:long, :lat)) < 5000";  // 5 กม.

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lat' => $predicted_lat, ':long' => $predicted_long]);

    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($locations) {
        echo json_encode([
            'status' => 'success',
            'nearest_locations' => $locations
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบสถานที่ใกล้เคียง'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่ได้รับภาพ'
    ]);
}
?>
