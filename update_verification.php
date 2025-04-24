<?php
include 'connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
mysqli_set_charset($conn, 'utf8mb4');
$location_id = $_POST['Location_Id'];
$user_id = $_POST['User_Id'];
$vote = $_POST['Vote'];
$update_data = json_decode($_POST['Update_Data'], true, 512, JSON_UNESCAPED_UNICODE);

if ($vote == 1) {
    $sql = "UPDATE location SET Verified_Count = Verified_Count + 1 WHERE Location_Id = ?";
} elseif ($vote == -1) {
    $sql = "UPDATE location SET Rejected_Count = Rejected_Count + 1 WHERE Location_Id = ?";
} else {
    echo json_encode(["message" => "No changes made"]);
    exit();
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $location_id);
$stmt->execute();

if ($vote == -1 && !empty($update_data)) {
    $stmt = $conn->prepare("UPDATE location l JOIN address a ON l.Address_Id = a.Address_Id 
        SET l.Location_Name=?, l.Description=?, l.Type_Id=?, l.Verified_Count=0, l.Rejected_Count=0,
            a.Street=?, a.Sub_Dist=?, a.District=?, a.Province=?, a.Zip_Code=?
        WHERE l.Location_Id=?");
    
    $type_id = intval($update_data['Type_Id']);
    $sub_dist = intval($update_data['Sub_Dist']);
    $zip_code = intval($update_data['Zip_Code']);

    $stmt->bind_param("ssisisssi",
        $update_data['Location_Name'], 
        $update_data['Description'], 
        $type_id, 
        $update_data['Street'], 
        $sub_dist, 
        $update_data['District'], 
        $update_data['Province'], 
        $zip_code, 
        $location_id
    );
    $stmt->execute();
}
file_put_contents("debug_log.txt", print_r($_POST, true));

echo json_encode(["message" => "Verification updated successfully"]);
$conn->close();
?>
