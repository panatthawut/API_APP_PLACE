<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// รับค่าจาก request
$imageId = filter_input(INPUT_POST, 'Image_Id', FILTER_VALIDATE_INT);
$isVerified = filter_input(INPUT_POST, 'Is_Verified', FILTER_VALIDATE_INT);

if ($imageId === null || $isVerified === null || ($isVerified !== 0 && $isVerified !== 1)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($isVerified === 1) {
        // ยืนยันภาพ (อัปเดต Is_Verified เป็น 1)
        $sql = "UPDATE location_image SET Is_Verified = 1 WHERE Image_Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $imageId);
        if ($stmt->execute()) {
            // ดึง Image_Path ของรูปภาพ
            $sql = "SELECT Image_Path FROM location_image WHERE Image_Id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $imageId);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc();

            if ($image) {
                $imagePath = $image['Image_Path'];
                // เรียก API สำหรับการสกัดฟีเจอร์
                $apiUrl = "http://<your-fastapi-server>/extract_feature";
                $postData = json_encode(['image_path' => $imagePath]);

                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    echo json_encode(['status' => 'success', 'message' => 'Image verified and features extracted']);
                } else {
                    throw new Exception("Failed to extract features: " . $response);
                }
            } else {
                throw new Exception("Image not found");
            }
        } else {
            throw new Exception("Failed to update status");
        }
    } else {
        // ลบภาพเมื่อไม่ยืนยัน
        $sql = "SELECT Image_Path FROM location_image WHERE Image_Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();

        if ($image) {
            // ลบภาพจากระบบไฟล์
            $imagePath = $image['Image_Path'];
            $fullPath = realpath(__DIR__ . '/' . $imagePath);

            // ตรวจสอบให้แน่ใจว่า path อยู่ในโฟลเดอร์ที่ปลอดภัย
            $allowedPath = realpath(__DIR__);
            if (strpos($fullPath, $allowedPath) === 0 && file_exists($fullPath)) {
                unlink($fullPath); // ลบไฟล์ภาพ
            }

            // ลบข้อมูลภาพจากฐานข้อมูล
            $sql = "DELETE FROM location_image WHERE Image_Id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $imageId);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Image deleted']);
            } else {
                throw new Exception("Failed to delete image from database");
            }
        } else {
            throw new Exception("Image not found");
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
