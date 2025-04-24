<?php
include 'connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_id = $_POST['Location_Id'];
    $location_name = $_POST['Location_Name'];
    $description = $_POST['Description'];
    $latitude = $_POST['Latitude']; // Decimal
    $longitude = $_POST['Longitude']; // Decimal
    $type_id = $_POST['Type_Id']; // Integer
    $verified_by = $_POST['Verified_By']; // Integer (ผู้ใช้ admin)
    
    // ข้อมูลที่อยู่เพิ่มเติม
    $province = $_POST['Province'];
    $street = $_POST['Street'];
    $sub_dist = $_POST['Sub_Dist'];
    $district = $_POST['District'];
    $zip_code = $_POST['Zip_Code']; // Varchar

    // อัปเดตข้อมูลสถานที่
    $query = "UPDATE location SET 
            Location_Name = ?, 
            Description = ?, 
            Location_Lat = ?, 
            Location_Long = ?, 
            Type_Id = ?, 
            Verified_By = ?
            WHERE Location_Id = ?";

    $stmt = $conn->prepare($query);
    
    // Bind param ใช้ 's' สำหรับ string, 'd' สำหรับ decimal, 'i' สำหรับ integer
    $stmt->bind_param('ssddiii', $location_name, $description, $latitude, $longitude, $type_id, $verified_by, $location_id);

    // ดึง Address_Id จากตาราง location โดยใช้ Location_Id
    $query_get_address = "SELECT Address_Id FROM location WHERE Location_Id = ?";
    $stmt_get_address = $conn->prepare($query_get_address);
    $stmt_get_address->bind_param('i', $location_id);
    $stmt_get_address->execute();
    $result = $stmt_get_address->get_result();

    if ($row = $result->fetch_assoc()) {
        $address_id = $row['Address_Id'];

        // อัปเดตข้อมูลที่อยู่
        $query_address = "UPDATE address SET
                        Province = ?, 
                        Street = ?, 
                        Sub_Dist = ?, 
                        District = ?, 
                        Zip_Code = ?
                        WHERE Address_Id = ?";
        $stmt_address = $conn->prepare($query_address);
        $stmt_address->bind_param('sssssi', $province, $street, $sub_dist, $district, $zip_code, $address_id);

        // อัปเดตข้อมูล
        if ($stmt->execute() && $stmt_address->execute()) {
            echo "Location and Address updated successfully";
        } else {
            echo "Error updating location or address: " . $conn->error;
        }
    } else {
        echo "Error: Location not found.";
    }

    // ปิด statement และ connection
    $stmt->close();
    $stmt_get_address->close();
    $stmt_address->close();
    mysqli_close($conn);
}
?>
