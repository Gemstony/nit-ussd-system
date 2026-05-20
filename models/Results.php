<?php
require_once __DIR__ . '/../includes/Database.php';

class Results {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all results for a given student, optionally filtered by semester.
     * @param string $reg_no
     * @param string|null $semester
     * @return array
     */
    public function getResults($reg_no, $semester = null) {
        if ($semester) {
            $stmt = $this->db->prepare("
                SELECT course_code, course_name, grade, marks 
                FROM results 
                WHERE reg_no = :reg_no AND semester = :semester
                ORDER BY course_code
            ");
            $stmt->execute(['reg_no' => $reg_no, 'semester' => $semester]);
        } else {
            $stmt = $this->db->prepare("
                SELECT course_code, course_name, grade, marks, semester 
                FROM results 
                WHERE reg_no = :reg_no 
                ORDER BY semester DESC, course_code
            ");
            $stmt->execute(['reg_no' => $reg_no]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get a list of distinct semesters for which results exist.
     * @param string $reg_no
     * @return array
     */
    public function getAvailableSemesters($reg_no) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT semester 
            FROM results 
            WHERE reg_no = :reg_no 
            ORDER BY semester DESC
        ");
        $stmt->execute(['reg_no' => $reg_no]);
        return $stmt->fetchAll();
    }

    /**
     * Format results as a plain text string suitable for USSD.
     * (Limits to 5 results per message to avoid USSD length limits)
     * @param array $results
     * @param int $offset
     * @return string
     */
    public function formatResultsForUssd($results, $offset = 0) {
        if (empty($results)) {
            return "No results found.";
        }

        $output = "";
        $count = 0;
        $max = 5; // show 5 results per screen
        
        for ($i = $offset; $i < count($results) && $count < $max; $i++, $count++) {
            $r = $results[$i];
            $output .= $r['course_code'] . " : " . $r['grade'] . " (" . $r['marks'] . ")\n";
        }
        
        return $output;
    }

    /**
     * Check if there are more results after a given offset.
     * @param array $results
     * @param int $offset
     * @return bool
     */
    public function hasMoreResults($results, $offset) {
        return ($offset + 5) < count($results);
    }
}
?>