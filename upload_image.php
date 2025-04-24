<?php
// รับ Location_Id จากการส่ง POST
$locationId = isset($_POST['location_id']) ? $_POST['location_id'] : null;

if ($locationId === null) {
    echo "Location ID is required.";
    exit;
}

// สร้างโฟลเดอร์เฉพาะสำหรับสถานที่นั้น ๆ
$target_dir = "C:/xampp/htdocs/api_php/image_place_50/location_$locationId/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // สร้างโฟลเดอร์หากยังไม่มี
}

$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// ตรวจสอบว่าเป็นไฟล์รูปภาพจริงหรือไม่
if (isset($_POST["submit"])) {
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}

// ตรวจสอบไฟล์มีอยู่แล้วหรือไม่
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}

// จำกัดชนิดของไฟล์ที่อนุญาต (เช่น jpg, png)
if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif") {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}

// ตรวจสอบการอัพโหลด
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
} else {
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        echo "The file " . htmlspecialchars(basename($_FILES["image"]["name"])) . " has been uploaded.";

        // บันทึกพาธไฟล์ลงในฐานข้อมูล
        $imagePath = "http://localhost/api_php/image_place_50/location_$locationId/" . basename($_FILES["image"]["name"]);
        // เชื่อมต่อกับฐานข้อมูลและบันทึกพาธไฟล์
        $conn = new mysqli('localhost', 'root', '', 'your_database_name');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO images (Location_Id, Image_Path) VALUES (?, ?)");
        $stmt->bind_param("is", $locationId, $imagePath);
        if ($stmt->execute()) {
            echo "Image path saved to database.";
        } else {
            echo "Failed to save image path to database.";
        }
        $stmt->close();
        $conn->close();
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>
