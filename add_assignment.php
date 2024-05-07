<?php

session_start(); 

require 'connection.php';  
require 'Assignment.class.php';

header('Access-Control-Allow-Origin: http://localhost:3000'); // Match this exactly to the client's origin
header('Access-Control-Allow-Credentials: true'); // Allow credentials like cookies or authorization headers
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Respond OK to preflight requests
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);
if (isset($_SESSION['userId'])) {
    $data['ProfessorId'] = isset($_GET['userId']) ? intval($_GET['userId']) : null;

    echo json_encode(['error' => 'Professor ID not set in session']);
    http_response_code(403); 
    exit;
}
$assignment = new Assignment($conn);
$result = $assignment->insertAssignment($data);

if ($result) {
    error_log("Insertion successful");
    echo json_encode(['success' => 'Assignment added successfully']);
} else {
    error_log("Insertion failed");
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add assignment']);
}
?>
