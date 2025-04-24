<?php
include 'connection.php';
// ตรวจสอบ character set ของการเชื่อมต่อ
if (!$conn->set_charset("utf8mb4")) { // หรือ utf8
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
    exit();
}
$sql = "SELECT l.*, a.Street, a.Sub_Dist, a.District, a.Province, a.Zip_Code 
        FROM location l
        JOIN address a ON l.Address_Id = a.Address_Id
        WHERE l.Status_Location = 0";

$result = $conn->query($sql);

$locations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

echo json_encode($locations, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
