<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db.php';

$username = $_SESSION['username'];

// Fetch user information
$sql = "SELECT id, full_name FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $full_name);
$stmt->fetch();
$stmt->close();

// Handle delete application request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application'])) {
    $application_id = $_POST['application_id'];

    $delete_sql = "DELETE FROM applications WHERE id = ? AND student_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $application_id, $user_id);
    if ($stmt->execute()) {
        $delete_message = "Hồ sơ đã được xóa thành công.";
    } else {
        $delete_message = "Có lỗi xảy ra khi xóa hồ sơ.";
    }
    $stmt->close();
}

// Handle edit application request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_application'])) {
    $application_id = $_POST['application_id'];
    $exam_block_code = $_POST['exam_block_code'];
    $grades_json = json_encode($_POST['grades']);

    $edit_sql = "UPDATE applications SET exam_block_code = ?, grades = ?, status = 'PENDING' WHERE id = ? AND student_id = ?";
    $stmt = $conn->prepare($edit_sql);
    $stmt->bind_param("ssii", $exam_block_code, $grades_json, $application_id, $user_id);
    if ($stmt->execute()) {
        $edit_message = "Hồ sơ đã được cập nhật thành công và đang chờ duyệt.";
    } else {
        $edit_message = "Có lỗi xảy ra khi cập nhật hồ sơ.";
    }
    $stmt->close();
}

// Fetch applications for the logged-in student
$sql = "SELECT a.id, m.name AS major_name, a.exam_block_code, a.grades, a.status 
        FROM applications a 
        JOIN majors m ON a.major_id = m.id 
        WHERE a.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Đã Nộp - Student</title>
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
        .application {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .status-approved { color: green; }
        .status-pending { color: orange; }
        .status-rejected { color: red; }
        .status-edit_required { color: blue; }
        .delete-button, .edit-button {
            padding: 8px 16px;
            background-color: red;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .edit-button {
            background-color: blue;
        }
        .delete-button:hover {
            background-color: darkred;
        }
        .edit-button:hover {
            background-color: darkblue;
        }
        .logout-button {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Chào mừng, <?php echo htmlspecialchars($full_name); ?></h1>
        <form method="post" action="../auth/logout.php">
            <input type="submit" name="logout" value="Logout" class="logout-button">
        </form>
    </header>
    <main>
        <h2>Hồ Sơ Đã Nộp</h2>

        <?php
        if (isset($delete_message)) {
            echo '<p class="message">' . $delete_message . '</p>';
        }
        if (isset($edit_message)) {
            echo '<p class="message">' . $edit_message . '</p>';
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $grades = json_decode($row['grades'], true);
                $status_class = '';
                $status_text = '';
                switch ($row['status']) {
                    case 'APPROVED':
                        $status_class = 'status-approved';
                        $status_text = 'Đã duyệt';
                        break;
                    case 'PENDING':
                        $status_class = 'status-pending';
                        $status_text = 'Chưa duyệt';
                        break;
                    case 'REJECTED':
                        $status_class = 'status-rejected';
                        $status_text = 'Không duyệt';
                        break;
                    case 'EDIT_REQUIRED':
                        $status_class = 'status-edit_required';
                        $status_text = 'Yêu cầu chỉnh sửa hồ sơ';
                        break;
                }

                echo '<div class="application">';
                echo '<h3>' . htmlspecialchars($row['major_name']) . '</h3>';
                echo '<p>Khối xét tuyển: ' . htmlspecialchars($row['exam_block_code']) . '</p>';
                echo '<p>Điểm: ';
                foreach ($grades as $subject => $grade) {
                    echo htmlspecialchars($subject) . ': ' . htmlspecialchars($grade) . ', ';
                }
                echo '</p>';
                echo '<p class="' . $status_class . '">Trạng thái: ' . $status_text . '</p>';

                // Delete button
                echo '<form method="POST" action="">';
                echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                echo '<button type="submit" name="delete_application" class="delete-button">Xóa Hồ Sơ</button>';
                echo '</form>';

                // Edit 
                if ($row['status'] === 'EDIT_REQUIRED') {
                    echo '<form method="POST" action="">';
                    echo '<input type="hidden" name="application_id" value="' . $row['id'] . '">';
                    echo '<p>Khối xét tuyển: <input type="text" name="exam_block_code" value="' . htmlspecialchars($row['exam_block_code']) . '"></p>';
                    foreach ($grades as $subject => $grade) {
                        echo '<p>' . htmlspecialchars($subject) . ': <input type="text" name="grades[' . htmlspecialchars($subject) . ']" value="' . htmlspecialchars($grade) . '"></p>';
                    }
                    echo '<button type="submit" name="edit_application" class="edit-button">Cập Nhật Hồ Sơ</button>';
                    echo '</form>';
                }

                echo '</div>';
            }
        } else {
            echo '<p>Bạn chưa nộp hồ sơ nào.</p>';
        }
        $conn->close();
        ?>
    </main>
</body>
</html>
