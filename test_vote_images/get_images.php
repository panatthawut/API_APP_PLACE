<?php
include '../connection.php';

$place_id = $_GET["place_id"];

$sql_images = "SELECT Image_Id, Image_Path FROM location_image WHERE Location_Id = 1";
$image_result = $conn->query($sql_images);
$images = [];

while ($row = $image_result->fetch_assoc()) {
    // ✅ สร้าง URL รูปภาพให้ถูกต้อง
    $image_url = "http://localhost/api_php/" . $row["Image_Path"];
    
    $images[] = [
        "Image_Id" => (int)$row["Image_Id"],
        "Image_URL" => $image_url
    ];
}

echo json_encode($images);
?>
