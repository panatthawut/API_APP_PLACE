<?php
require '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = $_POST['image_id'];
    $user_id = $_POST['user_id'];

    // ตรวจสอบว่าผู้ใช้เคยรายงานภาพนี้หรือไม่
    $checkReport = $conn->prepare("SELECT * FROM image_reports WHERE image_id = ? AND user_id = ? AND vote_type = 'report'");
    $checkReport->bind_param("ii", $image_id, $user_id);
    $checkReport->execute();
    $result = $checkReport->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["reported" => true]);
    } else {
        echo json_encode(["reported" => false]);
    }
} else {
    echo json_encode(["error" => "Method Not Allowed"]);
}
?>