<?php


$dsn = "mysql:host=localhost;dbname=oralradiology";
$dbUserName = "root";
$dbPassword = "";



try {
  $pdo = new PDO($dsn, $dbUserName, $dbPassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Connection Failed: " . $e->getMessage() . "<br>";
}
