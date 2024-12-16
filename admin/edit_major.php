<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_major'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("SELECT * FROM majors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $major = $result->fetch_assoc();
    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_major'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $exam_blocks = json_encode($_POST['exam_blocks']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE majors SET name = ?, exam_blocks = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $name, $exam_blocks, $start_date, $end_date, $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php");
    exit();
} else {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Ngành</title>
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
    </style>
</head>
<body>
    <header>
        <h1>Sửa Ngành</h1>
    </header>
    <main>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($major['id']); ?>">
            <div class="form-group">
                <label for="name">Tên ngành:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($major['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Khối xét tuyển:</label>
                <div>
                    <label><input type="checkbox" name="exam_blocks[]" value="A00" <?php echo in_array('A00', json_decode($major['exam_blocks'], true)) ? 'checked' : ''; ?>> A00</label>
                </div>
                <div>
                    <label><input type="checkbox" name="exam_blocks[]" value="A01" <?php echo in_array('A01', json_decode($major['exam_blocks'], true)) ? 'checked' : ''; ?>> A01</label>
                </div>
                <div>
                    <label><input type="checkbox" name="exam_blocks[]" value="C00" <?php echo in_array('C00', json_decode($major['exam_blocks'], true)) ? 'checked' : ''; ?>> C00</label>
                </div>
            </div>
            <div class="form-group">
                <label for="start_date">Thời gian bắt đầu:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($major['start_date']); ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">Thời gian kết thúc:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($major['end_date']); ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Trạng thái:</label>
                <select id="status" name="status" required>
                    <option value="VISIBLE" <?php echo $major['status'] === 'VISIBLE' ? 'selected' : ''; ?>>Hiện</option>
                    <option value="HIDDEN" <?php echo $major['status'] === 'HIDDEN' ? 'selected' : ''; ?>>Ẩn</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" name="update_major">Cập nhật ngành</button>
            </div>
        </form>
    </main>
</body>
</html>