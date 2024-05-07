<?php
require 'connection.php';
require 'Assignment.class.php';
$assignment = new Assignment($conn);
echo $assignment->fetchAllAssignments();  
?>
