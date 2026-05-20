<?php
require_once __DIR__ . '/../models/Student.php';

$studentModel = new Student();

// Test finding a student
$student = $studentModel->findByRegNo('NIT/2022/1234');
var_dump($student);

// Test PIN verification (use the PIN you stored in the DB as plain text during testing)
// For a test student with PIN 1234, password_hash('1234', PASSWORD_DEFAULT) should be stored.
$isValid = $studentModel->verifyPin('NIT/2022/1234', '1234');
var_dump($isValid); // Should output true
?>