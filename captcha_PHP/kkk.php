<?php
header("Content-Type: application/json");

require '../connection.php';

$sql = "SELECT Location_Name, Status_Location ,Location_Id FROM location"; // เลือกทั้ง Location_Name และ Status_Location
$result = $conn->query($sql);

$data = array();

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $data[] = array(
      "Location_Name" => $row["Location_Name"], // เพิ่ม Location_Name
      "Status_Location" => $row["Status_Location"], // เพิ่ม Status_Location
      "Location_Id" => $row["Location_Id"]
    );
  }
} else {
  $data = array("message" => "ไม่พบข้อมูล");
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conn->close();
?>