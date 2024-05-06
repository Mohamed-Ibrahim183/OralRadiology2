<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
// session_start();

class USER
{
  private $Id, $Password, $MSAId, $Name, $Email, $Type, $PersonalImage, $lastIndex, $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function getConnection()
  {
    return $this->pdo;
  }

  public function Insert($postKeys, $tableColumns, $tableName, $hashName)
  {
    $ready = true;
    foreach ($postKeys as $key => $value) {
      if (htmlspecialchars(trim($value)) === "")
        $ready = false;
    }
    if ($ready === true) {
      // $this->Id = $postKeys["Id"];
      $this->Password = $postKeys["Password"];
      $this->MSAId = $postKeys["MSAId"];
      $this->Name = $postKeys["Name"];
      $this->Email = $postKeys["Email"];
      $this->Type = $postKeys["Type"];
      if (isset($postKeys["PersonalImage"])) {
        $this->PersonalImage = $postKeys["PersonalImage"];
      }
    }
    if ($ready === true) {
      // require("./includes.inc.php");
      if ($hashName !== "")
        $postKeys[$hashName] = password_hash($postKeys[$hashName], PASSWORD_BCRYPT, ['cost' => 12]);

      $query = "INSERT INTO $tableName (";
      $query .= implode(", ", $tableColumns);
      $query .= ") VALUES (";
      foreach ($tableColumns as $key => $value) {
        $variable = htmlspecialchars(trim($postKeys[$value]));
        $query .= "'$variable', ";
      }
      $query = rtrim($query, ", "); // Remove trailing comma
      $query .= ")";

      $stmt = $this->pdo->prepare($query);
      $stmt->execute();
      $this->lastIndex = $this->pdo->lastInsertId();
      $this->Id = $this->pdo->lastInsertId();

      return $this->pdo->lastInsertId();
    }
    return -1;
  }

  public function getUser($value, $key = "Id")
  {
    $query = "Select * from users Where $key =:selected;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $value);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
      $this->Id = $result["Id"];
      $this->Password = $result["Password"];
      $this->MSAId = $result["MSAId"];
      $this->Name = $result["Name"];
      $this->Email = $result["Email"];
      $this->Type = $result["Type"];
      $this->PersonalImage = $result["PersonalImage"];
      session_start();
      $_SESSION["LoggedIn"] = true;
      $_SESSION["Id"] = $result["Id"];
      $_SESSION["MSAId"] = $result["MSAId"];
      $_SESSION["Name"] = $result["Name"];
      $_SESSION["Email"] = $result["Email"];
      $_SESSION["Type"] = $result["Type"];
      $_SESSION["PersonalImage"] = $result["PersonalImage"];
      return $result;
    }
  }

  public function uploadImage($file, $userID, $id)
  {
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    // $file = $_FILES["personalImage"];
    $fileName = $file["name"];
    $fileSize = $file["size"];
    $fileTmpName = $file["tmp_name"];
    $fileType = $file["type"];

    $allowedTypes = array("image/jpeg", "image/png", "image/gif");
    if (!in_array($fileType, $allowedTypes)) {
      echo "Error: File is not an image.";
      // exit();
      return;
    }

    // Check file size
    if ($fileSize > $maxFileSize) {
      echo "Error: File size exceeds the maximum limit (5MB).";
      // exit();
      return;
    }
    if (!is_dir("../uploads")) {
      mkdir("../uploads", 0777);
    }
    $targetDir = "../uploads/" . $userID;

    // Check if the directory doesn't already exist
    if (!is_dir($targetDir))
      mkdir($targetDir, 0777);

    $extension = explode(".", $file["name"]);
    $extension = $extension[count($extension) - 1];
    $targetFile = $targetDir . "/" . "PersonalImage" . "." . $extension;

    if (move_uploaded_file($fileTmpName, $targetFile)) {
      $query = "Update users set personalImage =:target where Id =:selected;";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":target", $targetFile);
      $stmt->bindParam(":selected", $id);
      $stmt->execute();
      echo "DONE";
    } else {
      echo "Sorry, there was an error uploading your file.";
    }
  }
  public function getMSAId()
  {
    return $this->MSAId;
  }
  public function setMSAId($newID)
  {
    require_once("./DataBase.class.php");
    $db = new DATABASE();
    $pdo = $db->createConnection("oralradiology");
    $result = $this->getUser($newID, "MSAId");
    if ($result) {
      // done
    }
  }
  public function Login($MSAId, $password)
  {
    $query = "SELECT * FROM users WHERE MSAId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $MSAId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $logged = false;

    if ($user && $password === $user["Password"]) {
      unset($user["Password"]);
      return $user;
    } else {
      echo "not Logged In";
      return false;
    }
  }

  public function getIMage($id)
  {
    $result = $this->getUser($id, "Id");
    if ($this->PersonalImage)
      return $this->PersonalImage;
  }
  public function getAll()
  {
    $query = "Select * from users;";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results) {
      foreach ($results as $key => $value) {
        unset($value["Password"]);
      }
    }
    return $results;
  }

  public function getSpecificType($type)
  {
    $query = "Select * from users Where Type =:selected;";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $type);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }
}
