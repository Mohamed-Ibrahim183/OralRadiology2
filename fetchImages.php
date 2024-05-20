<?php
require 'connection.php';
require 'DataBase.class.php';  
require 'Assignment.class.php';
require 'User.class.php';

$db = new DATABASE();
$pdo = $db->createConnection();

if (!$pdo) {
    echo json_encode(['error' => 'Database connection failed']);
    http_response_code(500);
    exit;
}

$userClass = new USER($pdo);
$assignment = new Assignment($pdo); // Assuming $conn is your database connection

$studentId = isset($_GET['StudentId']) ? (int)$_GET['StudentId'] : null;;
$assignmentId = isset($_GET['assignmentId']) ? (int)$_GET['assignmentId'] : null;; 

$images = $assignment->getAssignmentImages($studentId, $assignmentId);

// Check for errors
if (isset($images['error'])) {
    // Handle error
    echo $images['error'];
} else {
    // Images were fetched successfully
    // Loop through $images array and display or process them
    foreach ($images as $image) {
        echo json_encode($image['Path']); 
    }
}
?>
