<?php
require'connection.php'

// Create connection to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// SQL to fetch assignments
$sql = "SELECT Name, Topic , Id FROM assignments";
$result = $conn->query($sql);

$assignments = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $assignments[] = $row; // Directly push row data into $assignments array
    }
    echo json_encode($assignments);
} else {
    echo json_encode([]); // Return an empty array if no assignments found
}

$conn->close(); 
?>
