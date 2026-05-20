<?php
require_once __DIR__ . '/../includes/Database.php';

class Announcement {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get active announcements for a specific audience or all.
     * @param string $audience 'students', 'staff', or 'all'
     * @return array
     */
    public function getActiveAnnouncements($audience = 'students') {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            SELECT announcement_id, title, content, created_at
            FROM announcements
            WHERE is_active = 1
            AND (target_audience = :audience OR target_audience = 'all')
            AND (expires_at IS NULL OR expires_at > :now)
            ORDER BY created_at DESC
        ");
        $stmt->execute(['audience' => $audience, 'now' => $now]);
        return $stmt->fetchAll();
    }

    /**
     * Format a single announcement for USSD display.
     * @param array $announcement
     * @return string
     */
    public function formatSingleAnnouncement($announcement) {
        $output = "📢 " . $announcement['title'] . "\n";
        $output .= str_repeat("-", 20) . "\n";
        $output .= $announcement['content'] . "\n";
        $output .= "Date: " . date('d/m/Y', strtotime($announcement['created_at']));
        return $output;
    }

    /**
     * Format multiple announcements for USSD pagination.
     * @param array $announcements
     * @param int $offset
     * @return string
     */
    public function formatAnnouncementsForUssd($announcements, $offset = 0) {
        if (empty($announcements)) {
            return "No announcements at this time.";
        }

        $output = "";
        $count = 0;
        $max = 2; // show 2 announcements per screen due to length

        for ($i = $offset; $i < count($announcements) && $count < $max; $i++, $count++) {
            $a = $announcements[$i];
            $output .= ($count + 1) . ". " . $a['title'] . "\n";
            $output .= "   " . $this->truncate($a['content'], 50) . "\n";
            $output .= "   [" . date('d/m/Y', strtotime($a['created_at'])) . "]\n\n";
        }

        return rtrim($output, "\n");
    }

    /**
     * Format a single announcement in full (for detailed view).
     * @param array $announcement
     * @return string
     */
    public function formatFullAnnouncement($announcement) {
        $output = "CON 📢 " . $announcement['title'] . "\n";
        $output .= str_repeat("=", 20) . "\n";
        $output .= $announcement['content'] . "\n\n";
        $output .= "Posted: " . date('d/m/Y H:i', strtotime($announcement['created_at'])) . "\n";
        $output .= "0. Back to list\n";
        $output .= "00. Main Menu";
        return $output;
    }

    /**
     * Helper to truncate long text for preview.
     * @param string $text
     * @param int $length
     * @return string
     */
    private function truncate($text, $length = 50) {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . "...";
    }

    /**
     * Check if there are more announcements after a given offset.
     * @param array $announcements
     * @param int $offset
     * @return bool
     */
    public function hasMoreAnnouncements($announcements, $offset) {
        return ($offset + 2) < count($announcements);
    }
}
?>