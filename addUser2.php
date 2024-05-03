<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  include_once './includes.inc.php';
  if (isset($_FILES['personalImage'])) {
    $targetDir = "uploads/";
    $fileName = basename($_FILES["personalImage"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $check = getimagesize($_FILES["personalImage"]["tmp_name"]);
    if ($check !== false) {
      if (move_uploaded_file($_FILES["personalImage"]["tmp_name"], $targetFilePath)) {
      } else {
        echo json_encode(["error" => "Sorry, there was an error uploading your file."]);
        exit;
      }
    } else {
      echo json_encode(["error" => "File is not an image."]);
      exit;
    }
  }

  $userId = htmlspecialchars(trim($_POST["MAID"]));
  $userUsername = trim($_POST["userName"]);
  $userPassword = htmlspecialchars(trim($_POST["pwd"]));
  $userName = htmlspecialchars(trim($_POST["name"]));
  $userEmail = htmlspecialchars(trim($_POST["email"]));

  $userType = trim($_POST["userType"]);
  if ($userName != "" && $userEmail != "" && $userId != "" && $userUsername != "" && $userPassword != "") {
    require_once('./includes.inc.php');
    $query = "INSERT INTO users (Username, Password, MSAId, Name, Email, Type, PersonalImage) VALUES (:username, :password, :MSAid, :name, :email, :type, :personalImage)";

    $stmt = $pdo->prepare($query);

    $stmt->bindParam(':personalImage', $targetFilePath);
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
    echo json_encode(["success" => "User added successfully."]);
    die();
  }
} else {
  echo "Not post";
}
