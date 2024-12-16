<?php
session_start();
require_once '../includes/db.php';


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$error_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (isset($_POST['delete_major'])) {
        $major_id = $_POST['id'];
        
      
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE major_id = ?");
            $stmt->bind_param("i", $major_id);
            $stmt->execute();
            $stmt->close();
            
            
            $stmt = $conn->prepare("DELETE FROM majors WHERE id = ?");
            $stmt->bind_param("i", $major_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            header("Location: admin_dashboard.php");
            exit();
        } catch (Exception $e) {
         
            $conn->rollback();
            $error_message = "Không thể xóa ngành này.";
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit();
    }
}


$sql = "SELECT * FROM majors";
$result = $conn->query($sql);


$assigned_teachers = [];
$teacher_sql = "SELECT ta.major_id, u.full_name 
                FROM teacher_assignments ta 
                JOIN users u ON ta.teacher_id = u.id";
$teacher_result = $conn->query($teacher_sql);
while ($row = $teacher_result->fetch_assoc()) {
    $assigned_teachers[$row['major_id']][] = $row['full_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin-left: 20px;
        }
        header form {
            margin-right: 20px;
        }
        main {
            padding: 20px;
        }
        .major {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
        .logout-button {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .button-container a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
        .button-container a:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <form method="post" action="">
            <input type="submit" name="logout" value="Logout" class="logout-button">
        </form>
    </header>
    <main>
        <h2>Quản lý ngành xét tuyển</h2>
        <div class="button-container">
            <a href="create_major.php">Tạo ngành mới</a>
            <a href="add_exam_block.php">Thêm khối</a>
            <a href="statistics.php">Xem thống kê hồ sơ</a>
            <a href="create_teacher.php">Tạo tài khoản giáo viên</a>
        </div>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <h2>Danh sách ngành xét tuyển</h2>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $exam_blocks = json_decode($row['exam_blocks'], true);
                echo '<div class="major">';
                echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                if (is_array($exam_blocks)) {
                    echo '<p>Khối xét tuyển: ' . implode(', ', $exam_blocks) . '</p>';
                } else {
                    echo '<p>Khối xét tuyển: Không có</p>';
                }
                echo '<p>Thời gian: ' . htmlspecialchars($row['start_date']) . ' - ' . htmlspecialchars($row['end_date']) . '</p>';
                echo '<p>Trạng thái: ' . htmlspecialchars($row['status']) . '</p>';
                if (isset($assigned_teachers[$row['id']])) {
                    echo '<p>Giáo viên phân công: ' . implode(', ', $assigned_teachers[$row['id']]) . '</p>';
                } else {
                    echo '<p>Giáo viên phân công: Chưa có</p>';
                }
                echo '<form method="POST" action="" style="display:inline-block;" onsubmit="return confirm(\'Bạn có chắc chắn muốn xóa ngành này?\');">';
                echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                echo '<button type="submit" name="delete_major">Xóa</button>';
                echo '</form>';
                echo '<form method="POST" action="edit_major.php" style="display:inline-block;">';
                echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                echo '<button type="submit" name="edit_major">Sửa</button>';
                echo '</form>';
                echo '<form method="GET" action="assign_teachers.php" style="display:inline-block;">';
                echo '<input type="hidden" name="major_id" value="' . $row['id'] . '">';
                echo '<button type="submit">Phân công giáo viên</button>';
                echo '</form>';
                echo '<form method="GET" action="view_applications.php" style="display:inline-block;">';
                echo '<input type="hidden" name="major_id" value="' . $row['id'] . '">';
                echo '<button type="submit">Xem hồ sơ đã nộp</button>';
                echo '</form>';
                echo '</div>';
            }
        } else {
            echo '<p>Không có ngành nào.</p>';
        }
        $conn->close();
        ?>
    </main>
</body>
</html>