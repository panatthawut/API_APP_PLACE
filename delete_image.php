<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล



$image_id = $_POST['Image_Id'];

$sql = "DELETE FROM location_image WHERE Image_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $image_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Image deleted successfully"]);
} else {
    echo json_encode(["message" => "Failed to delete image"]);
}

$stmt->close();
$conn->close();
?>
