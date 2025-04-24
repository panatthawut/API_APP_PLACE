<?php
header('Content-Type: application/json');
include '../connection.php';

$location_id = isset($_GET['Location_Id']) ? $_GET['Location_Id'] : null;

if ($location_id === null) {
    echo json_encode(['error' => 'Location_Id is required']);
    exit;
}

try {
    // ดึงข้อมูลเวอร์ชันทั้งหมด
    if (isset($_GET['versions'])) {
        $query_versions = "SELECT Version_Id, Version_Number, Changed_At, Verified FROM location_versions WHERE Location_Id = ?";
        $stmt = $conn->prepare($query_versions);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $location_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result_versions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['versions' => $result_versions]);
        exit;
    }

    // ดึงข้อมูลเวอร์ชันที่เลือก
    if (isset($_GET['version_id'])) {
        $version_id = $_GET['version_id'];
        $query_changes = "SELECT Column_Name, New_Value FROM location_changes WHERE Version_Id = ?";
        $stmt = $conn->prepare($query_changes);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $version_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $result_changes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $data = [];
        foreach ($result_changes as $change) {
            $data[$change['Column_Name']] = $change['New_Value'];
        }

        // ดึงสถานะ Verified ของเวอร์ชันที่เลือก
        $query_verified = "SELECT Verified FROM location_versions WHERE Version_Id = ?";
        $stmt_verified = $conn->prepare($query_verified);
        if (!$stmt_verified) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt_verified->bind_param("i", $version_id);
        if (!$stmt_verified->execute()) {
            throw new Exception("Execute failed: " . $stmt_verified->error);
        }
        $verified_result = $stmt_verified->get_result()->fetch_assoc();
        $data['Verified'] = $verified_result['Verified'];

        echo json_encode(['version_data' => $data]);
        exit;
    }

    // ดึงข้อมูลล่าสุด
    $query = "SELECT l.*, a.Street, a.Sub_Dist, a.District, a.Province, a.Zip_Code, t.Type_Name, t.Type_Id FROM location l LEFT JOIN address a ON l.Address_Id = a.Address_Id LEFT JOIN type t ON l.Type_Id = t.Type_Id WHERE l.Location_Id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $location_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result()->fetch_assoc();

    $query_types = "SELECT Type_Id, Type_Name FROM type";
    $result_types = $conn->query($query_types);
    if (!$result_types) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $types = [];
    while ($row = $result_types->fetch_assoc()) {
        $types[] = $row;
    }

    $result['types'] = $types;
    echo json_encode($result);

} catch (Exception $e) {
    error_log("PHP Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}