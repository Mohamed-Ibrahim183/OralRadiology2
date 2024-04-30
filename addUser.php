<?php
header("Access-Control-Allow-Origin: *");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $userId = htmlspecialchars(trim($_POST["MAID"]));
  $userUsername = trim($_POST["userName"]);
  $userPassword = htmlspecialchars(trim($_POST["pwd"]));
  $userName = htmlspecialchars(trim($_POST["name"]));
  $userEmail = htmlspecialchars(trim($_POST["email"]));

  $userType = trim($_POST["userType"]);
  if ($userName != "" && $userEmail != "" && $userId != "" && $userUsername != "" && $userPassword != "") {
    require_once('./includes.inc.php');
    $query = "INSERT INTO users (Username, Password, MSAId, Name, Email, Type) VALUES (:username, :password, :MSAid, :name, :email, :type)";

    $stmt = $pdo->prepare($query);

    $stmt->bindParam(':username', $userUsername);
    $stmt->bindParam(':password', $userPassword);
    $stmt->bindParam(':MSAid', $userId);
    $stmt->bindParam(':name', $userName);
    $stmt->bindParam(':email', $userEmail);
    $stmt->bindParam(':type', $userType);
    $stmt->execute();

    $table2 = match ($userType) {
      "Student" => "Insert into students (user) values (:user)",
      "Professor" => "Insert into professors (user) values (:user)",
      "Admin" => "Insert into admins (user) values (:user)",
    };
    // if()
    $lastIndex = $pdo->lastInsertId();
    $stmt = $pdo->prepare($table2);
    $stmt->bindParam(":user", $lastIndex);
    $stmt->execute();

    $pdo = $stmt = null;
    echo json_encode($_POST);
    die();
  }
  // $json_data = json_encode($_POST);
  // echo $json_data;
} else {
  echo "Not post";
  // header("./index.php?method=1");
}
