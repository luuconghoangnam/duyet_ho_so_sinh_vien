<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'TEACHER') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_application'])) {
        $application_id = $_POST['application_id'];
        $stmt = $conn->prepare("UPDATE applications SET status = 'APPROVED', reviewer_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $application_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['reject_application'])) {
        $application_id = $_POST['application_id'];
        $stmt = $conn->prepare("UPDATE applications SET status = 'REJECTED', reviewer_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $application_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit_required'])) {
        $application_id = $_POST['application_id'];
        $stmt = $conn->prepare("UPDATE applications SET status = 'EDIT_REQUIRED', reviewer_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $application_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit();
    }
    header("Location: teacher_home.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT m.id AS major_id, m.name AS major_name 
    FROM teacher_assignments ta
    JOIN majors m ON ta.major_id = m.id
    WHERE ta.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$majors_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Home</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
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
        button {
            padding: 8px 16px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }
        button:hover {
            background-color: #555;
        }
        .major-section {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .major-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <h1>Teacher Home</h1>
        <form method="post" action="">
            <input type="submit" name="logout" value="Logout" class="logout-button">
        </form>
    </header>
    <main>
        <?php
        while ($major = $majors_result->fetch_assoc()) {
            $stmt = $conn->prepare("
                SELECT a.id, u.full_name AS student_name, a.exam_block_code, a.total_points, a.status, r.full_name AS reviewer_name
                FROM applications a
                JOIN users u ON a.student_id = u.id
                LEFT JOIN users r ON a.reviewer_id = r.id
                WHERE a.major_id = ?
            ");
            $stmt->bind_param("i", $major['major_id']);
            $stmt->execute();
            $applications_result = $stmt->get_result();
            $stmt->close();
        ?>
            <div class="major-section">
                <h2><?php echo htmlspecialchars($major['major_name']); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Họ tên học sinh</th>
                            <th>Khối xét hồ sơ</th>
                            <th>Tổng điểm</th>
                            <th>Trạng thái hồ sơ</th>
                            <th>Người duyệt cuối cùng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stt = 1;
                        while ($row = $applications_result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $stt++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['exam_block_code']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['total_points']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['reviewer_name']) . '</td>';
                            echo '<td>';
                            echo '<form method="POST" action="" style="display:inline-block;">';
                            echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" name="approve_application">Duyệt</button>';
                            echo '</form>';
                            echo '<form method="POST" action="" style="display:inline-block;">';
                            echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" name="reject_application">Không duyệt</button>';
                            echo '</form>';
                            echo '<form method="GET" action="view_application_details.php" style="display:inline-block;">';
                            echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                            echo '<button type="submit">Xem chi tiết</button>';
                            echo '</form>';
                            //
                            // echo '<form method="POST" action="" style="display:inline-block;">';
                            // echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                            // echo '<button type="submit" name="edit_required">Yêu cầu chỉnh sửa hồ sơ</button>';
                            // echo '</form>';
                            echo '<form method="POST" action="" style="display:inline-block;">';
                            echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                            echo '<button type="submit" name="edit_required">Yêu cầu chỉnh sửa hồ sơ</button>';
                            echo '</form>';
                            //
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php
        }
        ?>
    </main>
</body>
</html>