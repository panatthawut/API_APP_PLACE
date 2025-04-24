<?php
header('Content-Type: application/json');
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// ดึงเฉพาะ users ที่เป็น admin
$sql = "SELECT User_Id, User_Username FROM users WHERE Role_Id = 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $admins = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $admins[] = $row;
    }
    echo json_encode($admins);
} else {
    echo json_encode(["message" => "No admin users found"]);
}

mysqli_close($conn);
?>