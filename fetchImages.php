<?php
ini_set('display_errors', 0); // Do not display errors
error_reporting(E_ALL); // Report all errors and warnings

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require 'connection.php';
require 'DataBase.class.php';  
require 'Assignment.class.php';
require 'User.class.php';

// Initialize the database connection
$db = new DATABASE();
$pdo = $db->createConnection();

if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    http_response_code(500); // Internal server error
    exit;
}

$assignment = new Assignment($pdo);

$studentId = isset($_GET['studentId']) ? (int)$_GET['studentId'] : null;
$assignmentId = isset($_GET['assignmentId'])  ? (int)$_GET['assignmentId'] : null;

if (!$studentId || !$assignmentId) {
    echo json_encode(['error' => 'Missing required parameters']);
    http_response_code(400); // Bad request
    exit;
}

try {
    $images = $assignment->getAssignmentImages($studentId, $assignmentId);
    if ($images === false) {
        throw new Exception("Failed to fetch images.");
    }
    
    if (empty($images)) {
        echo json_encode(['error' => 'No images found for the provided IDs']);
        http_response_code(404); // Not found
    } else {
        echo json_encode($images);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
