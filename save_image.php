<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบข้อมูลที่ส่งมา
    if (isset($_FILES['image']) && isset($_POST['location_class'])) {
        $locationClass = $_POST['location_class'];

        // กำหนดโฟลเดอร์ที่ต้องการบันทึกรูปภาพ
        $targetDir = "new_image_unverified/" . $locationClass . "/";
        
        // สร้างโฟลเดอร์หากยังไม่มี
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // กำหนดชื่อไฟล์สำหรับบันทึก
        $targetFile = $targetDir . basename($_FILES['image']['name']); // ใช้ชื่อไฟล์ที่อัพโหลด

        // ตรวจสอบว่าการอัพโหลดไฟล์สำเร็จ
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // ค้นหา Location_Id ตาม Location_Class
            $sql = "SELECT Location_Id FROM location WHERE Location_Class = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $locationClass);
                $stmt->execute();
                $stmt->bind_result($locationId);

                // ตรวจสอบว่าพบ Location_Id หรือไม่
                if ($stmt->fetch()) {
                    // ปิดการเตรียมคำสั่ง
                    $stmt->close();

                    // เตรียมคำสั่ง SQL สำหรับการบันทึกในตาราง location_image
                    $sqlInsert = "INSERT INTO location_image (Location_Id, User_Id, Verified_By, Image_Path, Is_Verified, Status_Image) 
                                  VALUES (?, ?, NULL, ?, 0, 1)";

                    // เตรียมคำสั่ง SQL สำหรับการบันทึก
                    if ($stmtInsert = $conn->prepare($sqlInsert)) {
                        $userId = 1; // เปลี่ยนตามที่ต้องการหรือดึงจากเซสชันผู้ใช้
                        $stmtInsert->bind_param("iis", $locationId, $userId, $targetFile);

                        // ตรวจสอบการบันทึกข้อมูลในฐานข้อมูล
                        if ($stmtInsert->execute()) {
                            // ส่งผลลัพธ์กลับไปยัง Flutter
                            echo json_encode(["success" => true, "message" => "Image saved and database updated successfully"]);
                        } else {
                            // ส่งผลลัพธ์เมื่อบันทึกในฐานข้อมูลไม่สำเร็จ
                            echo json_encode(["success" => false, "message" => "Database insertion failed: " . $stmtInsert->error]);
                        }

                        // ปิดการเตรียมคำสั่ง
                        $stmtInsert->close();
                    } else {
                        // ส่งผลลัพธ์เมื่อเตรียมคำสั่ง SQL ไม่สำเร็จ
                        echo json_encode(["success" => false, "message" => "Failed to prepare SQL statement for insert"]);
                    }
                } else {
                    // ส่งผลลัพธ์เมื่อไม่พบ Location_Id
                    echo json_encode(["success" => false, "message" => "No matching Location_Id found for the given Location_Class"]);
                }
            } else {
                // ส่งผลลัพธ์เมื่อเตรียมคำสั่ง SQL ไม่สำเร็จ
                echo json_encode(["success" => false, "message" => "Failed to prepare SQL statement for select"]);
            }
        } else {
            // ส่งผลลัพธ์เมื่อบันทึกไฟล์ไม่สำเร็จ
            echo json_encode(["success" => false, "message" => "Failed to save image"]);
        }
    } else {
        // ส่งผลลัพธ์เมื่อไม่มีข้อมูลครบถ้วน
        echo json_encode(["success" => false, "message" => "Invalid input data"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
