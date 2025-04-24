<?php
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_GET['Location_Id'])) {
    echo json_encode(['success' => false, 'message' => 'Location_Id ไม่ถูกต้อง']);
    exit;
}

$location_id = $_GET['Location_Id'];

$query = "SELECT AVG(Rating) AS Average_Rating, COUNT(*) AS Total_Reviews 
          FROM reviews WHERE Location_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $location_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'average_rating' => round($data['Average_Rating'], 1),
    'total_reviews' => $data['Total_Reviews']
]);
?>
