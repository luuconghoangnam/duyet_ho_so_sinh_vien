<?php
session_start();
require_once '../includes/db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message = "Username đã tồn tại.";
    } else {
        $sql = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'STUDENT')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password, $full_name);
        if ($stmt->execute()) {
           
            header("Location: login.php");
            exit();
        } else {
            $error_message = "Đăng ký không thành công. Vui lòng thử lại.";
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        .register-container h2 {
            margin-bottom: 20px;
        }
        .register-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
        }
        .register-container input[type="text"],
        .register-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .register-container input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .register-container input[type="submit"]:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Đăng Ký</h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="register.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <label for="full_name">Họ và tên:</label>
            <input type="text" id="full_name" name="full_name" required>
            <input type="submit" value="Đăng Ký">
        </form>
    </div>
</body>
</html>