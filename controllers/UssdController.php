<?php
require_once __DIR__ . '/../models/UssdSession.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Results.php';
require_once __DIR__ . '/../models/Fee.php';
require_once __DIR__ . '/../models/CourseRegistration.php';

// We'll add the other models (Results, Fee, CourseReg, Announcement) later.

class UssdController
{
    private $sessionModel;
    private $studentModel;
    private $resultsModel;
    private $feeModel;
    private $courseRegModel;
    public function __construct()
    {
        $this->sessionModel = new UssdSession();
        $this->studentModel = new Student();
        $this->resultsModel = new Results();
        $this->feeModel = new Fee();
        $this->courseRegModel = new CourseRegistration();
    }

    /**
     * Main entry point – called from callback.php
     * @param array $beem_data The decoded JSON payload from Beem
     * @return string The response to send back to Beem (starting with "CON " or "END ")
     */
    public function handleRequest($beem_data)
    {
        // Extract data from Beem's payload
        $session_id = $beem_data['session_id'] ?? null;
        $phone_number = $beem_data['msisdn'] ?? null;
        $user_input = $beem_data['payload']['response'] ?? null;

        // Validate required fields
        if (!$session_id || !$phone_number) {
            return "END An error occurred. Please try again later.";
        }

        // Get or create the session
        $session = $this->sessionModel->findOrCreate($session_id, $phone_number);
        $current_state = $session['current_state'];
        $payload = json_decode($session['payload'], true);

        // Process based on the current state
        switch ($current_state) {
            case 'welcome':
                return $this->handleWelcome($session_id);

            case 'main_menu':
                return $this->handleMainMenu($session_id, $user_input);

            case 'awaiting_regno':
                return $this->handleRegNoInput($session_id, $user_input);

            case 'awaiting_pin':
                return $this->handlePinInput($session_id, $user_input, $payload);
            case 'viewing_results':
                return $this->handleViewingResults($session_id, $user_input, $payload);

            case 'viewing_fees':
                return $this->handleViewingFees($session_id, $user_input, $payload);

            case 'viewing_courses':
                return $this->handleViewingCourses($session_id, $user_input, $payload);
            // We'll add more states as we build the menu (e.g., showing results, fees, etc.)

            default:
                // If we don't recognise the state, reset to welcome
                $this->sessionModel->updateState($session_id, 'welcome');
                return $this->handleWelcome($session_id);
        }
    }

    /**
     * Display the welcome message and main menu options.
     * @param string $session_id
     * @return string
     */
    private function handleWelcome($session_id)
    {
        $menu = "CON Welcome to NIT Information System\n";
        $menu .= "========================\n";
        $menu .= "1. Check Exam Results\n";
        $menu .= "2. Check Fee Balance\n";
        $menu .= "3. Course Registration Status\n";
        $menu .= "4. View Announcements\n";
        $menu .= "0. Exit\n";
        $menu .= "\nEnter your choice:";

        // Move to main_menu state
        $this->sessionModel->updateState($session_id, 'main_menu');

        return $menu;
    }

    /**
     * Process the user's main menu selection.
     * @param string $session_id
     * @param string $input
     * @return string
     */
    private function handleMainMenu($session_id, $input)
    {
        switch ($input) {
            case '1':
                $this->sessionModel->updateState($session_id, 'awaiting_regno', ['service' => 'results']);
                return "CON Please enter your Registration Number:";

            case '2':
                $this->sessionModel->updateState($session_id, 'awaiting_regno', ['service' => 'fee']);
                return "CON Please enter your Registration Number:";

            case '3':
                $this->sessionModel->updateState($session_id, 'awaiting_regno', ['service' => 'registration']);
                return "CON Please enter your Registration Number:";

            case '4':
                return $this->showAnnouncements($session_id);

            case '0':
                $this->sessionModel->deleteSession($session_id);
                return "END Thank you for using NIT Information System. Goodbye!";

            default:
                // Invalid option – show menu again
                return $this->handleWelcome($session_id);
        }
    }

