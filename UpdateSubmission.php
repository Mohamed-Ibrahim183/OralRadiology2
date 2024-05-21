<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return status 204 (No Content) for OPTIONS requests
    http_response_code(204);
    exit;
}

include 'DataBase.class.php';
include 'Assignment.class.php';

session_start(); // Start the session

// Retrieve submission data from the session
$submissionData = $_SESSION['submissionData'] ?? [];

if (empty($submissionData)) {
    echo json_encode(['success' => false, 'error' => 'Submission data not found in session']);
    exit;
}

// Database connection
$db = new DATABASE();
$conn = $db->Connection2();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$assignment = new Assignment($conn);

// Loop through the submission data and update the database
foreach ($submissionData as $studentId => $submission) {
    $assignmentId = $submission['assignmentId'];
    $grade = $submission['grade'];
    $comment = $submission['comment'];

    // Update the grade and comment in the database
    $success = $assignment->AddAssignmentGradeAndComment($assignmentId, $studentId, $grade, $comment);

    if (!$success) {
        echo json_encode(['success' => false, 'error' => 'Failed to save submissions. Please try again.']);
        exit;
    }
}

// Clear the submission data from the session after updating the database
unset($_SESSION['submissionData']);

echo json_encode(['success' => true]);
?>
