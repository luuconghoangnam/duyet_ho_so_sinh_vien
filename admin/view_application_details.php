<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'TEACHER')) {
    header("Location: ../auth/login.php");
    exit();
}


$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
$stmt = $conn->prepare("
    SELECT a.id, u.full_name AS student_name, m.name AS major_name, a.exam_block_code, a.total_points, a.grades, a.transcript, a.status, r.full_name AS reviewer_name
    FROM applications a
    JOIN users u ON a.student_id = u.id
    JOIN majors m ON a.major_id = m.id
    LEFT JOIN users r ON a.reviewer_id = r.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();


$grades = json_decode($application['grades'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Hồ Sơ</title>
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
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .grades-table, .grades-table th, .grades-table td {
            border: 1px solid #ddd;
        }
        .grades-table th, .grades-table td {
            padding: 10px;
            text-align: left;
        }
        .grades-table th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <header>
        <h1>Chi Tiết Hồ Sơ</h1>
    </header>
    <main>
        <h2>Họ tên học sinh: <?php echo htmlspecialchars($application['student_name']); ?></h2>
        <p>Ngành nộp hồ sơ: <?php echo htmlspecialchars($application['major_name']); ?></p>
        <p>Khối xét hồ sơ: <?php echo htmlspecialchars($application['exam_block_code']); ?></p>
        <p>Điểm: <?php echo htmlspecialchars($application['total_points']); ?></p>
        <p>Trạng thái hồ sơ: <?php echo htmlspecialchars($application['status']); ?></p>
        <p>Người duyệt hồ sơ: <?php echo htmlspecialchars($application['reviewer_name']); ?></p>
        <p>Ảnh học bạ: <a href="<?php echo htmlspecialchars($application['transcript']); ?>" target="_blank">Xem ảnh</a></p>
        <h3>Điểm các môn:</h3>
        <table class="grades-table">
            <thead>
                <tr>
                    <th>Môn học</th>
                    <th>Điểm</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $subject => $grade): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($subject); ?></td>
                        <td><?php echo htmlspecialchars($grade); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>