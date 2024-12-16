<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db.php';

$username = $_SESSION['username'];
$sql = "SELECT full_name FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
$sql = "SELECT id, name, exam_blocks, start_date, end_date FROM majors WHERE status = 'VISIBLE'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Student</title>
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
        .apply-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .logout-button {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .notice {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Chào mừng, <?php echo htmlspecialchars($full_name); ?></h1>
        <form method="post" action="">
            <input type="submit" name="logout" value="Logout" class="logout-button">
        </form>
    </header>
    <main>
        <h2>Danh sách các ngành xét tuyển hồ sơ học bạ</h2>
        <a href="view_applications.php" class="apply-button">Xem hồ sơ đã nộp</a>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $start_date = new DateTime($row['start_date']);
                $end_date = new DateTime($row['end_date']);
                $exam_blocks = json_decode($row['exam_blocks'], true);
                
                echo '<div class="major">';
                echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                echo '<p>Khối xét tuyển: ' . implode(', ', $exam_blocks) . '</p>';
                echo '<p>Thời gian: ' . $start_date->format('d-m-Y') . ' - ' . $end_date->format('d-m-Y') . '</p>';
                

                $current_date = new DateTime();
                if ($current_date >= $start_date && $current_date <= $end_date) {
                    echo '<a href="apply.php?major_id=' . $row['id'] . '" class="apply-button">Nộp hồ sơ</a>';
                } else if ($current_date < $start_date) {
                    echo '<p class="notice">Chưa đến thời gian nộp hồ sơ</p>';
                } else {
                    echo '<p class="notice">Đã hết thời gian nộp hồ sơ</p>';
                }
                echo '</div>';
            }
        } else {
            echo '<p>Không có ngành nào đang xét tuyển.</p>';
        }
        $conn->close();
        ?>
    </main>
</body>
</html>