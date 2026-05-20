<?php
require_once __DIR__ . '/../includes/Database.php';

class CourseRegistration {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all registered courses for a student, optionally filtered by semester.
     * @param string $reg_no
     * @param string|null $semester
     * @return array
     */
    public function getRegisteredCourses($reg_no, $semester = null) {
        if ($semester) {
            $stmt = $this->db->prepare("
                SELECT course_code, semester, registration_date, status
                FROM course_registrations
                WHERE reg_no = :reg_no AND semester = :semester
                ORDER BY course_code
            ");
            $stmt->execute(['reg_no' => $reg_no, 'semester' => $semester]);
        } else {
            $stmt = $this->db->prepare("
                SELECT course_code, semester, registration_date, status
                FROM course_registrations
                WHERE reg_no = :reg_no
                ORDER BY semester DESC, course_code
            ");
            $stmt->execute(['reg_no' => $reg_no]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get a list of distinct semesters for which registrations exist.
     * @param string $reg_no
     * @return array
     */
    public function getAvailableSemesters($reg_no) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT semester 
            FROM course_registrations 
            WHERE reg_no = :reg_no 
            ORDER BY semester DESC
        ");
        $stmt->execute(['reg_no' => $reg_no]);
        return $stmt->fetchAll();
    }

    /**
     * Format a list of registered courses for USSD display.
     * @param array $courses
     * @param int $offset
     * @return string
     */
    public function formatCoursesForUssd($courses, $offset = 0) {
        if (empty($courses)) {
            return "No registered courses found.";
        }

        $output = "";
        $count = 0;
        $max = 6; // show 6 courses per screen (USSD character limit)

        for ($i = $offset; $i < count($courses) && $count < $max; $i++, $count++) {
            $c = $courses[$i];
            $output .= $c['course_code'];
            if ($c['status'] !== 'registered') {
                $output .= " [" . strtoupper($c['status']) . "]";
            }
            $output .= "\n";
        }
        
        return $output;
    }

    /**
     * Format courses grouped by semester (for displaying multiple semesters).
     * @param array $courses
     * @param int $offset
     * @return string
     */
    public function formatCoursesBySemester($courses, $offset = 0) {
        if (empty($courses)) {
            return "No registered courses found.";
        }

        // Group courses by semester
        $grouped = [];
        foreach ($courses as $course) {
            $sem = $course['semester'];
            if (!isset($grouped[$sem])) {
                $grouped[$sem] = [];
            }
            $grouped[$sem][] = $course['course_code'];
        }

        // Convert to list for pagination
        $items = [];
        foreach ($grouped as $sem => $codes) {
            $items[] = [
                'semester' => $sem,
                'courses' => $codes
            ];
        }

        $output = "";
        $count = 0;
        $max = 2; // show 2 semesters per screen

        for ($i = $offset; $i < count($items) && $count < $max; $i++, $count++) {
            $item = $items[$i];
            $output .= "=== " . $item['semester'] . " ===\n";
            foreach ($item['courses'] as $code) {
                $output .= $code . "\n";
            }
            $output .= "\n";
        }

        return rtrim($output, "\n");
    }

    /**
     * Check if there are more courses after a given offset.
     * @param array $courses
     * @param int $offset
     * @return bool
     */
    public function hasMoreCourses($courses, $offset) {
        return ($offset + 6) < count($courses);
    }

    /**
     * Check if there are more semesters after a given offset.
     * @param array $courses (original list, will be grouped internally)
     * @param int $offset
     * @return bool
     */
    public function hasMoreSemesters($courses, $offset) {
        // Group semesters first
        $semesters = [];
        foreach ($courses as $course) {
            if (!in_array($course['semester'], $semesters)) {
                $semesters[] = $course['semester'];
            }
        }
        return ($offset + 2) < count($semesters);
    }

    /**
     * Get a simple list of courses for a single semester (no grouping).
     * @param array $courses
     * @return string
     */
    public function getSimpleCourseList($courses) {
        if (empty($courses)) {
            return "No courses registered.";
        }
        $list = [];
        foreach ($courses as $c) {
            $list[] = $c['course_code'];
        }
        return implode(", ", $list);
    }
}
?>