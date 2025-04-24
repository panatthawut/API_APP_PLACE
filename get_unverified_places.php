<?php
include 'connection.php';

$sql = "SELECT * FROM location WHERE Status_Location = 0";
$result = $conn->query($sql);
$places = array();

while ($row = $result->fetch_assoc()) {
    $places[] = $row;
}

echo json_encode($places);
$conn->close();
?>
