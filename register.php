<?php
include 'connection.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // เก็บเป็น plain text

    // ตรวจสอบว่าผู้ใช้มีอยู่แล้วหรือไม่
    $check_stmt = $conn->prepare("SELECT User_Id FROM users WHERE User_Username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
    } else {
        // เพิ่มผู้ใช้ใหม่
        $role_id = 2; // ค่า default role (User)
        $stmt = $conn->prepare("INSERT INTO users (User_Username, User_Password, Role_Id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $password, $role_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User registered successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error registering user']);
        }

        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
