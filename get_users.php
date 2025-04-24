<?php

include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// คำสั่ง SQL เพื่อดึงข้อมูลจากตาราง user
$sql = "SELECT * FROM user";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $users = array();
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users); // แปลงข้อมูลเป็น JSON
} else {
    echo json_encode([]); // ถ้าไม่มีข้อมูล ให้ส่ง array ว่าง
}

$conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล
?>
