<?php
require 'connection.php';
require 'DataBase.class.php';  
require 'Assignment.class.php';
require 'User.class.php';

// Initialize the database connection
$db = new DATABASE();
$pdo = $db->createConnection(); // Assuming it returns a PDO object

if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    http_response_code(500); // Internal server error
    exit;
}

$assignmentClass = new Assignment($conn);
$userClass = new USER($pdo);

// Fetch parameters from URL
$assignmentId = isset($_GET['assignmentId']) ? (int)$_GET['assignmentId'] : null;
$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;

if (!$assignmentId || !$userId) {
    echo json_encode(['error' => 'Missing required parameters']);
    http_response_code(400); // Bad request
    exit;
}

try {
    // Fetch submissions data
    $submissions = $assignmentClass->getSubmissionsByAssignmentId($assignmentId);

    if (!$submissions) {
        echo json_encode(['error' => 'No submissions found']);
        http_response_code(404); // Not found
        exit;
    }

    $responseData = [];
    foreach ($submissions as $submission) {
        $userData = $userClass->getUser($submission['StudentId'], 'Id');
        if ($userData) {
            $userData['submitTime'] = $submission['submitTime'];
            // Fetch images for this student's submission
            $stmt = $pdo->prepare("SELECT Path FROM assignmentimages WHERE StudentID = :studentId AND AssignmentId = :assignmentId");
            $stmt->execute(['studentId' => $submission['StudentId'], 'assignmentId' => $assignmentId]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $userData['images'] = $images;
            $responseData[] = $userData;
        }
    }

    echo json_encode(['images' => $responseData]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    http_response_code(500); // Internal Server Error
}
?>
