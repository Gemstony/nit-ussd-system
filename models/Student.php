<?php
require_once __DIR__ . '/../includes/Database.php';

class Student {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a student by registration number.
     * @param string $reg_no
     * @return array|false
     */
    public function findByRegNo($reg_no) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE reg_no = :reg_no");
        $stmt->execute(['reg_no' => $reg_no]);
        return $stmt->fetch();
    }

    /**
     * Verify PIN for a given registration number.
     * @param string $reg_no
     * @param string $plain_pin
     * @return bool
     */
    public function verifyPin($reg_no, $plain_pin) {
        $student = $this->findByRegNo($reg_no);
        if (!$student) {
            return false;
        }
        return password_verify($plain_pin, $student['pin_hash']);
    }

    /**
     * Get phone number of a student (for logging or fallback).
     * @param string $reg_no
     * @return string|null
     */
    public function getPhoneNumber($reg_no) {
        $stmt = $this->db->prepare("SELECT phone_number FROM students WHERE reg_no = :reg_no");
        $stmt->execute(['reg_no' => $reg_no]);
        $row = $stmt->fetch();
        return $row ? $row['phone_number'] : null;
    }
}
?>