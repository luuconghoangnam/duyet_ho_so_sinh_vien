<?php
session_start();
require_once '../includes/db.php';

// Authentication check
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_major'])) {
    $name = $_POST['name'];
    $exam_blocks = json_encode($_POST['exam_blocks']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO majors (name, exam_blocks, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $exam_blocks, $start_date, $end_date, $status);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error_message = "Đã xảy ra lỗi khi tạo ngành.";
    }
    $stmt->close();
}

// Fetch all exam blocks
$sql = "SELECT code FROM exam_blocks";
$exam_blocks_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Ngành</title>
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
        <h1>Tạo Ngành</h1>
    </header>
    <main>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Tên ngành:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label>Khối xét tuyển:</label>
                <?php while ($exam_block = $exam_blocks_result->fetch_assoc()): ?>
                    <div>
                        <label><input type="checkbox" name="exam_blocks[]" value="<?php echo $exam_block['code']; ?>"> <?php echo $exam_block['code']; ?></label>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="form-group">
                <label for="start_date">Thời gian bắt đầu:</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">Thời gian kết thúc:</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <div class="form-group">
                <label for="status">Trạng thái:</label>
                <select id="status" name="status" required>
                    <option value="VISIBLE">Hiện</option>
                    <option value="HIDDEN">Ẩn</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" name="create_major">Tạo ngành</button>
            </div>
        </form>
    </main>
</body>
</html>