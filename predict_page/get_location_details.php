<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include '../connection.php'; // เชื่อมต่อฐานข้อมูล

if (isset($_GET['Location_Class'])) {
    $location_class = $_GET['Location_Class'];
    
    // จำนวนภาพที่จะโหลด
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    // ป้องกัน SQL Injection โดยใช้ prepared statements
    $sql = "
        SELECT l.*, a.Street, a.Sub_Dist, a.District, a.Province, a.Zip_Code
        FROM location l
        LEFT JOIN address a ON l.Address_Id = a.Address_Id
        WHERE l.Location_Class = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $location_class);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $locationDetails = null;
    
    if ($result->num_rows > 0) {
        $locationDetails = $result->fetch_assoc(); // ดึงข้อมูลสถานที่เพียงครั้งเดียว
    } else {
        echo json_encode(['error' => 'No location found with the provided Location_Class']);
        exit;
    }

    // ดึงข้อมูลรูปภาพตามจำนวนที่ระบุ
    $imageSql = "
        SELECT li.Image_Path
        FROM location_image li
        WHERE li.Location_Id = ? AND li.Is_Verified = 1
        LIMIT ?
    ";
    
    $imageStmt = $conn->prepare($imageSql);
    $imageStmt->bind_param("ii", $locationDetails['Location_Id'], $limit);
    $imageStmt->execute();
    $imageResult = $imageStmt->get_result();
    
    $images = [];
    while ($imgRow = $imageResult->fetch_assoc()) {
        $images[] = $imgRow['Image_Path'];
    }
    
    // ส่งคืนข้อมูลเป็น JSON
    $response = [
        'locationDetails' => $locationDetails,
        'images' => $images,
    ];
    
    echo json_encode($response);
    
    $stmt->close();
    $imageStmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Location_Class parameter is missing']);
}
?>
