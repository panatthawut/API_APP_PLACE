<?php
header("Content-Type: application/json");

require '../connection.php'; 

$user_id = $_POST['user_id'];
$question_id = $_POST['question_id'];
$selected_image_id = $_POST['selected_image_id'];

// ตรวจสอบว่าภาพที่เลือกถูกต้องหรือไม่
$check_query = "SELECT Location_Id FROM location_image 
                WHERE Image_Id = $selected_image_id AND Is_Verified = 1";
$check_result = mysqli_query($conn, $check_query);
$is_correct = mysqli_num_rows($check_result) > 0 ? 1 : 0;

// บันทึกคำตอบลงฐานข้อมูล
$insert_query = "INSERT INTO image_verification_answer (User_Id, Question_Id, Selected_Image_Id, Is_Correct) 
                 VALUES ($user_id, $question_id, $selected_image_id, $is_correct)";
mysqli_query($conn, $insert_query);

$response = ["is_correct" => $is_correct];

// ถ้าผู้ใช้ตอบถูกต้อง ➜ ให้แสดงคำถามเกี่ยวกับสถานที่ที่ยังไม่ได้รับการยืนยัน
if ($is_correct) {
    $unverified_query = "SELECT l.Location_Id, l.Location_Name 
                         FROM location l 
                         WHERE NOT EXISTS (SELECT 1 FROM location_image i WHERE i.Location_Id = l.Location_Id AND i.Is_Verified = 1)
                         ORDER BY RAND() LIMIT 1";
    $unverified_result = mysqli_query($conn, $unverified_query);
    if ($unverified_row = mysqli_fetch_assoc($unverified_result)) {
        $unverified_location_id = $unverified_row['Location_Id'];
        $unverified_location_name = $unverified_row['Location_Name'];

        // ดึงรูปภาพของสถานที่ที่ยังไม่ได้รับการยืนยัน
        $unverified_images_query = "SELECT Image_Id, Image_Path FROM location_image 
                                    WHERE Location_Id = $unverified_location_id 
                                    ORDER BY RAND() LIMIT 2";
        $unverified_images_result = mysqli_query($conn, $unverified_images_query);
        $unverified_images = mysqli_fetch_all($unverified_images_result, MYSQLI_ASSOC);

        // ดึงรูปภาพอื่น ๆ ให้ครบ 12 รูป
        $random_images_query = "SELECT Image_Id, Image_Path FROM location_image 
                                WHERE Location_Id != $unverified_location_id 
                                ORDER BY RAND() LIMIT 10";
        $random_images_result = mysqli_query($conn, $random_images_query);
        $random_images = mysqli_fetch_all($random_images_result, MYSQLI_ASSOC);

        $all_images = array_merge($unverified_images, $random_images);
        shuffle($all_images);

        $response["next_question"] = [
            "question" => "รูปใดคือ $unverified_location_name?",
            "images" => $all_images
        ];
    }
}

echo json_encode($response);
?>
