<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['version_id']) || !isset($data['action']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$version_id = $data['version_id'];
$action = $data['action']; // 'approve' หรือ 'reject'
$user_id = $data['user_id']; // User_Id ของเจ้าของ

error_log("Version ID: " . $version_id);
error_log("Action: " . $action);
error_log("User ID: " . $user_id);

mysqli_begin_transaction($conn);

try {
    // ดึง Location_Id และ Address_Id จาก location
    $query_location = "SELECT Location_Id, Address_Id FROM location WHERE Location_Id = (SELECT Location_Id FROM location_versions WHERE Version_Id = ?)";
    $stmt_location = $conn->prepare($query_location);
    $stmt_location->bind_param("i", $version_id);
    if (!$stmt_location->execute()) {
        throw new Exception("Error executing query_location: " . $stmt_location->error);
    }
    $result_location = $stmt_location->get_result();
    $location_row = $result_location->fetch_assoc();
    if (!$location_row) {
        throw new Exception("No Location_Id or Address_Id found for Version_Id: " . $version_id);
    }
    $location_id = $location_row['Location_Id'];
    $address_id = $location_row['Address_Id'];
    error_log("Location_Id: " . $location_id);
    error_log("Address_Id: " . $address_id);

    if ($action === 'approve') {
        // เพิ่มจำนวนการยืนยันใน location_changes
        $update_approvals = "UPDATE location_changes SET Approvals = Approvals + 1 WHERE Version_Id = ?";
        $stmt_approvals = $conn->prepare($update_approvals);
        $stmt_approvals->bind_param("i", $version_id);
        if (!$stmt_approvals->execute()) {
            throw new Exception("Error updating approvals: " . $stmt_approvals->error);
        }

        // ตรวจสอบจำนวนการยืนยัน
        $query_check_approvals = "SELECT Approvals FROM location_changes WHERE Version_Id = ?";
        $stmt_check_approvals = $conn->prepare($query_check_approvals);
        $stmt_check_approvals->bind_param("i", $version_id);
        if (!$stmt_check_approvals->execute()) {
            throw new Exception("Error checking approvals: " . $stmt_check_approvals->error);
        }
        $result_check_approvals = $stmt_check_approvals->get_result();
        $approvals_row = $result_check_approvals->fetch_assoc();
        $approvals = $approvals_row['Approvals'];
        error_log("Approvals for Version_Id " . $version_id . ": " . $approvals);

        // หากจำนวนการยืนยันถึง 2 คน ให้บันทึกข้อมูลลงใน location และ address
        if ($approvals >= 2) {
            // ดึงข้อมูลการเปลี่ยนแปลงจาก location_changes
            $query_changes = "SELECT Column_Name, New_Value 
                              FROM location_changes 
                              WHERE Version_Id = ?";
            $stmt_changes = $conn->prepare($query_changes);
            $stmt_changes->bind_param("i", $version_id);
            if (!$stmt_changes->execute()) {
                throw new Exception("Error executing query_changes: " . $stmt_changes->error);
            }
            $result_changes = $stmt_changes->get_result();
        
            // อัปเดตข้อมูลใน location และ address
            while ($change = $result_changes->fetch_assoc()) {
                error_log("Change Row: " . json_encode($change));
        
                $valid_columns_location = ['Location_Name', 'Location_Lat', 'Location_Long', 'Description', 'Type_Id'];
                $valid_columns_address = ['Street', 'Sub_Dist', 'District', 'Province', 'Zip_Code'];
        
                if (in_array($change['Column_Name'], $valid_columns_location)) {
                    $update_query = "UPDATE location SET {$change['Column_Name']} = ? WHERE Location_Id = ?";
                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param("si", $change['New_Value'], $location_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Error executing update_query for location: " . $stmt_update->error);
                    }
                    error_log("Updated Location_Id: " . $location_id . " Column: " . $change['Column_Name']);
                } elseif (in_array($change['Column_Name'], $valid_columns_address)) {
                    if ($address_id === null) {
                        throw new Exception("Address_Id is null, cannot update address");
                    }
                    $update_query = "UPDATE address SET {$change['Column_Name']} = ? WHERE Address_Id = ?";
                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param("si", $change['New_Value'], $address_id);
                    if (!$stmt_update->execute()) {
                        throw new Exception("Error executing update_query for address: " . $stmt_update->error);
                    }
                    error_log("Updated Address_Id: " . $address_id . " Column: " . $change['Column_Name']);
                } else {
                    throw new Exception("Invalid column name: " . $change['Column_Name']);
                }
            }
        
            // อัปเดตสถานะใน location_changes เป็น 'Confirmed'
            $update_changes_status = "UPDATE location_changes SET Is_Confirmed = 1 WHERE Version_Id = ?";
            $stmt_status = $conn->prepare($update_changes_status);
            $stmt_status->bind_param("i", $version_id);
            if (!$stmt_status->execute()) {
                throw new Exception("Error executing update_changes_status: " . $stmt_status->error);
            }
            error_log("Updated status for Version_Id: " . $version_id);
        
            // อัปเดต Verified ใน location_versions เป็น 1
            $update_verified_status = "UPDATE location_versions SET Verified = 1 WHERE Version_Id = ?";
            $stmt_verified = $conn->prepare($update_verified_status);
            $stmt_verified->bind_param("i", $version_id);
            if (!$stmt_verified->execute()) {
                throw new Exception("Error updating Verified status in location_versions: " . $stmt_verified->error);
            }
            error_log("Updated Verified status for Version_Id: " . $version_id);
        
            echo json_encode(['success' => true, 'message' => 'การเปลี่ยนแปลงได้รับการยืนยันและบันทึกแล้ว']);
        } else {
            echo json_encode(['success' => true, 'message' => 'การยืนยันสำเร็จ แต่ยังไม่ครบจำนวน']);
        }
    } elseif ($action === 'reject') {
        // อัปเดตสถานะใน location_changes เป็น 'Rejected'
        $update_changes_status = "UPDATE location_changes SET Is_Rejected = 1 WHERE Version_Id = ?";
        $stmt_changes_status = $conn->prepare($update_changes_status);
        $stmt_changes_status->bind_param("i", $version_id);
        if (!$stmt_changes_status->execute()) {
            throw new Exception("Error updating location_changes status: " . $stmt_changes_status->error);
        }
        error_log("Updated location_changes to Rejected for Version_Id: " . $version_id);
        echo json_encode(['success' => true, 'message' => 'การเปลี่ยนแปลงถูกปฏิเสธ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
    }

    // อัปเดตสถานะใน notifications เป็น 'Is_Read = 1' สำหรับ User_Id ที่ตรงกัน
    $update_notifications = "UPDATE notifications SET Is_Read = 1 WHERE Version_Id = ? AND User_Id = ?";
    $stmt_notifications = $conn->prepare($update_notifications);
    $stmt_notifications->bind_param("ii", $version_id, $user_id);
    if (!$stmt_notifications->execute()) {
        throw new Exception("Error updating notifications: " . $stmt_notifications->error);
    }
    error_log("Updated notifications for Version_Id: " . $version_id . " and User_Id: " . $user_id);

    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด', 'error' => $e->getMessage()]);
}
?>