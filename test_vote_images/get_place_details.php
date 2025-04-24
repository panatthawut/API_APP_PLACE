<?php
include '../connection.php';

$place_id = $_GET["place_id"];
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

// Fetch place details
$sql = "SELECT * FROM location WHERE Location_Id = ?";
$place_stmt = $conn->prepare($sql);
$place_stmt->bind_param("i", $place_id);
$place_stmt->execute();
$place_result = $place_stmt->get_result();
$place = $place_result->fetch_assoc();

// Fetch images with voting statistics
$sql_images = "
    SELECT 
        li.Image_Id, 
        li.Image_Path, 
        COALESCE(SUM(CASE WHEN iv.Vote_Value = 1 THEN 1 ELSE 0 END), 0) AS Likes,
        COALESCE(SUM(CASE WHEN iv.Vote_Value = -1 THEN 1 ELSE 0 END), 0) AS Dislikes,
        (COALESCE(SUM(CASE WHEN iv.Vote_Value = 1 THEN 1 ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN iv.Vote_Value = -1 THEN 1 ELSE 0 END), 0)) AS Vote_Score,
        COUNT(iv.Vote_Id) AS Total_Votes
    FROM location_image li
    LEFT JOIN image_votes iv ON li.Image_Id = iv.Image_Id
    WHERE li.Location_Id = ?
    GROUP BY li.Image_Id
    ORDER BY Vote_Score DESC, Total_Votes DESC
    LIMIT ? OFFSET ?
";


$image_stmt = $conn->prepare($sql_images);
$image_stmt->bind_param("iii", $place_id, $limit, $offset);
$image_stmt->execute();
$image_result = $image_stmt->get_result();

$images = [];
while ($row = $image_result->fetch_assoc()) {
    $image_url = str_replace("\\", "/", $row["Image_Path"]);
    $images[] = [
        "Image_Id" => (int)$row["Image_Id"],
        "Image_URL" => $image_url,
        "Likes" => (int)$row["Likes"],
        "Dislikes" => (int)$row["Dislikes"],
        "Vote_Score" => (int)$row["Vote_Score"],
        "Total_Votes" => (int)$row["Total_Votes"]
    ];
}

echo json_encode(["place" => $place, "images" => $images], JSON_UNESCAPED_SLASHES);

$conn->close();
?>
