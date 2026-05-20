<?php
require_once __DIR__ . '/../includes/Database.php';

class Fee {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get fee balance for a student, optionally filtered by semester.
     * @param string $reg_no
     * @param string|null $semester
     * @return array Array of fee records (each contains semester, total_fees, paid_amount, balance)
     */
    public function getFeeBalance($reg_no, $semester = null) {
        if ($semester) {
            $stmt = $this->db->prepare("
                SELECT semester, total_fees, paid_amount, balance
                FROM fee_balances
                WHERE reg_no = :reg_no AND semester = :semester
            ");
            $stmt->execute(['reg_no' => $reg_no, 'semester' => $semester]);
        } else {
            $stmt = $this->db->prepare("
                SELECT semester, total_fees, paid_amount, balance
                FROM fee_balances
                WHERE reg_no = :reg_no
                ORDER BY semester DESC
            ");
            $stmt->execute(['reg_no' => $reg_no]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Get a list of distinct semesters for which fee records exist.
     * @param string $reg_no
     * @return array
     */
    public function getAvailableSemesters($reg_no) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT semester 
            FROM fee_balances 
            WHERE reg_no = :reg_no 
            ORDER BY semester DESC
        ");
        $stmt->execute(['reg_no' => $reg_no]);
        return $stmt->fetchAll();
    }

    /**
     * Format a single fee record as a readable string.
     * @param array $feeRecord
     * @return string
     */
    public function formatSingleFeeRecord($feeRecord) {
        $output = "Semester: " . $feeRecord['semester'] . "\n";
        $output .= "Total Fees: TZS " . number_format($feeRecord['total_fees'], 2) . "\n";
        $output .= "Paid: TZS " . number_format($feeRecord['paid_amount'], 2) . "\n";
        $output .= "Balance: TZS " . number_format($feeRecord['balance'], 2);
        return $output;
    }

    /**
     * Format multiple fee records (all semesters) for USSD display.
     * Limits to 2 semesters per screen to avoid character limits.
     * @param array $feeRecords
     * @param int $offset
     * @return string
     */
    public function formatMultipleFeesForUssd($feeRecords, $offset = 0) {
        if (empty($feeRecords)) {
            return "No fee records found.";
        }

        $output = "";
        $count = 0;
        $max = 2; // show 2 semesters per screen

        for ($i = $offset; $i < count($feeRecords) && $count < $max; $i++, $count++) {
            $f = $feeRecords[$i];
            $output .= "--- " . $f['semester'] . " ---\n";
            $output .= "Total: TZS " . number_format($f['total_fees'], 2) . "\n";
            $output .= "Paid: TZS " . number_format($f['paid_amount'], 2) . "\n";
            $output .= "Balance: TZS " . number_format($f['balance'], 2) . "\n\n";
        }

        return rtrim($output, "\n");
    }

    /**
     * Check if there are more fee records after a given offset.
     * @param array $feeRecords
     * @param int $offset
     * @return bool
     */
    public function hasMoreRecords($feeRecords, $offset) {
        return ($offset + 2) < count($feeRecords);
    }
}
?>