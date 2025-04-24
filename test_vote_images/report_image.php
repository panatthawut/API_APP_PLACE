<?php
require '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image_id = $_POST['image_id'];
    $user_id = $_POST['user_id'];
    $report_reason = $_POST['report_reason'];

    // ตรวจสอบว่าผู้ใช้เคยรายงานภาพนี้หรือไม่
    $checkReport = $conn->prepare("SELECT * FROM image_reports WHERE image_id = ? AND user_id = ? AND vote_type = 'report'");
    $checkReport->bind_param("ii", $image_id, $user_id);
    $checkReport->execute();
    $result = $checkReport->get_result();

    if ($result->num_rows > 0) {
        // ถ้าผู้ใช้เคยรายงาน → ลบการรายงานออก (ยกเลิกการรายงาน)
        $deleteReport = $conn->prepare("DELETE FROM image_reports WHERE image_id = ? AND user_id = ? AND vote_type = 'report'");
        $deleteReport->bind_param("ii", $image_id, $user_id);
        if ($deleteReport->execute()) {
            echo json_encode(["message" => "ยกเลิกการรายงานสำเร็จ!", "reported" => false]);
        } else {
            echo json_encode(["error" => "ไม่สามารถยกเลิกการรายงานได้"]);
        }
    } else {
        // ถ้ายังไม่เคยรายงาน → เพิ่มข้อมูลการรายงาน
        $insertReport = $conn->prepare("INSERT INTO image_reports (image_id, user_id, report_reason, created_at, vote_type) VALUES (?, ?, ?, NOW(), 'report')");
        $insertReport->bind_param("iis", $image_id, $user_id, $report_reason);
        if ($insertReport->execute()) {
            echo json_encode(["message" => "รายงานสำเร็จ!", "reported" => true]);
        } else {
            echo json_encode(["error" => "ไม่สามารถเพิ่มการรายงานได้"]);
        }
    }
} else {
    echo json_encode(["error" => "Method Not Allowed"]);
}
?>