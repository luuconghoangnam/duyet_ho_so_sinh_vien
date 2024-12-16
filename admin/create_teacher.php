<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Tên đăng nhập đã tồn tại.";
    } else {
   
        $sql = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'TEACHER')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $full_name);
        if ($stmt->execute()) {
        
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "Đã xảy ra lỗi khi tạo tài khoản giáo viên.";
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Tài Khoản Giáo Viên</title>
</head>
<body>
    <h1>Tạo Tài Khoản Giáo Viên</h1>
    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label for="full_name">Họ và tên:</label>
        <input type="text" id="full_name" name="full_name" required><br><br>
        <label for="username">Tên đăng nhập:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Mật khẩu:</label>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" value="Tạo Tài Khoản">
    </form>
</body>
</html>