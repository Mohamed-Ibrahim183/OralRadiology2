<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $userName = htmlspecialchars(trim($_POST["username"]));
  $Password = htmlspecialchars(trim($_POST["password"]));

  if ($userName != "" && $Password != "") {
    require_once('./includes.inc.php');
    $query = "Select * from users where MSAId = :username and Password = :password";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $userName);
    $stmt->bindParam(':password', $Password);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdo = $stmt = null;
    if (!empty($result)) {
    }
    session_start();
    $_SESSION['loggedIn'] = true;
    echo json_encode($result);
    die();
  }
} else {
  echo "Not post";
}
