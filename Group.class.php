<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");
class GROUP
{
  private $pdo;
  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }
  public function Insert($postKeys)
  {
    $FrontData = $postKeys;
    unset($FrontData['Name']);

    // 1. Save Group
    $query = "INSERT into Groups (Name) VALUES (:Name);";
    $stmt = $this->pdo->prepare($query);
    $name = htmlspecialchars(trim($postKeys['Name']));
    $stmt->bindParam(":Name", $name);
    $stmt->execute();
    $lastGroupID = $this->pdo->lastInsertId();

    foreach ($FrontData as $key => $value) {
      $condition = true;
      foreach (json_decode($value) as $subKey => $subValue) {
        if (htmlspecialchars(trim($subValue)) ===  "")
          $condition = false;
      }
      if ($condition === true) {
        // 2. Save Each Slot
        $secondArray = json_decode($value, true);
        $query = "INSERT into Slots (Day, StartTime, EndTime, Room) VALUES (:day, :Start, :End, :Room)";
        $stmt = $this->pdo->prepare($query);

        $day = htmlspecialchars(trim($secondArray["day"]));
        $Start = htmlspecialchars(trim($secondArray["Start"]));
        $End = htmlspecialchars(trim($secondArray["End"]));
        $Room = htmlspecialchars(trim($secondArray["Room"]));

        $stmt->bindParam(":day", $day);
        $stmt->bindParam(":Start", $Start);
        $stmt->bindParam(":End", $End);
        $stmt->bindParam(":Room", $Room);
        $stmt->execute();
        $lastSlotID = $this->pdo->lastInsertId();

        // 3. table groupsSlots
        $query = "INSERT into GroupsSlots (GroupId, SlotId) VALUES (:group, :slot)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":group", $lastGroupID);
        $stmt->bindParam(":slot", $lastSlotID);
        $stmt->execute();
      }
    }
    echo "DONE Insert Group with name {$postKeys['Name']}";
  }

  public function getAll()
  {
    $query = "Select * from Groups;";
    $stmt = $this->pdo->prepare($query);
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
    $query = "Select * from groups";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    $GroupsNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $GroupsNames;
  }

  public function Delete($postKeys)
  {
    // Get all Slot IDs associated with the Group ID
    $query = "SELECT SlotId FROM GroupsSlots WHERE GroupId=:selected";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(":selected", $postKeys["id"]);
    $stmt->execute();
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
