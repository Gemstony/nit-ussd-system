<?php
require_once __DIR__ . '/../includes/Database.php';

class UssdSession {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find an existing session or create a new one if it doesn't exist.
     * @param string $session_id Unique ID from Beem
     * @param string $phone_number User's phone number
     * @return array Session data
     */
    public function findOrCreate($session_id, $phone_number) {
        // Clean any expired sessions first
        $this->cleanExpiredSessions();

        // Try to find existing session
        $stmt = $this->db->prepare("
            SELECT * FROM ussd_sessions 
            WHERE session_id = :session_id AND expires_at > NOW()
        ");
        $stmt->execute(['session_id' => $session_id]);
        $session = $stmt->fetch();

        if ($session) {
            // Update the expiry to give more time
            $this->updateExpiry($session_id);
            return $session;
        }

        // Create new session
        $stmt = $this->db->prepare("
            INSERT INTO ussd_sessions (session_id, phone_number, current_state, payload, expires_at)
            VALUES (:session_id, :phone_number, 'welcome', '{}', NOW() + INTERVAL 60 SECOND)
        ");
        $stmt->execute([
            'session_id' => $session_id,
            'phone_number' => $phone_number
        ]);

        // Return the newly created session
        $stmt = $this->db->prepare("SELECT * FROM ussd_sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $session_id]);
        return $stmt->fetch();
    }

    /**
     * Update the session state and payload after processing a user's input.
     * @param string $session_id
     * @param string $new_state
     * @param array $payload_data (optional) New data to merge into payload
     */
    public function updateState($session_id, $new_state, $payload_data = []) {
        // Get current payload
        $stmt = $this->db->prepare("SELECT payload FROM ussd_sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $session_id]);
        $current = $stmt->fetch();
        
        $payload = $current ? json_decode($current['payload'], true) : [];
        $payload = array_merge($payload, $payload_data);
        $payload_json = json_encode($payload);

        $stmt = $this->db->prepare("
            UPDATE ussd_sessions 
            SET current_state = :state, payload = :payload, expires_at = NOW() + INTERVAL 60 SECOND
            WHERE session_id = :session_id
        ");
        $stmt->execute([
            'state' => $new_state,
            'payload' => $payload_json,
            'session_id' => $session_id
        ]);
    }

    /**
     * Delete a session when the USSD session ends.
     * @param string $session_id
     */
    public function deleteSession($session_id) {
        $stmt = $this->db->prepare("DELETE FROM ussd_sessions WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $session_id]);
    }

    /**
     * Extend the expiry time for an active session.
     * @param string $session_id
     */
    public function updateExpiry($session_id) {
        $stmt = $this->db->prepare("
            UPDATE ussd_sessions 
            SET expires_at = NOW() + INTERVAL 60 SECOND 
            WHERE session_id = :session_id
        ");
        $stmt->execute(['session_id' => $session_id]);
    }

    /**
     * Remove all expired sessions from the database.
     */
    private function cleanExpiredSessions() {
        $stmt = $this->db->prepare("DELETE FROM ussd_sessions WHERE expires_at < NOW()");
        $stmt->execute();
    }
}
?>