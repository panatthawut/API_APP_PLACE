<?php
include 'connection.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT User_Id, User_Password, Role_Id, User_Username FROM users WHERE User_Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password === $row['User_Password']) { // เปรียบเทียบรหัสผ่านแบบ plain text
            // ดึง Role Name
            $role_stmt = $conn->prepare("SELECT Role_Name FROM role WHERE Role_Id = ? LIMIT 1");
            $role_stmt->bind_param("i", $row['Role_Id']);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $role = ($role_row = $role_result->fetch_assoc()) ? $role_row['Role_Name'] : 'User';
            $role_stmt->close();

            echo json_encode([
                'success' => true,
                'user_id' => $row['User_Id'],
                'role' => $role,
                'username' => $row['User_Username']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

    $stmt->close();
    $conn->close();
}