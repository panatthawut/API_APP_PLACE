<?php
include '../connection.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// คำสั่ง SQL เพื่อดึง 5 สถานที่ที่มีคะแนนรีวิวเฉลี่ยสูงสุด
$sql = "
SELECT 
    l.Location_Id, 
    l.Location_Name, 
    l.Description, 
    l.Status_Location,
    l.Location_Class,
    t.Type_Name, 
    a.Province,
    COALESCE(AVG(r.Rating), 0) AS Avg_Rating, 
    (SELECT Image_Path FROM location_image 
     WHERE location_image.Location_Id = l.Location_Id 
     LIMIT 1) AS Image_Path
FROM location l
LEFT JOIN reviews r ON l.Location_Id = r.Location_Id
LEFT JOIN type t ON l.Type_Id = t.Type_Id
LEFT JOIN address a ON l.Address_Id = a.Address_Id
GROUP BY l.Location_Id
ORDER BY Avg_Rating DESC 
LIMIT 7;
";

$result = $conn->query($sql);

$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = [
            "Location_Id" => $row["Location_Id"],
            "Location_Name" => $row["Location_Name"],
            "Description" => $row["Description"],
            "Location_Class" => $row["Location_Class"],
            "Status_Location" => $row["Status_Location"],
            "Type_Name" => $row["Type_Name"],
            "Province" => $row["Province"],
            "Avg_Rating" => floatval($row["Avg_Rating"]),
            "Image_Path" => $row["Image_Path"] ,
        ];
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ส่งข้อมูลกลับเป็น JSON
header('Content-Type: application/json');
echo json_encode(["success" => true, "top_locations" => $locations]);
?>
