<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *');
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "oralradiology";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    file_put_contents($logFile, "Connection Failed: " . $conn->connect_error . "\n", FILE_APPEND);
    die("Connection failed: " . $conn->connect_error);
}

?>