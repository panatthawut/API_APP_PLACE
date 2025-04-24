<?php
include '../connection.php';

$sql = "SELECT Location_Id, Location_Name FROM location";
$result = $conn->query($sql);

$places = []; // ✅ สร้าง array ว่างก่อน

while ($row = $result->fetch_assoc()) {
    $places[] = [
        "Location_Id" => (int)$row["Location_Id"],  // ✅ แปลงเป็น int
        "Location_Name" => $row["Location_Name"]
    ];
}

echo json_encode($places);
?>
