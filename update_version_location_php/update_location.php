<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);
function send_notification($conn, $location_id, $user_id) {
    // ดึงชื่อสถานที่
    $query_location = "SELECT Location_Name FROM location WHERE Location_Id = ?";
    $stmt_location = $conn->prepare($query_location);
    $stmt_location->bind_param("i", $location_id);
    $stmt_location->execute();
    $result_location = $stmt_location->get_result();
    $location_data = $result_location->fetch_assoc();
    $location_name = $location_data['Location_Name'];

    // ดึง `Version_Id` ล่าสุดของสถานที่
    $query_version = "SELECT Version_Id, Version_Number FROM location_versions WHERE Location_Id = ? ORDER BY Version_Number DESC LIMIT 1";
    $stmt_version = $conn->prepare($query_version);
    $stmt_version->bind_param("i", $location_id);
    $stmt_version->execute();
    $result_version = $stmt_version->get_result();
    $row_version = $result_version->fetch_assoc();
    $latest_version_id = $row_version['Version_Id'];
    $latest_version_number = $row_version['Version_Number'];

    // ดึงผู้ใช้ที่เคยแก้ไขสถานที่นี้ (ยกเว้นตัวเอง)
    $query_users = "SELECT DISTINCT Changed_By FROM location_versions WHERE Location_Id = ? AND Changed_By != ?";
    $stmt_users = $conn->prepare($query_users);
    $stmt_users->bind_param("ii", $location_id, $user_id);
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();

    // ข้อความแจ้งเตือน
    $message = "สถานที่ '$location_name' มีการเปลี่ยนแปลงเป็นเวอร์ชัน $latest_version_number";

    while ($row = $result_users->fetch_assoc()) {
        $notified_user = $row['Changed_By'];
        $insert_notification = "INSERT INTO notifications (User_Id, Location_Id, Version_Id, Message, Is_Read, Created_At) 
                                VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt_insert = $conn->prepare($insert_notification);
        $stmt_insert->bind_param("iiis", $notified_user, $location_id, $latest_version_id, $message);
        $stmt_insert->execute();
    }
}


$location_id = $data['Location_Id'];
$user_id = $data['User_Id'];

function get_old_value($conn, $location_id, $column) {
    $query = "SELECT $column FROM location WHERE Location_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result[$column] : null;
}

function get_old_value_address($conn, $address_id, $column) {
    $query = "SELECT $column FROM address WHERE Address_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $address_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result[$column] : null;
}

$Location_Name = isset($data['Location_Name']) ? $data['Location_Name'] : get_old_value($conn, $location_id, 'Location_Name');
$Location_Lat = isset($data['Location_Lat']) ? $data['Location_Lat'] : get_old_value($conn, $location_id, 'Location_Lat');
$Location_Long = isset($data['Location_Long']) ? $data['Location_Long'] : get_old_value($conn, $location_id, 'Location_Long');
$Description = isset($data['Description']) ? $data['Description'] : get_old_value($conn, $location_id, 'Description');
$street = isset($data['Street']) ? $data['Street'] : get_old_value($conn, $location_id, 'Street');
$sub_dist = isset($data['Sub_Dist']) ? $data['Sub_Dist'] : get_old_value($conn, $location_id, 'Sub_Dist');
$district = isset($data['District']) ? $data['District'] : get_old_value($conn, $location_id, 'District');
$province = isset($data['Province']) ? $data['Province'] : get_old_value($conn, $location_id, 'Province');
$zip_code = isset($data['Zip_Code']) ? $data['Zip_Code'] : get_old_value($conn, $location_id, 'Zip_Code');
$type_id = isset($data['Type_Id']) ? $data['Type_Id'] : get_old_value($conn, $location_id, 'Type_Id');
$address_id = get_old_value($conn, $location_id, 'Address_Id');

mysqli_begin_transaction($conn);

try {
    $insert_version = "INSERT INTO location_versions (Location_Id, Version_Number, Changed_By) SELECT ?, COALESCE(MAX(Version_Number) + 1, 1), ? FROM location_versions WHERE Location_Id = ?";
    $stmt = $conn->prepare($insert_version);
    $stmt->bind_param("iii", $location_id, $user_id, $location_id);
    if (!$stmt->execute()) {
        throw new Exception("Error inserting version: " . $stmt->error);
    }
    $version_id = $conn->insert_id;

    // บันทึกทุกคอลัมน์ใน location_changes (รวม address)
    $columns = [
        'Location_Name' => $Location_Name,
        'Location_Lat' => $Location_Lat,
        'Location_Long' => $Location_Long,
        'Description' => $Description,
        'Street' => $street,
        'Sub_Dist' => $sub_dist,
        'District' => $district,
        'Province' => $province,
        'Zip_Code' => $zip_code,
        'Type_Id' => $type_id
    ];

    foreach ($columns as $col => $new_value) {
        if (in_array($col, ['Street', 'Sub_Dist', 'District', 'Province', 'Zip_Code']) && $address_id !== null) {
            $old_value = get_old_value_address($conn, $address_id, $col);
        } else {
            $old_value = get_old_value($conn, $location_id, $col);
        }
        $is_changed = ($old_value != $new_value) ? 1 : 0;
        $insert_change = "INSERT INTO location_changes (Version_Id, Column_Name, Old_Value, New_Value, Is_Changed) VALUES (?, ?, ?, ?, ?)";
        $stmt_change = $conn->prepare($insert_change);
        $stmt_change->bind_param("isssi", $version_id, $col, $old_value, $new_value, $is_changed);
        if (!$stmt_change->execute()) {
            throw new Exception("Error inserting change: " . $stmt_change->error);
        }
    }

   /* $update_location = "UPDATE location SET Location_Name = ?, Location_Lat = ?, Location_Long = ?, Description = ?, Type_Id = ? WHERE Location_Id = ?";
    $stmt = $conn->prepare($update_location);
    $stmt->bind_param("ssssii", $Location_Name, $Location_Lat, $Location_Long, $Description, $type_id, $location_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating location: " . $stmt->error);
    }

    if ($address_id !== null) {
        $update_address = "UPDATE address SET Street = ?, Sub_Dist = ?, District = ?, Province = ?, Zip_Code = ? WHERE Address_Id = ?";
        $stmt = $conn->prepare($update_address);
        $stmt->bind_param("sssssi", $street, $sub_dist, $district, $province, $zip_code, $address_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating address: " . $stmt->error);
        }
    }*/
    // ส่ง notification
    send_notification($conn, $location_id, $user_id);

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'update location success']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'error', 'error' => $e->getMessage()]);
}
?>