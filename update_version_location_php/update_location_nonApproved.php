<?php
header('Content-Type: application/json');
include '../connection.php';

$data = json_decode(file_get_contents("php://input"), true);

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
$type_id = isset($data['Type_Id']) ? $data['Type_Id'] : get_old_value($conn, $location_id, 'Type_Id');
$address_id = get_old_value($conn, $location_id, 'Address_Id');

$street = isset($data['Street']) ? $data['Street'] : get_old_value_address($conn, $address_id, 'Street');
$sub_dist = isset($data['Sub_Dist']) ? $data['Sub_Dist'] : get_old_value_address($conn, $address_id, 'Sub_Dist');
$district = isset($data['District']) ? $data['District'] : get_old_value_address($conn, $address_id, 'District');
$province = isset($data['Province']) ? $data['Province'] : get_old_value_address($conn, $address_id, 'Province');
$zip_code = isset($data['Zip_Code']) ? $data['Zip_Code'] : get_old_value_address($conn, $address_id, 'Zip_Code');

mysqli_begin_transaction($conn);

try {
    // บันทึกการเปลี่ยนแปลงเข้าสู่ตาราง location_changes เพื่อรอการตรวจสอบ
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
        $old_value = ($col == 'Street' || $col == 'Sub_Dist' || $col == 'District' || $col == 'Province' || $col == 'Zip_Code')
            ? get_old_value_address($conn, $address_id, $col)
            : get_old_value($conn, $location_id, $col);

        if ($old_value != $new_value) {
            $insert_change = "INSERT INTO location_changes (Location_Id, Column_Name, Old_Value, New_Value, Changed_By, Is_Approved) VALUES (?, ?, ?, ?, ?, 0)";
            $stmt_change = $conn->prepare($insert_change);
            $stmt_change->bind_param("isssi", $location_id, $col, $old_value, $new_value, $user_id);
            if (!$stmt_change->execute()) {
                throw new Exception("Error inserting change: " . $stmt_change->error);
            }
        }
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Changes submitted for review']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error', 'error' => $e->getMessage()]);
}
?>
