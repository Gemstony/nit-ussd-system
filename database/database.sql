CREATE DATABASE IF NOT EXISTS nit_ussd_system;
USE nit_ussd_system;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,   -- stores password_hash() output
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reg_no (reg_no),
    INDEX idx_phone (phone_number)
);


CREATE TABLE results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(20) NOT NULL,
    semester VARCHAR(10) NOT NULL,      -- e.g., "2024/2025 Sem I"
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100),
    grade VARCHAR(5),
    marks DECIMAL(5,2),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reg_no) REFERENCES students(reg_no) ON DELETE CASCADE,
    INDEX idx_reg_sem (reg_no, semester),
    INDEX idx_reg_course (reg_no, course_code)
);


CREATE TABLE fee_balances (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(20) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    total_fees DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    balance DECIMAL(12,2) GENERATED ALWAYS AS (total_fees - paid_amount) STORED,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reg_no) REFERENCES students(reg_no) ON DELETE CASCADE,
    UNIQUE KEY unique_reg_sem (reg_no, semester),
    INDEX idx_balance (balance)
);



CREATE TABLE course_registrations (
    reg_id INT AUTO_INCREMENT PRIMARY KEY,
    reg_no VARCHAR(20) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('registered', 'dropped', 'completed') DEFAULT 'registered',
    FOREIGN KEY (reg_no) REFERENCES students(reg_no) ON DELETE CASCADE,
    UNIQUE KEY unique_reg_course (reg_no, semester, course_code),
    INDEX idx_reg_sem (reg_no, semester)
);

CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM('students', 'staff', 'all') DEFAULT 'all',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_active (is_active, target_audience)
);

CREATE TABLE ussd_sessions (
    session_id VARCHAR(50) PRIMARY KEY,   -- provided by Beem Africa
    phone_number VARCHAR(15) NOT NULL,
    current_state VARCHAR(50) NOT NULL,   -- e.g., 'welcome', 'awaiting_regno', 'awaiting_pin'
    payload JSON,                         -- optional: store extra data like reg_no after authentication
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,            -- implement timeout (e.g., +60 seconds)
    INDEX idx_phone (phone_number),
    INDEX idx_state (current_state),
    INDEX idx_expires (expires_at)
);

CREATE TABLE admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



INSERT INTO admin_users (username, password_hash, role) VALUES ('scar avo', '$2y$10$5xiq9CL78jGZtwgusLyATedqG8twTAhXRjny3G6OCcJEqTw77SQ5a', 'admin');


-- Insert a test student
-- PIN = 1234
INSERT INTO students (
    reg_no,
    full_name,
    phone_number,
    pin_hash
) VALUES (
    'NIT/2022/1234',
    'John Doe',
    '255712345678',
    '$2y$10$b7eoX6hfXgopcLziU9yRR.YIDeU43Vf24XIjAe0DCXmMvPO4g6rkO'
);

-- Insert student result
INSERT INTO results (
    reg_no,
    semester,
    course_code,
    grade,
    marks
) VALUES (
    'NIT/2022/1234',
    '2024/2025 Sem I',
    'BIT 101',
    'B+',
    78.5
);

-- Insert fee balance
INSERT INTO fee_balances (
    reg_no,
    semester,
    total_fees,
    paid_amount
) VALUES (
    'NIT/2022/1234',
    '2024/2025 Sem I',
    1250000.00,
    800000.00
);

-- Insert course registration
INSERT INTO course_registrations (
    reg_no,
    semester,
    course_code,
    registration_date
) VALUES (
    'NIT/2022/1234',
    '2024/2025 Sem I',
    'BIT 101',
    '2024-08-15'
);

-- Insert announcement
INSERT INTO announcements (
    title,
    content,
    target_audience
) VALUES (
    'Library Hours',
    'Library will be open until 10 PM during exams.',
    'students'
);

-- Simulate USSD session
INSERT INTO ussd_sessions (
    session_id,
    phone_number,
    current_state,
    payload,
    expires_at
) VALUES (
    'session_12345',
    '255712345678',
    'awaiting_pin',
    '{"reg_no":"NIT/2022/1234"}',
    NOW() + INTERVAL 60 SECOND
);