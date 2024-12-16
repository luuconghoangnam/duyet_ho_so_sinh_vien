<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo "User ID is not set in the session.";
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
    } elseif (isset($_POST['delete_application']) && $_SESSION['role'] === 'ADMIN') {
        $application_id = $_POST['application_id'];
        $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $stmt->close();
    }
   
    header("Location: view_applications.php?major_id=" . $_GET['major_id']);
    exit();
}


$major_id = isset($_GET['major_id']) ? intval($_GET['major_id']) : 0;
$stmt = $conn->prepare("
    SELECT a.id, u.full_name AS student_name, m.name AS major_name, a.exam_block_code, a.total_points, a.status, r.full_name AS reviewer_name
    FROM applications a
    JOIN users u ON a.student_id = u.id
    JOIN majors m ON a.major_id = m.id
    LEFT JOIN users r ON a.reviewer_id = r.id
    WHERE a.major_id = ?
");
$stmt->bind_param("i", $major_id);
$stmt->execute();
$result = $stmt->get_result();


$major_name = '';
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $major_name = $row['major_name'];
    $result->data_seek(0); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Hồ Sơ</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
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
        <h1>Danh Sách Hồ Sơ</h1>
    </header>
    <main>
        <h2>Ngành: <?php echo htmlspecialchars($major_name); ?></h2>
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Họ tên học sinh</th>
                    <th>Tên ngành nộp hồ sơ</th>
                    <th>Tên khối xét hồ sơ</th>
                    <th>Tổng điểm</th>
                    <th>Tên người duyệt hồ sơ</th>
                    <th>Trạng thái hồ sơ</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $stt++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['major_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['exam_block_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['total_points']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['reviewer_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['status']) . '</td>';
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
                    echo '<form method="POST" action="" style="display:inline-block;">';
                    echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                    echo '<button type="submit" name="delete_application">Xóa</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </main>
</body>
</html>