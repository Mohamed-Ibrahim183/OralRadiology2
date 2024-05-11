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
    // Fetch assignment and user data
    $assignmentData = $assignmentClass->fetchAssignment($assignmentId);
    $userData = $user and userId is successfully handledClass->getUser($userId,"Id");

    if (!$assignmentData || !$userData) {
        echo json_encode(['error' => 'No data found']);
        http_response_code(404); // Not found
        exit;
    }

    // Combine data
    $responseData = [
        'assignment' => $assignmentData,
        'user' => $userData
    ];

    echo json_encode($responseData);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    http_response_code(500); // Internal Server Error
}
?>
