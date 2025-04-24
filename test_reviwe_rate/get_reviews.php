<?php
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_GET['location_id'])) {
    echo json_encode(['success' => false, 'message' => 'ต้องระบุ Location_Id']);
    exit;
}

$location_id = $_GET['location_id'];

// ดึงค่าเฉลี่ย Rating
$query_avg = "SELECT AVG(Rating) AS avg_rating FROM reviews WHERE Location_Id = ?";
$stmt_avg = $conn->prepare($query_avg);
$stmt_avg->bind_param("i", $location_id);
$stmt_avg->execute();
$result_avg = $stmt_avg->get_result();
$row_avg = $result_avg->fetch_assoc();
$avg_rating = $row_avg['avg_rating'] ?? 0; // ถ้าไม่มีรีวิว ให้คืนค่า 0

// ดึงรีวิวทั้งหมดของสถานที่
$query = "SELECT r.Review_Id, r.User_Id, u.User_Username, r.Rating, r.Comment, r.Updated_At 
          FROM reviews r 
          JOIN users u ON r.User_Id = u.User_Id
          WHERE r.Location_Id = ?
          ORDER BY r.Updated_At DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $location_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode([
    'success' => true,
    'avg_rating' => round($avg_rating, 2), // ปัดค่าทศนิยมให้เหลือ 2 ตำแหน่ง
    'reviews' => $reviews
]);
?>