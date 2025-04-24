<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// กำหนด character set เป็น utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// เขียนคำสั่ง SQL โดยเพิ่มเงื่อนไข Status_Location = 1
$sql = "SELECT Location_Name, Location_Lat, Location_Long, Location_Class ,Location_Id
        FROM Location
        "; // เพิ่มบรรทัดนี้

$result = mysqli_query($conn, $sql);

// ตรวจสอบว่ามีข้อมูลหรือไม่
if (mysqli_num_rows($result) > 0) {
    $locations = [];
    while($row = mysqli_fetch_assoc($result)) {
        $locations[] = [
            "Location_Name" => $row["Location_Name"],
            "Location_Lat" => (float)$row["Location_Lat"],
            "Location_Long" => (float)$row["Location_Long"],
            "Location_Class" => $row["Location_Class"],
            "Location_Id" => $row["Location_Id"]
        ];
    }
    // ส่งข้อมูลเป็น JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($locations, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}

mysqli_close($conn); // ปิดการเชื่อมต่อฐานข้อมูล
?>