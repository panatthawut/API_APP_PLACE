<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

try {
    // ดึงข้อมูลจาก location_image และ Location_Name จาก location
    $sql = "
        SELECT li.Image_Id, li.Image_Path, l.Location_Name 
        FROM location_image li
        JOIN location l ON li.Location_Id = l.Location_Id
        WHERE li.Is_Verified = 0
    ";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Failed to fetch unverified images');
    }

    $images = [];
    while ($row = $result->fetch_assoc()) {
        // เพิ่ม full path ของ image path ในการตอบกลับ
        $row['Image_Path'] = $row['Image_Path'];
        $images[] = $row;
    }

    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode($images);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
