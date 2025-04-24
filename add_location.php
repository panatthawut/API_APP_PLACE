<?php
header('Content-Type: application/json');
include 'connection.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $street = $_POST['Street'] ?? null;
    $sub_dist = $_POST['Sub_Dist'] ?? null;
    $district = $_POST['District'] ?? null;
    $province = $_POST['Province'] ?? null;
    $zip_code = $_POST['Zip_Code'] ?? null;

    $type_id = intval($_POST['Type_Id'] ?? 0);
    $verified_by = isset($_POST['Verified_By']) ? intval($_POST['Verified_By']) : null;
    $location_name = $_POST['Location_Name'] ?? null;
    $location_lat = $_POST['Location_Lat'] ?? null;
    $location_long = $_POST['Location_Long'] ?? null;
    $description = $_POST['Description'] ?? null;
    $location_class = $_POST['Location_Class'] ?? null;
    $user_id = intval($_POST['User_Id'] ?? 1);
    $status_location = 0;

    if ($street && $district && $province && $zip_code && $location_name && $location_lat && $location_long && $type_id) {
        $sql_check = "SELECT Location_Id FROM location WHERE Location_Class = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $location_class);
        $stmt_check->execute();
        $stmt_check->bind_result($existing_location_id);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($existing_location_id) {
            $location_id = $existing_location_id;
        } else {
            $sql_address = "INSERT INTO address (Street, Sub_Dist, District, Province, Zip_Code) VALUES (?, ?, ?, ?, ?)";
            $stmt_address = $conn->prepare($sql_address);
            $stmt_address->bind_param("sssss", $street, $sub_dist, $district, $province, $zip_code);

            if ($stmt_address->execute()) {
                $address_id = $stmt_address->insert_id;
                $stmt_address->close();

                $sql_location = "INSERT INTO location 
                (Address_Id, Type_Id, Verified_By, Location_Name, Location_Lat, Location_Long, Description, Status_Location, Location_Class, Verified_Count, Rejected_Count) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";

                $stmt_location = $conn->prepare($sql_location);
                $stmt_location->bind_param(
                    "iiissssss",
                    $address_id, $type_id, $verified_by, $location_name, $location_lat, $location_long, $description, $status_location, $location_class
                );

                if ($stmt_location->execute()) {
                    $location_id = $stmt_location->insert_id;
                    $stmt_location->close();
                } else {
                    $response['success'] = false;
                    $response['message'] = "Failed to add location: " . $stmt_location->error;
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['success'] = false;
                $response['message'] = "Failed to add address: " . $stmt_address->error;
                echo json_encode($response);
                exit;
            }
        }

        // อัปเดต Location_Class ในฐานข้อมูล
        $cleaned_location_name = preg_replace("/[^\p{L}\p{N}_-]/u", "_", $location_name);
        $sql_update_class = "UPDATE location SET Location_Class = ? WHERE Location_Id = ?";
        $stmt_update_class = $conn->prepare($sql_update_class);
        $stmt_update_class->bind_param("si", $cleaned_location_name, $location_id);
        $stmt_update_class->execute();
        $stmt_update_class->close();

        // อัปโหลดรูปภาพ
        if (isset($_FILES['images']) && is_array($_FILES['images']['tmp_name'])) {
            $targetDir = "new_image_unverified" . DIRECTORY_SEPARATOR . $cleaned_location_name . DIRECTORY_SEPARATOR;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                $response['success'] = false;
                $response['message'] = "Failed to create directory";
                echo json_encode($response);
                exit;
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileExt = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($fileExt, $allowedExts)) {
                        continue; // ข้ามไฟล์ที่ไม่ได้เป็นรูปภาพ
                    }

                    if (mime_content_type($tmp_name) !== "image/jpeg" && mime_content_type($tmp_name) !== "image/png") {
                        continue;
                    }

                    $fileName = uniqid("img_", true) . "." . $fileExt;
                    $targetFile = realpath($targetDir) . DIRECTORY_SEPARATOR . $fileName;

                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $imagePath = str_replace("\\", "/", $targetFile);

                        $sql_insert_image = "INSERT INTO location_image (Location_Id, User_Id, Verified_By, Image_Path, Is_Verified, Status_Image) 
                                            VALUES (?, ?, NULL, ?, 0, 1)";
                        $stmt_insert_image = $conn->prepare($sql_insert_image);
                        $stmt_insert_image->bind_param("iis", $location_id, $user_id, $imagePath);

                        if (!$stmt_insert_image->execute()) {
                            $response['success'] = false;
                            $response['message'] = "Database insertion failed: " . $stmt_insert_image->error;
                            echo json_encode($response, JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                        $stmt_insert_image->close();
                    } else {
                        $response['success'] = false;
                        $response['message'] = "Failed to save image: " . $_FILES['images']['name'][$key];
                        echo json_encode($response, JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
            }
        } else {
            $response['message'] = "Location added without an image";
        }

        $response['success'] = true;
        $response['message'] = "Location added successfully";
    } else {
        $response['success'] = false;
        $response['message'] = "Missing required fields";
    }
} else {
    $response['success'] = false;
    $response['message'] = "Invalid request method";
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
