<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];

if (is_numeric($last)) {
  require_once("./includes.inc.php");
  $query = "Select PersonalImage from users where Id =:Id";
  $stmt = $pdo->prepare($query);
  $stmt->bindParam(":Id", $last);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if (empty($result) or !$result["PersonalImage"]) {
    echo "/uploads/General/mainPerson.jpg";
  } else {
    echo $result["PersonalImage"];
  }

  die();
}
