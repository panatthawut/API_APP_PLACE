<?php
// ส่วนของsql ฉันต้องการเพิ่มในคอลัม Location_Class ตาราง location โดยเพิ่มชื่อของโฟเดอลงไปในคอลัม 
include 'connection.php'; // เชื่อมต่อฐานข้อมูล

// เขียนคำสั่ง SQL สำหรับดึงข้อมูล Location_Id และ Location_Name
$sql = "SELECT Location_Id, Location_Name FROM location";
$result = $conn->query($sql);

// ตรวจสอบว่ามีข้อมูลในตารางหรือไม่
if ($result->num_rows > 0) {
    // แสดงข้อมูลเป็นตาราง HTML
    echo "<table border='1'>";
    echo "<tr><th>Location_Id</th><th>Location_Name</th></tr>";
    
    // วนลูปเพื่อแสดงข้อมูลแต่ละแถว
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["Location_Id"] . "</td><td>" . $row["Location_Name"] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

// ปิดการเชื่อมต่อกับฐานข้อมูล
$conn->close();
?>
