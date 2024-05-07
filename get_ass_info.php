<?php
require 'connection.php'; 
require 'Assignment.class.php'; 

$assignment = new Assignment($conn);  

$assignmentId = isset($_GET['assignmentId']) ? intval($_GET['assignmentId']) : null;

if ($assignmentId) {
    $assignmentData = $assignment->fetchAssignment($assignmentId);
    if ($assignmentData) {
        echo json_encode($assignmentData);
    } else {
        echo json_encode(["error" => "No data found for assignment ID $assignmentId"]);
    }
} else {
    echo json_encode(["error" => "Assignment ID is not provided"]);
}
?>
