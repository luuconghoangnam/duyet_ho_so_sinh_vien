<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teachers'])) {
    $major_id = $_POST['major_id'];
    $teacher_ids = $_POST['teacher_ids'];

    $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE major_id = ?");
    $stmt->bind_param("i", $major_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, major_id) VALUES (?, ?)");
    foreach ($teacher_ids as $teacher_id) {
        $stmt->bind_param("ii", $teacher_id, $major_id);
        $stmt->execute();
    }
    $stmt->close();

    header("Location: admin_dashboard.php");
    exit();
}

$sql = "SELECT id, full_name FROM users WHERE role = 'TEACHER'";
$teachers_result = $conn->query($sql);

$major_id = isset($_GET['major_id']) ? intval($_GET['major_id']) : 0;
$stmt = $conn->prepare("SELECT * FROM majors WHERE id = ?");
$stmt->bind_param("i", $major_id);
$stmt->execute();
$major_result = $stmt->get_result();
$major = $major_result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT teacher_id FROM teacher_assignments WHERE major_id = ?");
$stmt->bind_param("i", $major_id);
$stmt->execute();
$assigned_teachers_result = $stmt->get_result();
$assigned_teachers = [];
while ($row = $assigned_teachers_result->fetch_assoc()) {
    $assigned_teachers[] = $row['teacher_id'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân Công Giáo Viên</title>
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
        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Phân Công Giáo Viên</h1>
    </header>
    <main>
        <h2>Ngành: <?php echo htmlspecialchars($major['name']); ?></h2>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="major_id" value="<?php echo htmlspecialchars($major['id']); ?>">
            <div class="form-group">
                <label for="teacher_ids">Chọn giáo viên:</label>
                <select id="teacher_ids" name="teacher_ids[]" multiple required>
                    <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                        <option value="<?php echo $teacher['id']; ?>" <?php echo in_array($teacher['id'], $assigned_teachers) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" name="assign_teachers">Phân công</button>
            </div>
        </form>
    </main>
</body>
</html>