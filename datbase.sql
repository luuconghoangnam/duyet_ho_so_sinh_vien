Drop DATABASE if exists k73_3;
CREATE DATABASE k72_3;
USE k72_3;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('ADMIN', 'TEACHER', 'STUDENT') NOT NULL
);


CREATE TABLE majors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    exam_blocks JSON NOT NULL, 
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('VISIBLE', 'HIDDEN') NOT NULL
);


CREATE TABLE exam_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE, 
    subjects JSON NOT NULL 
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    major_id INT NOT NULL,
    exam_block_code VARCHAR(10) NOT NULL, 
    grades JSON NOT NULL, 
    transcript VARCHAR(255) NOT NULL, 
    status ENUM('APPROVED', 'REJECTED', 'PENDING', 'EDIT_REQUIRED') DEFAULT 'PENDING',
    reviewer_id INT, 
    submission_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    UNIQUE(student_id, major_id), 
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (major_id) REFERENCES majors(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (exam_block_code) REFERENCES exam_blocks(code)
);

CREATE TABLE teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    major_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (major_id) REFERENCES majors(id),
    
);

CREATE TABLE statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    major_id INT NOT NULL,
    approved_count INT DEFAULT 0,
    rejected_count INT DEFAULT 0,
    pending_count INT DEFAULT 0,
    FOREIGN KEY (major_id) REFERENCES majors(id)
);

INSERT INTO exam_blocks (code, subjects) VALUES
('A00', '["Toán", "Lý", "Hóa"]'),
('A01', '["Toán", "Lý", "Anh"]'),
('C00', '["Văn", "Sử", "Địa"]');

INSERT INTO users (username, password, full_name, role) VALUES
('admin_user', 'e10adc3949ba59abbe56e057f20f883e', 'Admin User', 'ADMIN'),
('teacher_user', 'e10adc3949ba59abbe56e057f20f883e', 'Teacher User', 'TEACHER'),
('student_user', 'e10adc3949ba59abbe56e057f20f883e', 'Student User', 'STUDENT');

INSERT INTO majors (name, exam_blocks, start_date, end_date, status) VALUES
('Công nghệ thông tin', '["A00", "A01"]', '2024-11-01', '2024-12-15', 'VISIBLE'),
('Kinh tế', '["A00", "C00"]', '2024-11-01', '2024-12-15', 'VISIBLE');

ALTER TABLE applications
ADD COLUMN total_points FLOAT NOT NULL;


