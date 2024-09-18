<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
require_once("./Helpers.class.php");

class GROUP
{
  private PDO $pdo;
  private Helpers $helpers;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
    $this->helpers = new Helpers($this->pdo);
  }
  // ----------------------------------------------------------------
  function prepareAndBind(string $query, $params, bool $execute = false): PDOStatement
  {
    $keys = array_keys($params);
    $query .=  !empty($keys) ?  " (:" . implode(", :", $keys) . ");" : ";";
    $stmt = $this->pdo->prepare($query);
    $stmt = !empty($keys) ? $this->bindParams($params, $stmt) : $stmt;
    if ($execute) $stmt->execute();
    return $stmt;
  }
  function bindParams(array $params, PDOStatement &$stmt, bool $execute = false): PDOStatement
  {
    foreach ($params as $key => $value)
      $stmt->bindValue($this->ensureColon($key), $value);
    if ($execute) $stmt->execute();
    return $stmt;
  }
  private function ensureColon($key): string
  {
    // Check if the key already starts with a colon
    return strpos($key, ':') === 0 ? $key : ':' . $key;
  }
  // ----------------------------------------------------------------
  public function Insert($postKeys)
  {
    echo("hola from backend ");
      $FrontData = $postKeys;
      unset($FrontData['Name']);
  
      // 1. Save Group
      $this->prepareAndBind("INSERT into Groups (Name) VALUES", [
        "Name" => htmlspecialchars(trim($postKeys['Name']))
      ], true);
      $lastGroupID = $this->pdo->lastInsertId();
  
      foreach ($FrontData as $key => $value) {
        $condition = true;
        foreach (json_decode($value) as $subKey => $subValue) {
          if (htmlspecialchars(trim($subValue)) === "")
            $condition = false;
        }
        if ($condition === true) {
          // 2. Save Each Slot
          $secondArray = json_decode($value, true);
          $this->prepareAndBind("INSERT into Slots (Day, StartTime, EndTime, Room) VALUES", [
            "Day" => htmlspecialchars(trim($secondArray["day"])),
            "StartTime" => htmlspecialchars(trim($secondArray["Start"])),
            "EndTime" => htmlspecialchars(trim($secondArray["End"])),
            "Room" => htmlspecialchars(trim($secondArray["Room"]))
          ], true);
          $lastSlotID = $this->pdo->lastInsertId();
  
          // 3. Insert into groupsSlots table
          $this->prepareAndBind("INSERT into GroupsSlots (GroupId, SlotId) VALUES", [
            "GroupId" => $lastGroupID,
            "SlotId" => $lastSlotID
          ], true);
        }
      }
  
      // Retrieve Assignment-related data
      require_once("./Helpers.class.php");
      require_once('./Assignment.class.php');
      $assignment = new Assignment($this->pdo);
      $startWeek = $assignment->getstartweek();
      $startDay = $startWeek[0]["Day"];
        
      // Fetch all assignment IDs
      $stmt = $this->pdo->prepare("SELECT Id FROM assignments");
      $stmt->execute();
      $assignmentIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
      foreach ($assignmentIds as $assid) {
          $assignmentid = $assid["Id"];
  
          // Fetch week numbers for each assignment
          $stmt = $this->pdo->prepare("SELECT week_num FROM assignment_weeks WHERE assignment_id = :assignment_id;");
          $stmt->bindParam(":assignment_id", $assignmentid, PDO::PARAM_INT);
          $stmt->execute();
          $weekNums = $stmt->fetchAll(PDO::FETCH_ASSOC);
          $weekNumArray = array_column($weekNums, 'week_num');
        
          // Get SlotIds where GroupId === $lastGroupID
          $stmt = $this->pdo->prepare("SELECT SlotId FROM GroupsSlots WHERE GroupId = :GroupId;");
          $stmt->bindParam(":GroupId", $lastGroupID, PDO::PARAM_INT);
          $stmt->execute();
          $slotIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
          foreach ($slotIds as $slot) {
              $slotId = $slot['SlotId'];
  
              // Retrieve Slot details (Day, StartTime, EndTime) for each SlotId
              $stmt = $this->pdo->prepare("SELECT Day, StartTime, EndTime FROM Slots WHERE Id = :slotId;");
              $stmt->bindParam(":slotId", $slotId, PDO::PARAM_INT);
              $stmt->execute();
              $slotDetails = $stmt->fetch(PDO::FETCH_ASSOC);
              $targetDay = $slotDetails['Day'];
              $startTime = $slotDetails['StartTime'];
              $endTime = $slotDetails['EndTime'];
              
              // Loop through each week number and generate open/close times
              foreach ($weekNumArray as $weekNum) {
                  $wantedDay = $assignment->getWantedDay($startDay, $weekNum, $targetDay);
                  
                  $open = new DateTime($wantedDay . ' ' . $startTime);
                  $open = $open->format('Y-m-d H:i:s');
  
                  $close = new DateTime($wantedDay . ' ' . $endTime);
                  $close = $close->format('Y-m-d H:i:s');
                  
                  // Insert into groupsassignments table
                  $this->prepareAndBind("INSERT INTO GroupsAssignments (open, close, Assignment, Group, week_num) VALUES", [
                      "open" => $open,
                      "close" => $close,
                      "Assignment" => $assignmentid,
                      "Group" => $lastGroupID,
                      "week_num" => $weekNum
                  ], true);
              }
          }
      }
      
      echo "DONE Insert Group with name {$postKeys['Name']}";
      return true;
  }
  

  public function getAll()
  {
    $stmt = $this->pdo->prepare("Select * from Groups;");
    $stmt->execute();
    $Groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($Groups) === 0)
      return [];
    $final = [];
    foreach ($Groups as $key => $value) {
      $newGroupObject = [];
      // $final[$value["Id"]] = [];
      // array_push($final[$value["Id"]], $value["Name"]);
      $newGroupObject = $value;
      $newGroupObject["Slots"] = [];
      $groupId = $value["Id"];

      $query = "Select * from GroupsSlots where GroupId =:group;";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":group", $groupId);
      $stmt->execute();
      $SlotsIDs = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($SlotsIDs as $key2 => $value2) {
        $slotID = $value2["SlotId"];

        $query = "Select * from Slots where Id =:slotID;";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":slotID", $slotID);
        $stmt->execute();
        $NewSlot = $stmt->fetch(PDO::FETCH_ASSOC);

        // array_push($final[$value["Id"]], $NewSlot);
        array_push($newGroupObject["Slots"], $NewSlot);
      }
      array_push($final, $newGroupObject);
    }
    return json_encode($final);
  }


  public function getGroupsNames()
  {
    $stmt = $this->pdo->prepare("Select * from groups");
    $stmt->execute();
    $GroupsNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $GroupsNames;
  }

  public function Delete($postKeys)
  {
    // Get all Slot IDs associated with the Group ID
    $stmt = $this->pdo->prepare("SELECT SlotId FROM GroupsSlots WHERE GroupId=:selected");
    $this->bindParams([
      "selected" => $postKeys["id"]
    ], $stmt, true);
    $slotIds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Delete records from GroupsSlots
    $query = "DELETE FROM GroupsSlots WHERE GroupId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $postKeys["id"]);
    $stmt->execute();

    // Delete records from Slots
    foreach ($slotIds as $slot) {
      $query = "DELETE FROM Slots WHERE Id=:slotId";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":slotId", $slot["SlotId"]);
      $stmt->execute();
    }

    // Delete record from Groups
    $query = "DELETE FROM Groups WHERE Id=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $postKeys["id"]);
    $stmt->execute();

    $stmt = null;
  }

  public function insertUserInGroup($userId, $GroupName)
  {
    // Get Group Name
    $query = "SELECT Id from groups where Name =:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $GroupName);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);



    // DELETE Old Record
    $query = "DELETE from usersingroups Where userId =:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $userId);
    $stmt->execute();


    $query = "INSERT into usersingroups (userId, GroupId) Values (:userId, :GroupId)";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":userId", $userId);
    $stmt->bindParam(":GroupId", $result["Id"]);
    $stmt->execute();
  }
  public function getUserGroup($userID)
  {
    $query = "SELECT * FROM usersingroups WHERE userId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $userID);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
      return -1;
    }

    $groupId = $result["GroupId"];
    $query = "SELECT * FROM groups WHERE Id=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $groupId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result)
      return $result;
  }
  public function getUsersInGroup($groupId)
  {
    $query = "SELECT * FROM usersingroups WHERE GroupId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $groupId);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $final = [];
    foreach ($result as $row) {
      $query = "SELECT * FROM users WHERE Id=:selected";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":selected", $row["userId"]);
      $stmt->execute();
      $result2 = $stmt->fetch(PDO::FETCH_ASSOC);
      array_push($final, $result2);
    }
    return $final;
  }
}
