<?php
include 'connection.php';

$location_id = $_POST['Location_Id'];
$user_id = $_POST['User_Id'];
$vote = $_POST['Vote']; // 1 = ถูกต้อง, -1 = ไม่ถูกต้อง

// เพิ่มการโหวตลงในตาราง location_verification
$sql = "INSERT INTO location_verification (Location_Id, User_Id, Vote, Timestamp) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $location_id, $user_id, $vote);
$stmt->execute();

// อัปเดตค่า Verified_Count และ Rejected_Count
if ($vote == 1) {
    $update_sql = "UPDATE location SET Verified_Count = Verified_Count + 1 WHERE Location_Id = ?";
} elseif ($vote == -1) {
    $update_sql = "UPDATE location SET Rejected_Count = Rejected_Count + 1 WHERE Location_Id = ?";
}
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $location_id);
$update_stmt->execute();

// ตรวจสอบว่าผ่านเกณฑ์โหวตหรือไม่
$check_sql = "SELECT Verified_Count, Rejected_Count FROM location WHERE Location_Id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $location_id);
$check_stmt->execute();
$result = $check_stmt->get_result()->fetch_assoc();

if ($result['Verified_Count'] >= 30) {
    // อัปเดต Status_Location เป็น 1
    $status_sql = "UPDATE location SET Status_Location = 1 WHERE Location_Id = ?";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("i", $location_id);
    $status_stmt->execute();
}

echo json_encode(["message" => "Vote submitted successfully"]);
$conn->close();
?>
