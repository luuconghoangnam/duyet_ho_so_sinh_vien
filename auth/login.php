<?php
session_start();
require_once '../includes/db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id']; 
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($_SESSION['role'] === 'STUDENT') {
            header("Location: ../student/home.php");
            exit();
        } elseif ($_SESSION['role'] === 'ADMIN') {
            header("Location: ../admin/admin_dashboard.php");
            exit();
        } elseif ($_SESSION['role'] === 'TEACHER') {
            header("Location: ../teacher/teacher_home.php");
            exit();
        } else {
            $error_message = "Role không hợp lệ.";
        }
    } else {
        $error_message = "Invalid username or password.";
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
    <title>Login</title>
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
        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
        }
        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-container input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-container input[type="submit"]:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>