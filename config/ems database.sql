-- EduSkill Marketplace System (EMS) Database
-- Run this in phpMyAdmin > SQL tab

CREATE DATABASE IF NOT EXISTS ems_db;
USE ems_db;

-- --------------------------------------------------------
-- Table: ministry_officers
-- Pre-seeded admin accounts for the Ministry
-- --------------------------------------------------------
CREATE TABLE ministry_officers (
    officerID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Table: training_providers
-- Registered training organisations (pending/approved/rejected)
-- --------------------------------------------------------
CREATE TABLE training_providers (
    providerID INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    org_profile TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    document_path VARCHAR(255),          -- uploaded supporting doc
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Table: learners
-- Learner accounts
-- --------------------------------------------------------
CREATE TABLE learners (
    learnerID INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Table: courses
-- Courses listed by approved training providers
-- --------------------------------------------------------
CREATE TABLE courses (
    courseID INT AUTO_INCREMENT PRIMARY KEY,
    providerID INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100),
    duration_hours INT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    seats_available INT DEFAULT 30,
    start_date DATE,
    end_date DATE,
    status ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (providerID) REFERENCES training_providers(providerID) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table: enrolments
-- Learner enrolments in courses
-- --------------------------------------------------------
CREATE TABLE enrolments (
    enrolmentID INT AUTO_INCREMENT PRIMARY KEY,
    learnerID INT NOT NULL,
    courseID INT NOT NULL,
    enrolment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('PENDING','PAID','FAILED') DEFAULT 'PENDING',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    amount_paid DECIMAL(10,2),
    receipt_number VARCHAR(50) UNIQUE,
    completion_status ENUM('ENROLLED','COMPLETED') DEFAULT 'ENROLLED',
    FOREIGN KEY (learnerID) REFERENCES learners(learnerID) ON DELETE CASCADE,
    FOREIGN KEY (courseID) REFERENCES courses(courseID) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table: reviews
-- Learner reviews for completed courses
-- --------------------------------------------------------
CREATE TABLE reviews (
    reviewID INT AUTO_INCREMENT PRIMARY KEY,
    enrolmentID INT NOT NULL UNIQUE,   -- one review per enrolment
    learnerID INT NOT NULL,
    courseID INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrolmentID) REFERENCES enrolments(enrolmentID) ON DELETE CASCADE,
    FOREIGN KEY (learnerID) REFERENCES learners(learnerID) ON DELETE CASCADE,
    FOREIGN KEY (courseID) REFERENCES courses(courseID) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Seed Data: Default Ministry Officer account
-- Password: admin123  (stored as SHA-256 for demo; use password_hash in PHP)
-- --------------------------------------------------------
INSERT INTO ministry_officers (name, email, password) VALUES
('Admin Officer', 'admin@mohr.gov.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Note: The hash above = 'password' via bcrypt. Change in production!

-- Seed a test learner (password: learner123)
INSERT INTO learners (full_name, email, password, phone) VALUES
('Test Learner', 'learner@test.com', '$2y$10$TKh8H1.PffkAp/LoO2BDCuHvnog5UHBqFbKOCNnEHMauFopnQXAuK', '0123456789');
