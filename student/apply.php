<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: ../auth/login.php");
    exit();
}

try {
  
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Người dùng không tồn tại");
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    
    $major_id = isset($_GET['major_id']) ? intval($_GET['major_id']) : 0;
    

    $stmt = $conn->prepare("SELECT * FROM majors WHERE id = ?");
    $stmt->bind_param("i", $major_id);
    $stmt->execute();
    $major_result = $stmt->get_result();

    if ($major_result->num_rows === 0) {
        throw new Exception("Ngành học không tồn tại");
    }

    $major = $major_result->fetch_assoc();

    $stmt = $conn->prepare("
        SELECT e.code, e.subjects 
        FROM exam_blocks e 
        WHERE JSON_CONTAINS(?, CONCAT('\"', e.code, '\"'))
    ");
    $stmt->bind_param("s", $major['exam_blocks']);
    $stmt->execute();
    $blocks_result = $stmt->get_result();

    $exam_blocks = [];
    $subjects_by_block = [];
    while ($block = $blocks_result->fetch_assoc()) {
        $exam_blocks[] = $block['code'];
        $subjects_by_block[$block['code']] = json_decode($block['subjects'], true);
    }


    $stmt = $conn->prepare("
        SELECT * FROM applications 
        WHERE student_id = ? AND major_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $major_id);
    $stmt->execute();
    $existing_application = $stmt->get_result()->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_application) {
        $exam_block = $_POST['exam_block'];
        if (!in_array($exam_block, $exam_blocks)) {
            throw new Exception("Khối thi không hợp lệ");
        }

        $grades = [];
        $total_points = 0;
        foreach ($_POST['grades'] as $subject => $grade) {
            $grade = floatval($grade);
            if ($grade < 0 || $grade > 10) {
                throw new Exception("Điểm số không hợp lệ");
            }
            $grades[$subject] = $grade;
            $total_points += $grade;
        }

        $transcript = $_FILES['transcript'];
        $allowed_types = ['image/jpeg', 'image/png'];
        
        if (!isset($transcript) || $transcript['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Lỗi upload file");
        }

        if (!in_array($transcript['type'], $allowed_types)) {
            throw new Exception("Loại file không hợp lệ");
        }

        if ($transcript['size'] > 100 * 1024 * 1024) {
            throw new Exception("File quá lớn");
        }

        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . $user_id . '.' . pathinfo($transcript['name'], PATHINFO_EXTENSION);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($transcript['tmp_name'], $file_path)) {
            $stmt = $conn->prepare("
                INSERT INTO applications (
                    student_id, major_id, exam_block_code, 
                    grades, transcript, total_points
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $grades_json = json_encode($grades);
            $stmt->bind_param("iisssd", 
                $user_id, $major_id, $exam_block, 
                $grades_json, $file_path, $total_points
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi lưu hồ sơ");
            }

            header("Location: home.php?success=1");
            exit();
        } else {
            throw new Exception("Lỗi khi upload file");
        }
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp Hồ Sơ Xét Tuyển</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --success-color: #27ae60;
            --error-color: #c0392b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: #f5f6fa;
            color: #2c3e50;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 1rem;
        }

        .application-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .grade-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .grade-input {
            display: flex;
            flex-direction: column;
        }

        input[type="number"] {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        input[type="file"] {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: var(--secondary-color);
        }

        .error {
            color: var(--error-color);
            background: #ffd2d2;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .success {
            color: var(--success-color);
            background: #d4edda;
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Nộp Hồ Sơ Xét Tuyển</h1>
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        </header>

        <div class="application-form">
            <h2><?php echo htmlspecialchars($major['name'] ?? ''); ?></h2>
            
            <?php if ($existing_application): ?>
                <div class="notice">Bạn đã nộp hồ sơ cho ngành này.</div>
            <?php elseif (!empty($exam_blocks)): ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Chọn khối xét tuyển:</label>
                        <select name="exam_block" required onchange="updateSubjects(this.value)">
                            <?php foreach ($exam_blocks as $block): ?>
                                <option value="<?php echo htmlspecialchars($block); ?>">
                                    <?php echo htmlspecialchars($block); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nhập điểm các môn:</label>
                        <div id="subjectInputs" class="grade-inputs">
                            <?php foreach ($subjects_by_block[$exam_blocks[0]] as $subject): ?>
                                <div class="grade-input">
                                    <label for="grade_<?php echo htmlspecialchars($subject); ?>">
                                        <?php echo htmlspecialchars($subject); ?>:
                                    </label>
                                    <input type="number" 
                                           id="grade_<?php echo htmlspecialchars($subject); ?>"
                                           name="grades[<?php echo htmlspecialchars($subject); ?>]"
                                           min="0" max="10" step="0.01" required
                                           onchange="calculateTotal()">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="grade-total">
                            <span>Tổng điểm: </span>
                            <span id="totalGrade">0</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload học bạ (JPG/PNG, tối đa 100MB):</label>
                        <input type="file" name="transcript" accept=".jpg,.jpeg,.png" required>
                    </div>

                    <input type="hidden" name="total_points" id="total_points" value="0">
                    <button type="submit" class="submit-btn">Nộp hồ sơ</button>
                </form>

                <script>
                const subjects = <?php echo json_encode($subjects_by_block); ?>;

                function calculateTotal() {
                    const inputs = document.querySelectorAll('#subjectInputs input[type="number"]');
                    let total = 0;
                    inputs.forEach(input => {
                        if (input.value) {
                            total += parseFloat(input.value);
                        }
                    });
                    document.getElementById('totalGrade').textContent = total.toFixed(2);
                    document.getElementById('total_points').value = total.toFixed(2);
                }

                function updateSubjects(blockCode) {
                    const container = document.getElementById('subjectInputs');
                    container.innerHTML = '';
                    
                    subjects[blockCode].forEach(subject => {
                        const div = document.createElement('div');
                        div.innerHTML = `
                            <label>${subject}:</label>
                            <input type="number" 
                                   name="grades[${subject}]"
                                   min="0" max="10" 
                                   step="0.01"
                                   onchange="calculateTotal()"
                                   required>
                        `;
                        container.appendChild(div);
                    });
                    calculateTotal();
                }

        
                updateSubjects(document.querySelector('select[name="exam_block"]').value);
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>