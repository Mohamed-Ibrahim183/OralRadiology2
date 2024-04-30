<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");


$method = $_SERVER["REQUEST_METHOD"];
$path = explode("/", $_SERVER['REQUEST_URI']);
$last = $path[count($path) - 1];
require_once("./includes.inc.php");
switch ($method) {
  case "GET":
    require_once("./includes.inc.php");
    if ($last == "Display") { // display all users
      $query = "SELECT * FROM `users`";
      $stmt = $pdo->prepare($query);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $pdo = $stmt = null;
      echo json_encode($result);
    }
    if (is_numeric($last)) { // display specific user
      $query = "SELECT * FROM `users` WHERE Id = :ID";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(":ID",  $last);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      $pdo = $stmt = null;
      echo json_encode($result);
      die();
    }
    if ($last == "Groups") { // display all groups
      $query = "Select * from Groups;";
      $stmt = $pdo->prepare($query);
      $stmt->execute();
      $Groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($Groups) === 0) {
        die();
      }
      $final;
      foreach ($Groups as $key => $value) {
        //   (
        //     [Id] => 3
        //     [Name] => 
        //     [Room] => G100
        // )
        $final[$value["Id"]] = [];
        array_push($final[$value["Id"]], $value["Name"]);
        $groupId = $value["Id"];
        // $membersQuery = "select UserId from GroupMembers where GroupId=:GroupId";
        $query = "Select * from GroupsSlots where GroupId =:group;";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":group", $groupId);
        $stmt->execute();
        $SlotsIDs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($SlotsIDs as $key2 => $value2) {
          $slotID = $value2["SlotId"];

          $query = "Select * from Slots where Id =:slotID;";
          $stmt = $pdo->prepare($query);
          $stmt->bindParam(":slotID", $slotID);
          $stmt->execute();
          $NewSlot = $stmt->fetch(PDO::FETCH_ASSOC);
          // print_r($NewSlot);
          array_push($final[$value["Id"]], $NewSlot);
        }
      }
      // echo json_decode(json_encode($final));
      echo json_encode($final);
      die();
    }
  case "PUT":
    require_once("./includes.inc.php");
    $FrontData = json_decode(file_get_contents("php://input"));
    if (is_numeric($last)) {
      $userId = htmlspecialchars(trim($FrontData->MSAId));
      $userUsername = trim($FrontData->Username);
      $userPassword = htmlspecialchars(trim($FrontData->Password));
      $userName = htmlspecialchars(trim($FrontData->Name));
      $userEmail = htmlspecialchars(trim($FrontData->Email));
      $userType = htmlspecialchars(trim($FrontData->Type));
      // $personalImage  = htmlspecialchars(trim($_POST["PersonalImage"]));

      $query = " UPDATE users set Username=:username, Password=:password, MSAId=:MSAid, Name=:name, Email=:email, Type=:type where Id=$last";
      $stmt = $pdo->prepare($query);

      $stmt->bindParam(':username', $userUsername);
      $stmt->bindParam(':password', $userPassword);
      $stmt->bindParam(':MSAid', $userId);
      $stmt->bindParam(':name', $userName);
      $stmt->bindParam(':email', $userEmail);
      $stmt->bindParam(':type', $userType);
      // $stmt->bindParam(":ID",  $last);

      $stmt->execute();
      // $result = $stmt->fetch(PDO::FETCH_ASSOC);
      $pdo = $stmt = null;
      // echo json_encode($result);
      echo "DONE";
      die();
    }
  case "POST":
    if ($last == "Group") { // add new group
      $FrontData = $_POST;
      unset($FrontData['Name']);
      print_r($FrontData);
      echo "\n";

      // 1. Save Group
      $query = "INSERT into Groups (Name, Room) VALUES (:Name, :room);";
      $stmt = $pdo->prepare($query);
      $name = htmlspecialchars(trim($_POST['Name']));
      $stmt->bindParam(":Name", $name);
      $groupRoom = "G100";
      $stmt->bindParam(":room", $groupRoom);
      $stmt->execute();
      $lastGroupID = $pdo->lastInsertId();

      foreach ($FrontData as $key => $value) {
        $condition = true;
        foreach (json_decode($value) as $subKey => $subValue) {
          // echo "$key =>  $subKey \n";
          if (htmlspecialchars(trim($subValue)) ===  "") {
            $condition = false;
          }
        }
        if ($condition === true) {
          // 2. Save Each Slot
          $secondArray = json_decode($value, true);
          $query = "INSERT into Slots (Day, Hour, Minute, DurationInMinutes) VALUES (:day, :hour, :minute, :duration)";
          $stmt = $pdo->prepare($query);

          $day = htmlspecialchars(trim($secondArray["day"]));
          $hour = htmlspecialchars(trim($secondArray["hour"]));
          $duration = htmlspecialchars(trim($secondArray["duration"]));
          $minutes = htmlspecialchars(trim($secondArray["minutes"]));

          $stmt->bindParam(":day", $day);
          $stmt->bindParam(":hour", $hour);
          $stmt->bindParam(":minute", $minutes);
          $stmt->bindParam(":duration", $duration);
          $stmt->execute();
          $lastSlotID = $pdo->lastInsertId();

          // 3. table groupsSlots
          $query = "INSERT into GroupsSlots (GroupId, SlotId) VALUES (:group, :slot)";
          $stmt = $pdo->prepare($query);
          $stmt->bindParam(":group", $lastGroupID);
          $stmt->bindParam(":slot", $lastSlotID);
          $stmt->execute();
        }
      }
      echo "DONE Insert Group";
    }
    if ($last == "Delete") {
      // Get all Slot IDs associated with the Group ID
      $query = "SELECT SlotId FROM GroupsSlots WHERE GroupId=:selected";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(":selected", $_POST["id"]);
      $stmt->execute();
      $slotIds = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Delete records from GroupsSlots
      $query = "DELETE FROM GroupsSlots WHERE GroupId=:selected";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(":selected", $_POST["id"]);
      $stmt->execute();

      // Delete records from Slots
      foreach ($slotIds as $slot) {
        $query = "DELETE FROM Slots WHERE Id=:slotId";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":slotId", $slot["SlotId"]);
        $stmt->execute();
      }

      // Delete record from Groups
      $query = "DELETE FROM Groups WHERE Id=:selected";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(":selected", $_POST["id"]);
      $stmt->execute();

      echo "DELETED";
      die();
    }

    die();
  default:
    die();
    // echo json_encode(explode("/", $_SERVER['REQUEST_URI']));
}