    /**
     * Handle registration number input.
     * @param string $session_id
     * @param string $reg_no
     * @return string
     */
    private function handleRegNoInput($session_id, $reg_no)
    {
        // Basic validation (you can add more specific format checks)
        if (empty($reg_no)) {
            $this->sessionModel->updateState($session_id, 'main_menu');
            return "CON Invalid Registration Number.\n\n" . $this->getMainMenuText();
        }

        // Store the reg_no in the session payload and move to PIN step
        $this->sessionModel->updateState($session_id, 'awaiting_pin', ['reg_no' => $reg_no]);
        return "CON Please enter your PIN:";
    }

    /**
     * Handle PIN input and authenticate the user.
     * @param string $session_id
     * @param string $pin
     * @param array $payload Current session payload
     * @return string
     */
    private function handlePinInput($session_id, $pin, $payload)
    {
        $reg_no = $payload['reg_no'] ?? null;
        $service = $payload['service'] ?? 'results';

        if (!$reg_no) {
            // Something went wrong, restart
            $this->sessionModel->updateState($session_id, 'welcome');
            return "CON Session expired. Please start again.\n\n" . $this->getWelcomeText();
        }

        // Verify PIN
        if (!$this->studentModel->verifyPin($reg_no, $pin)) {
            // Failed authentication – ask again (you may want to limit retries)
            return "CON Invalid PIN. Please try again.\nEnter your PIN:";
        }

        // Authentication successful – move to the requested service
        switch ($service) {
            case 'results':
                return $this->showResults($session_id, $reg_no);
            case 'fee':
                return $this->showFeeBalance($session_id, $reg_no);
            case 'registration':
                return $this->showRegistrationStatus($session_id, $reg_no);
            default:
                return $this->handleWelcome($session_id);
        }
    }

    /**
     * Helper method to get the main menu text (for reuse).
     * @return string
     */
    private function getMainMenuText()
    {
        return "1. Check Exam Results\n2. Check Fee Balance\n3. Course Registration Status\n4. View Announcements\n0. Exit\n\nEnter your choice:";
    }

    /**
     * Helper method to get the welcome text.
     * @return string
     */
    private function getWelcomeText()
    {
        return "Welcome to NIT Information System\n========================\n" . $this->getMainMenuText();
    }

    // We'll implement these service methods (showResults, showFeeBalance, etc.) in the next step.
    private function showAnnouncements($session_id)
    {
        // Placeholder – to be implemented
        return "END Announcements feature coming soon.";
    }

    private function showResults($session_id, $reg_no)
    {
        // Get all results for this student
        $results = $this->resultsModel->getResults($reg_no);

        if (empty($results)) {
            $this->sessionModel->deleteSession($session_id);
            return "END No results found for registration number: $reg_no";
        }

        // Format the results
        $output = "CON Your Exam Results:\n------------------------\n";
        $output .= $this->resultsModel->formatResultsForUssd($results);
        $output .= "\n0. Main Menu\n";

        // Store the results and current offset in session payload for potential pagination
        $this->sessionModel->updateState($session_id, 'viewing_results', [
            'reg_no' => $reg_no,
            'results_data' => $results,
            'offset' => 0
        ]);

        return $output;
    }

    private function showFeeBalance($session_id, $reg_no)
    {
        // Get all fee records for this student
        $feeRecords = $this->feeModel->getFeeBalance($reg_no);

        if (empty($feeRecords)) {
            $this->sessionModel->deleteSession($session_id);
            return "END No fee records found for registration number: $reg_no";
        }

        // If only one record, show it directly and end session (or go back to menu)
        if (count($feeRecords) == 1) {
            $output = "CON Fee Balance:\n------------------------\n";
            $output .= $this->feeModel->formatSingleFeeRecord($feeRecords[0]);
            $output .= "\n\n0. Main Menu\n";
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $output;
        }

        // Multiple semesters – show paginated
        $output = "CON Fee Balances:\n------------------------\n";
        $output .= $this->feeModel->formatMultipleFeesForUssd($feeRecords);
        $output .= "\n0. Main Menu\n";

        // Store fee records and offset for pagination
        $this->sessionModel->updateState($session_id, 'viewing_fees', [
            'reg_no' => $reg_no,
            'fee_data' => $feeRecords,
            'offset' => 0
        ]);

        return $output;
    }

