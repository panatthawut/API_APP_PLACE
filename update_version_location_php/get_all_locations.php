<?php
include '../connection.php';

$query = "SELECT Location_Id, Location_Name FROM location ORDER BY Location_Name ASC";
$result = $conn->query($query);

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

echo json_encode($locations);
?>
