<?php
include '../connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $image_id = $_POST["image_id"] ?? null;
    $user_id = $_POST["user_id"] ?? null;
    $vote_value = $_POST["vote_value"] ?? null;

    if (!$image_id || !$user_id || !in_array($vote_value, [1, -1])) {
        echo json_encode(["error" => "พารามิเตอร์ไม่ถูกต้อง"]);
        exit;
    }

    // ตรวจสอบว่าผู้ใช้เคยโหวตมาก่อนหรือไม่
    $checkVote = $conn->prepare("SELECT Vote_Value FROM image_votes WHERE Image_Id = ? AND User_Id = ?");
    $checkVote->bind_param("ii", $image_id, $user_id);
    $checkVote->execute();
    $result = $checkVote->get_result();
    $existingVote = $result->fetch_assoc();

    if ($existingVote) {
        $currentVote = $existingVote['Vote_Value'];

        if ($currentVote == $vote_value) {
            // ⚠️ ถ้าโหวตซ้ำค่าเดิม → ไม่ต้องทำอะไร
            echo json_encode(["message" => "คุณได้โหวตไปแล้ว"]);
        } else {
            // 🔄 ถ้าเปลี่ยนใจ → อัปเดตโหวตใหม่
            $updateVote = $conn->prepare("UPDATE image_votes SET Vote_Value = ?, Created_At = NOW() WHERE Image_Id = ? AND User_Id = ?");
            $updateVote->bind_param("iii", $vote_value, $image_id, $user_id);
            if ($updateVote->execute()) {
                echo json_encode(["message" => "โหวตของคุณถูกอัปเดต"]);
            } else {
                echo json_encode(["error" => "ไม่สามารถอัปเดตโหวตได้"]);
            }
        }
    } else {
        // ✅ ถ้ายังไม่เคยโหวต → เพิ่มโหวตใหม่
        $insertVote = $conn->prepare("INSERT INTO image_votes (Image_Id, User_Id, Vote_Value, Created_At) VALUES (?, ?, ?, NOW())");
        $insertVote->bind_param("iii", $image_id, $user_id, $vote_value);
        if ($insertVote->execute()) {
            echo json_encode(["message" => "โหวตสำเร็จ"]);
        } else {
            echo json_encode(["error" => "ไม่สามารถเพิ่มโหวตได้"]);
        }
    }
}
?>
