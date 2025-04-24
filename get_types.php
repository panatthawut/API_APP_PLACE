<?php
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

$query = "SELECT Type_Id, Type_Name FROM type";
$result = mysqli_query($conn, $query);

$types = array();

while ($row = mysqli_fetch_assoc($result)) {
    $types[] = $row;
}

header('Content-Type: application/json');
echo json_encode($types);
?>