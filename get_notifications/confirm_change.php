<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);
$location_id = $data['Location_Id'];
$user_id = $data['User_Id'];

// ตรวจสอบว่าผู้ใช้เคยแก้ไขข้อมูลก่อนหน้าหรือไม่
$query_check = "SELECT COUNT(*) AS count FROM location_versions WHERE Location_Id = ? AND Changed_By = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("ii", $location_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();

if ($result_check['count'] > 0) {
    // อัปเดตให้เป็นการยืนยัน (Is_Confirmed = 1)
    $update_confirm = "UPDATE location_changes SET Is_Confirmed = 1 WHERE Location_Id = ? AND Is_Confirmed = 0";
    $stmt_update = $conn->prepare($update_confirm);
    $stmt_update->bind_param("i", $location_id);
    $stmt_update->execute();
    
    // ตรวจสอบว่ามีคนกดยืนยันครบ 2 คนหรือยัง
    $query_confirmed = "SELECT COUNT(*) AS count FROM location_changes WHERE Location_Id = ? AND Is_Confirmed = 1";
    $stmt_confirmed = $conn->prepare($query_confirmed);
    $stmt_confirmed->bind_param("i", $location_id);
    $stmt_confirmed->execute();
    $result_confirmed = $stmt_confirmed->get_result()->fetch_assoc();

    if ($result_confirmed['count'] >= 2) {
        // อัปเดตข้อมูลจริงจาก location_changes ไปยัง location
        $update_location = "UPDATE location l
            JOIN location_changes lc ON l.Location_Id = lc.Location_Id
            SET l.Location_Name = lc.New_Value
            WHERE lc.Column_Name = 'Location_Name' AND lc.Is_Confirmed = 1";
        $conn->query($update_location);
        
        echo json_encode(['success' => true, 'message' => 'ข้อมูลถูกอัปเดตเรียบร้อย']);
    } else {
        echo json_encode(['success' => true, 'message' => 'รอการยืนยันจากผู้ใช้เพิ่มเติม']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ยืนยัน']);
}
?>
