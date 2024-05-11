<?php
$data = "2024-05-10T12:43";
$newData = date("Y-m-d H:i:s", strtotime($data));
echo $newData;
require_once("./DataBase.class.php");
$db = new DATABASE();
$pdo = $db->createConnection();

$query = "Insert Into groupsassignments (`open`, `close`, `Assignment`, `Group`) Values ('$newData', '$newData', '1', '16')";
$stmt = $pdo->prepare($query);
$stmt->execute();
$stmt = $pdo = null;
