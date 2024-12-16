<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$sql = "
    SELECT 
        m.name AS major_name,
        SUM(CASE WHEN a.status = 'APPROVED' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN a.status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN a.status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected_count
    FROM majors m
    LEFT JOIN applications a ON m.id = a.major_id
    GROUP BY m.id
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống Kê Hồ Sơ</title>
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
    </style>
</head>
<body>
    <header>
        <h1>Thống Kê Hồ Sơ</h1>
    </header>
    <main>
        <h2>Thống Kê Hồ Sơ Theo Ngành</h2>
        <table>
            <thead>
                <tr>
                    <th>Tên ngành</th>
                    <th>Số lượng hồ sơ đã duyệt</th>
                    <th>Số lượng hồ sơ chưa duyệt</th>
                    <th>Số lượng hồ sơ không duyệt</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['major_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['approved_count']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['pending_count']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['rejected_count']) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </main>
</body>
</html>