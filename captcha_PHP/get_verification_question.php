<?php
header("Content-Type: application/json; charset=utf-8");
require '../connection.php'; // เชื่อมต่อฐานข้อมูล
mysqli_set_charset($conn, "utf8"); 
// ดึงคำถามเกี่ยวกับสถานที่ที่ได้รับการยืนยัน
$query = "SELECT q.Question_Id, q.Verified_Location_Id, l.Location_Name 
          FROM image_verification_question q
          JOIN location l ON q.Verified_Location_Id = l.Location_Id
          ORDER BY RAND() LIMIT 1"; // สุ่ม 1 คำถาม
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    $question_id = $row['Question_Id'];
    $verified_location_id = $row['Verified_Location_Id'];
    $location_name = $row['Location_Name'];

    // ดึงรูปภาพที่ถูกต้อง (ของสถานที่ที่ได้รับการยืนยัน)
    $correct_images_query = "SELECT Image_Id, Image_Path FROM location_image 
                             WHERE Location_Id = $verified_location_id AND Is_Verified = 1
                             ORDER BY RAND() LIMIT 2"; // ดึงรูปที่ได้รับการยืนยันมา 2 รูป
    $correct_images_result = mysqli_query($conn, $correct_images_query);
    $correct_images = mysqli_fetch_all($correct_images_result, MYSQLI_ASSOC);

    // ดึงรูปภาพอื่น ๆ ให้ครบ 12 รูป
    $random_images_query = "SELECT Image_Id, Image_Path FROM location_image 
                            WHERE Location_Id != $verified_location_id 
                            ORDER BY RAND() LIMIT 10"; // ดึงรูปอื่น ๆ มา 10 รูป
    $random_images_result = mysqli_query($conn, $random_images_query);
    $random_images = mysqli_fetch_all($random_images_result, MYSQLI_ASSOC);

    // รวมรูปภาพทั้งหมด (สุ่มตำแหน่ง)
    $all_images = array_merge($correct_images, $random_images);
    shuffle($all_images); // สุ่มลำดับรูปภาพ

    echo json_encode([
        "question_id" => $question_id,
        "question" => "รูปใดคือ $location_name?",
        "images" => $all_images
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "ไม่พบคำถาม"]);
}
?>
