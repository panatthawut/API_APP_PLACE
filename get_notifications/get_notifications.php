<?php
header('Content-Type: application/json');
include '../connection.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'user_id ไม่ถูกต้อง']);
    exit;
}

$user_id = $_GET['user_id'];

$query = "SELECT n.Notification_Id, n.Message, n.Created_At, 
                 lv.Version_Id, lv.Version_Number, lv.Changed_At AS Version_Created_At, lv.Location_Id
          FROM notifications n
          JOIN location_versions lv ON n.Version_Id = lv.Version_Id
          WHERE n.User_Id = ? AND n.Is_Read = 0 
          ORDER BY n.Created_At DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $version_id = $row['Version_Id'];

    // ค้นหารายการการเปลี่ยนแปลงจาก location_changes
    $change_query = "SELECT Column_Name, Old_Value, New_Value, Is_Changed, Is_Confirmed 
                     FROM location_changes 
                     WHERE Version_Id = ?";
    
    $change_stmt = $conn->prepare($change_query);
    $change_stmt->bind_param("i", $version_id);
    $change_stmt->execute();
    $change_result = $change_stmt->get_result();

    $changes = [];
    while ($change_row = $change_result->fetch_assoc()) {
        $changes[] = [
            'Column_Name' => $change_row['Column_Name'],
            'Old_Value' => $change_row['Old_Value'],
            'New_Value' => $change_row['New_Value'],
            'Is_Changed' => (bool) $change_row['Is_Changed'],
            'Is_Confirmed' => (bool) $change_row['Is_Confirmed'],
        ];
    }

    $notifications[] = [
        'Notification_Id' => $row['Notification_Id'],
        'Message' => $row['Message'],
        'Created_At' => $row['Created_At'],
       
        'Version_Info' => [
            'Version_Id' => $row['Version_Id'],
            'Version_Number' => $row['Version_Number'],
            'Version_Created_At' => $row['Version_Created_At'],
            'Location_Id' => $row['Location_Id'], 
            'Changes' => $changes
        ]
    ];
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>