    private function showRegistrationStatus($session_id, $reg_no)
    {
        // Get all registered courses for this student
        $courses = $this->courseRegModel->getRegisteredCourses($reg_no);

        if (empty($courses)) {
            $this->sessionModel->deleteSession($session_id);
            return "END No course registration records found for registration number: $reg_no";
        }

        // Check if courses span multiple semesters
        $semesters = array_unique(array_column($courses, 'semester'));

        if (count($semesters) == 1) {
            // Only one semester – show courses directly
            $output = "CON Registered Courses (" . $semesters[0] . "):\n------------------------\n";
            $output .= $this->courseRegModel->formatCoursesForUssd($courses);
            $output .= "\n0. Main Menu\n";
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $output;
        } else {
            // Multiple semesters – show grouped by semester with pagination
            $output = "CON Course Registration Summary:\n------------------------\n";
            $output .= $this->courseRegModel->formatCoursesBySemester($courses);
            $output .= "\n0. Main Menu\n";

            $this->sessionModel->updateState($session_id, 'viewing_courses', [
                'reg_no' => $reg_no,
                'course_data' => $courses,
                'offset' => 0
            ]);
            return $output;
        }
    }

    private function handleViewingResults($session_id, $input, $payload)
    {
        $reg_no = $payload['reg_no'] ?? null;
        $results = $payload['results_data'] ?? [];
        $offset = $payload['offset'] ?? 0;

        if ($input === '0') {
            // Go back to main menu
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $this->handleWelcome($session_id);
        }

        // If user presses anything else, try to show next page
        $new_offset = $offset + 5;
        if ($this->resultsModel->hasMoreResults($results, $offset)) {
            $output = "CON Your Exam Results (continued):\n------------------------\n";
            $output .= $this->resultsModel->formatResultsForUssd($results, $new_offset);
            $output .= "\n0. Main Menu\n";

            $this->sessionModel->updateState($session_id, 'viewing_results', [
                'reg_no' => $reg_no,
                'results_data' => $results,
                'offset' => $new_offset
            ]);
            return $output;
        } else {
            // No more results, end session or return to main menu
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $this->handleWelcome($session_id);
        }
    }


    private function handleViewingFees($session_id, $input, $payload)
    {
        $reg_no = $payload['reg_no'] ?? null;
        $feeRecords = $payload['fee_data'] ?? [];
        $offset = $payload['offset'] ?? 0;

        if ($input === '0') {
            // Go back to main menu
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $this->handleWelcome($session_id);
        }

        // User pressed any other key – show next page if available
        $new_offset = $offset + 2;
        if ($this->feeModel->hasMoreRecords($feeRecords, $offset)) {
            $output = "CON Fee Balances (continued):\n------------------------\n";
            $output .= $this->feeModel->formatMultipleFeesForUssd($feeRecords, $new_offset);
            $output .= "\n0. Main Menu\n";

            $this->sessionModel->updateState($session_id, 'viewing_fees', [
                'reg_no' => $reg_no,
                'fee_data' => $feeRecords,
                'offset' => $new_offset
            ]);
            return $output;
        } else {
            // No more records, return to main menu
            $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
            return $this->handleWelcome($session_id);
        }
    }


    private function handleViewingCourses($session_id, $input, $payload) {
    $reg_no = $payload['reg_no'] ?? null;
    $courses = $payload['course_data'] ?? [];
    $offset = $payload['offset'] ?? 0;
    
    if ($input === '0') {
        // Go back to main menu
        $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
        return $this->handleWelcome($session_id);
    }
    
    // User pressed any other key – show next page
    $new_offset = $offset + 2; // because formatCoursesBySemester shows 2 semesters per page
    
    if ($this->courseRegModel->hasMoreSemesters($courses, $offset)) {
        $output = "CON Course Registration (continued):\n------------------------\n";
        $output .= $this->courseRegModel->formatCoursesBySemester($courses, $new_offset);
        $output .= "\n0. Main Menu\n";
        
        $this->sessionModel->updateState($session_id, 'viewing_courses', [
            'reg_no' => $reg_no,
            'course_data' => $courses,
            'offset' => $new_offset
        ]);
        return $output;
    } else {
        $this->sessionModel->updateState($session_id, 'main_menu', ['reg_no' => $reg_no]);
        return $this->handleWelcome($session_id);
    }
}
}
?>