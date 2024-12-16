<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam_block'])) {
    $major_id = $_POST['major_id'];
    $new_exam_block = $_POST['new_exam_block'];
    $subjects = [
        $_POST['subject1'],
        $_POST['subject2'],
        $_POST['subject3']
    ];

   
    $subjects_json = json_encode($subjects);
    $stmt = $conn->prepare("INSERT INTO exam_blocks (code, subjects) VALUES (?, ?)");
    $stmt->bind_param("ss", $new_exam_block, $subjects_json);
    if ($stmt->execute()) {
        
        $stmt = $conn->prepare("SELECT exam_blocks FROM majors WHERE id = ?");
        $stmt->bind_param("i", $major_id);
        $stmt->execute();
        $stmt->bind_result($exam_blocks_json);
        $stmt->fetch();
        $stmt->close();

        $exam_blocks = json_decode($exam_blocks_json, true);
        $exam_blocks[] = $new_exam_block;

        $exam_blocks_json = json_encode($exam_blocks);
        $stmt = $conn->prepare("UPDATE majors SET exam_blocks = ? WHERE id = ?");
        $stmt->bind_param("si", $exam_blocks_json, $major_id);
        if ($stmt->execute()) {
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "Đã xảy ra lỗi khi cập nhật ngành.";
        }
        $stmt->close();
    } else {
        $error_message = "Đã xảy ra lỗi khi thêm khối.";
    }
    $stmt->close();
}

$sql = "SELECT id, name FROM majors";
$majors_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Khối</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        main {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Thêm Khối</h1>
    </header>
    <main>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="new_exam_block">Tên Khối Mới:</label>
                <input type="text" id="new_exam_block" name="new_exam_block" required>
            </div>
            <div class="form-group">
                <label for="subject1">Tên Môn Học 1:</label>
                <input type="text" id="subject1" name="subject1" required>
            </div>
            <div class="form-group">
                <label for="subject2">Tên Môn Học 2:</label>
                <input type="text" id="subject2" name="subject2" required>
            </div>
            <div class="form-group">
                <label for="subject3">Tên Môn Học 3:</label>
                <input type="text" id="subject3" name="subject3" required>
            </div>
            <div class="form-group">
                <button type="submit" name="add_exam_block">Thêm Khối</button>
            </div>
        </form>
    </main>
</body>
</html>