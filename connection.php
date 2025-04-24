<?php
$servername = "127.0.0.1";
$username = "root";
$password = ""; // ไม่มีรหัสผ่าน
$dbname = "place_app_db";
$port = 3307; // เพิ่มพอร์ตตาม config.inc.php

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

?>