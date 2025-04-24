<?php

include 'connection.php'; // เชื่อมต่อฐานข้อมูล

$locationId = $_GET['Location_Id'];

if ($locationId) {
    // Query เพื่อดึง Address_Id ที่ตรงกับ Location_Id
    $query = "SELECT Address_Id FROM location WHERE Location_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $locationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $addressId = $row['Address_Id'];

        // Query เพื่อดึงข้อมูลที่อยู่จาก address โดยใช้ Address_Id
        $query = "SELECT * FROM address WHERE Address_Id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $addressId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // ส่งข้อมูลที่อยู่เป็น object เดียว
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'Address not found']);
        }
    } else {
        echo json_encode(['error' => 'Location not found']);
    }
} else {
    echo json_encode(['error' => 'Location_Id is required']);
}
?>