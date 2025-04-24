<?php

include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// ดึงข้อมูลที่อยู่ทั้งหมด
$sql = "SELECT * FROM address";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $addresses = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $addresses[] = $row;
    }
    echo json_encode($addresses);
} else {
    echo json_encode(["message" => "No addresses found"]);
}

mysqli_close($conn);
?>
